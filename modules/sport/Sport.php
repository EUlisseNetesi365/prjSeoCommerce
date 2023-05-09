<?php

namespace Modules\sport;

use Modules\categories\Classes\Category;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use PerSeo\Translator;

class Sport {

    protected $container;
    protected $twig;
    protected $global;
    protected $log;
    protected $db;
    protected $cache;
    protected $cache_settings;

    public function __construct(ContainerInterface $container, Twig $twig, DBDefault $db, Psr16Adapter $cache, LoggerInterface $logger) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->db = $db;
        $this->cache = $cache;
        $this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $this->cache_settings = (bool) (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
        $this->log = $logger;
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/sport';
		$language = $request->getAttribute('locale');
		$curtemplate = $this->global['template'];
		$langs = (new Translator($language, $module))->get();
        $categories = new Category($this->db, $this->cache, $this->cache_settings, $language);
        $altmenu = $categories->readAll(2);

		$viewData = [
            'title' => "Sport",
            'lang' => $langs['body'],
            'altmenu' => $altmenu
        ];

        return $this->twig->render($response, $curtemplate . '/sport/sport.twig', $viewData);
    }
}