<?php
    // Set your timezone here
    date_default_timezone_set('Europe/Stockholm');
    mb_internal_encoding('UTF-8');
    
    if ($_SERVER['SERVER_NAME'] =='ec2-52-59-247-193.eu-central-1.compute.amazonaws.com') {
        setlocale(LC_TIME, 'sv_SE.utf8');
    } else {
        setlocale(LC_TIME, 'sv_SE.UTF-8');
    }
    

    // Do not send fatal errors to the response body!
    ini_set("display_errors", "on");

    /* Instantiate the Slim application */
    $app = new \UserFrosting\UserFrosting([
        'view' =>           new \Slim\Views\Twig(),
        'mode' =>           ($_SERVER['SERVER_NAME'] =='ec2-52-59-247-193.eu-central-1.compute.amazonaws.com' ? 'production':'dev'),   // Set to 'dev' or 'production'
    ]);


    // Get file path to public directory for this website.  Is this guaranteed to work in all environments?
    $public_path = $_SERVER['DOCUMENT_ROOT'] . $app->environment()['SCRIPT_NAME'];

    // Construct public URL (e.g. "http://www.userfrosting.com/admin").  Feel free to hardcode this if you feel safer.
    $environment = $app->environment();
    $serverport = (($environment['SERVER_PORT'] == 443) or ($environment['SERVER_PORT'] == 80)) ? '' : ':' . $environment['SERVER_PORT'];
    $uri_public_root = $environment['slim.url_scheme'] . "://" . $environment['SERVER_NAME'] . $serverport . $environment['SCRIPT_NAME'];

    /********* DEVELOPMENT SETTINGS *********/
    $app->configureMode('dev', function () use ($app, $public_path, $uri_public_root) {
        $app->config([
            'log.enable' => true,
            'debug' => false,
            'base.path'     => __DIR__,
            'templates.path' => __DIR__ . '/templates',     // This will be overridden anyway by the default theme.
            'themes.path'    =>  __DIR__ . '/templates/themes',
            'plugins.path' => __DIR__ . '/plugins',
            'schema.path' =>    __DIR__ . '/schema',
            'locales.path' =>   __DIR__ . '/locale',
            'log.path' =>   __DIR__ . '/log',
            'public.path' => $public_path,
            'js.path.relative' => "/js",
            'css.path.relative' => "/css",
            'session' => [
                'name' => 'UserFrosting',
                'cache_limiter' => false
            ],
            'db'            =>  [
                'db_host'  => '127.0.0.1',
                'db_name'  => 'taxibo',
                'db_user'  => 'root',
                'db_pass'  => 'ode52nse',
                'db_prefix'=> 'taxibo_'
            ],
            'mail' => 'smtp',
            'smtp'  => [
                'host' => 'smtp.mandrillapp.com',
                'port' => 587,
                'auth' => true,
                'secure' => 'tls',
                'user' => 'taxibooking@allanth.dk',
                'pass' => 'ObhjkeBMtPfojax5jENmDw'
            ],
            
            'uri' => [
                'public'            => $uri_public_root,
                'js-relative'       => "/js",
                'css-relative'      => "/css",
                'favicon-relative'  => "/css/favicon.ico",
                'image-relative'    => "/images"
            ],
            'user_id_guest'  => 0,
            'user_id_master' => 1,
            'theme-base'     => "default",
            'theme-root'     => "root"
        ]);
    });

    /********* PRODUCTION SETTINGS *********/
    $app->configureMode('production', function () use ($app, $public_path, $uri_public_root) {
        $app->config([
            'log.enable' => true,
            'debug' => false,
            'base.path'     => __DIR__,
            'templates.path' => __DIR__ . '/templates',
            'themes.path'    =>  __DIR__ . '/templates/themes',
            'plugins.path' => __DIR__ . '/plugins',
            'schema.path' =>    __DIR__ . '/schema',
            'locales.path' =>   __DIR__ . '/locale',
            'log.path' =>   __DIR__ . '/log',
            'public.path' => $public_path,
            'js.path.relative' => "/js",
            'css.path.relative' => "/css",
            'session' => [
                'name' => 'UserFrosting',
                'cache_limiter' => false
            ],
            'db'            =>  [
                'db_host'  => 'taxibo-db-instance.ck9eksqtjqn1.eu-central-1.rds.amazonaws.com',
                'db_name'  => 'taxibo',
                'db_user'  => 'root',
                'db_pass'  => 'ode52nse',
                'db_prefix'=> 'taxibo_'
            ],
            'mail' => 'smtp',
            'smtp'  => [
                'host' => 'smtp.mandrillapp.com',
                'port' => 587,
                'auth' => true,
                'secure' => 'tls',
                'user' => 'taxibooking@allanth.dk',
                'pass' => 'I4fjgFnNJNVLRz-EZyH00A'
            ],
            'uri' => [
                'public'            => $uri_public_root,
                'js-relative'       => "/js",
                'css-relative'      => "/css",
                'favicon-relative'  => "/css/favicon.ico",
                'image-relative'    => "/images"
            ],
            'user_id_guest'  => 0,
            'user_id_master' => 1,
            'theme-base'     => "default",
            'theme-root'     => "root"
        ]);
    });

    // Set up derived configuration values
    $app->config([
        'js.path' =>  $app->config('public.path') . $app->config('js.path.relative'),
        'css.path' => $app->config('public.path') . $app->config('css.path.relative'),
        'uri' => [
            'js' =>        $app->config('uri')['public'] . $app->config('uri')['js-relative'],
            'css' =>       $app->config('uri')['public'] . $app->config('uri')['css-relative'],
            'favicon' =>   $app->config('uri')['public'] . $app->config('uri')['favicon-relative'],
            'image' =>     $app->config('uri')['public'] . $app->config('uri')['image-relative'],
        ]
    ], true);


