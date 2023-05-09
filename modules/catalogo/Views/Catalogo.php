<?php

namespace Modules\catalogo\Views;

use PerSeo\Translator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class Catalogo {
    protected ContainerInterface $container;
    protected Twig $twig;
    protected $global;


    public function __construct(ContainerInterface $container, Twig $twig) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');

    }

    public function __invoke(Request $request, Response $response): Response
    {
        $module = $this->container->get('settings.modules') . '/catalogo';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Catalogo",
            'lang' => $langs['body']
        ];

        return $this->twig->render($response, $curtemplate . '/catalogo/index.twig', $viewData);
    }
}