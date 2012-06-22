<?php
/**
 * Twig SiteDriver v1.0.1
 *
 * Licensed under the terms of the Simplified BSD License
 *
 * The Twig SiteDriver provides a simple PHP environment that is designed to serve
 * a primarily static website from Twig templates. Twig templates provide access to
 * a clean building-block style structure to the website, and eliminates duplicate
 * code.
 *
 * A site with less code is easier to maintain in the long run.
 *
 * Usage:
 * Place the .htaccess file, and twig-sitedriver.php files in the root directory of
 * the website. Place any templates in "templates/" and create "template_cache" so
 * that it can be written to by your webserver by Twig.
 *
 * Copyright (c) 2012, Stacey Vetzal, Three Wise Men Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met: 
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer. 
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution. 
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies, 
 * either expressed or implied, of Three Wise Men Inc.
 */

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
      ini_set('display_errors','On');
    }

    // Extract path and args from URI
    if (preg_match('/^([^\?]*)(\?.*)?$/', $_SERVER['REQUEST_URI'], $matches) > 0) {
      $this->filename = $matches[1];
      if (count($matches)>2) {
        $this->args = $matches[2];
      }
    }

    if (preg_match('/\/$/', $this->filename, $matches) > 0) {
      $this->filename = $this->filename . "index.html";
    }

    // Calculate PHP filename if looking for .htm or .html
    if (preg_match('/^([^\.]*)\.(.*)$/', $this->filename, $matches) > 0) {
      $this->base_filename = $matches[1];
      if (count($matches)>2) {
        $this->extension = $matches[2];
        if ($this->extension == "htm" || $this->extension == "html") {
            $this->php_filename = $this->base_filename . ".php";
        }
        $this->php_filepath = $_SERVER['DOCUMENT_ROOT'].$this->php_filename;
      }
    }

  }

  public function get_twig() {
    if (!$this->twig) {
      // Set up templating
      require_once 'twig-1.8.1/lib/Twig/Autoloader.php';
      Twig_Autoloader::register();
      $loader = new Twig_Loader_Filesystem('templates');
      $opts = array();
      if ($this->config['cache']) { array_merge($opts, array('cache' => $this->config['cache_dir'])); }
      if ($this->config['reload']) { array_merge($opts, array('auto_reload' => true)); }
      $this->twig = new Twig_Environment($loader, $opts);
    }
    return $this->twig;
  }

  public function get_server_uri() {
   if (array_key_exists('HTTPS', $_SERVER)) {
      $scheme = $_SERVER['HTTPS']? "https":"http";
    } else {
      $scheme = "http";
    }
    return $scheme."://".$_SERVER['SERVER_NAME'];
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
      header("Location: ".$this->get_server_uri().$this->php_filename.$this->args);
      exit(0);
    } else {
      // Check for template file with provided name, and render it or 404
      $template_file = $_SERVER['DOCUMENT_ROOT']."/".$this->options['template_dir'].$this->filename;
      if (file_exists($template_file)) {
          echo $this->get_twig()->render($this->filename, $this->default_variables());
      } else {
          header("HTTP/1.0 404 Not Found");
          echo $this->get_twig()->render($this->options['404_template']);
      }
    }
  }
}

$tsd = new TwigSiteDriver();
$tsd->service_request();
?>


