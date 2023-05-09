<?php

namespace PerSeo\Helpers;

use PerSeo\DB;
use Psr\Container\ContainerInterface;
use Phpfastcache\Helper\Psr16Adapter;

class Countries
{
    protected $db;

    public function __construct(DB $database, ContainerInterface $container, Psr16Adapter $cache) {
        $this->db = $database;
		$this->cache = $cache;
		$this->cache_settings = ($container->has('settings.cache') ? $container->get('settings.cache') : array());
		$this->cache_settings = (!empty($this->cache_settings) ? $this->cache_settings['query'] : false);
    }

    public function get(string $language = 'it')
    {
		$cachename = 'key_countries_list_'. $language;
		$jsonlang = '$.'. $language;
		if ($this->cache_settings) {
			if (!$this->cache->has($cachename)) {
				$result = $this->db->select("countries", [
					"id",
					"value",
					"name" => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(<countries.name>, :language))", [ ":language" => $jsonlang ])
				],[
					"enable" => "1",
					"active" => "1"
				]);
				$result = json_encode($result);
				$this->cache->set($cachename, $result, 3600);
			}
			else {
				$result = $this->cache->get($cachename);
			}
		}
		else {
			$result = $this->db->select("countries", [
					"id",
					"value",
					"name" => DB::raw("JSON_UNQUOTE(JSON_EXTRACT(<countries.name>, :language))", [ ":language" => $jsonlang ])
				],[
					"enable" => "1",
					"active" => "1"
				]);
			$result = json_encode($result);
		}
		return $result;
    }
}
