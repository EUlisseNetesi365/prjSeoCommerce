<?php

namespace Modules\categories\Controllers;

use Modules\products\Classes\VariantList;
use Modules\search\Classes\Search;
use Slim\App;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class Page
{
    protected App $app;
    protected SessionInterface $session;
    protected DBDefault $db;
    protected Psr16Adapter $cache;
    protected $cache_settings;
    protected LoggerInterface $log;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, Psr16Adapter $cache, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->db = $db;
        $this->cache = $cache;
        $this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $this->cache_settings = (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
        $this->session = $session;
        $this->log = $logger;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        $language = $request->getAttribute('locale');

        $getItems = (!empty($post['getVariants'])) ? $post['getVariants'] : '';
        $variantList = new VariantList($this->db, $this->cache, $this->cache_settings, $language, 'it', $this->log);

        $items = $variantList->readAll($getItems, 'json');

        $response->getBody()->write($items);
        return $response;
    }
}