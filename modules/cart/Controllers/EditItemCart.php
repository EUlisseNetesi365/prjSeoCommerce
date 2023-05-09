<?php

namespace Modules\cart\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\cart\Models\Cart as CartModel;
use http\Cookie;
use Psr\Container\ContainerInterface;
use PerSeo\DB\DBDefault;
use Psr\Log\LoggerInterface;


class EditItemCart
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
		$v4warehouses_id = (int) (!empty($post['v4warehouses_id']) ? $post['v4warehouses_id'] : 0);
        $quantity = (int) (!empty($post['quantity']) ? $post['quantity'] : 0);
		if (($quantity >= 0) && ($v4warehouses_id > 0)) {
			$cart_data = new CartModel($this->db, $this->container, $this->session, $this->log);
			$cart_result = $cart_data->EditItem($v4warehouses_id, $quantity, $language);
		}
		else {
			$cart_result = array(
                'success' => 0,
                'error' => 1,
				'data' => NULL,
                'code' => 001,
                'msg' => 'INVALID_REQUEST'
            );
		}
        $response->getBody()->write($cart_result);
        return $response;
    }
}