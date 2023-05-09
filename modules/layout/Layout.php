<?php

namespace Modules\layout;

use Slim\App;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;

class Layout
{
	protected $app;
	protected $container;
    protected $twig;
    protected $global;


    public function __construct(App $app, ContainerInterface $container, Twig $twig)
    {
		$this->app = $app;
		$this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
		
    }

    public function __invoke(Request $request, Response $response): Response {
        $curtemplate = $this->global['template'];
        $curlocale = $this->global['locale'];
        $module = $this->container->get('settings.modules') .'/index';
        $language = (!empty($request->getAttribute('locale')) ? $request->getAttribute('locale') : $request->getAttribute('language'));
		$lang = new Translator($language, $module);
		$langs = $lang->get();
        $loginame = ($this->session->has('customer.login')) ? $this->session->get('customer.user') : null;

        $viewData = [
			'basepath' => (string) $this->app->getBasePath(),
            'uripath' => (string) ($curlocale ? $this->app->getBasePath() .'/'. $request->getAttribute('language') : $this->app->getBasePath()),
            'language' => $language,
			'lang' => $langs['body'],
            'template' => $curtemplate,
            'sitename' => $this->global['sitename'],
            'username' => $loginame,
            
        ];
        return $this->twig->render($response, $curtemplate.'/layout/layout.twig', $viewData);
    }
}