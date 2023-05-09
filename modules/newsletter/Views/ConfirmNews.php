<?php

namespace Modules\newsletter\Views;

use Odan\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;
use Modules\newsletter\Classes\Newsletter;
use PerSeo\DB\DBDefault;


class ConfirmNews {

	protected $container;
	protected $twig;
	protected $global;
	protected $db;
	protected $session;
	protected $log;

	public function __construct(ContainerInterface $container, SessionInterface $session, Twig $twig, DBDefault $db, LoggerInterface $logger) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->db = $db;
        $this->session = $session;
        $this->log = $logger;

	}

	public function __invoke(Request $request, Response $response, $params): Response {
		$module = $this->container->get('settings.modules') . '/newsletter';
		$language = $request->getAttribute('locale');
		$curtemplate = $this->global['template'];
		$langs = (new Translator($language, $module))->get();

		$viewData = [
			'title' => "Conferma email newsletter",
			'lang' => $langs['body'],
		];

        $code = (!empty($params['code']) ? $params['code'] : '');

		$newsletter = new Newsletter($this->db, $this->container, $this->session, $this->log);
		$result = $newsletter->newsConfirm($code);

		$viewData = [ ...$viewData,
			'filter' => false,
			'result' => $result,
		];

		if ($result) return $this->twig->render($response, $curtemplate. '/newsletter/newsconfirm.twig', $viewData);
		else return $this->twig->render($response, $curtemplate. '/newsletter/newsnoconfirm.twig', $viewData);
	}
}