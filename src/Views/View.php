<?php namespace Flight\Views;
class View
{
    private $__base_dir;
    public function __invoke($vars) {
        throw new \RuntimeException("This method should be overridden!");
    }
    # creates an page-rendering action
    function page($path, array $vars = [])
    {
        /**
         * Credit to noodlehaus/dispatch.
         * https://github.com/noodlehaus/dispatch
         */
        return function () use ($path, $vars) {
            return self::response($this->phtml($path, $vars))();
        };
    }

    # renders and returns the content of a template
    function phtml($path, array $vars = [])
    {
        /**
         * Credit to noodlehaus/dispatch.
         * https://github.com/noodlehaus/dispatch
         */
        ob_start();
        extract($vars, EXTR_SKIP);
        $dispatcher = \Flight\Dispatcher::$dispatcher;
        require "{$dispatcher->base_dir}/templates/{$path}";
        return trim(ob_get_clean());
    }
    static function guess_mime_type($path) {
        $mime_type = mime_content_type($path);
        if ($mime_type == "text/plain") {
            // backup incase mime_content_type can't recognize the type
            $path_parts = pathinfo($path);
            $mimet = array(
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',

                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',

                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',

                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',

                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',

                // ms office
                'doc' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',
                'docx' => 'application/msword',
                'xlsx' => 'application/vnd.ms-excel',
                'pptx' => 'application/vnd.ms-powerpoint',


                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            );

            if (isset( $mimet[$path_parts['extension']] )) {
                return $mimet[$path_parts['extension']];
            } else {
                return 'text/plain';
            }
        }
        return $mime_type;
    }
    function static_file($path) {
        $dispatcher = \Flight\Dispatcher::$dispatcher;
        $path = $dispatcher->base_dir."/static/".$path;
        $mime_type = self::guess_mime_type($path);
        $file = fopen($path, "r");
        if (!$file) {
           return function () {
               return self::response("Data could not be found!", 404)();
           };
        }
        $data = fread($file, filesize($path));
        fclose($file);
        return function () use ($data, $mime_type) {
            return self::response($data, 200, ["content-type" => $mime_type])();
        };
    }
    static function response($data, $status_code = 200, $headers = [])
    {
        /**
         * Credit to noodlehaus/dispatch.
         * https://github.com/noodlehaus/dispatch
         */
        return function () use ($data, $status_code, $headers) {
            http_response_code($status_code);
            array_walk($headers, function ($value, $key) {
                if (!preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $key)) {
                    throw new \InvalidArgumentException("Invalid header name - {$key}");
                }
                $values = is_array($value) ? $value : [$value];
                foreach ($values as $val) {
                    if (
                        preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $val) ||
                        preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $val)
                    ) {
                        throw new \InvalidArgumentException("Invalid header value - {$val}");
                    }
                }
                header($key . ': ' . implode(',', $values));
            });
            print $data;
        };
    }
}


