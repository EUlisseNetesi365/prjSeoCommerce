<?php

namespace Modules\about_us\Views;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;

class About {
	protected ContainerInterface $container;
    protected Twig $twig;
    protected $global;

    public function __construct(ContainerInterface $container, Twig $twig) {
		$this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/about_us';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "About us",
            'lang' => $langs['body'],
        ];

        return $this->twig->render($response, $curtemplate.'/about_us/about_us.twig', $viewData);
    }
}