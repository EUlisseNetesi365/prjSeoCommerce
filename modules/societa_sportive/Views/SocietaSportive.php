<?php

namespace Modules\societa_sportive\Views;

use PerSeo\Translator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class SocietaSportive {
    protected $container;
    protected $twig;
    protected $global;
    protected $mailerSettings;

    public function __construct(ContainerInterface $container, Twig $twig) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->mailerSettings = $container->get('settings.mailer');
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/societa_sportive';
		$language = $request->getAttribute('locale');
		$curtemplate = $this->global['template'];
		$langs = (new Translator($language, $module))->get();

		$viewData = [
            'title' => "Società sportive",
            'lang' => $langs['body'],
            'infomail' => $this->mailerSettings['default']['infofrom'],
        ];

        return $this->twig->render($response, $curtemplate . '/societa_sportive/index.twig', $viewData);
    }
}