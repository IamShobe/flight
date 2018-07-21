<?php namespace Flight;
use Flight\URLS\IncludeURLS;
use Flight\URLS\URL;


class Dispatcher
{
    private $routes_table;
    public $base_dir;
    static $dispatcher = null;

    function __construct($base_dir, $urls, $use_static=true)
    {
        if (self::$dispatcher != null) {
            throw new \RuntimeException("There can only be one dispatcher in the program!");
        }
        self::$dispatcher = $this;
        $this->base_dir = $base_dir;
        $this->routes_table = array();
        if($use_static) {
            $static_url = new URLS\URL("^static\/(?P<path>.*)$", new Views\StaticFile());
            array_unshift($urls, $static_url);
        }
        $this->register_urls($urls);
    }

    function register_urls($urls, $base_regex = "")
    {
        foreach ($urls as $url) {
            if (!($url instanceof URL)) {
                $type = gettype($url);
                print_r($url);
                throw new \InvalidArgumentException("URL object expected. got {$type}");
            }
            $regex = $base_regex;
            if (strlen($base_regex) > 0) {
                if (strlen($url->regex) > 0) {
                    $last_base_char = substr($base_regex, -1);
                    if($last_base_char == "$") {
                        $base_regex = substr($base_regex, 0, strlen($base_regex) - 1);
                    }
                    if ($url->regex[0] == "^") {
                        $regex = $base_regex . substr($url->regex, 1);
                    } else {
                        $regex = $base_regex . ".*?" . $url->regex;
                    }
                }
            } else {
                $regex = $url->regex;
            }
            $handler = $url->handler;
            $methods = $url->methods;
            if ($handler instanceof IncludeURLS) {
                $this->register_urls($handler->urls, $regex);
            } else {
                $handler->__base_dir = $this->base_dir;
                foreach ($methods as $method) {
                    $this->route($method, $regex, $handler);
                }
            }
        }
    }

    function route($method, $regex_url, $handler)
    {
        $this->routes_table[$method][$regex_url] = $handler;
    }

    function inner_dispatch($uri, $method, ...$args)
    {
        $tries = [];
        foreach ($this->routes_table[$method] as $regex => $handler) {
            array_push($tries, $regex);
            if (preg_match("/" . $regex . "/", $uri, $matches)) {
                // execute the view
                $handler($matches, ...$args)();
                return true;
            }
        }
        header("content-type: text/plain");
        echo "URI: `{$uri}` wasn't found.\ntried the followings:\n";
        print_r($tries);
        return false;
    }

    function dispatch(...$args)
    {
        /**
         * Credit to noodlehaus/dispatch for post methods.
         * https://github.com/noodlehaus/dispatch
         */
        $method = strtoupper($_SERVER['REQUEST_METHOD']);
        if ($method === 'POST') {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } else {
                $method = isset($_POST['_method']) ? strtoupper($_POST['_method']) : $method;
            }
        }
        $uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        if(!$this->inner_dispatch($uri, $method, ...$args)) {
            Views\View::response("Data could not be found!", 404)();
        }
    }
};

