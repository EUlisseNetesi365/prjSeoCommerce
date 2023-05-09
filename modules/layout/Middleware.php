<?php

namespace Modules\layout;

use Modules\categories\Classes\Category;
use Modules\settings\Settings;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Slim\App;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Views\Twig;
use PerSeo\Translator;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;

final class Middleware implements MiddlewareInterface {
    protected $app;
    protected $twig;
    protected $settings;
    protected $global;
    protected $session;
    protected $db;
    protected $cache;
    protected $cache_settings;
    protected $dbsettings;

    public function __construct(App $app, Twig $twig, ContainerInterface $container, DBDefault $db, Psr16Adapter $cache, SessionInterface $session)
    {
        $this->app = $app;
        $this->twig = $twig;
        $this->settings = $container->get('settings.modules');
        $this->global = $container->get('settings.global');
        $this->dbsettings = $container->get(Settings::class);
        $this->session = $session;
        $this->db = $db;
        $this->cache = $cache;
        $cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
        $this->cache_settings = $cache_settings['query']??false;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
      
      $module = $this->settings .'/layout';
      
      $language = $request->getAttribute('locale')??$request->getAttribute('language');
      $request =  $request->withAttribute('locale',$language);
      $this->twig->getEnvironment()->addGlobal('language', $language);

      $lang = new Translator($language, $module);
      $langs = $lang->get();
      $this->twig->getEnvironment()->addGlobal('lang_layout', $langs['body']);

      $basepath = (string) $this->app->getBasePath();
      $this->twig->getEnvironment()->addGlobal('basepath', $basepath);
      
      $uripath = (string) ($this->global['locale'] ? $this->app->getBasePath() . '/' . $request->getAttribute('language') : $this->app->getBasePath());
      $this->twig->getEnvironment()->addGlobal('uripath', $uripath);

      $curtemplate = $this->global['template'];
      $this->twig->getEnvironment()->addGlobal('template', $curtemplate);

      $loginame = ($this->session->has('customer.login')) ? $this->session->get('customer.user') : null;
      $this->twig->getEnvironment()->addGlobal('username', $loginame);

      $sitename = $this->global['sitename'];
      $this->twig->getEnvironment()->addGlobal('sitename', $sitename);

      $categories = new Category($this->db, $this->cache, $this->cache_settings, $language);
      $categories_menu = $categories->readAll();
      $request = $request->withAttribute('categories_menu', $categories_menu);
      $this->twig->getEnvironment()->addGlobal('categories_menu', json_decode($categories_menu));
      $this->twig->getEnvironment()->addGlobal('categories', $categories_menu);

      $searchItems = json_decode($this->dbsettings->get("search_result"));
      $searchResultShow = $searchItems->search_show[0]->set;
      $this->twig->getEnvironment()->addGlobal('result_items', $searchResultShow);

      return $handler->handle($request);
    }
}