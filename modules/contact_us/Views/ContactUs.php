<?php

namespace Modules\contact_us\Views;

use PerSeo\Translator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ContactUs {

    protected ContainerInterface $container;
    protected Twig $twig;
    protected $global;
    protected $mailerSettings;

    public function __construct(ContainerInterface $container, Twig $twig) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->mailerSettings = $container->get('settings.mailer');
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/contact_us';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Contattaci",
            'lang' => $langs['body'],
            'infomail' => $this->mailerSettings['default']['infofrom'],
            'contact_us_js' => true
        ];
        return $this->twig->render($response, $curtemplate.'/contact_us/contact_us.twig', $viewData);
    }
}