<?php

namespace Modules\privacy_policy\Views;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;

class Privacy {

    protected $container;
    protected $twig;
    protected $global;

    public function __construct(ContainerInterface $container, Twig $twig) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/privacy_policy';
		$language = $request->getAttribute('locale');
		$curtemplate = $this->global['template'];
		$langs = (new Translator($language, $module))->get();

		$viewData = [
            'title' => "Privacy policy",
            'lang' => $langs['body'],
		];

        return $this->twig->render($response, $curtemplate . '/privacy_policy/index.twig', $viewData);
    }
}