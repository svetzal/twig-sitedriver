Twig-SiteDriver
===============

If you've ever wanted to build out a website to be hosted on a PHP based
server, this project might help.

I don't like using PHP include directives to extract common HTML
elements, as it ends up with files that have unbalanced tags (ie. your
header opens the html, your footer closes it).

Twig is a PHP templating engine that is reasonably mature and uses a
syntax that I don't find awful.

To build a site effectively with this driver, you need to understand how
to build Twig templates; for more information go here:
http://twig.sensiolabs.org/documentation

Using Twig-SiteDriver
---------------------

The best way to use it is to set it up as an error handler, the provided
.htaccess file does this for you. If you rename the script, don't forget
to update the name in the .htaccess file.

By default, you will need a folder called "template_cache" that your
host's webserver can write to. Sometimes this means using your FTP
client and setting all of the "write" flags on the directory on. For
Unix folk, the permissions on this folder should be 777 or rwxrwxrwx.

The default configuration should work well for most sites, but there are
several options to tune.

At the bottom of the sitedriver file, you will see:

    $tsd = new TwigSiteDriver();
    $tsd->service_request();

You can pass configuration options in when the TwigSiteDriver object is
instantiated. ie.

    $tsd = new TwigSiteDriver(array('cache' => false));
    $tsd->service_request();

This will turn off caching. If you want to set several options at once,
you may want to do something like:

    $opts = array(
      'template_dir' => 'site',
      'cache_dir' => 'my_template_cache',
      '404_template' => 'missing.html',
      'reload' => false
    );
    $tsd = new TwigSiteDriver($opts);
    $tsd->service_request();

Here are what the options are and what they mean:

`template_dir`
The folder in which your site templates are located. Defaults to 'templates'.

cache_dir        the folder used to cache output
                 defaults to 'template_cache'

404_template     the template in the template_dir to be used for 404
                 (page not found) errors
                 defaults to '404.html'

cache            true or false, turns caching on or off
                 defaults to true

reload           true or false, whether cache is rebuilt when templates
                 change so you can update templates, reload, see changes
                 (note, in my experience this does not improve
                 performance)
                 defaults to true

debug            not useful for most, turns on PHP error output

At this time, Twig is expected to be found in a folder called
'twig-1.8.1' (the current version as of this writing). This will be
improved in the future.
