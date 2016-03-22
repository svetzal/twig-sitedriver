<?php

class TwigSiteDriver {
    protected $options = array(
        'template_dir' => 'templates',
        'cache_dir' => 'template_cache',
        '404_template' => '404.html',
        'debug' => false,
        'cache' => true,
        'reload' => true
    );
    protected $filename, $args, $base_filename, $extension;
    protected $php_filepath, $php_filename;
    protected $twig;

    public function __construct($options = array()) {
        // Update defaults with any provided options
        $this->options = array_merge($this->options, $options);

        if ($this->options['debug']) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        }

        // Extract path and args from URI
        if (preg_match('/^([^\?]*)(\?.*)?$/', $_SERVER['REQUEST_URI'], $matches) > 0) {
            $this->filename = $matches[1];
            if (count($matches) > 2) {
                $this->args = $matches[2];
            }
        }

        if (preg_match('/\/$/', $this->filename, $matches) > 0) {
            $this->filename = $this->filename . "index.html";
        }

        // Calculate PHP filename if looking for .htm or .html
        if (preg_match('/^([^\.]*)\.(.*)$/', $this->filename, $matches) > 0) {
            $this->base_filename = $matches[1];
            if (count($matches) > 2) {
                $this->extension = $matches[2];
                if ($this->extension == "htm" || $this->extension == "html") {
                    $this->php_filename = $this->base_filename . ".php";
                }
                $this->php_filepath = $_SERVER['DOCUMENT_ROOT'] . $this->php_filename;
            }
        }

    }

    public function get_twig() {
        if (!$this->twig) {
            // Set up templating
            $loader = new Twig_Loader_Filesystem('templates');
            $opts = array();
            if ($this->config['cache']) {
                array_merge($opts, array('cache' => $this->config['cache_dir']));
            }
            if ($this->config['reload']) {
                array_merge($opts, array('auto_reload' => true));
            }
            $this->twig = new Twig_Environment($loader, $opts);
        }
        return $this->twig;
    }

    public function get_server_uri() {
        if (array_key_exists('HTTPS', $_SERVER)) {
            $scheme = $_SERVER['HTTPS'] ? "https" : "http";
        } else {
            $scheme = "http";
        }
        return $scheme . "://" . $_SERVER['SERVER_NAME'];
    }

    public function php_file_exists() {
        return $this->php_filename && file_exists($this->php_filepath);
    }

    public function default_variables() {
        return array(
            'base_filename' => $this->base_filename,
            'filename' => $this->filename
        );
    }

    public function service_request() {
        // If this file is called 'index.php', need to avoid a redirect loop
        if ($this->php_filename != "/index.php" && $this->php_file_exists()) {
            // Issue header and redirect to PHP file so the URL in the browser updates
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: " . $this->get_server_uri() . $this->php_filename . $this->args);
            exit(0);
        } else {
            // Check for template file with provided name, and render it or 404
            $template_file = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->options['template_dir'] . $this->filename;
            if (file_exists($template_file)) {
                echo $this->get_twig()->render($this->filename, $this->default_variables());
            } else {
                header("HTTP/1.0 404 Not Found");
                echo $this->get_twig()->render($this->options['404_template']);
            }
        }
    }
}
