<?php

namespace PerSeo\Helpers;

use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Log\LoggerInterface;

class UtilityFunctions
{
    protected $db;
    protected $cache;
    protected $is_cache;
    protected $log;

    public function __construct(DBDefault $database, Psr16Adapter $cache, bool $is_cache = false, LoggerInterface $logger = null) {
        $this->db = $database;
		$this->cache = $cache;
        $this->is_cache = $is_cache;
        $this->log = $logger;
    }

    /**
     * @param $filterjson
     * @param $cachedata
     * @return false|string
     * Utility build json for Variant_list search field
     */
    public function buildJson4VariantList($filterjson, $cacheJson) {
        $buildJson = [];
        $keywords = json_decode($filterjson);

        if (isset($keywords->cat_id)) {
            $buildJson['cat_id'] = $keywords->cat_id;
            $cacheJson .= '_cat_'. $keywords->cat_id;
        }
        if (isset($keywords->sector_id)) {
            $buildJson['sector_id'] = $keywords->sector_id;
            $cacheJson .= '_secid_'. $keywords->sector_id;
        }
        if (isset($keywords->sector)) {
            $buildJson['sector'] = $keywords->sector;
            $cacheJson .= '_sec_'. $keywords->sector;
        }
        if (isset($keywords->brand_id)) {
            $buildJson['brand'] = $keywords->brand_id;
            $cacheJson .= '_brand_'. $keywords->brand_id;
        }
        if (isset($keywords->cat_att_ids)) {
            $buildJson['cat_att_ids'] = $keywords->cat_att_ids;
            $cacheJson .= '_cat_att_ids_'. $keywords->cat_att_ids;
        }
        if (isset($keywords->queries)) {
            $buildJson['query'] = $keywords->queries;
            $cacheJson .= '_query_'. $keywords->queries;
        }
        if (isset($keywords->min_price)) {
            $buildJson['pricemin'] = $keywords->min_price;
            $cacheJson .= '_pricemin_'. $keywords->min_price;
        }
        if (isset($keywords->max_price)) {
            $buildJson['pricemax'] = $keywords->max_price;
            $cacheJson .= '_pricemax_'. $keywords->max_price;
        }
        if (isset($keywords->limit)){
            $buildJson['limit'] = $keywords->limit;
            $cacheJson .= '_lim_'. preg_replace("/[^0-9]/", '', $keywords->limit);
        }
        return ['json' => $buildJson, 'cname' => $cacheJson];
    }

    /**
     * @param $id
     * @param $array
     * @return false|string
     */
    public function loadCattAttrValue($cat_id, $lang) {
        $db = $this->db;
        $cachename = 'cat_att_list'. $lang .'_'. $cat_id;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $db->select('category_attributes_list', '*', ['l_value' => $lang, 'c_id' => $cat_id]);
                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $db->select('category_attributes_list', '*', ['l_value' => $lang, 'c_id' => $cat_id]);
        }

        if (!$result) { $this->log->error('UtilityFunction -> loadCattAttrValue -> Call category_attributes_list return NULL for (cat_id) / (language): ' . $cat_id . ' / ' . $lang ); }

        return json_encode($result);
    }

    /**
     * @param $catId
     * @return bool|mixed
     */
    public function getCategoryMenuId($catId) {
        $db = $this->db;
        $result = $db->get('categories', 'menu_id', ['id' => $catId]);
        return $result;
    }

}
