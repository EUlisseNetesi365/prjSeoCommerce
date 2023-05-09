<?php


namespace Modules\products\Controllers;

use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\products\Model\VariantAttributes;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Log\LoggerInterface;

class VariantAttributePrimary
{
    protected $session;
    protected $customer;
    protected $db;
    protected $cache;
    protected $container;
    protected $log;
    protected $app;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, Psr16Adapter $cache, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->session = $session;
        $this->db = $db;
        $this->log = $logger;
        $this->container = $container;
        $this->cache = $cache;

    }

    public function __invoke(Request $request, Response $response): Response {

        $post = $request->getParsedBody();
        $product_id = (!empty( $post['p_id'] )) ?  $post['p_id'] : 0;
        $attrList = (int) (!empty( $post['data'] )) ?  $post['data'] : '';
        $primaryattr = new VariantAttributes($this->app, $this->container, $this->session, $this->db, $this->cache, $this->log);
        $items = $primaryattr->getVariant($product_id, $attrList);

        $response->getBody()->write($items);
        return $response;
    }
}