<?php


namespace Modules\customer\Controllers;


use Modules\customer\Classes\Customer;
use Modules\notice\Classes\Notice as SendEmailConfirm;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use PerSeo\Translator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;
use PHPMailer\PHPMailer\PHPMailer;

class CreateAdditionalMail
{
    protected $container;
    protected $session;
    protected $customer;
    protected $app;
    protected $db;
    protected $twig;
    protected $global;
    protected $mailer;
    protected $log;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, PHPMailer $mailer, DBDefault $db, Twig $twig, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->session = $session;
        $this->container =  $container;
        $this->db = $db;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->mailer = $mailer;
        $this->log = $logger;
    }

    public function __invoke(Request $request, Response $response): Response {
        $post = $request->getParsedBody();
        $emailnew = (string) (!empty($post['email']) ? $post['email'] : '');
        $customer_id = (int) $this->session->get('customer.id');

        $curtemplate = $this->global['template'];
        $curlocale = $this->global['locale'];
        $basepath = (string)$this->app->getBasePath();
        $uripath = (string)($curlocale ? $basepath . '/' . $request->getAttribute('language') : $basepath);
        /**
         * Get URL Host for sending link confirmation email address
         */
        $uriHost = [
            'uriHostLink' => 'https://' . $request->getUri()->getHost() . $uripath,
            'uriHostImg' => 'https://' . $request->getUri()->getHost() . '/templates/arcigraphic/img/logo/arcigraphic-fooder.png'
        ];
        $module = $this->container->get('settings.modules') . '/customer';
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
        $lang = new Translator($language, $module);
        $langs = $lang->get();

        $customer = new Customer($this->db, $this->container, $this->session, $this->log);
        $result = $customer->createMailAdditional($customer_id, $emailnew);

        /**
         * if success prepare data for sending order email with model NOTICE
         */
        try {
            if(json_decode($result,1)['success']) {
                $subjMail = $langs['mail']['subj_Add_Customer_confirm'];
                $data_send = json_decode($result, true);
                $sendEmail = new SendEmailConfirm($this->app, $this->container, $this->session, $this->db, $this->mailer, $this->twig, $this->log);
                $sendData = json_decode($sendEmail->sendNoticeConfirm($language, $curtemplate, $data_send, $uriHost, $subjMail), true);
                $sendData['emails'] = $data_send['emails'];
                $result = json_encode($sendData);

            }
        } catch (\Exception $e) {

            $result = json_encode(array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ));

        }

        $response->getBody()->write($result);
        return $response;
    }

}