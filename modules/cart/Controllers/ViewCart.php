<?php

namespace Modules\cart\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\cart\Models\Cart as CartModel;
use Psr\Container\ContainerInterface;
use PerSeo\DB\DBDefault;
use Psr\Log\LoggerInterface;


class ViewCart
{
    protected SessionInterface $session;
    protected DBDefault $db;
    protected ContainerInterface $container;
    protected LoggerInterface $log;

    public function __construct(ContainerInterface $container, SessionInterface $session, DBDefault $db, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->db =  $db;
        $this->container = $container;
        $this->log = $logger;
    }


    public function __invoke(Request $request, Response $response): Response {
        $post = $request->getParsedBody();
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
        $cart_data = new CartModel($this->db, $this->container, $this->session, $this->log);
        $response->getBody()->write($cart_data->view($language));
        return $response;
    }
}