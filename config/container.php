<?php

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;

use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use BrainStorm\BasePath\BasePathMiddleware;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Twig\Extension\DebugExtension;
use Odan\Twig\TwigAssetsExtension;
use Odan\Session\PhpSession;
use Odan\Session\SessionInterface;
use Odan\Session\Middleware\SessionMiddleware;
use PerSeo\LoggerFactory;
use PerSeo\MiddleWare\DefaultErrorRender;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use PerSeo\DB\DBDefault;
use PHPMailer\PHPMailer\PHPMailer;
use Phpfastcache\Helper\Psr16Adapter;
use PerSeo\MiddleWare\LoadSettings;
use PerSeo\Twig\Extensions\Custom;
use Modules\settings\Settings;


return [

    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },

    BasePathMiddleware::class => function (App $app) {
        return new BasePathMiddleware($app);
    },

    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings.logger'));
    },

    LoggerInterface::class => function (ContainerInterface $container): Logger {
        $loggerSettings = $container->get('settings.logger');

        $logger = new Logger($loggerSettings['name']);

        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        $handler = new StreamHandler($loggerSettings['path'] . '/' . $loggerSettings['filename'] . md5(date("mdy")) . '.log', $loggerSettings['level']);
        $logger->pushHandler($handler);

        return $logger;
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);
        $settings = $container->get('settings.error');
        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool) $settings['display_error_details'],
            (bool) $settings['log_errors'],
            (bool) $settings['log_error_details']
        );
        $errorHandler = $errorMiddleware->getDefaultErrorHandler();
        $errorHandler->registerErrorRenderer('text/html', DefaultErrorRender::class);
        return $errorMiddleware;
    },

    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    },

    SessionInterface::class => function (ContainerInterface $container) {
        $settings = $container->get('settings.session');
        $session = new PhpSession();
        $session->setOptions((array) $settings);

        return $session;
    },

    SessionMiddleware::class => function (ContainerInterface $container) {
        return new SessionMiddleware($container->get(SessionInterface::class));
    },

    DBDefault::class => function (ContainerInterface $container) {
        if ($container->has('settings.db')) {
            $settings = $container->get('settings.db');
            return new DBDefault([
                'database_type' => $settings['default']['driver'],
                'database_name' => $settings['default']['database'],
                'server' => $settings['default']['host'],
                'username' => $settings['default']['username'],
                'password' => $settings['default']['password'],
                'prefix' => $settings['default']['prefix'],
                'charset' => $settings['default']['charset']
            ]);
        }
    },

    Twig::class => function (ContainerInterface $container) {
        $twigSettings = $container->get('settings.twig');

        $options['debug'] = $twigSettings['debug'];
        $options['cache'] = $twigSettings['cache_enabled'] ? $twigSettings['cache_path'] : false;

        $twig = Twig::create($twigSettings['paths'], $options);

        $environment = $twig->getEnvironment();

        // Set global variables
        /*$globalVar = $container->get('settings.global');
        $environment->addGlobal('images', $globalVar['assets']['images']);
        $environment->addGlobal('favicon', $globalVar['assets']['favicon']);
        $environment->addGlobal('css', $globalVar['assets']['css']);
        $environment->addGlobal('js', $globalVar['assets']['js']);
        */
        // Add extension here
        $twig->addExtension(new DebugExtension());
        $twig->addExtension(new TwigAssetsExtension($environment, (array) $twigSettings));
        $twig->addExtension(new Custom());

        return $twig;
    },

    TwigMiddleware::class => function (ContainerInterface $container) {
        return TwigMiddleware::createFromContainer(
            $container->get(App::class),
            Twig::class
        );
    },

    PHPMailer::class => function (ContainerInterface $container) {
        if ($container->has('settings.mailer')) {
            $mail_settings = $container->get('settings.mailer');
            $mail = new PHPMailer(true);
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            //$mail->SMTPDebug = 1;
            $mail->isSMTP();
            $mail->Host = $mail_settings['default']['host'];
            $mail->SMTPAuth = ($mail_settings['default']['auth'] ? true : false);
            $mail->Username = $mail_settings['default']['username'];
            $mail->Password = $mail_settings['default']['password'];
            $mail->SMTPSecure = ($mail_settings['default']['secure'] ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS);
            $mail->Port = $mail_settings['default']['port'];
            $mail->CharSet = 'UTF-8';
            return $mail;
        }
    },
    Psr16Adapter::class => function (ContainerInterface $container) {
        return new Psr16Adapter('Redis');
    },
    Settings::class => function (DBDefault $db, ContainerInterface $container, Psr16Adapter $cache) {
        $cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $is_cache = (!empty($cache_settings) ? $cache_settings['query'] : false);

        return new Settings($db, $cache, $is_cache);
    }
];