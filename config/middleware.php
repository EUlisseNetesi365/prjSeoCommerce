<?php

use Slim\App;
use BrainStorm\BasePath\BasePathMiddleware;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Middleware\ErrorMiddleware;
use PerSeo\MiddleWare\Language;
use BrainStorm\Slim4Locale\Locale;
use PerSeo\MiddleWare\Alias;
use PerSeo\MiddleWare\Admin;
use PerSeo\MiddleWare\Maintenance;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use PerSeo\MiddleWare\LoadSettings;
use PerSeo\MiddleWare\Wizard;
use PerSeo\MiddleWare\HttpExceptionMiddleware;
use PerSeo\MiddleWare\ErrorHandlerMiddleware;
use PerSeo\MiddleWare\GZIP;
use Odan\Session\Middleware\SessionMiddleware;
use Modules\layout\Middleware as LayoutMiddleware;


return function (App $app) {
    $settings = $app->getContainer()->get('settings.global');
    
    if (file_exists('settings.php')) { $app->add(LayoutMiddleware::class); }
    // Parse json, form data and xml
    $app->addBodyParsingMiddleware();
    
    $app->add(TwigMiddleware::class);

    //$app->add(LoadSettings::class);

    // Add the Slim built-in routing middleware
    $app->addRoutingMiddleware();
    
    // Add locale in url Middleware
    $app->add(new Locale($app, $settings['locale'], $settings['languages']));
    
    if (file_exists('settings.php')) { $app->add(Alias::class); }
    
    $app->add(Maintenance::class);
    
    $app->add(Admin::class);
    
    $app->add(Wizard::class);
    
    // Set language from browser
    $app->add(Language::class);

    // Session
    $app->add(SessionMiddleware::class);
    
    //Add Basepath Middleware
    $app->add(BasePathMiddleware::class);
    
    $app->add(HttpExceptionMiddleware::class);
    
    $app->add(ErrorHandlerMiddleware::class);
    
    // Catch exceptions and errors
    $app->add(ErrorMiddleware::class);
    
    //$app->add(GZIP::class);
};
