<?php

namespace Modules\index\Views;

use Slim\App;
use Odan\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;
use Modules\categories\Classes\Category;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Modules\products\Classes\VariantList;

class Main {

    protected App $app;
    protected ContainerInterface $container;
    protected Twig $twig;
    protected $global;
    protected DBDefault $db;
    protected Psr16Adapter $cache;
    protected $cache_settings;
    protected $country;
    protected LoggerInterface $log;
    protected SessionInterface $session;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, Twig $twig, DBDefault $db, Psr16Adapter $cache, LoggerInterface $logger) {

        $this->app = $app;
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->db = $db;
        $this->session = $session;
        $this->cache = $cache;
        $this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $this->cache_settings = (bool) (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
        $this->log = $logger;
    }

    public function __invoke(Request $request, Response $response): Response {
        
        $module = $this->container->get('settings.modules') . '/index';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Homepage",
            'lang' => $langs['body'],
        ];

        $variantList = new VariantList($this->db, $this->cache, $this->cache_settings, $language, 'it', $this->log);
        $id1 = 2;
        $itemscat2 = $variantList->getVariantsCarousel($id1, '5');
        $id2 = 3;
        $itemscat3 = $variantList->getVariantsCarousel($id2, '5');
        $id3 = 11;
        $itemscat9 = $variantList->getVariantsCarousel($id3, '5');

        $categories = new Category($this->db, $this->cache, $this->cache_settings, $language);
        $alias1 = $categories->getCategoryAlias($id1);
        $alias2 = $categories->getCategoryAlias($id2);
        $alias3 = $categories->getCategoryAlias($id3);
        var_dump("Emilio è un ubriacone"); die();
        $viewData = [ ...$viewData,
            'index' => "true",
            'filter' => false,
            'carousel1' => $itemscat2,
            'carousel2' => $itemscat3,
            'carousel3' => $itemscat9,
            'alias1' => $alias1,
            'alias2' => $alias2,
            'alias3' => $alias3,
        ];
        return $this->twig->render($response, $curtemplate . '/index/index.twig', $viewData);
    }
}