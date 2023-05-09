<?php

namespace Modules\cart\Views;

use Odan\Session\SessionInterface;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PerSeo\Translator;
use PerSeo\Helpers\Countries;
use PerSeo\DB\DBDefault;
use Modules\cart\Models\Cart;


class CartCheckout {
	protected ContainerInterface $container;
    protected Twig $twig;
    protected $global;
    protected DBDefault $db;
    protected Psr16Adapter $cache;
    protected SessionInterface $session;
    protected LoggerInterface $log;

    public function __construct(ContainerInterface $container, Twig $twig, DBDefault $db, SessionInterface $session, Psr16Adapter $cache, LoggerInterface $logger) {
		$this->container = $container;
        $this->twig = $twig;
		$this->global = $container->get('settings.global');
		$this->db = $db;
		$this->cache = $cache;
		$this->session = $session;
		$this->log = $logger;
    }

    public function __invoke(Request $request, Response $response): Response {
        $module = $this->container->get('settings.modules') . '/cart';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Carrello",
            'lang' => $langs['body'],
        ];

		$helpers = new Countries($this->db, $this->container, $this->cache);
		$countriesList = $helpers->get($language);
		$cart = new Cart($this->db, $this->container, $this->session, $this->log);
		$cart_items = $cart->view($language);

        $viewData = [ ...$viewData,
            'cartItems' => $cart_items,
            'countries' => $countriesList,
            'cart_script' => true,
            'is_logged' => $this->session->has('customer.id') ? 1 : 0,
        ];
        return $this->twig->render($response, $curtemplate . '/cart/cart.twig', $viewData);
    }
}