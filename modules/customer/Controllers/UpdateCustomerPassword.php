<?php


namespace Modules\customer\Controllers;


use Modules\customer\Classes\Customer;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;

class UpdateCustomerPassword
{
    protected $container;
    protected $session;
    protected $customer;
    protected $app;
    protected $db;
    protected $twig;
    protected $log;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, Twig $twig, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->session = $session;
        $this->container = $container;
        $this->db = $db;
        $this->twig = $twig;
        $this->log = $logger;
        $this->customer = new Customer($db, $container, $session, $this->log);

    }

    public function __invoke(Request $request, Response $response): Response {
        $post = $request->getParsedBody();
        $customer_id = (int) $this->session->get('customer.id');
        $password = (string) (!empty($post['password']) ? trim($post['password']) : '');
        $code = (string) (!empty($post['code']) ? $post['code'] : '');
        $result = $this->customer->updatePasswordCustomer($customer_id, $password, $code);
        $response->getBody()->write($result);
        return $response;
    }

}