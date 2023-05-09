<?php


namespace Modules\categories\Classes;


use PerSeo\DB\DBDefault;
use PerSeo\Helpers\UtilityFunctions;
use Phpfastcache\Helper\Psr16Adapter;

class Category
{

    protected DBDefault $db;
    protected Psr16Adapter $cache;
    protected bool $is_cache;
    protected string $language;

    /**
     * CartCheckout constructor.
     * @param DBDefault $database
     * @param Psr16Adapter $cache
     * @param bool $is_cache
     * @param string $language
     */
    public function __construct (DBDefault $database, Psr16Adapter $cache, bool $is_cache, string $language = 'it')
    {
        $this->db = $database;
        $this->cache = $cache;
        $this->is_cache = $is_cache;
        $this->language = $language;
    }

    public function readAll(int $id_menu = 1)
    {
        $db = $this->db;
        $language = $this->language;

        $cachename = 'list_categories_menu_'. $id_menu . '_' . $language;

        if ($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $query = $db->query("CALL list_categories(:lang, :idmenu);", [
                    ":lang" => $language,
                    ":idmenu" => $id_menu
                ]);
                $result = $query->fetchAll(\PDO::FETCH_ASSOC);
                $query->closeCursor();

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $query = $db->query("CALL list_categories(:lang, :idmenu);", [
                ":lang" => $language,
                ":idmenu" => $id_menu
            ]);
            $result = $query->fetchAll(\PDO::FETCH_ASSOC);
            $query->closeCursor();
        }

        return json_encode($result);
    }


    /**
     * @param string $arrayIds
     * @param int $cat_Id
     * @return mixed|string
     */
    public function getCategoryBreadcrumb($arrayIds, int $cat_Id = 0) {
        $language = $this->language;
        $catid = 0;
        $outFor = 0;
        $categories = [];
        $catsIds = '';

        if ($cat_Id > 0) {
            $futil = new UtilityFunctions($this->db, $this->cache);
            $menuid = $futil->getCategoryMenuId($cat_Id);
            $categories = json_decode($this->readAll($menuid), true);
        } else {
            $categories = json_decode($this->readAll(), true);
        }

        $arrBreadcrumb = [];
        $colors = ['#009fe2', '#f8e300', '#e7047e'];
        foreach($categories as $category) {
            if (isset($category['category_attributes_ids'])) {
                $catsIds = json_decode($category['category_attributes_ids'], true);
            } else {
                $catsIds = '';
            }
            $pathNames = explode(' / ' , $category['PATH']);
            $pathLinks = explode(',', $category['alias']);
            $catid = (int) $category['id'];
            $count = 0;
            $colorsCount = 1;
            if (($cat_Id == $catid) && ($outFor == 0)) {
                 foreach($pathNames as $pathName){
                    if((int)$pathLinks[$count] > 0){
                        $arrBreadcrumb['data'][] = ['link' => '/'.$language.'/category/'.(int)$pathLinks[$count], 'text' => $pathName, 'color' => $colors[$colorsCount]];
                    } else {
                        $arrBreadcrumb['data'][] = ['link' => $pathLinks[$count], 'text' => $pathName, 'color' => $colors[$colorsCount]];
                    }
                    ++$count;
                    if($colorsCount < 3) {
                        ++$colorsCount;
                    }else{
                        $colorsCount = 0;
                    }
                }
                $outFor = 1;
            } elseif ($outFor == 0) {
                if ($catsIds == $arrayIds) {
                    foreach($pathNames as $pathName){
                        if((int)$pathLinks[$count] > 0){
                            $arrBreadcrumb['data'][] = ['link' => '/'.$language.'/category/'.(int)$pathLinks[$count], 'text' => $pathName, 'color' => $colors[$colorsCount]];
                        } else {
                            $arrBreadcrumb['data'][] = ['link' => $pathLinks[$count], 'text' => $pathName, 'color' => $colors[$colorsCount]];
                        }
                        ++$count;
                        if($colorsCount < 3) {
                            ++$colorsCount;
                        }else{
                            $colorsCount = 0;
                        }
                    }
                    $outFor = 1;
                }
            }
        }
        return isset($arrBreadcrumb['data']) ? $arrBreadcrumb['data'] : '';
    }


    /**
     * Get the alias of category by passing the id
     * @param int $catId
     *
     * @return int|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getCategoryAlias(int $catId)
    {
        $db = $this->db;
        $language = $this->language;
        $cachename = 'alias_category_'. $language .'_'.$catId;

        if ($catId < 0) { return 0; }
        if($this->is_cache) {
            if (!$this->cache->has($cachename)) {
                $result = $this->db->select('routes', [
                        '[>]categories_route_pivot' => ['id' => 'route_id'],
                        '[><]languages' => ['categories_route_pivot.language_id' => 'id']
                    ],
                    ['request'],
                    [
                        'AND' => [
                            'languages.value' => $language,
                            'categories_route_pivot.category_id' => $catId
                        ]
                    ],
                );

                $this->cache->set($cachename, $result, 7200);
            } else {
                $result = $this->cache->get($cachename);
            }
        } else {
            $result = $this->db->select('routes', [
                '[>]categories_route_pivot' => ['id' => 'route_id'],
                '[><]languages' => ['categories_route_pivot.language_id' => 'id']
                ],
                ['request'],
                [
                    'AND' => [
                        'languages.value' => $language,
                        'categories_route_pivot.category_id' => $catId
                    ]
                ],
            );
        }

        if (!empty($result)) return (string) $result[0]['request'];
        else return 0;
    }

    /**
     * @param $array
     * @return false|string
     */
    public function createUniqueAttr($array) {
        $flushAttr = [];
        $valattr = [];
        $nameadded = '';
        foreach ($array as $data) {
            $priattr = json_decode($data['primary_attributes'], true);
            $count = count($priattr);
            for ($i = 0; $i < $count; $i++) {
                $keyvalue = $priattr[$i]['name'];
                $result = array_filter($priattr, function ($item) use ($keyvalue) {
                    if (stripos($item['name'], $keyvalue) !== false) {
                        return true;
                    }
                    return false;
                });
                if ($keyvalue != $nameadded ){
                    $valattr = ['name' => $keyvalue];
                    $y=0;
                    foreach($result as $attrs) {
                        $val = 'att_' . $y;
                        $valattr[$val]=$attrs['att_name'];
                        $val1 = 'path_' . $y;
                        $valattr[$val1]=$attrs['path'];
                        $y++;
                    }
                    //var_dump($valattr);
                    array_push($flushAttr, $valattr);
                    $valattr = [];
                }
                $nameadded = $keyvalue;
            }
        }
        return json_encode($flushAttr);
    }

}
