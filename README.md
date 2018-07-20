# Flight - Simplified PHP Framework
This framework is used for simple web navigation.
Originally I wanted something similar to Python Django framework but simpler, 
and I wanted to experience development of a web framework - just for a fun side project.

The first basic dispatcher I came across was https://github.com/noodlehaus/dispatch/blob/master/dispatch.php,
but I wanted something a little more complicated (with support of nested uris and regex!),
so part of the code is taken from that repo and got wrapped with classes.  


### How to Install?
simply copy flight/ directory to your project lib folder!!

In your project:
```php
require_once "./lib/flight/loader.php";
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
+-- lib/
|   +-- flight/
+-- static/
|  +-- js/
|  |  +-- test.js
+-- template/
|  +-- index.phtml
+-- index.php
+-- urls.php
+-- views.php
```

views.php:
```php
<?php namespace App\Views;
use \Flight\Views\View as View;


class Index extends View {
    public function __invoke($vars) {
        $json = json_encode(["hello" => $vars["hello"]]);
        return $this->response($json, 200, ["content-type" => "application/json"]);
    }
}

class File extends View {
    public function __invoke($vars) {
        return $this->page("index.phtml");
    }
}
```

urls.php:
```php
<?php namespace App\URLS;
require_once './views.php';

use \Flight\URLS\URL as URL;


function urls() {
    return [
        new URL("^lol$", new \App\Views\File()),
        new URL("^(?P<hello>.*)$", new \App\Views\Index())
    ];
}
```

index.php:
```php
<?php

require_once './lib/flight/loader.php';
require_once "./urls.php";


(new \Flight\Dispatcher(__DIR__, App\URLS\urls()))->dispatch();
```

templates/index.phtml:
```php
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
urls2.php
```php
<?php namespace App\URLS;
require_once './urls2.php';

use \Flight\URLS\URL as URL;


function urls2() {
    return [
        new URL("^lol$", new \Flight\URLS\IncludeURLS(\App\URLS\urls()))
    ];
}
```
urls.php
```php
<?php namespace App\URLS;
require_once './views.php';

use \Flight\URLS\URL as URL;


function urls() {
    return [
        new URL("^lol$", new \App\Views\File(), ["GET"]),
        new URL("^(?P<hello>.*)$", new \App\Views\Index(). ["POST"])
    ];
}
```
