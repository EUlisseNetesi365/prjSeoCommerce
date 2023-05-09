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

class DeleteCustomerData
{
    protected $type;
    protected $container;
    protected $session;
    protected $args;
    protected $app;
    protected $log;
    protected $company_private = 1;
    protected $db;
    protected $twig;

    public function __construct(App $app, DBDefault $db, ContainerInterface $container, SessionInterface $session, Twig $twig, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->container = $container;
        $this->app = $app;
        $this->db = $db;
        $this->twig = $twig;
        $this->log = $logger;
    }


    public function __invoke(Request $request, Response $response): Response {

        $post = $request->getParsedBody();
        $accountdata = new CustomerData($this->db, $this->container, $this->session, $this->log);
        $custId = $this->session->get('customer.id');

        $custDataId = (int)(!empty($post['data_id']) ? $post['data_id'] : '');

        $result = $accountdata->deleteDataCustomer($custId, $custDataId);

        $response->getBody()->write($result);
        return $response;

    }

}