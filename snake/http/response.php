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

        // Re-Globalizing the passed data variables for rendering blade helpers
        if ($data != NULL) {
            foreach ($data as $var => $val) {
                $$var = $val;
            }
        }

        global $root_path;

        // 1. Regex to match all @layout(...) occurrences
        $pattern = '/@layout\((.*?)\)/';

        // Find all occurrences
        preg_match_all($pattern, $html, $matches);

        // $matches[1] will contain all strings inside parentheses
        foreach ($matches[1] as $layout) {

            // Example: replace @layout(...) with some PHP include
            $name = str_replace('.', '/', $layout);
            $view_file = $root_path . '/app/views/' . $name . '.php';
            ob_start();
            include_once $view_file;
            $include_html = ob_get_clean();

            $include_html = $this->renderBladeSyntax($include_html, $data);

            // Replace in original HTML
            $html = str_replace("@layout($layout)", $include_html, $html);
        }

        // 2. Regex to match all @assets(...) occurrences
        $pattern = '/@assets\((.*?)\)/';

        // Find all occurrences
        preg_match_all($pattern, $html, $matches);

        // $matches[1] will contain all strings inside parentheses
        foreach ($matches[1] as $assets) {

            // Example: replace @assets(...) with some PHP include
            $asset_path = '/' . 'assets/' . $assets;

            // Replace in original HTML
            $html = str_replace("@assets($assets)", $asset_path, $html);
        }

        // Output or return modified HTML
        return $html;
    }
}
