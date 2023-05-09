<?php

namespace Modules\customer\Controllers;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\customer\Classes\Login;
use PerSeo\DB\DBDefault;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Modules\customer\Classes\CustomerData;
use Slim\App;

class LoginController
{
	protected $type;
	protected $db;
	protected $container;
	protected $session;
	protected $customer;
	protected $login;
	protected $twig;
	protected $app;
	protected $log;

	public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, Twig $twig, LoggerInterface $logger)
	{
		$this->app = $app;
		$this->container = $container;
	    $this->session = $session;
		$this->login = new Login($db, $session);
		$this->db = $db;
		$this->twig = $twig;
		$this->log = $logger;

	}

	public function __invoke(Request $request, Response $response): Response
	{
		$post = $request->getParsedBody();
		$username = (string) (!empty($post['username']) ? trim($post['username']) : '');
		$password = (string) (!empty($post['password']) ? trim($post['password']) : '');
		$result = $this->login->verify($username, $password);

		if( json_decode($result,1)['success']){
             $defaultAddresses = new CustomerData($this->db, $this->container, $this->session, $this->log);
             $address = $defaultAddresses->getDefAddressForCart();
            /**
             * get ID default ADDRESSES ID from customer_data for logged user
             * and put in session 'customerData.is_default_ship' & 'customerData.is_default_invoice'
             * if the ADDRESSES are the same set only session variable 'customerData.is_default_ship' for both
             * return not used, the response if OK or KO in Perseo format.
             * provided as standard in case it is needed.
             * 20230215 - EUL - Controller CustomerLoggedAddress in customer\Controllers
             *
             */
        }

		$response->getBody()->write($result);
		return $response;
	}
}