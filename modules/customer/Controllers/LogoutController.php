<?php

namespace Modules\customer\Controllers;

use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Odan\Session\SessionInterface;
use Modules\customer\Classes\Logout;
use PerSeo\DB\DBDefault;


class LogoutController
{
	protected $type;
	protected $db;
	protected $container;
	protected $session;
	protected $customer;
	protected $app;
	protected $logout;
	protected $global;

	public function __construct(App $app,ContainerInterface $container, SessionInterface $session, DBDefault $db)
	{
		$this->session = $session;
		$this->app = $app;
		$this->logout = new Logout($db, $session);
		$this->global = $container->get('settings.global');
		$this->db = $db;
	}

	public function __invoke(Request $request, Response $response): Response
	{
        $result = $this->logout->clear();
        $curlocale = $this->global['locale'];
        $uripath = (string) ($curlocale ? $this->app->getBasePath() .'/'. $request->getAttribute('language') : $this->app->getBasePath());
        return $response->withHeader('Location', $uripath)->withStatus(303);
	}
}