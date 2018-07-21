# Flight - Simplified PHP Framework
This framework is used for simple web navigation.
Originally I wanted something similar to Python Django framework but simpler, 
and I wanted to experience development of a web framework - just for a fun side project.

The first basic dispatcher I came across was https://github.com/noodlehaus/dispatch/blob/master/dispatch.php,
but I wanted something a little more complicated (with support of nested uris and regex!),
so part of the code is taken from that repo and got wrapped with classes.  


### How to Install?
in your project:
```bash
$ composer require iamshobe/flight
$ composer update  # if already installed
```
```php
require_once "./vendor/autoload.php";
```
### Documentation 

##### \Flight
###### class Dispatcher($base_dir, $urls, $use_static=true)
$base_dir - is the directory of the application root.
$urls - the root urls array using URL class objects.
$use_static - true/false if the app uses static folder.

Dispatcher::dispatch(...$args) - the dispatch method - call at the end of the main file.
- ...$args - arguments to send to all the views.


##### \Flight\URLS
###### class URL($regex, $handler, $methods = ["GET", "POST", "PUT", "DELETE", "PATCH"])
$regex - the regex of the uri.
$handler - an handler view that will be called.
$methods - which methods should the url be active on.

###### class IncludeURLS($urls)

$urls - the expanded sub urls that should be included from a different location.

##### \Flight\Views
###### class View()
View::__invoke($vars) - this method should be overrided by inheriting views.
the return value should be a response function that will be activated once the view is on.

static View::response($data, $status_code = 200, $headers = []) - a response function.
$data - the data to send
$status_code - response status code.
$headers - the headers of the response.

View::static_file($path) - serve a static file.
$path - the path of the file.

View::page($path, $vars) - render a phtml file.
$path - the path of the file.
$vars - the vars that should be passed to the template file.

###### class StaticFile() 
static file serve view.


### How to Use Example
.htaccess file:
```apacheconfig
RewriteEngine On

RewriteCond %{REQUEST_URI} !^/index.php$
RewriteRule .* ./index.php
```

your project working tree:

```
project_dir/
+-- vendor/
|   +-- .....(libs)....
|   +-- autoload.php
+-- src/
|   +-- URLS
|   |   +-- URLS.php
|   +-- Views
|   |   +-- Index.php
|   |   +-- File.php
+-- static/
|  +-- js/
|  |  +-- test.js
+-- template/
|  +-- index.phtml
+-- index.php
```

src/Views/Index.php:
```php
<?php namespace App\Views;
use \Flight\Views\View;


class Index extends View {
    public function __invoke($vars) {
        $json = json_encode(["hello" => $vars["hello"]]);
        return $this->response($json, 200, ["content-type" => "application/json"]);
    }
}
```
src/Views/File.php:
```php
<?php namespace App\Views;
use \Flight\Views\View;

class File extends View {
    public function __invoke($vars) {
        return $this->page("index.phtml");
    }
}
```

src/URLS/URLS.php:
```php
<?php namespace App\URLS;
use \Flight\URLS\URL;

class URLS {
    function __invoke()
    {
        return [
            new URL("^lol$", new \App\Views\File()),
            new URL("^(?P<hello>.*)$", new \App\Views\Index())
        ];
    }
}
```

index.php:
```php
<?php

require_once './lib/flight/loader.php';
require_once "./urls.php";


(new \Flight\Dispatcher(__DIR__, (new App\URLS\URLS())()))->dispatch();
```

templates/index.phtml:
```phtml
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
<script src="static/test.js"></script> <!-- This is an example for static file import -->
    This is the main file!
</body>
</html>
```

### Nested URLS
URLS2.php
```php
<?php namespace App\URLS;
use \Flight\URLS\URL;

class URLS2 {
    function __invoke()
    {
        return [
            // this will cause a prefix of http://example.com/lol....
            new URL("^lol$", new \Flight\URLS\IncludeURLS((new \App\URLS\URLS())())) 
        ];
    }
}
```
URLS.php
```php
<?php namespace App\URLS;
use \Flight\URLS\URL;

class URLS {
    function __invoke()
    {
        return [
            // this will cause a url of http://example.com/lollol
            new URL("^lol$", new \App\Views\File(), ["GET"]),
            // this will cause a prefix of http://example.com/lol(.*)
            // where everything in brackets will be passed to hello var array.
            new URL("^(?P<hello>.*)$", new \App\Views\Index(). ["POST"])
        ];
    }
}
```
