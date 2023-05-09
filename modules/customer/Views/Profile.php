<?php

namespace Modules\customer\Views;

use PerSeo\Helpers\Countries;
use Phpfastcache\Helper\Psr16Adapter;
use PerSeo\DB\DBDefault;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;

class Profile {
	protected $container;
    protected $twig;
    protected $global;
    protected $db;
    protected $cache;

    public function __construct(ContainerInterface $container, DBDefault $db, Twig $twig, Psr16Adapter $cache) {
		$this->container = $container;
        $this->twig = $twig;
		$this->global = $container->get('settings.global');
		$this->db = $db;
		$this->cache = $cache;
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/customer';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Profilo utente",
            'lang' => $langs['body'],
        ];

		$helpers = new Countries($this->db, $this->container, $this->cache);
        $countriesList = $helpers->get($language);

        $viewData = [ ...$viewData,
            'countries' => $countriesList,
            'filter' => false,
            'profile_js' => true,
        ];
        $viewData['categories_menu'] = json_decode($request->getAttribute('categories_menu'), true);

        return $this->twig->render($response, $curtemplate . '/customer/profile.twig', $viewData);
    }
}