<?php


namespace Modules\customer\Classes;

use Psr\Log\LoggerInterface;
use Slim\App;
use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use PerSeo\Validator;
use Psr\Container\ContainerInterface;
use Slim\Views\Twig;

class CustomerData
{

    protected DBDefault $db;
    protected $global;
    protected SessionInterface $session;
    protected LoggerInterface $log;
    protected ContainerInterface $container;
    protected Validator $validator;
    protected $listaddr;
    protected int $actionError;
    protected $rowdb1;
    protected $rowdb2;

    public function __construct(DBDefault $database, ContainerInterface $container, SessionInterface $session, LoggerInterface $logger)
    {

        $this->db = $database;
        $this->session = $session;
        $this->container = $container;
        $this->log = $logger;
        $this->validator = new Validator();
        $this->listaddr = [];

    }

    /**
     * @param $custId
     * @return false|string
     */
    public function readDataCustomer($custId, string $language = 'it')
    {

        $result = [];
        $db = $this->db;
        $userData = [];

        try {
            $userData = $db->query("select customers.id, customers.user, datas.data, emailsdata.emails,
                                            market_channels.id as mc_id,
                                            JSON_UNQUOTE(JSON_EXTRACT(market_channels.name, CONCAT('$.', LOWER(languages.value)))) as mc_name,
											mkt_chan_type.id as mct_id,
                                            JSON_UNQUOTE(JSON_EXTRACT(mkt_chan_type.name, CONCAT('$.', LOWER(languages.value)))) as mct_name,
                                            languages.id AS l_id, LOWER(languages.value) AS l_value
                                            from customers 
                                            INNER JOIN(SELECT customer_emails.customer_id,
                                                concat('[',GROUP_CONCAT(
                                                JSON_OBJECT(
                                                    'id', emails.id,
                                                    'address', emails.value,
                                                    'is_login', customer_emails.is_login,
                                                    'confirmed', customer_emails.confirmed
                                                )
                                                ),']') AS emails
                                                FROM customer_emails
                                                INNER JOIN emails ON customer_emails.email_id = emails.id
                                                GROUP BY customer_emails.customer_id
                                            ) emailsdata ON customers.id = emailsdata.customer_id
                                            LEFT JOIN 
                                            (SELECT customer_data.customer_id, concat('[',GROUP_CONCAT(
                                                JSON_OBJECT(
                                                    'id',customer_data.id,
                                                    'name',customer_data.name,
                                                    'surname',customer_data.surname,
                                                    'birthdate',customer_data.birthdate,
                                                    'company_name',customer_data.company_name,
                                                    'country_id', customer_data.country_id,
                                                    'country_value', countries.value,
                                                    'province',customer_data.province,
                                                    'city',customer_data.city,
                                                    'zip_code',customer_data.zip_code,
                                                    'address',customer_data.address,
                                                    'location_gps',customer_data.location_gps,
                                                    'phone1',customer_data.phone1,
                                                    'phone2',customer_data.phone2,
                                                    'vat_number',customer_data.vat_number,
                                                    'fiscal_code',customer_data.fiscal_code,
                                                    'shipping_note',customer_data.shipping_note,
                                                    'is_default_ship',customer_data.is_default_ship,
                                                    'is_default_invoice',customer_data.is_default_invoice,
                                                    'is_private',customer_data.is_private,
                                                    'pec', customer_data.pec,
                                                    'sdi', customer_data.sdi
                                                )
                                                ),']') AS data
                                            FROM customer_data
                                             INNER JOIN countries ON customer_data.country_id = countries.id
                                             GROUP BY customer_data.customer_id
                                            ) datas ON customers.id = datas.customer_id
                                            LEFT JOIN customer_mkt_chan_pivot ON customer_mkt_chan_pivot.customer_id = customers.id
                                            LEFT JOIN market_channels ON market_channels.id = customer_mkt_chan_pivot.mkt_chan_id
                                            LEFT JOIN mkt_chan_type ON mkt_chan_type.id = market_channels.mkt_chan_type_id
                                            INNER JOIN languages ON (languages.`enable` = 1 AND languages.active = 1) 
                                            where customers.id = :custId AND languages.value = :lang ",
                                            [":custId" => $custId, ":lang" => $language])->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($userData)) {
                throw new \Exception("CUSTOMER_DATA_NO_EXIST", '001');
            }

            // errore db
            if ($this->db->error) {
                throw new \Exception("CUSTOMER_DATA_DB_SELECT_ERROR", '002');
            }

        } catch (\Exception $e) {
            $this->log->error('CUSTOMERDATA: readDataCustomer -> (code) / (message) / (customerid) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $custId);
            json_encode($result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            )
            );
        }

        return json_encode($result = array(
            'success' => 1,
            'error' => 0,
            'code' => 0,
            'msg' => 'ok',
            'mkt' => $this->session->get('customer.mkt_chan'),
            'userdata' => $userData
        )
        );

    }


    /**
     * @param $custId
     * @param $name
     * @param $surname
     * @param $birth
     * @param $company
     * @param $countryId
     * @param $province
     * @param $city
     * @param $zip_code
     * @param $address
     * @param $gps
     * @param $ph1
     * @param $ph2
     * @param $vat_num
     * @param $fiscal_code
     * @param $note
     * @param $ship_def
     * @param $invoice_def
     * @param $private
     * @return false|string
     */
    public function createDataCustomer(
        int $custId,
        string $name = '',
        string $surname = '',
        string $birth = '',
        string $company = '',
        int $countryId = 0,
        string $province = '',
        string $city = '',
        string $zip_code = '',
        string $address = '',
        string $pec = '',
        string $sdi = '',
        string $gps = '',
        string $ph1 = '',
        string $ph2 = '',
        string $vat_num = '',
        string $fiscal_code = '',
        string $note = '',
        int $ship_def = 0,
        int $invoice_def = 0,
        int $private = 1
    )
    {

        $result = [];
        $db = $this->db;
        $this->actionError = 0;

        try {

            if ((int) $private === 1) {
                if (empty($name)) {
                    throw new \Exception('NAME_EMPTY', '001');
                }
                if (empty($surname)) {
                    throw new \Exception('SURNAME_EMPTY', '002');
                }
            } else {
                if (empty($company)) {
                    throw new \Exception('COMPANY_NAME_EMPTY', '003');
                }

            }
            if ((int) $countryId == 0) {
                throw new \Exception('COMPANY_NAME_EMPTY', '004');
            }
            if (empty($province)) {
                throw new \Exception('PROVINCE_EMPTY', '005');
            }
            if (empty($city)) {
                throw new \Exception('CITY_EMPTY', '006');
            }
            if (empty($zip_code)) {
                throw new \Exception('ZIP_CODE_EMPTY', '007');
            }
            if (empty($address)) {
                throw new \Exception('ADDRESS_EMPTY', '008');
            }

            $db->action(function ($db) use ($custId, $name, $surname, $birth, $company, $countryId, $province, $city, $zip_code, $address, $gps, $ph1, $ph2, $vat_num, $fiscal_code, $note, $ship_def, $invoice_def, $private, $pec, $sdi) {

                /**
                 * Verify new Default Addresses sent by Frontend and modify existing default to null
                 */
                if ($ship_def == 1 || $invoice_def == 1) {
                    $dbverifyDefault = $db->select(
                        "customer_data",
                        [
                            'id',
                            'is_default_ship',
                            'is_default_invoice'
                        ],
                        [
                            "AND" => [
                                "OR" => [
                                    "is_default_ship" => 1,
                                    "is_default_invoice" => 1
                                ],
                                "customer_id" => $custId
                            ]
                        ]
                    );


                    if ($dbverifyDefault != null) {
                        if ($dbverifyDefault[0]['is_default_ship'] == 1 && $ship_def == 1) {
                            $data = $db->update(
                                "customer_data",
                                [
                                    'is_default_ship' => DBDefault::RAW('NULL')
                                ],
                                [
                                    'id' => $dbverifyDefault[0]['id'],
                                    'customer_id' => $custId
                                ]
                            );

                            $countData = $data->rowCount();
                            if ($countData <= 0) {
                                $this->actionError = 2; /* NO AFFECTED DATA */
                                return false;
                            }
                        }

                        if ($dbverifyDefault[0]['is_default_invoice'] == 1 && $invoice_def == 1) {
                            $data = $db->update(
                                "customer_data",
                                [
                                    'is_default_invoice' => DBDefault::RAW('NULL')
                                ],
                                [
                                    'id' => $dbverifyDefault[0]['id'],
                                    'customer_id' => $custId
                                ]
                            );
                            $countData = $data->rowCount();
                            if ($countData <= 0) {
                                $this->actionError = 2; /* NO AFFECTED DATA */
                                return false;
                            }
                        }
                    }
                    //else {
                    //    $this->actionError = 2; /* NO AFFECTED DATA */
                    //    return false;
                    //}
                }

                $db->insert(
                    "customer_data",
                    [
                        'customer_id' => $custId,
                        'name' => !empty($name) ? $name : DBDefault::RAW('NULL'),
                        'surname' => !empty($surname) ? $surname : DBDefault::RAW('NULL'),
                        'birthdate' => !empty($birth) ? $birth : DBDefault::RAW('NULL'),
                        'company_name' => !empty($company) ? $company : DBDefault::RAW('NULL'),
                        'country_id' => $countryId,
                        'province' => $province,
                        'city' => $city,
                        'zip_code' => $zip_code,
                        'address' => $address,
                        'location_gps' => !empty($gps) ? $gps : DBDefault::RAW('NULL'),
                        'phone1' => !empty($ph1) ? $ph1 : DBDefault::RAW('NULL'),
                        'phone2' => !empty($ph2) ? $ph2 : DBDefault::RAW('NULL'),
                        'vat_number' => !empty($vat_num) ? $vat_num : DBDefault::RAW('NULL'),
                        'fiscal_code' => !empty($fiscal_code) ? $fiscal_code : DBDefault::RAW('NULL'),
                        'shipping_note' => !empty($note) ? $note : DBDefault::RAW('NULL'),
                        'is_default_ship' => (($ship_def != 0) ? $ship_def : DBDefault::RAW('NULL')),
                        'is_default_invoice' => (($invoice_def !== 0) ? $invoice_def : DBDefault::RAW('NULL')),
                        'is_private' => $private,
                        'pec' => !empty($pec) ? $pec : DBDefault::RAW('NULL'),
                        'sdi' => !empty($sdi) ? $sdi : DBDefault::RAW('NULL')
                    ]
                );
                $address = (int) $db->id();

                if ($address <= 0) {
                    $this->actionError = 10; /* ADDRESS_NOT_INSERTED */
                    return false;
                }

                /**
                 * Update Session Variable if frontend send new Default Address
                 */
                if ($ship_def == 1) {
                    $this->session->set('customer.default_ship_id', (int) $address);
                }
                if ($invoice_def == 1) {
                    $this->session->set('customer.default_invoice_id', (int) $address);
                }

                /**
                 * return new list of address to frontend
                 */
                $list = $db->select(
                    "customer_data",
                    "*",
                    [
                        "customer_id" => $custId
                    ]
                );
                if (!$list) {
                    $this->actionError = 12; /* LIST_ADDRESS_ERROR */
                    return false;
                }

                $this->listaddr = $list;

            });

            if ($this->actionError === 0) {
                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => 0,
                    'msg' => "OK",
                    'list' => $this->listaddr
                ];
            } else {
                throw new \Exception("ERROR_CREATE", $this->actionError);
            }

        } catch (\Exception $e) {
            $this->log->error('CUSTOMERDATA: createDataCustomer -> (code) / (message) / (custid) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $custId);
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);
    }

    /**
     * @param $id
     * @param $custId
     * @param $name
     * @param $surname
     * @param $birth
     * @param $company
     * @param $countryId
     * @param $province
     * @param $city
     * @param $zip_code
     * @param $address
     * @param $gps
     * @param $ph1
     * @param $ph2
     * @param $vat_num
     * @param $fiscal_code
     * @param $note
     * @param $ship_def
     * @param $invoice_def
     * @param $private
     * @return false|string
     */

    public function updateDataCustomer(
        int $custId,
        int $id,
        string $name = '',
        string $surname = '',
        string $birth = '',
        string $company = '',
        int $countryId = 0,
        string $province = '',
        string $city = '',
        string $zip_code = '',
        string $address = '',
        string $pec = '',
        string $sdi = '',
        string $gps = '',
        string $ph1 = '',
        string $ph2 = '',
        string $vat_num = '',
        string $fiscal_code = '',
        string $note = '',
        int $ship_def = 0,
        int $invoice_def = 0,
        int $private = 1
    )
    {


        $result = [];
        $db = $this->db;
        $this->actionError = 0;

        try {

            if ((int) $private === 1) {
                if (empty($name)) {
                    throw new \Exception('NAME_EMPTY', '001');
                }
                if (empty($surname)) {
                    throw new \Exception('SURNAME_EMPTY', '002');
                }
            } else {
                if (empty($company)) {
                    throw new \Exception('COMPANY_NAME_EMPTY', '003');
                }

            }

            if (empty($province)) {
                throw new \Exception('PROVINCE_EMPTY', '005');
            }
            if (empty($city)) {
                throw new \Exception('CITY_EMPTY', '006');
            }
            if (empty($zip_code)) {
                throw new \Exception('ZIP_CODE_EMPTY', '007');
            }
            if (empty($address)) {
                throw new \Exception('ADDRESS_EMPTY', '008');
            }

            $db->action(function ($db) use ($custId, $id, $name, $surname, $birth, $company, $countryId, $province, $city, $zip_code, $address, $gps, $ph1, $ph2, $vat_num, $fiscal_code, $note, $ship_def, $invoice_def, $private, $pec, $sdi) {

                /**
                 * Verify new Default Addresses sent by Frontend and modify existing default to null
                 */
                if ($ship_def == 1 || $invoice_def == 1) {
                    $dbverifyDefault = $db->select(
                        "customer_data",
                        [
                            'id',
                            'is_default_ship',
                            'is_default_invoice'
                        ],
                        [
                            "AND" => [
                                "OR" => [
                                    "is_default_ship" => 1,
                                    "is_default_invoice" => 1
                                ],
                                "customer_id" => $custId
                            ]
                        ]
                    );

                    if ($dbverifyDefault != null) {
                        if ($dbverifyDefault[0]['is_default_ship'] == 1 && $ship_def == 1) {
                            $data = $db->update(
                                "customer_data",
                                [
                                    'is_default_ship' => DBDefault::RAW('NULL')
                                ],
                                [
                                    'id' => $dbverifyDefault[0]['id']
                                ]
                            );
                            $countData = $data->rowCount();
                            if ($countData <= 0) {
                                $this->actionError = 2; /* NO AFFECTED DATA */
                                return false;
                            }
                        }
                        if ($dbverifyDefault[0]['is_default_invoice'] == 1 && $invoice_def == 1) {
                            $data = $db->update(
                                "customer_data",
                                [
                                    'is_default_invoice' => DBDefault::RAW('NULL')
                                ],
                                [
                                    'id' => $dbverifyDefault[0]['id']
                                ]
                            );
                            $countData = $data->rowCount();
                            if ($countData <= 0) {
                                $this->actionError = 2; /* NO AFFECTED DATA */
                                return false;
                            }
                        }

                    } else {
                        $this->actionError = 2; /* NO AFFECTED DATA */
                        return false;
                    }

                }

                $data = $db->update(
                    "customer_data",
                    [
                        'name' => !empty($name) ? $name : DBDefault::RAW('NULL'),
                        'surname' => !empty($surname) ? $surname : DBDefault::RAW('NULL'),
                        'birthdate' => !empty($birth) ? $birth : DBDefault::RAW('NULL'),
                        'company_name' => !empty($company) ? $company : DBDefault::RAW('NULL'),
                        'country_id' => $countryId,
                        'province' => $province,
                        'city' => $city,
                        'zip_code' => $zip_code,
                        'address' => $address,
                        'location_gps' => !empty($gps) ? $gps : DBDefault::RAW('NULL'),
                        'phone1' => !empty($ph1) ? $ph1 : DBDefault::RAW('NULL'),
                        'phone2' => !empty($ph2) ? $ph2 : DBDefault::RAW('NULL'),
                        'vat_number' => !empty($vat_num) ? $vat_num : DBDefault::RAW('NULL'),
                        'fiscal_code' => !empty($fiscal_code) ? $fiscal_code : DBDefault::RAW('NULL'),
                        'shipping_note' => !empty($note) ? $note : DBDefault::RAW('NULL'),
                        'is_default_ship' => (($ship_def != 0) ? $ship_def : DBDefault::RAW('NULL')),
                        'is_default_invoice' => (($invoice_def != 0) ? $invoice_def : DBDefault::RAW('NULL')),
                        'is_private' => $private,
                        'pec' => !empty($pec) ? $pec : DBDefault::RAW('NULL'),
                        'sdi' => !empty($sdi) ? $sdi : DBDefault::RAW('NULL')
                    ],
                    [
                        'id' => $id
                    ]
                );

                $countData = $data->rowCount();

                if ($countData <= 0) {
                    $this->actionError = 2; /* NO AFFECTED DATA */
                    return false;
                }

                /**
                 * Update Session Variable if frontend send updated Default Address
                 */
                if ($ship_def == 1) {
                    $this->session->set('customer.default_ship_id', (int) $id);
                }
                if ($invoice_def == 1) {
                    $this->session->set('customer.default_invoice_id', (int) $id);
                }

                /**
                 * return new list of address to frontend
                 */
                $list = $db->select(
                    "customer_data",
                    "*",
                    [
                        "customer_id" => $custId
                    ]
                );

                if (!$list) {
                    $this->actionError = 12; /* LIST_ADDRESS_ERROR */
                    return false;
                }
                $this->listaddr = $list;
            });

            if ($this->actionError === 0) {
                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => 0,
                    'msg' => "OK",
                    'list' => $this->listaddr
                ];
            } else {
                throw new \Exception("UPDATE_DATA", $this->actionError);
            }

        } catch (\Exception $e) {
            $this->log->error('CUSTOMERDATA: updateDataCustomer -> (code) / (message) / (custid) / (addressid) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $custId . ' / ' . $id);
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);
    }

    /**
     * @param $id
     * @return false|string
     */
    public function deleteDataCustomer($custId, $id)
    {

        $result = [];
        $db = $this->db;
        $this->actionError = 0;

        try {

            $db->action(function ($db) use ($custId, $id) {
                $defVal = $db->select(
                    "customer_data",
                    [
                        "is_default_ship",
                        "is_default_invoice"
                    ],
                    [
                        "id" => $id
                    ]
                );

                if (!$defVal) {
                    $this->actionError = 10; /* ADDRESS_NOT_EXIST */
                    return false;
                }
                ;

                if ($defVal[0]['is_default_ship'] == 1 || $defVal[0]['is_default_invoice'] == 1) {
                    $this->actionError = 11; /* ADDRESS_IS_DEFAULT */
                    return false;
                }

                $db->delete(
                    "customer_data",
                    [
                        'id' => $id
                    ]
                );

                $error = isset($this->db->error) ? $this->db->error : null;
                if ($error) {
                    $this->actionError = 2; /* ERROR_DELETE */
                    return false;
                }

                /**
                 * return new list of address to frontend
                 */
                $list = $db->select(
                    "customer_data",
                    "*",
                    [
                        "customer_id" => $custId
                    ]
                );

                if (!$list) {
                    $this->actionError = 12; /* LIST_ADDRESS_ERROR */
                    return false;
                }

                $this->listaddr = $list;

            });

            if ($this->actionError === 0) {
                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => 0,
                    'msg' => "OK",
                    'list' => $this->listaddr
                ];
            } else {
                throw new \Exception("ERROR_DELETE", $this->actionError);
            }

        } catch (\Exception $e) {
            $this->log->error('CUSTOMERDATA: deleteDataCustomer -> (code) / (message) / (custid) / (addressid) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $custId . ' / ' . $id);
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);
    }

    /**
     *
     * Section for Addresses Customer Logged
     *
     */
    /**
     * @return JSON ok || ko
     *
     * get ID default ADDRESSES from customer_data for logged user
     * and put in session 'customerData.is_default_ship' & 'customerData.is_default_invoice'
     * if the ADDRESSES are the same set only session variable 'customerData.is_default_ship'
     * return not used, the response if OK or KO in Perseo format.
     * provided as standard in case it is needed.
     * 20230215 - EUL - Controller called from LoginController in this Controllers path
     *
     */
    public function getDefAddressForCart()
    {

        $customer_id = (int) $this->session->get('customer.id') ?? '';

        if (!$customer_id) {
            $this->log->error('CUSTOMERDATA: getDefAddressForCart -> customer_id not in session (null) ');
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => '003',
                'msg' => 'NOT_LOGGED'
            );
        } else {
            $loggedCustomerData = json_decode($this->readDataCustomer($customer_id), true);
            $readData = $loggedCustomerData['userdata'];
            $loggedData = isset($readData[0]['data']) ? json_decode($readData[0]['data'], true) : '';

            if ($loggedData != '') {
                foreach ($loggedData as $customerInfo) {
                    if ($customerInfo['is_default_ship'] == 1 && $customerInfo['is_default_invoice'] == 1) {
                        $this->session->set('customer.default_ship_id', (int) $customerInfo['id']);
                    }
                    if ($customerInfo['is_default_ship'] == null && $customerInfo['is_default_invoice'] == 1) {
                        $this->session->set('customer.default_invoice_id', (int) $customerInfo['id']);
                    }
                    if ($customerInfo['is_default_ship'] == 1 && $customerInfo['is_default_invoice'] == null) {
                        $this->session->set('customer.default_ship_id', (int) $customerInfo['id']);
                    }
                }
                if (!$this->session->has('customer.default_invoice_id')) {
                    $this->session->set('customer.default_invoice_id', 0);
                }
            } else {

                $this->session->set('customer.default_ship_id', 0);
                $this->session->set('customer.default_invoice_id', 0);

            }

            $result = array(
                'success' => 1,
                'error' => 0,
                'code' => '0',
                'msg' => 'OK'
            );
        }

        return json_encode($result);
    }

    /**
     *
     * @param $hiddenbilling (1 add address)
     * @param $hiddenship (1 add address)
     * @param $customer_addnew (new address for insert)
     * @return JSON data customer || ko
     *
     * Called from UpdateCart controller from module cart_checkout\Controllers
     * Add new Address insereted by logged user in the final process cart checkout
     *  update the id customer_data in session variable
     *  if logger select default new adrress ship or bill, function update data default for address in session and set new default when insert
     * 20230215 - EUL
     */
    public function updateAddressForCart($hiddenship, $hiddenbilling, $customer_addnew)
    {

        $db = $this->db;
        $customer_id = (int) $this->session->get('customer.id') ?? 0;
        $dbRetShip = [];
        $dbRetBill = [];
        $this->rowdb1 = 0;
        $this->rowdb2 = 0;
        $this->actionError = 0;

        if ($customer_id <= 0) {
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => '004',
                'msg' => 'USER_NOT_LOGGED'
            );
        } else {

            try {

                $db->action(function ($db) use ($hiddenbilling, $hiddenship, $customer_addnew, $customer_id) {

                    if ($hiddenbilling > 0 && $hiddenship > 0) {
                        if ((int) $customer_addnew['billing_is_default'] > 0 && $this->session->get('customer.default_invoice_id') > 0) {
                            $db = $db->update("customer_data", [
                                "is_default_invoice" => DBDefault::RAW('NULL')
                            ], ['id' => $this->session->get('customer.default_invoice_id')]);
                            $this->rowdb1 = $db->rowCount();
                        }
                        if ((int) $customer_addnew['shipping_is_default'] > 0 && $this->session->get('customer.default_ship_id') > 0) {
                            $db = $db->update("customer_data", [
                                "is_default_ship" => DBDefault::RAW('NULL')
                            ], ['id' => $this->session->get('customer.default_ship_id')]);
                            $this->rowdb2 = $db->rowCount();
                        }
                        if (($this->rowdb1 <= 0) || ($this->rowdb2 <= 0)) {
                            $this->actionError = 2; /* NO AFFECTED DATA */
                            return false;
                        }
                    }
                    if ($hiddenbilling > 0 && $hiddenship == 0) {
                        if ((int) $customer_addnew['billing_is_default'] > 0 && $this->session->get('customer.default_invoice_id') > 0) {
                            $db->update("customer_data", [
                                "is_default_invoice" => DBDefault::RAW('NULL')
                            ], ['id' => $this->session->get('customer.default_invoice_id')]);
                            $this->rowdb1 = $db->rowCount();
                        }
                    }
                    if ($hiddenbilling == 0 && $hiddenship > 0 && $this->session->get('customer.default_invoice_id') > 0) {
                        if ((int) $customer_addnew['shipping_is_default'] > 0) {
                            $db->update("customer_data", [
                                "is_default_invoice" => DBDefault::RAW('NULL')
                            ], ['id' => $this->session->get('customer.default_invoice_id')]);
                            $this->rowdb2 = $db->rowCount();
                        }
                        if (($this->rowdb1 <= 0) || ($this->rowdb2 <= 0)) {
                            $this->actionError = 2; /* NO AFFECTED DATA */
                            return false;
                        }
                    }

                    if ($hiddenbilling > 0) {
                        $dbRetBill = $this->insertNewAddr(
                            $customer_id,
                            (string) $customer_addnew['billing_name'],
                            (string) $customer_addnew['billing_surname'],
                            (string) $customer_addnew['billing_birthdate'],
                            (string) $customer_addnew['billing_company_name'],
                            (int) $customer_addnew['billing_country'],
                            (string) $customer_addnew['billing_province'],
                            (string) $customer_addnew['billing_city'],
                            (string) $customer_addnew['billing_zip_code'],
                            (string) $customer_addnew['billing_address'],
                            (string) $customer_addnew['billing_pec'],
                            (string) $customer_addnew['billing_sdi'],
                            '',
                            (string) $customer_addnew['billing_phone1'],
                            (string) $customer_addnew['billing_phone2'],
                            (string) $customer_addnew['billing_vat_number'],
                            (string) $customer_addnew['billing_fiscal_code'],
                            '',
                            0,
                            (int) $customer_addnew['billing_is_default'] ?? 0,
                            (int) $customer_addnew['billing_private'] ?? 0,
                            'billing'
                        );
                        if (!$dbRetBill) {
                            $this->actionError = 2; /* NO AFFCTED DATA*/
                            return false;
                        }
                    }

                    if ($hiddenship > 0) {
                        $dbRetShip = $this->insertNewAddr(
                            $customer_id,
                            (string) $customer_addnew['shipping_name'],
                            (string) $customer_addnew['shipping_surname'],
                            (string) $customer_addnew['shipping_birthdate'],
                            (string) $customer_addnew['shipping_company_name'],
                            (int) $customer_addnew['shipping_country'],
                            (string) $customer_addnew['shipping_province'],
                            (string) $customer_addnew['shipping_city'],
                            (string) $customer_addnew['shipping_zip_code'],
                            (string) $customer_addnew['shipping_address'],
                            (string) $customer_addnew['shipping_pec'],
                            (string) $customer_addnew['shipping_sdi'],
                            '',
                            (string) $customer_addnew['shipping_phone1'],
                            (string) $customer_addnew['shipping_phone2'],
                            (string) $customer_addnew['shipping_vat_number'],
                            (string) $customer_addnew['shipping_fiscal_code'],
                            (string) $customer_addnew['shipping_note'],
                            (int) $customer_addnew['shipping_is_default'] ?? 0,
                            0,
                            (int) $customer_addnew['shipping_private'] ?? 0,
                            'shipping'
                        );
                        if (!$dbRetShip) {
                            $this->actionError = 2; /* NO AFFCTED DATA*/
                            return false;
                        }
                    }
                });

                if ($this->actionError === 0) {
                    /**
                     * Read New Address inserted
                     */
                    $result = $this->readDataCustomer($customer_id);
                } else {
                    throw new \Exception("ERROR_UPDATE", $this->actionError);
                }


            } catch (\Exception $e) {
                $this->log->error('CUSTOMERDATA: updateAddressForCart -> (code) / (message) / (customerid) -> ' . $e->getCode() . ' / ' . $e->getMessage() . ' / ' . $customer_id);
                $this->log->error('CUSTOMERDATA: updateAddressForCart -> (customerdata) ' . $customer_addnew);
                $result = json_encode(
                    array(
                        'success' => 0,
                        'error' => 1,
                        'code' => $e->getCode(),
                        'msg' => $e->getMessage()
                    )
                );
            }
        }

        return $result;
    }

    private function insertNewAddr(
        int $custId,
        string $name = '',
        string $surname = '',
        string $birth = '',
        string $company = '',
        int $countryId = 0,
        string $province = '',
        string $city = '',
        string $zip_code = '',
        string $address = '',
        string $pec = '',
        string $sdi = '',
        string $gps = '',
        string $ph1 = '',
        string $ph2 = '',
        string $vat_num = '',
        string $fiscal_code = '',
        string $shipping_note = '',
        int $ship_def = 0,
        int $invoice_def = 0,
        int $private = 0,
        string $typeAddr = ''
    )
    {

        $result = [];
        $db = $this->db;


        if ((int) $private === 1) {
            if (empty($name)) {
                $this->actionError = 1;
                return false;
            }
            if (empty($surname)) {
                $this->actionError = 2;
                return false;
            }
        } else {
            if (empty($company)) {
                $this->actionError = 3;
                return false;
            }
        }
        if ((int) $countryId == 0) {
            $this->actionError = 4;
            return false;
        }
        if (empty($province)) {
            $this->actionError = 5;
            return false;
        }
        if (empty($city)) {
            $this->actionError = 6;
            return false;
        }
        if (empty($zip_code)) {
            $this->actionError = 7;
            return false;
        }
        if (empty($address)) {
            $this->actionError = 8;
            return false;
        }


        $db->insert(
            "customer_data",
            [
                'customer_id' => $custId,
                'name' => !empty($name) ? $name : DBDefault::RAW('NULL'),
                'surname' => !empty($surname) ? $surname : DBDefault::RAW('NULL'),
                'birthdate' => !empty($birth) ? $birth : DBDefault::RAW('NULL'),
                'company_name' => !empty($company) ? $company : DBDefault::RAW('NULL'),
                'country_id' => $countryId,
                'province' => $province,
                'city' => $city,
                'zip_code' => $zip_code,
                'address' => $address,
                'pec' => $pec,
                'sdi' => $sdi,
                'location_gps' => $gps,
                'phone1' => !empty($ph1) ? $ph1 : DBDefault::RAW('NULL'),
                'phone2' => !empty($ph1) ? $ph2 : DBDefault::RAW('NULL'),
                'vat_number' => !empty($vat_num) ? $vat_num : DBDefault::RAW('NULL'),
                'fiscal_code' => !empty($fiscal_code) ? $fiscal_code : DBDefault::RAW('NULL'),
                'shipping_note' => !empty($shipping_note) ? $shipping_note : DBDefault::RAW('NULL'),
                'is_default_ship' => (($ship_def != 0) ? $ship_def : DBDefault::RAW('NULL')),
                'is_default_invoice' => (($invoice_def != 0) ? $invoice_def : DBDefault::RAW('NULL')),
                'is_private' => $private
            ]
        );
        $newId = $db->id();

        if ($newId <= 0) {
            $this->actionError = 2;
            return false;
        } /* NO_AFFECTED_DATA */

        if ($typeAddr == 'billing') {
            $this->session->set('customer.default_invoice_id', (int) $newId);
        } else {
            $this->session->set('customer.default_ship_id', (int) $newId);
        }

        if ($this->actionError === 0) {
            return true;
        } else {
            return false;
        }

    }

}