<?php

namespace PerSeo\MiddleWare;

use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;


class LoadSettings implements Middleware
{
    protected $app;
    protected $db;
    protected $cache;
    protected $container;
    protected $cache_settings;

    public function __construct(App $app, ContainerInterface $container, Psr16Adapter $cache, DBDefault $db)
    {
        $this->app = $app;
        $this->db = $db;
        $this->container = $container;
        $this->cache = $cache;
        $this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $this->cache_settings = (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $db = $this->db;
        $items = [];
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));

        $cachename = 'settings_params';

        if ($this->cache_settings) {
            if (!$this->cache->has($cachename)) {
                $items = json_encode($db->select("settings", "params"));
                $this->cache->set($cachename, $items, 3600);
            } else {
                $items = $this->cache->get($cachename);
            }
        } else {
            $items = json_encode($db->select("settings", "params"));
        }

        $request = $request->withAttribute('settings_list', $items);
        $response = $handler->handle($request);
        return $response;
    }
}