<?php


namespace Modules\products\Classes;

use PerSeo\DB\DBDefault;
use PerSeo\Validator;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Log\LoggerInterface;

class VariantList
{
    protected $db;
    protected $language;
	protected $country;
	protected $cacheFilter;
	protected $cache;
	protected $is_cache;
	protected $log;
    protected $actionError;

    /**
     * VariantList constructor.
     * @param DBDefault $database
     * @param Psr16Adapter $cache
     * @param bool|bool $is_cache
     * @param string|string $language
     * @param string|string $country
     * @param LoggerInterface $logger
     */
    public function __construct(DBDefault $database, Psr16Adapter $cache, bool $is_cache = false, string $language = 'it', string $country = 'it', LoggerInterface $logger = null)
    {
        $this->db = $database;
        $this->language = $language;
		$this->country = $country;
		$this->cache = $cache;
		$this->is_cache = $is_cache;
		$this->log = $logger;
    }
	
	public function readSingle(int $id = 0, string $dataType = 'json')
    {
        $db = $this->db;

        $language = $this->language;
		$country = $this->country;
        $json = '{}';
		$cachename = 'key_variant_'. $language .'_'. $country;
		$json = '{';
        if ($id > 0) {
            $json .= '"id" : "' . $id . '"';
			$cachename .= '_vid_'. $id;
		}
		$json .= '}';

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                 $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                    ":lang" => $language, ":country" => $country, ":json" => $json
                ]);
                $resqry = $query->fetchAll(\PDO::FETCH_ASSOC);
                $query->closeCursor();
                $this->cache->set($cachename, $resqry, 3600);
           }
            else {
                $resqry = $this->cache->get($cachename);
           }
        } else {
            $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                ":lang" => $language, ":country" => $country, ":json" => $json
            ]);
            $resqry = $query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }

        if($resqry == null) {
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => '0',
                'msg' => 'NULL_VALUE',
                'data' => null
            ];
            $this->log->error('VARIANTLIST -> readSingle -> Call Variant_list return NULL for id: ' . $id);
        } else {
            $result = [
                'success' => 1,
                'error' => 0,
                'code' => '0',
                'msg' => 'OK',
                'data' => $resqry
            ];
        }

        if($dataType == 'json'){
            return json_encode($result);
        } else {
            return $result;
        }
    }

    /**
     * @param string|string $filters
     * @param string|string $dataType
     * @return array|false|int|mixed|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *
     */
    public function readAll(string $filters = '', string $dataType = 'json')
    {
        $db = $this->db;
        $language = $this->language;
        $country = $this->country;

        if (empty($filters)) { return '[]'; }

        $validator = new Validator();
        if(!$validator->json_validator($filters)) {
            $this->log->error('VARIANTLIST -> readAll -> Call function with INVALID JSON ');
            return '[]';
        }

        $cachename = preg_replace('/[^A-Za-z0-9\_]/','', $filters);
        $cachename .= "_" . $this->language . "_" . $this->country;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                    ":lang" => $language, ":country" => $country, ":json" => $filters
                ]);
                $result = $query->fetchAll(\PDO::FETCH_ASSOC);
                $query->closeCursor();
                $this->cache->set($cachename, $result, 3600);
            }
            else {
                $result = $this->cache->get($cachename);
            }
        }
        else {
            $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                ":lang" => $language, ":country" => $country, ":json" => $filters
            ]);
            $result = $query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }

        if (!$result) { $this->log->error('VARIANTLIST -> readAll -> Call Variant_list return NULL for filters : ' . $filters); }

        if($dataType == 'json'){
            return json_encode($result);
        } else {
            return $result;
        }

    }

    /**
     * @param int $sector_id
     * @return false|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function readAttributeList(int $sector_id)
    {
        $db = $this->db;
        $language = $this->language;
        $cachename = 'attribute_sector_'. $language .'_'. $sector_id;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $query = $db->query("CALL Attribute_list(:lang, :id);", [
                    ":lang" => $language, ":id" => $sector_id
                ]);
                $result = $query->fetchAll(\PDO::FETCH_ASSOC);
                $query->closeCursor();
                $this->cache->set($cachename, $result, 7200);
            }
            else {
                $result = $this->cache->get($cachename);
            }
        }
        else {
            $query = $db->query("CALL Attribute_list(:lang, :id);", [
                ":lang" => $language, ":id" => $sector_id
            ]);
            $result = $query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }

        if (!$result) { $this->log->error('VARIANTLIST -> readAttributeList -> Call Variant_list return NULL for sector_id: ' . $sector_id ); }

        return json_encode($result);

    }

    /**
     * @param $cat_id
     * @param $lang
     * @return false|int|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function readAttributeList4Filters($cat_id, $lang) {
        $db = $this->db;
        $language = $this->language;
        $cachename = 'cat_filters_'. $language .'_'. $cat_id;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $db->select('category_filters', '*', ['l_value' => $lang, 'c_id' => $cat_id]);
                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $db->select('category_filters', '*', ['l_value' => $lang, 'c_id' => $cat_id]);
        }

        if (!$result) { $this->log->error('VARIANTLIST -> readAttributeList4Filters -> Call category_filters return NULL for (cat_id) / (language): ' . $cat_id . ' / ' . $lang ); }

        return json_encode($result);
    }

    /**
     * @param int|int $cat_id
     * @return false|int|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */

    public function getCategoryFilterPaging(int $cat_id = 0) {
        $db = $this->db;
        $language = $this->language;
        $cachename = 'category_paging_'. $language .'_'. $cat_id;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $db->get('total_variants', '*', ['id' => $cat_id]);

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $db->get('total_variants', '*', ['id' => $cat_id]);
        }

        if ($result) return json_encode($result);
        else return 0;

    }


    public function getCategoryCatAttrPaging(string $cat_value = '') {
        $db = $this->db;
        $language = $this->language;
        $cachename = 'cat_attr_variants_paging_'. $language .'_'. $cat_value;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $db->get('total_variants_cat_attr', '*', ['cat_value' => $cat_value]);

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $db->get('total_variants_cat_attr', '*', ['cat_value' => $cat_value]);
        }

        if ($result) return json_encode($result);
        else return 0;
    }

    /**
     * @param string|string $cat_value
     * @return bool|int|mixed
     * Get id from category_attributes searching value
     */
    public function getCategoryCatAttrId(string $cat_value = ''){
        $db = $this->db;
        $result = $db->get('category_attributes', 'id', ['value' => $cat_value]);
        if ($result) return $result;
        else return 0;
    }


    /**
     * Get Variants for Carousel by passing category ID and limit
     *
     * @param int $cat_id
     * @param int $limit
     * @return int|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */

    public function getVariantsCarousel(int $cat_id = 0, int $limit = 0) {
        $db = $this->db;
        $language = $this->language;
        $cachename = 'variants_carousel_'. $language .'_'.$cat_id;

        if ($cat_id < 0) { return 0; }
        if ($limit < 0) { return 0; }
        if($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                    $result = $db->select("variant_list_carousel", '*',
                        DBDefault::raw('WHERE
		            language_value = :language
		            AND JSON_CONTAINS(category_ids, :cat_id)
		            LIMIT :limit', [
                        ':language' => $language,
                        ':cat_id' => $cat_id,
                        ':limit' => $limit
                    ]));

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $db->select("variant_list_carousel", '*',
                DBDefault::raw('WHERE
		            language_value = :language
		            AND JSON_CONTAINS(category_ids, :cat_id)
		            LIMIT :limit', [
                    ':language' => $language,
                    ':cat_id' => $cat_id,
                    ':limit' => $limit
                ]));
        }
        if ($result) return json_encode($result);
        else return 0;
    }
}