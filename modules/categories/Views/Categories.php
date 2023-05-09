<?php

namespace Modules\categories\Views;

use Modules\settings\Settings;
use PerSeo\Helpers\UtilityFunctions;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;
use PerSeo\Translator;
use Modules\categories\Classes\Category;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Modules\products\Classes\VariantList;

class Categories {

    protected ContainerInterface $container;
    protected Twig $twig;
    protected $global;
    protected DBDefault $db;
	protected Psr16Adapter $cache;
	protected $cache_settings;
	protected LoggerInterface $log;

    public function __construct(ContainerInterface $container, Twig $twig, DBDefault $db, Psr16Adapter $cache, LoggerInterface $logger) {
		$this->container = $container;
        $this->twig = $twig;
        $this->global = $container->get('settings.global');
		$this->db = $db;
		$this->cache = $cache;
		$this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
		$this->cache_settings = (bool) (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
		$this->log = $logger;
    }

    public function __invoke(Request $request, Response $response, $params): Response {

        $module = $this->container->get('settings.modules') . '/categories';
        $language = $request->getAttribute('locale');
        $curtemplate = $this->global['template'];
        $langs = (new Translator($language, $module))->get();
        $breadCrumbs = [];
        $filter = true;
        $mmin = 0.0;
        $mmax = 0.0;
        $page_items = null;

        $viewData = [
            'title' => "Categoria",
            'lang' => $langs['body'],
        ];
        
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $settings = $this->container->get(Settings::class);
        $tot_items = 0;
        $variantList = new VariantList($this->db, $this->cache, $this->cache_settings, $language, 'it', $this->log);
        $filterPaging = json_decode($variantList->getCategoryFilterPaging($id));

        if (!$filterPaging) { $filter = false; }

        if ($filterPaging) {
            $mmin = (float) $filterPaging->mmin;
            $mmax = (float) $filterPaging->mmax;
            $setting = json_decode($settings->get('page_items'));
            $page_items = (int) $setting->page_items[0]->set;
        }
        $secattList = $variantList->readAttributeList4Filters($id, $language);
        $categories = new Category($this->db, $this->cache, $this->cache_settings, $language);
        $uniqueAttrs = $categories->createUniqueAttr(json_decode($secattList, true));
        $utilView = new UtilityFunctions($this->db, $this->cache, $this->cache_settings, $this->log);
        $catAttr = $utilView->loadCattAttrValue($id, $language);

        if ($id > 0) {

            $breadCrumbs = $categories->getCategoryBreadcrumb('', $id);
        }

        $viewData = [ ...$viewData,
            'products_script' => true,
            'filter' => $filter,
            'cat_id' => $id,
            'cat_attr_values' => ($catAttr) ? $catAttr : null,
            'sec_attr_values' => ($secattList) ? $secattList : null,
            'list_attr' => $uniqueAttrs,
            'min_price' => $mmin,
            'max_price' => $mmax,
            'page_items' => $page_items,
            'settings_filter' => $settings->get('filter'),
            'category_breadcrumbs' => $breadCrumbs
        ];

        return $this->twig->render($response, $curtemplate.'/categories/categories.twig', $viewData);
    }
}