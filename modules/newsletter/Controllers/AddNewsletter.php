<?php

namespace Modules\newsletter\Controllers;

use Modules\notice\Classes\Notice as SendNewsConfirm;
use Odan\Session\SessionInterface;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;
use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;
use Modules\newsletter\Classes\Newsletter;
use PerSeo\DB\DBDefault;

class AddNewsletter
{
    protected $app;
    protected $container;
    protected $session;
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
        $email = (string) (!empty($post['email']) ? $post['email'] : '');
        $loginame = ($this->session->has('customer.login')) ? $this->session->get('customer.user') : null;

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
        $module = $this->container->get('settings.modules') . '/newsletter';
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
        $lang = new Translator($language, $module);
        $langs = $lang->get();

        $nletter = new Newsletter($this->db, $this->container, $this->session, $this->log);
        $result = $nletter->createMailNewsletter($email);

        /**
         * if success prepare data for sending confirm email with model NOTICE
         */
        try {
            if(json_decode($result,1)['success']) {
                $subjMail = $langs['mail']['subj_News_confirm'];
                $data_send = json_decode($result, true);
                $sendEmail = new SendNewsConfirm($this->app, $this->container, $this->session, $this->db, $this->mailer, $this->twig, $this->log);
                $result = $sendEmail->sendNoticeNews($language, $curtemplate, $data_send, $uriHost, $subjMail);

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