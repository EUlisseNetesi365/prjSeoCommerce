<?php


namespace Modules\customer\Controllers;

use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;

class CustomerLoggedAddress
{
    protected $type;
    protected $container;
    protected $session;
    protected $db;
    protected $global;
    protected $app;
    protected $log;
    protected $address;
    protected $twig;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, Twig $twig, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->container = $container;
        $this->db = $db;
        $this->global = $container->get('settings.global');
        $this->app = $app;
        $this->log = $logger;
        $this->twig = $twig;

    }

    public function __invoke(Request $request, Response $response): Response {
        $post = $request->getParsedBody();
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));

        $def_shipping_id = (int) (!empty($post['default_shipping']) ? $post['default_shipping'] : 0);
        $def_invoice_id= (int) (!empty($post['default_billing']) ? $post['default_billing'] : 0);
        $id_addr = (int) (!empty($post['id']) ? $post['id'] : 0);

        $customer_id = (int) $this->session->get('customer.id') ?? '';
        $result = [];

        //$this->log->info('address id Ship / Bill ---->>>>> ' . $id_addr . ' / ' . $def_shipping_id);

        if(!$customer_id) {
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => '001',
                'msg' => 'LOGGED_ERROR'
            );
        } else {
            if ($def_shipping_id == 0) {
                $result = $this->verify($customer_id, $id_addr);
                if(json_decode($result,1)['success']){
                    $this->session->set('customer.default_ship_id', (int)$id_addr);
                }
            } elseif ($def_invoice_id == 0) {
                $result = $this->verify($customer_id, $id_addr);
                if(json_decode($result,1)['success']){
                    $this->session->set('customer.default_invoice_id', (int)$id_addr);
                }
             }

        }

        $response->getBody()->write($result);
        return $response;

    }


    private function verify($custId, $addrId) {

        $db = $this->db;
        $ret = 0;

        try {
            /**
             * check if addressid belongs to customer_id
             */
            $dbaddr = $db->select("customer_data","*", [
                                 'id' => $addrId,
                                 'customer_id' => $custId
            ]);
            //$this->log->info('verify ---->>>>> ' . print_r($dbaddr, true));
            $ret = (int)(!empty($dbaddr) ? 1 : 0);

             if ($ret == 1) {
                 $result = [
                     'success' => 1,
                     'error' => 0,
                     'code' => 0,
                     'msg' => "OK"
                 ];
             } else {
                 $result = [
                     'success' => 0,
                     'error' => 1,
                     'code' => '001',
                     'msg' => "ADDRESS_INVALID"
                 ];
             }

        } catch (\Exception $e) {
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        return json_encode($result);

    }

}