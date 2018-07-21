<?php namespace Flight\URLS;
class URL
{
    public $regex;
    public $methods;
    public $handler;

    function __construct($regex, $handler, $methods = ["GET", "POST", "PUT", "DELETE", "PATCH"])
    {
        $this->regex = $regex;
        $this->handler = $handler;
        $this->methods = $methods;
    }
}

class IncludeURLS
{
    public $urls;

    function __construct($urls)
    {
        $this->urls = $urls;
    }
}
