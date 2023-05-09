<?php


namespace Modules\societa_sportive\Controllers;



use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Views\Twig;
use Modules\notice\Classes\Notice as SendEmailReqInfo;


class SendRequestInfo
{
    protected $session;
    protected $db;
    protected $mailer;
    protected $twig;
    protected $container;
    protected $global;
    protected $template;
    protected $app;


    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, PHPMailer $mailer, Twig $twig)
    {
        $this->app = $app;
        $this->container = $container;
        $this->session = $session;
        $this->db =  $db;
        $this->mailer =  $mailer;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
    }


    public function __invoke(Request $request, Response $response): Response {
        $post = $request->getParsedBody();
        $post = $request->getParsedBody();
        $curtemplate = $this->global['template'];
        $curlocale = $this->global['locale'];
        $module = $this->container->get('settings.modules') . DIRECTORY_SEPARATOR . 'contact_us';
        $basepath = (string)$this->app->getBasePath();
        $uripath = (string)($curlocale ? $basepath . '/' . $request->getAttribute('language') : $basepath);
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
        /**
         * Get URL Host for sending link confirmation email address
         */
        $uriHost = [
            'uriHostLink' => 'https://' . $request->getUri()->getHost() . $uripath,
            'uriHostImg' => 'https://' . $request->getUri()->getHost() . '/templates/arcigraphic/img/logo/arcigraphic-fooder.png'
        ];

        $name = (!empty($post['name']) ? $post['name'] : '');
        $surname = (!empty($post['surname']) ? $post['surname'] : '');
        $email = (!empty($post['email']) ? $post['email'] : '');
        $note = (!empty($post['note']) ? $post['note'] : '');

        if ($email) {
            $dataInfo = [
                    'name' => $name,
                    'surname' => $surname,
                    'email' => $email,
                    'note' => $note,
                    'uriHostLink' => $uriHost['uriHostLink'],
                    'uriHostImg' => $uriHost['uriHostImg'],
            ];
            $mailInfo = new SendEmailReqInfo($this->app, $this->container, $this->session, $this->db, $this->mailer, $this->twig);
            $result = $mailInfo->sendNoticeRequestInfo($language, $curtemplate, $dataInfo, $email);
        }
        else {
            $result = json_encode(array(
                'success' => 0,
                'error' => 1,
                'data' => NULL,
                'code' => 001,
                'msg' => 'INVALID_REQUEST'
            ));
        }
        $response->getBody()->write($result);
        return $response;

    }


}