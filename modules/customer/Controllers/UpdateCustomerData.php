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

class UpdateCustomerData
{
    protected $type;
    protected $container;
    protected $session;
    protected $args;
    protected $app;
    protected $company_private = 1;
    protected $db;
    protected $twig;
    protected $log;

    public function __construct(App $app, DBDefault $db, ContainerInterface $container, SessionInterface $session, Twig $twig, LoggerInterface $log)
    {
        $this->session = $session;
        $this->container = $container;
        $this->app = $app;
        $this->db = $db;
        $this->twig = $twig;
        $this->log = $log;
    }


    public function __invoke(Request $request, Response $response): Response {

        $post = $request->getParsedBody();
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
        $custId = $this->session->get('customer.id');

        $accountdata = new CustomerData($this->db, $this->container, $this->session, $this->log);

        $dataId = (int) $post['data_id'];
        $name = (!empty($post['name']) ? $post['name'] : '');
        $surname = (!empty($post['surname']) ? $post['surname'] : '');
        $birth = (!empty($post['birthdate']) ? $post['birthdate'] : '');
        $company = (!empty($post['company_name']) ? $post['company_name'] : '');
        $countryId = (!empty($post['country']) ? $post['country'] : 0);
        $province = (!empty($post['province']) ? $post['province'] : '');
        $city = (!empty($post['city']) ? $post['city'] : '');
        $zip_code = (!empty($post['zip_code']) ? $post['zip_code'] : '');
        $address = (!empty($post['address']) ? $post['address'] : '');
        $pec = (!empty($post['pec']) ? $post['pec'] : '');
        $sdi = (!empty($post['sdi']) ? $post['sdi'] : '');
        $gps = (!empty($post['gps']) ? $post['gps'] : '');
        $ph1 = (!empty($post['phone1']) ? $post['phone1'] : '');
        $ph2 = (!empty($post['phone2']) ? $post['phone2'] : '');
        $vat_num = (!empty($post['vat_number']) ? $post['vat_number'] : '');
        $fiscal_code = (!empty($post['fiscal_code']) ? $post['fiscal_code'] : '');
        $note = (!empty($post['note']) ? $post['note'] : '');
        $ship_def = isset($post['shipping_is_default']) ? (int) $post['shipping_is_default'] : 0;
        $invoice_def = isset($post['billing_is_default']) ? (int) $post['billing_is_default'] : 0;
        $private = (int) (!empty($post['is_private']) ? $post['is_private'] : 0);

        if ($private == 0) {
            $this->company_private = 0;
        }

        $result = $accountdata->updateDataCustomer( $custId,
            $dataId,
            $name,
            $surname,
            $birth,
            $company,
            $countryId,
            $province,
            $city,
            $zip_code,
            $address,
            $pec,
            $sdi,
            $gps,
            $ph1,
            $ph2,
            $vat_num,
            $fiscal_code,
            $note,
            $ship_def,
            $invoice_def,
            $this->company_private
        );

        $response->getBody()->write($result);
        return $response;

    }

}