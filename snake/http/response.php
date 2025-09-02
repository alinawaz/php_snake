<?php

namespace Snake\Http;

class Response
{

    private $status_code = 0;

    public function view($name, $data = [])
    {
        global $root_path;

        // Globalizing the passed data variables
        if ($data != NULL) {
            foreach ($data as $var => $val) {
                $$var = $val;
            }
        }

        // Reading view file
        $name = str_replace('.', '/', $name);
        $view_file = $root_path . '/app/views/' . $name . '.php';
        ob_start();
        include_once $view_file;
        $html = ob_get_clean();

        // Handling blader helpers
        $html = $this->renderBladeSyntax($html, $data);

        // re-writing rendered html into /storage/views/..
        $cached_name = $this->createViewFile($name, $html);

        // Including new file
        include_once $root_path . '/storage/views/' . $cached_name . '.php';
        die();
    }

    public function createViewFile($name, $html)
    {

        global $root_path;

        $name = str_replace('/', '_', $name);
        $file = fopen($root_path . '/storage/views/' . $name . '.php', "w");
        fwrite($file, $html);
        fclose($file);

        return $name;
    }

    public function status($code)
    {

        $this->status_code = $code;
        return $this;
    }

    public function json($data)
    {

        header('Content-Type: application/json');

        if ($this->status_code > 0) {
            http_response_code(401);
        }

        return json_encode($data);
    }

    public function redirect($url)
    {
        header("Location: " . $url);
        exit;
    }

    private function renderBladeSyntax($html, $data)
    {

        global $root_path;

        /* 1. @layout(...) */
        $html = preg_replace_callback('/@layout\((.*?)\)/', function ($matches) use ($root_path, $data) {

            if ($data != NULL) {
                foreach ($data as $var => $val) {
                    $$var = $val;
                }
            }

            // Convert dot notation to path
            $name = str_replace('.', '/', trim($matches[1], "'\" "));
            $view_file = $root_path . '/app/views/' . $name . '.php';

            if (!file_exists($view_file)) {
                return "<!-- layout not found: {$name} -->";
            }

            // Capture output of included file
            ob_start();
            include_once $view_file;
            $include_html = ob_get_clean();

            // Re-run through Blade-like renderer
            if (method_exists($this, 'renderBladeSyntax')) {
                $include_html = $this->renderBladeSyntax($include_html, $data);
            }

            return $include_html;
        }, $html);

        /* 2. @assets(...) */
        $html = preg_replace_callback('/@assets\((.*?)\)/', function ($matches) {
            return '/assets/' . trim($matches[1], "'\" ");
        }, $html);

        /* 3. {{...}} */
        $html = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/', function ($matches) {
            return '<?php echo ' . $matches[1] . '; ?>';
        }, $html);

        // Matches @if(...)
        $html = preg_replace('/@if\s*\((.*?)\)/', '<?php if ($1): ?>', $html);

        // Matches @elseif(...)
        $html = preg_replace('/@elseif\s*\((.*?)\)/', '<?php elseif ($1): ?>', $html);

        // Matches @else
        $html = preg_replace('/@else\b/', '<?php else: ?>', $html);

        // Matches @endif
        $html = preg_replace('/@endif\b/', '<?php endif; ?>', $html);


        // Output or return modified HTML
        return $html;
    }
}
