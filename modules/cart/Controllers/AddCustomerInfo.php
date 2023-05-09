<?php

namespace Modules\cart\Controllers;

use Slim\App;
use Slim\Views\Twig;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\cart\Models\Cart as CartModel;
use Modules\notice\Classes\Notice as SendEmailOrder;
use PerSeo\Translator;
use PHPMailer\PHPMailer\PHPMailer;
use PerSeo\DB\DBDefault;
use Psr\Log\LoggerInterface;


class AddCustomerInfo
{
    protected App $app;
    protected DBDefault $db;
    protected SessionInterface $session;
    protected PHPMailer $mailer;
    protected Twig $twig;
    protected ContainerInterface $container;
    protected $global;
    protected LoggerInterface $log;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, PHPMailer $mailer, Twig $twig, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->global = $container->get('settings.global');
        $this->container = $container;
        $this->db = $db;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->session = $session;
        $this->log = $logger;

    }


    public function __invoke(Request $request, Response $response): Response
    {
        $post = $request->getParsedBody();

        $curtemplate = $this->global['template'];
        $curlocale = $this->global['locale'];
        $basepath = (string) $this->app->getBasePath();
        $uripath = (string) ($curlocale ? $basepath . '/' . $request->getAttribute('language') : $basepath);

        /**
         * Get URL Host for production
         */
        $uriHost = [
            'uriHostLink' => 'https://' . $request->getUri()->getHost() . $uripath,
            'uriHostImg' => 'https://' . $request->getUri()->getHost() . '/templates/arcigraphic/img/logo/arcigraphic-fooder.png'
        ];

        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'cart';
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
        $lang = new Translator($language, $module);
        $langs = $lang->get();

        $customer_id = $this->session->has('customer.id') ? $this->session->get('customer.id') : 0;
        $result = [];

        try {

            if (!$this->session->has('cart.id')) {
                throw new \Exception("NO_CART_ID", '001');
            }

            $customer_info = [];
            $customer_info['hidden_billing'] = isset($post['add_billing_address']) ? (int) $post['add_billing_address'] : 0;
            $customer_info['hidden_ship'] = isset($post['add_shipping_address']) ? (int) $post['add_shipping_address'] : 0;
            $customer_info['shipping_private'] = $post['shipping_is_private'];
            $customer_info['shipping_company'] = $post['shipping_company_name'];
            $customer_info['shipping_name'] = $post['shipping_name'];
            $customer_info['shipping_surname'] = $post['shipping_surname'];
            $customer_info['shipping_birthdate'] = $post['shipping_birthdate'];
            $customer_info['shipping_company_name'] = $post['shipping_company_name'];
            $customer_info['shipping_email'] = $post['shipping_email'];
            $customer_info['shipping_pec'] = $post['shipping_pec'];
            $customer_info['shipping_sdi'] = $post['shipping_code_sdi'];
            $customer_info['shipping_address'] = $post['shipping_address'];
            $customer_info['shipping_zip_code'] = $post['shipping_zip_code'];
            $customer_info['shipping_city'] = $post['shipping_city'];
            $customer_info['shipping_province'] = $post['shipping_province'];
            $customer_info['shipping_country'] = $post['shipping_country'];
            $customer_info['shipping_phone1'] = $post['shipping_phone1'];
            $customer_info['shipping_phone2'] = $post['shipping_phone2'];
            $customer_info['shipping_vat_number'] = $post['shipping_vat_number'];
            $customer_info['shipping_fiscal_code'] = $post['shipping_fiscal_code'];
            $customer_info['shipping_is_default'] = $post['shipping_is_default'];
            $customer_info['shipping_note'] = $post['shipping_note'];
            $customer_info['billing_private'] = $post['billing_is_private'];
            $customer_info['billing_name'] = $post['billing_name'];
            $customer_info['billing_surname'] = $post['billing_surname'];
            $customer_info['billing_birthdate'] = $post['billing_birthdate'];
            $customer_info['billing_company_name'] = $post['billing_company_name'];
            $customer_info['billing_email'] = $post['billing_email'];
            $customer_info['billing_pec'] = $post['billing_pec'];
            $customer_info['billing_sdi'] = $post['billing_code_sdi'];
            $customer_info['billing_address'] = $post['billing_address'];
            $customer_info['billing_zip_code'] = $post['billing_zip_code'];
            $customer_info['billing_city'] = $post['billing_city'];
            $customer_info['billing_province'] = $post['billing_province'];
            $customer_info['billing_country'] = $post['billing_country'];
            $customer_info['billing_phone1'] = $post['billing_phone1'];
            $customer_info['billing_phone2'] = $post['billing_phone2'];
            $customer_info['billing_vat_number'] = $post['billing_vat_number'];
            $customer_info['billing_fiscal_code'] = $post['billing_fiscal_code'];
            $customer_info['billing_is_default'] = $post['billing_is_default'];
            $customer_data_info = json_encode($customer_info);

            /**
             * CheckOut add data customer items, and address if anonymous
             */
            $cart_data = new CartModel($this->db, $this->container, $this->session, $this->log);
            $cart_result = $cart_data->AddCustomer($customer_data_info);

            /**
             * if success prepare data for sending order email with model NOTICE
             */
            if (json_decode($cart_result, 1)['success']) {
                $cart_send = $cart_data->getOrderDataEmail($language, $uriHost, $customer_data_info);
                /**
                 * if success send email with model NOTICE
                 */

                if (json_decode($cart_send, 1)['success']) {
                    $subjMail = $langs['mail']['subj_Customer_order'];
                    $data_send = json_decode($cart_send, true);
                    $sendEmail = new SendEmailOrder($this->app, $this->container, $this->session, $this->db, $this->mailer, $this->twig, $this->log);
                    $result = $sendEmail->sendNoticeOrder($language, $curtemplate, $data_send, $data_send['email'], $subjMail);
                }
                /**
                 * if success update status of cart to waiting on db
                 */

                if (json_decode($result, 1)['success']) {
                    $result = $cart_data->stepCartStatus('waiting');
                }
            } else {
                throw new \Exception("ERROR_DATA", '001');
            }

        } catch (\Exception $e) {
            $this->log->error('CART -> AddCustomerInfo (controller) -> (code) / (message) -> ' . $e->getCode() . ' / ' . $e->getMessage());
            json_encode($result = [
                'success' => 0,
                'error' => 1,
                'code' => (int) $e->getCode(),
                'msg' => $e->getMessage()
            ]);
        }

        $response->getBody()->write($result);
        return $response;
    }
}