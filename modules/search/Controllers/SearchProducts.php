<?php


namespace Modules\search\Controllers;

use Modules\search\Classes\Search;
use PerSeo\Validator;
use Slim\App;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

class SearchProducts
{
    protected $session;
    protected $customer;
    protected $db;
    protected $cache;
    protected $cache_settings;
    protected $log;
    protected $app;

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
        $language = (!empty($post['language'])) ? $post['language'] : 'it';
        $keystring = (!empty($post['value_search'])) ? (string) $post['value_search'] : '';
        $validate = new Validator();
        $newKey = $validate->sanitizeUtf8($keystring);
        $searchdata = new Search($this->db, $this->cache, $this->log, $this->cache_settings, $language);
        $items = $searchdata->searchData($newKey, 'json');

        $response->getBody()->write($items);
        return $response;
    }
}