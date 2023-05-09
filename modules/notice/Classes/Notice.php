<?php


namespace Modules\notice\Classes;

use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use PerSeo\Translator;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;

/**
 * Class Notice
 * Class for handle sending Emails
 *
 * Code Error start from 90
 *
 * @package Modules\notice\Models
 */

class Notice
{
    protected $app;
    protected $db;
    protected $session;
    protected $mailer;
    protected $mailSettings;
    protected $twig;
    protected $container;
    protected $global;
    protected $template;
    protected $log;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, PHPMailer $mailer, Twig $twig, LoggerInterface $logger)
    {

        $this->app = $app;
        $this->container = $container;
        $this->db = $db;
        $this->twig = $twig;
        $this->session = $session;
        $this->mailer = $mailer;
        $this->global = $container->get('settings.global');
        $this->mailSettings = $container->get('settings.mailer');
        $this->log = $logger;
    }

    /**
     * @param $language
     * @param $curTemplate
     * @param $data4Twig
     * @param $email
     * @return false|string
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * send email with orderdata and customer address logged or anonymous
     */
    public function sendNoticeOrder($language, $curTemplate, $data4Twig, $email, $subjMail)
    {
        $customer_id = (int) $this->session->get('customer.id') ?? 0;
        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'notice';
        $lang = new Translator($language, $module);
        $langs = $lang->get();
        $data4Twig['lang'] = $langs['body'];
        $data4Twig['mailsupport'] = $this->mailSettings['default']['mailfrom'];

        $this->template = $this->twig->fetch($curTemplate . '/notice/confirm_check_out.html.twig', $data4Twig);


        try {
            if (!$email)
                throw new \Exception("NO_MAIL_DEFINED", '091');

            $this->mailer->isHTML(true);
            $this->mailer->setFrom($this->mailSettings['default']['mailfrom']);
//            $this->mailer->setFrom($this->mailSettings['default']['mailfromNetesi']);
            $this->mailer->addAddress($email);
            //$this->mailer->addAddress($this->mailSettings['default']['mailto']);
//            $this->mailer->addAddress($this->mailSettings['default']['mailtoNetesi']);
            $this->mailer->Subject = $subjMail;
            $this->mailer->Body = $this->template;

            /**
             * PER DEBUG ONLY
             */
            // file_put_contents(__DIR__.'/../../../logs/mail.txt', $bodytemplate);

            if (!$this->mailer->send()) {
                throw new \Exception("NOT_MAIL_SEND", '090');
            }

            $result = array(
                'success' => 1,
                'error' => 0,
                'code' => '',
                'email_to' => $email,
                'msg' => 'OK'
            );
        } catch (\Exception $e) {
            $this->log->error('NOTICE-> Send Order -> (errcode) / (errmessage) / (mailreg) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $email);
            $this->log->error('NOTICE-> Send Order -> (OrderData) -> ' . print_r($data4Twig, true));
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => (int) $e->getCode(),
                'msg' => $e->getMessage(),
            );
        }
        return json_encode($result);
    }


    /**
     * @param $language
     * @param $curtemplate
     * @param $datasend
     * @param $uriHost (link with locale and img without locale)
     * @return array
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * send email confirmation
     */

    public function sendNoticeConfirm($language, $curtemplate, $datasend, $urihost, $subjMail)
    {

        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'notice';
        $lang = new Translator($language, $module);
        $langs = $lang->get();

        $bodymail = [
            'name' => $datasend['username'],
            'email' => $datasend['email'],
            'code' => $datasend['confirm'],
            'lang' => $langs['body'],
            'uriHostLink' => $urihost['uriHostLink'],
            'uriHostImg' => $urihost['uriHostImg']
        ];

        $this->template = $this->twig->fetch($curtemplate . '/notice/email_confirmation.html.twig', $bodymail);

        try {
            if (!$datasend['email'])
                throw new \Exception("NO_MAIL_DEFINED", '091');

            $this->mailer->isHTML(true);
            $this->mailer->setFrom($this->mailSettings['default']['mailfrom']);
//            $this->mailer->setFrom($this->mailSettings['default']['mailfromNetesi']);
            $this->mailer->addAddress($datasend['email']);
            $this->mailer->Subject = $subjMail;

            $this->mailer->Body = $this->template;

            if (!$this->mailer->send()) {
                throw new \Exception("NOT_SEND_MAIL", '090');
            }

            /**
             * PER DEBUG ONLY
             */
            file_put_contents(__DIR__ . '/../../../logs/mail.txt', $this->template);

            $result = [
                'success' => 1,
                'error' => 0,
                'code' => 0,
                'msg' => 'OK',
                'email' => $datasend['email']
            ];

        } catch (\Exception $e) {
            $this->log->error('NOTICE-> Send Confirm Mail -> (errcode) / (errmessage) / (mailreg) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $datasend['email']);
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => (int) $e->getCode(),
                'msg' => $e->getMessage(),
                'email' => $datasend['email']
            ];
        }

        return json_encode($result);
    }

    public function sendNoticeRequestInfo($language, $curtemplate, $bodyTemplate, $email, $subjMail)
    {

        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'notice';
        $lang = new Translator($language, $module);
        $langs = $lang->get();
        $bodyTemplate['lang'] = $langs['body'];

        $this->template = $this->twig->fetch($curtemplate . '/notice/request_info.html.twig', $bodyTemplate);

        try {
            if (!$email)
                throw new \Exception("NO_MAIL_DEFINED", '091');

            $this->mailer->isHTML(true);
            $this->mailer->setFrom($this->mailSettings['default']['mailfrom']);
            //$this->mailer->setFrom($this->mailSettings['default']['infofromNetesi']);
            //$this->mailer->setFrom($this->mailSettings['default']['mailfrom']);
            $this->mailer->addAddress($email);
            //$this->mailer->addAddress($this->mailSettings['default']['infoto']);
            $this->mailer->Subject = $subjMail;
            $this->mailer->Body = $this->template;

            /**
             * PER DEBUG ONLY
             */
            // file_put_contents(__DIR__.'/../../../logs/mail.txt', $bodytemplate);

            if (!$this->mailer->send())
                throw new \Exception("NOT_SEND_MAIL", '090');

            $result = array(
                'success' => 1,
                'error' => 0,
                'code' => 0,
                'email_to' => $email,
                'msg' => 'OK'
            );
        } catch (\Exception $e) {
            $this->log->error('NOTICE-> Send Request Info -> (errcode) / (errmessage) / (mailreq) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $email);
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => (int) $e->getCode(),
                'msg' => $e->getMessage(),
            );
        }
        return json_encode($result);
    }

    public function sendNoticeRecover($language, $curtemplate, $datasend, $urihost, $subjMail)
    {

        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'notice';
        $lang = new Translator($language, $module);
        $langs = $lang->get();

        $bodymail = [
            'name' => $datasend['username'],
            'email' => $datasend['email'],
            'code' => $datasend['confirm'],
            'lang' => $langs['body'],
            'uriHostLink' => $urihost['uriHostLink'],
            'uriHostImg' => $urihost['uriHostImg']
        ];

        $this->template = $this->twig->fetch($curtemplate . '/notice/email_recover_password.html.twig', $bodymail);

        try {
            if (!$datasend['email'])
                throw new \Exception("NO_MAIL_DEFINED", '091');

            $this->mailer->isHTML(true);
            $this->mailer->setFrom($this->mailSettings['default']['mailfrom']);
            //$this->mailer->setFrom($this->mailSettings['default']['mailfromNetesi']);
            $this->mailer->addAddress($datasend['email']);
            $this->mailer->Subject = $subjMail;

            $this->mailer->Body = $this->template;

            if (!$this->mailer->send()) {
                throw new \Exception("NOT_SEND_MAIL", '090');
            }

            /**
             * PER DEBUG ONLY
             */
            //file_put_contents(__DIR__ . '/../../../logs/mail.txt', $this->template);

            $result = [
                'success' => 1,
                'error' => 0,
                'code' => 0,
                'msg' => 'OK'
            ];

        } catch (\Exception $e) {
            $this->log->error('NOTICE-> Send Recover Password -> (errcode) / (errmessage) / (mailreq) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $datasend['email']);
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => (int) $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        return json_encode($result);
    }

    /**
     * NEWSLETTER
     */

    public function sendNoticeNews($language, $curtemplate, $datasend, $urihost, $subjMail)
    {

        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'notice';
        $lang = new Translator($language, $module);
        $langs = $lang->get();

        $bodymail = [
            'name' => $datasend['username'],
            'email' => $datasend['email'],
            'code' => $datasend['confirm'],
            'lang' => $langs['body'],
            'uriHostLink' => $urihost['uriHostLink'],
            'uriHostImg' => $urihost['uriHostImg']
        ];

        $this->template = $this->twig->fetch($curtemplate . '/notice/news_confirmation.html.twig', $bodymail);

        try {
            if (!$datasend['email'])
                throw new \Exception("NO_MAIL_DEFINED", '091');

            $this->mailer->isHTML(true);
            $this->mailer->setFrom($this->mailSettings['default']['mailfrom']);
            //$this->mailer->setFrom($this->mailSettings['default']['mailfromNetesi']);
            $this->mailer->addAddress($datasend['email']);
            $this->mailer->Subject = $subjMail;
            $this->mailer->Body = $this->template;

            if (!$this->mailer->send()) {
                throw new \Exception("NOT_SEND_MAIL", '090');
            }

            /**
             * PER DEBUG ONLY
             */
            //file_put_contents(__DIR__ . '/../../../logs/mail.txt', $this->template);

            $result = [
                'success' => 1,
                'error' => 0,
                'code' => 0,
                'msg' => 'OK'
            ];

        } catch (\Exception $e) {
            $this->log->error('NOTICE-> Send Newsletter Confirm -> (errcode) / (errmessage) / (mailreq) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $datasend['email']);
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => (int) $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }

        return json_encode($result);
    }

}