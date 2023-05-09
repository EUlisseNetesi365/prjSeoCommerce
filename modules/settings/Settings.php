<?php

namespace Modules\settings;

use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;

class Settings
{
    protected DBDefault $db;
    protected Psr16Adapter $cache;
    protected bool $is_cache;

    public function __construct(DBDefault $db, Psr16Adapter $cache, bool $is_cache = false)
    {
        $this->db = $db;
        $this->cache = $cache;
        $this->is_cache = $is_cache;
    }

    /**
     *  Get the string of params
     * @param string $key
     * @return string|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function get(string $key): ?string
    {
        $cachename = 'get_setting_' . $key;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $this->db->get('settings', 'params', ['value' => $key]);

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $this->db->get('settings', 'params', ['value' => $key]);
        }

        return $result;
    }

    /**
     * Get all the columns of the settings
     * @return string|null
     */
    public function getAllRAW(): ?string
    {
        $cachename = 'get_all_setting';

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $this->db->select('settings', '*');

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $this->db->select('settings', '*');
        }

        return json_encode($result);
    }
}