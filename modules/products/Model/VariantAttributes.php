<?php


namespace Modules\products\Model;

use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Phpfastcache\Helper\Psr16Adapter;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;

class VariantAttributes
{
    protected $app;
    protected $db;
    protected $global;
    protected $session;
    protected $log;
    protected $container;
    protected $listaddr;
    protected $actionError;
    protected $rowdb1;
    protected $rowdb2;

    public function __construct(App $app, ContainerInterface $container, SessionInterface $session, DBDefault $db, Psr16Adapter $cache, LoggerInterface $logger)
    {
        $this->app = $app;
        $this->db = $db;
        $this->session = $session;
        $this->container = $container;
        $this->log = $logger;

    }

    public function getVariant($id, $data) {
        $db = $this->db;

        try {

            $search = '[';
            for ($i = 0; $i < count($data); $i ++) {
                if ($i === 0) {
                    $search .= $data[$i];
                } else {
                    $search .= ',' . $data[$i];
                }
            }
            $search .= ']';


            $attrData = $db->query("select id from variants 
                                    where product_id = :p_id and JSON_CONTAINS(variants_attributes_list_ids, :p_attr, '$')",
                                    [":p_id" => $id, ":p_attr" => $search] )->fetchAll(\PDO::FETCH_ASSOC);


            if (empty($attrData)) {
                throw new \Exception("ATTRIBUTE_NO_EXIST", '001');
            }

            if ($this->db->error) {
                throw new \Exception("ATTRIBUTE_DB_SELECT_ERROR", '002');
            }

        } catch (\Exception $e) {
            $this->log->error('VARIANTATTRIBUTES: getVariant -> (code) / (message) / (product_id) / (primary attribute) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $id . ' / ' . $data);
            json_encode($result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'variantid' => 0
            ]
            );
        }

        return json_encode($result = [
            'success' => 1,
            'error' => 0,
            'code' => 0,
            'msg' => 'ok',
            'variantid' => $attrData[0]['id']
        ]
        );
    }

}