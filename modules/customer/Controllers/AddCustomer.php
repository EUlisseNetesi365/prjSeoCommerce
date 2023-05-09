<?php

namespace Modules\customer\Controllers;

use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;
use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\customer\Classes\Customer;
use Slim\Views\Twig;
use PerSeo\DB\DBDefault;
use Modules\notice\Classes\Notice as SendEmailConfirm;
use PerSeo\Translator;

final class AddCustomer
{
    protected $container;
    protected $session;
    protected $customer;
    protected $db;
    protected $twig;
    protected $log;
    protected $app;
    protected $global;
    protected $mailer;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, PHPMailer $mailer, Twig $twig, LoggerInterface $logger)
    {
        $this->session = $session;
        $this->container = $container;
        $this->twig = $twig;
        $this->db = $db;
        $this->app = $app;
        $this->global = $container->get('settings.global');
        $this->mailer = $mailer;
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

        $this->customer = new Customer($this->db, $this->container, $this->session, $this->log);
        $email = (string) (!empty($post['email']) ? $post['email'] : '');
        $username = (string) (!empty($post['username']) ? $post['username'] : '');
        $password = (string) (!empty($post['password']) ? $post['password'] : '');
        $result = $this->customer->create($email, $username, $password);

        /**
         * if success prepare data for sending confirm email with model NOTICE
         */
        try {
            if (json_decode($result, 1)['success']) {
                $subjMail = $langs['mail']['subj_Add_Customer_confirm'];
                $data_send = json_decode($result, true);
                $sendEmail = new SendEmailConfirm($this->app, $this->container, $this->session, $this->db, $this->mailer, $this->twig, $this->log);
                $result = $sendEmail->sendNoticeConfirm($language, $curtemplate, $data_send, $uriHost, $subjMail);
            }
        } catch (\Exception $e) {

            $result = json_encode(
                array(
                    'success' => 0,
                    'error' => 1,
                    'code' => $e->getCode(),
                    'msg' => $e->getMessage()
                )
            );

        }

        $response->getBody()->write($result);
        return $response;
    }
}