<?php


namespace Modules\search\Classes;


use PerSeo\DB;
use PerSeo\Helpers\UtilityFunctions;
use PerSeo\Validator;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Log\LoggerInterface;

class Search
{
    protected $db;
    protected $language;
    protected $country;
    protected $cache;
    protected $cacheFilter;
    protected $is_cache;
    protected $log;
    protected $actionError;

    /**
     * CartCheckout constructor.
     * @param DB $database
     * @param string $language
     */
    public function __construct(DB $database, Psr16Adapter $cache, LoggerInterface $logger, bool $is_cache = false , string $language = 'it', string $country = 'it')
    {
        $this->db = $database;
        $this->language = $language;
        $this->country = $country;
        $this->cache = $cache;
        $this->is_cache = $is_cache;
        $this->log = $logger;
    }

    public function searchData(string $keystring = '', string $dataType = 'json')
    {
        $db = $this->db;
        $language = $this->language;
        $country = $this->country;

        if (empty($keystring)) { return '[]'; }

        $cachename = 'key_s_list_' . $language . '_' . $country;

        $json = '{';
        if ($keystring != '') {
            $json .= '"query" : "' . $keystring . '"';
            $cachename .= '_string_' . str_replace(' ', '_', $keystring);
        }
        $json .= '}';

        $validator = new Validator();
        if(!$validator->json_validator($json)) {
            $this->log->error('SEARCH -> searchData -> Call function with INVALID JSON ');
            return '[]';
        }

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                    ":lang" => $language,
                    ":country" => $country,
                    ":json" => $json
                ]);
                $result = $query->fetchAll(\PDO::FETCH_ASSOC);
                $query->closeCursor();
                $this->cache->set($cachename, $result, 3600);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                ":lang" => $language,
                ":country" => $country,
                ":json" => $json
            ]);
            $result = $query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }
        if ($dataType == 'json') {
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
     */
    public function searchByFilter(string $filters = '', string $dataType = 'json')
    {
        $db = $this->db;
        $language = $this->language;
        $country = $this->country;

        if (empty($filters)) { return '[]'; }

        $validator = new Validator();
        if(!$validator->json_validator($filters)) {
            $this->log->error('SEARCH -> searchByFilter -> Call function with INVALID JSON ');
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
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $query = $db->query("CALL Variant_list(:lang, :country, :json);", [
                ":lang" => $language, ":country" => $country, ":json" => $filters
            ]);
            $result = $query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }

        if (!$result) { $this->log->error('SEARCH -> searchByFilter -> Call Variant_list return NULL for ($keywords) : ' . $filters); }

        if($dataType == 'json'){
            return json_encode($result);
        } else {
            return $result;
        }
    }

}