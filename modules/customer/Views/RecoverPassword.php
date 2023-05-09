<?php

namespace Modules\customer\Views;

use Odan\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;
use Modules\customer\Classes\Customer;
use PerSeo\DB\DBDefault;


class RecoverPassword {
    protected $container;
    protected $twig;
    protected $global;
    protected $db;
    protected $customer;
    protected $session;
    protected $log;


    public function __construct(ContainerInterface $container, SessionInterface $session, Twig $twig, DBDefault $db, LoggerInterface $logger) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->db = $db;
        $this->session = $session;
        $this->log = $logger;
        $this->customer = new Customer($db, $container, $session, $this->log);
    }

    public function __invoke(Request $request, Response $response, $params): Response {
        $module = $this->container->get('settings.modules') . '/customer';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Reset password",
            'lang' => $langs['body'],
        ];

        $code = (!empty($params['code']) ? $params['code'] : '');

        $viewData = [ ...$viewData,
            'username' => '',
            'recover_js' => true,
            'filter' => false,
            'confirm' => $code,
        ];

        return $this->twig->render($response, $curtemplate . '/customer/recover-password.twig', $viewData);

    }
}