<?php

namespace Modules\products\Views;

use Modules\categories\Classes\Category;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Modules\products\Classes\VariantList;


class Product {
    protected $container;
    protected $twig;
    protected $global;
    protected $db;
    protected $log;
    protected $cache;
    protected $cache_settings;

    public function __construct(ContainerInterface $container, Twig $twig, DBDefault $db, LoggerInterface $logger, Psr16Adapter $cache) {
        $this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
        $this->db = $db;
        $this->log = $logger;
        $this->cache = $cache;
        $this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $this->cache_settings = (bool) (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
    }

    public function __invoke(Request $request, Response $response, $params): Response {
        $module = $this->container->get('settings.modules') . '/products';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();

        $viewData = [
            'title' => "Prodotto",
            'lang' => $langs['body'],
        ];
        
        $id = (int) $params['id'];
        $variantSingle = new VariantList($this->db, $this->cache, $this->cache_settings, $language, 'it', $this->log);
        $singleprod = $variantSingle->readSingle($id, 'json');
        $categories = new Category($this->db, $this->cache, $this->cache_settings, $language);

        if(json_decode($singleprod,1)['success']) {
            $dataout = json_decode($singleprod, true);
            $attributelist = $variantSingle->readAttributeList($dataout['data'][0]['s_id']);
            $catdArr = json_decode($dataout['data'][0]['category_attributes_ids'], true);
            $breadCrumbs = $categories->getCategoryBreadcrumb($catdArr);
        } else {
            $attributelist = '{}';
        }

        $viewData = [ ...$viewData,
            'attributes' => $attributelist,
            'singleprod' => $singleprod,
            'product_script' => true,
            'product_name' => (isset($prodArr[0]['product_name']) ? $prodArr[0]['product_name'] : ''),
            'category_breadcrumbs' => $breadCrumbs
        ];

        return $this->twig->render($response, $curtemplate . '/products/product.twig', $viewData);

    }
}