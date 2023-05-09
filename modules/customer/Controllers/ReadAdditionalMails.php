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

class ReadAdditionalMails
{
    protected $type;
    protected $container;
    protected $session;
    protected $app;
    protected $db;
    protected $twig;
    protected $customer;
    protected $log;

    public function __construct(App $app, DBDefault $db, ContainerInterface $container, SessionInterface $session, Twig $twig, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->container = $container;
        $this->app = $app;
        $this->db = $db;
        $this->twig = $twig;
        $this->log = $logger;
        $this->customer = new Customer($db, $container, $session, $this->log);
    }


    public function __invoke(Request $request, Response $response): Response
    {

        $post = $request->getParsedBody();
        $customer_id = ($this->session->has('customer.login')) ? (int) $this->session->get('customer.id') : 0;
        $result = $this->customer->readEmailsAdditional($customer_id);
        $response->getBody()->write($result);

        return $response;
    }

}