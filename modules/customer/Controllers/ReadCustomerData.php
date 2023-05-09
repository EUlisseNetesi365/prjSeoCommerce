<?php


namespace Modules\customer\Controllers;


use Modules\customer\Classes\CustomerData;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;

class ReadCustomerData
{
    protected $type;
    protected $container;
    protected $session;
    protected $accountdata;
    protected $twig;
    protected $app;
    protected $db;
    protected $log;


    public function __construct(App $app, DBDefault $db, ContainerInterface $container, SessionInterface $session, Twig $twig, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->session = $session;
        $this->container = $container;
        $this->global = $container->get('settings.global');
        $this->db = $db;
        $this->twig = $twig;
        $this->log = $logger;

    }


    public function __invoke(Request $request, Response $response): Response {

        $post = $request->getParsedBody();
        $language = $request->getAttribute('locale');
        $accountdata = new CustomerData($this->db, $this->container, $this->session, $this->log);
        $customer_id = ($this->session->has('customer.login')) ? (int) $this->session->get('customer.id') : 0;

        $result = $accountdata->readDataCustomer($customer_id, $language);

        $response->getBody()->write($result);

        return $response;

    }

}