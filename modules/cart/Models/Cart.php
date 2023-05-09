<?php

namespace Modules\cart\Models;


use Modules\customer\Classes\CustomerData;
use PerSeo\DB\DBDefault;
use Odan\Session\SessionInterface;
use Psr\Container\ContainerInterface;
use PerSeo\Validator;
use Psr\Log\LoggerInterface;


class Cart
{

    protected DBDefault $db;
    protected SessionInterface $session;
    protected string $cart_code;
	protected int $cart_id;
	protected int $customer_id;
	protected int $customer_shipping_id;
	protected int $customer_invoice_id;
	protected int $market_channel_id = 1;
	protected ContainerInterface $container;
    protected int $actionError;
    protected LoggerInterface $log;

    /**
     * CartCheckout constructor.
     * @param DBDefault $database
     * @param ContainerInterface $container
     * @param SessionInterface $session
     * @param LoggerInterface $logger
     */
    public function __construct(DBDefault $database, ContainerInterface $container, SessionInterface $session, LoggerInterface $logger) {

        $this->container = $container;
        $this->db = $database;
        $this->session = $session;
		$this->cart_code = (string) ($session->has('cart.code') ? $session->get('cart.code') : '');
		$this->cart_id = (int) ($session->has('cart.id') ? $session->get('cart.id') : 0);
		$this->customer_id = (int) ($session->has('customer.id') ? $session->get('customer.id') : 0);
		$this->customer_shipping_id = (int) ($session->has('customer.default_ship_id') ? $session->get('customer.default_ship_id') : 0);
		$this->customer_invoice_id = (int) ($session->has('customer.default_invoice_id') ? $session->get('customer.default_invoice_id') : 0);
		$this->log = $logger;

    }
	
	public function getStatusIdByValue($value = ''){

		$db = $this->db;
        $status = $db->get('carts_status', 'id', ['value' => $value]);
        if ($status) return $status;
        else throw new \Exception('STATUS NOT FOUND',1);

    }
	
	protected function CreateCart(string $country = 'it') {
        $db = $this->db;
        try {
			$country_id = $db->get("countries", [
				"id"
			], [
				"value" => DBDefault::raw('UPPER(:country)', [
					':country' => $country
				])
			]);
			$country_id = (int) $country_id['id'];
            $db->insert("carts_checkout", [
                "cart_code" => DBDefault::raw('UUID()'),
                "country_id" => $country_id,
                "market_channel_id" => $this->market_channel_id,
                "status_id" => $this->getStatusIdByValue('created'),
                "creation_date" => DBDefault::raw('NOW()')
            ]);
            $cart_id = (int) $db->id();
            $cartCode = $db->get('carts_checkout', 'cart_code', ['id' => $cart_id]);
			$this->cart_id = $cart_id;
			$this->cart_code = $cartCode;
            $this->session->set('cart.id', $cart_id);
            $this->session->set('cart.code', $cartCode);
        } catch (\Exception $e){
            $cart_id = 0;
        }
        return $cart_id;
    }


	public function AddItem(int $v4warehouses_id = 0, float $quantity = 0.00, string $custcolor = '#FFFFFF', string $language = 'it', string $country = 'it') {
		$db = $this->db;
		$this->actionError = 0;
		try {
			if ($this->cart_id == 0) {
				$cart_id = $this->CreateCart($country);
			}
			else {
				$cart_id = $this->cart_id;
			}
			if ($cart_id <= 0) { throw new \Exception("INVALID_CART_ID",001); }
			$db->action(function($db) use ($quantity, $cart_id, $v4warehouses_id, $custcolor, $country) {
                $checkquery = $db->query("SELECT VARIANT_QUANTITY(:id,:country) as quantity",[":id"=>$v4warehouses_id , ":country"=>$country]);
                $quantityquery = $checkquery->fetch(\PDO::FETCH_OBJ);
				$checkquery->closeCursor();
                if ($quantityquery->quantity == '0.00' || $quantityquery->quantity < $quantity ) {
                   $this->actionError = 6; /* NO_AV_QUANTITY */
                   return false;
                }
                $db->query(
				"INSERT IGNORE INTO <cart_variant_4whouses> (cart_id, v4warehouses_id, quantity, variants_attributes_custom_values) VALUES (:cart_id, :v4warehouses_id, :quantity, :custcolor) ON DUPLICATE KEY UPDATE quantity=quantity + :quantity, variants_attributes_custom_values= :custcolor ", [
					":cart_id" => $cart_id,
					":v4warehouses_id" => $v4warehouses_id,
					":quantity" => $quantity,
                    ":custcolor" => $custcolor
				]
				);
            });

			if ($this->actionError != 0){
                throw new \Exception("NO_AV_QUANTITY",$this->actionError);
            }

		} catch (\Exception $e){
			$result = array(
                'success' => 0,
                'error' => 1,
				'data' => NULL,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
			return json_encode($result);
        }
		return $this->view($language, $country);
	}
	
	public function EditItem(int $v4warehouses_id = 0, int $quantity = 0, string $language = 'it', string $country = 'it') {
		$db = $this->db;
        $this->actionError = 0;
		try {
			if ($this->cart_id == 0) {
				$cart_id = $this->CreateCart($country);
			}
			else {
				$cart_id = $this->cart_id;
			}
			if ($cart_id <= 0) { throw new \Exception("INVALID_CART_ID",001); }
			$db->action(function($db) use ($quantity, $cart_id, $v4warehouses_id) {
                $checkquery = $db->query("SELECT VARIANT_QUANTITY(:id,:country) as quantity",[":country"=>'it',":id"=>$v4warehouses_id]);
                $quantityquery = $checkquery->fetch(\PDO::FETCH_OBJ);
				$checkquery->closeCursor();
				if ($quantityquery->quantity == '0.00'  || $quantityquery->quantity < $quantity) {
				    $this->actionError = 6; /* NO_AV_QUANTITY */
				    return false;
                }
                $db->query(
				"INSERT IGNORE INTO <cart_variant_4whouses> (cart_id, v4warehouses_id, quantity) VALUES (:cart_id, :v4warehouses_id, :quantity) ON DUPLICATE KEY UPDATE quantity=:quantity", [
					":cart_id" => $cart_id,
					":v4warehouses_id" => $v4warehouses_id,
					":quantity" => $quantity
				]
				);
            });

			if ($this->actionError != 0) {
                throw new \Exception("NO_AV_QUANTITY",$this->actionError);
            }

		} catch (\Exception $e){
			$result = array(
                'success' => 0,
                'error' => 1,
				'data' => NULL,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
			return json_encode($result);
        }
		return $this->view($language, $country);
	}	
	
    public function DelItem(int $v4warehouses_id = 0, string $language = 'it', string $country = 'it') {

        $db = $this->db;
        try {
			if (($this->cart_id == 0) || ($v4warehouses_id <= 0)) { throw new \Exception("INVALID_REQUEST-". $this->cart_id,001); }
			$cart_id = $this->cart_id;
            $db->delete("cart_variant_4whouses", ['cart_id' => $cart_id, 'v4warehouses_id' => $v4warehouses_id]);
            $totVarint =  $db->count("cart_variant_4whouses", ['cart_id'], [ 'cart_id' => $cart_id]);

            if($totVarint == 0){
                $db->delete("carts_checkout", ['id' => $cart_id]);
				$this->session->remove('cart.code');
                $this->session->remove('cart.id');
				$this->cart_id = 0;
				$this->cart_code = '';
            }
			else {
				return $this->view($language, $country);
			}
			$result = array(
                'success' => 1,
                'error' => 0,
                'code' => '',
                'msg' => 'OK',
                'items' => []
            );
        } catch (\Exception $e){
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);
    }	
	
	public function view(string $language = 'it', string $country = 'it') {
		try {
			$cartData = array();
			//var_dump($this->cart_code);die();
			if (!empty($this->cart_code)) {
				$db = $this->db;
				$query = $db->query("CALL cart_by_code(:lang,:country,:cart_code);", [
					":lang" => $language,
					":country" => $country,
					":cart_code" => $this->cart_code
				]);
				$cartData = $query->fetchAll(\PDO::FETCH_ASSOC);
				$query->closeCursor();
			}
			$result = array(
                'success' => 1,
                'error' => 0,
				'items' => $cartData,
				'shipping' => (!empty($cartData) ? $cartData[0]['shipping'] : '0.00'),
				'subtotal' => (!empty($cartData) ? $cartData[0]['subtotal'] : '0.00'),
				'total' => (!empty($cartData) ? $cartData[0]['total'] : '0.00'),
                'code' => 0,
                'msg' => 'OK'
            );
		} catch (\Exception $e){
            $result = array(
                'success' => 0,
                'error' => 1,
				'data' => NULL,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);
	}



    public function AddCustomer(string $customer_info = '{}') {
        $db = $this->db;
        $this->actionError = 0;
        try {

			if (empty($this->cart_id)) { throw new \Exception("INVALID_REQUEST",001); }

			$cart_id = $this->cart_id;
            $customer_id = $this->customer_id;
			$customer_shipping_id = $this->customer_shipping_id;
			$customer_invoice_id = $this->customer_invoice_id;
            $db->action(function($db) use ($customer_id, $customer_shipping_id, $customer_invoice_id, $customer_info, $cart_id) {
                $data = $db->update("carts_checkout", [
                    "customer_id" => (($customer_id > 0) ? $customer_id : DBDefault::raw('NULL')),
                    "customer_shipping_id" => (($customer_shipping_id > 0) ? $customer_shipping_id : DBDefault::raw('NULL')),
                    "customer_invoice_id" => (($customer_invoice_id > 0) ? $customer_invoice_id : DBDefault::raw('NULL')),
                    "customer_info" => $customer_info
                ],['id' =>$cart_id ]);

				if ($data->rowCount() <= 0) {
				    $this->actionError = 1;
				    return false;
				}
            });

            if ($this->actionError != 0) {
                throw new \Exception("INVALID_REQUEST",$this->actionError);
            }


            $result = array(
                'success' => 1,
                'error' => 0,
                'code' => '0',
                'msg' => 'OK'
            );
        }catch (\Exception $e){
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);
    }
	
	public function ConfirmOrder(int $type = 1, string $paypal_order_id = '') {
        $db = $this->db;
        $this->actionError = 0;

        try {
			if (empty($this->cart_id)) { throw new \Exception("INVALID_REQUEST",001); }
			$cart_id = $this->cart_id;
			$customer_shipping_id = $this->customer_shipping_id;
			$customer_invoice_id = $this->customer_invoice_id;
			$status_id = (int) $this->getStatusIdByValue('waiting');
            $db->action(function($db) use ($status_id, $cart_id) {
                $data = $db->update("carts_checkout", [
                    "status_id" => $status_id
                ],['id' =>$cart_id ]);
				if ($data->rowCount() <= 0) {
				    $this->actionError = 1;
				    return false;
				}
            });

            if ($this->actionError != 0) {
                throw new \Exception("INVALID_REQUEST",$this->actionError);
            }

            $result = array(
                'success' => 1,
                'error' => 0,
                'code' => '0',
                'msg' => 'OK'
            );
        }catch (\Exception $e){
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
     * @param $lang
     * @param $uriHost
     * @param $customer_data_info
     * @return false|string
     * Get Order Data for email to send
     */
    public function getOrderDataEmail($lang, $uriHost, $customer_data_info) {

        $db = $this->db;
        $cart_code = $this->cart_code;
        $customer_id = $this->customer_id;
        $customer_shipping_id = $this->customer_shipping_id;
        $customer_invoice_id = $this->customer_invoice_id;

        $customer_data = json_decode($customer_data_info, true);
        $shipping_add_info = $customer_data['hidden_ship'];
        $billing_add_info = $customer_data['hidden_billing'];
        $email = '';
        $items = [];
        $name = '';
        $surname = '';
        $company_name = '';
        $shippingAddress = [];
        $billingAddress = [];
        $cartTel = '';
        $cartTel2 = '';

        $query = $db->query("CALL cart_by_code(:lang,:country,:cart_code);", [
            ":lang" => $lang,
            ":country" => $lang,
            ":cart_code" => $cart_code
        ]);
        $cartData = $query->fetchAll(\PDO::FETCH_ASSOC);
        $query->closeCursor();

        if($customer_id > 0 ){

            /**
             * Prepare date to send for registered customer
             * adding address data and read new id
             * read address data and id selected
             * add id into session variables
             * Function Class into \customer\CustomerData
             */

            $customerReg = new CustomerData($this->db, $this->container, $this->session, $this->log);
            if($customer_id != 0 && ($shipping_add_info != 0 || $billing_add_info != 0)){
                $customer_addnew = $customer_data;
                $customer_data = json_decode($customerReg->updateAddressForCart($shipping_add_info, $billing_add_info, $customer_addnew), true);
            } elseif ($customer_id != 0  && ($shipping_add_info == 0 && $billing_add_info == 0)){
                $customer_data = json_decode($customerReg->readDataCustomer($customer_id), true);
            }

            $dataRead = $customer_data;
            $dataReadCust = $dataRead['userdata'];
            $emails = json_decode( $dataReadCust[0]['emails'], true);
            $data = json_decode( $dataReadCust[0]['data'], true);

            $email= $emails[0]['address'];
            $name= $data[0]['name'];
            $surname= $data[0]['surname'];
            $regShippingAddress = [];
            $regBillingAddress = [];

            foreach($data as $customerInfo){
                  if($customerInfo['id'] == $this->session->get('customer.default_ship_id')){
                     $regShippingAddress = [
                        'csname' => $customerInfo['name'],
                        'cssurname' => $customerInfo['surname'],
                        'cscompany' => $customerInfo['company_name'],
                        'csaddr' =>  $customerInfo['address'],
                        'cscity' => $customerInfo['city'],
                        'cszip' => $customerInfo['zip_code'],
                        'csprov' => $customerInfo['province'],
                        'csvat' => $customerInfo['vat_number'],
                        'csfcode'=> $customerInfo['fiscal_code'],
                        'cspec' => $customerInfo['pec'],
                        'csid' =>  $customerInfo['sdi'],
                        'cstel' => $customerInfo['phone1'],
                        'cstel2' => $customerInfo['phone2'],
                        'csnote' => $customerInfo['shipping_note'],
                        'cscurrency' => $customerInfo['country_value']
                    ];
                }

                if($customerInfo['id'] == $this->session->get('customer.default_invoice_id')){
                    $regBillingAddress = [
                        'cbname' => $customerInfo['name'],
                        'cbsurname' => $customerInfo['surname'],
                        'cbcompany' => $customerInfo['company_name'],
                        'cbaddr' =>  $customerInfo['address'],
                        'cbcity' => $customerInfo['city'],
                        'cbzip' => $customerInfo['zip_code'],
                        'cbprov' => $customerInfo['province'],
                        'cbvat' => $customerInfo['vat_number'],
                        'cbfcode'=> $customerInfo['fiscal_code'],
                        'cbpec' => $customerInfo['pec'],
                        'cbid' =>  $customerInfo['sdi'],
                        'cbmail' =>  isset($customerInfo['email']) ? $customerInfo['email'] : '' ,
                        'cbtel' => $customerInfo['phone1'],
                        'cbtel2' => $customerInfo['phone2'],
                        'cbcurrency' => $customerInfo['country_value']
                    ];
                    $company_name = $customerInfo['company_name'];
                }
            }

            $shippingAddress = $regShippingAddress;
            if($this->session->get('customer.default_invoice_id')){
                $billingAddress = $regBillingAddress;
            }
        } else {
            /**
             * Send mail to anonymous customer
             *
             */

            $customer = $customer_data;
            $shipping_is_private = (int)$customer['shipping_is_private'];

            $cartEmail = $customer['shipping_email'];
            $cartName = $customer['shipping_name'];
            $cartSurname = $customer['shipping_surname'];
            $cartTel = $customer['shipping_phone1'];
            $cartTel2 = $customer['shipping_phone2'];
            //$shipping_company_name = !empty($customer['shipping_company_name'])? $customer['shipping_company_name'] : '';
            //$billing_company_name = !empty($customer['billing_company_name'])? $customer['billing_company_name'] : $shipping_company_name;

            $shippingCartAddress = [
                'csname' => $cartName,
                'cssurname' => $cartSurname,
                'cscompany' => $customer['shipping_company_name'],
                'csaddr' =>  $customer['shipping_address'],
                'cscity' => $customer['shipping_city'],
                'cszip' => $customer['shipping_zip_code'],
                'csprov' => $customer['shipping_province'],
                'csvat' => $customer['shipping_vat_number'],
                'csfcode'=> $customer['shipping_fiscal_code'],
                'cspec' => $customer['shipping_pec'],
                'csid' =>  $customer['shipping_sdi'],
                'csmail' =>  $customer['shipping_email'],
                'cstel' => $customer['shipping_phone1'],
                'cstel2' => $customer['shipping_phone2'],
                'csnote' => $customer['shipping_note']
            ];


            if(trim($customer['billing_address']) != ''){

                $billingCartAddress = [
                    'cbname' => $customer['billing_name'],
                    'cbsurname' => $customer['billing_surname'],
                    'cbcompany' => $customer['billing_company_name'],
                    'cbaddr' =>  $customer['billing_address'],
                    'cbcity' => $customer['billing_city'],
                    'cbzip' => $customer['billing_zip_code'],
                    'cbprov' => $customer['billing_province'],
                    'cbvat' => $customer['billing_vat_number'],
                    'cbfcode'=> $customer['billing_fiscal_code'],
                    'cbpec' => $customer['billing_pec'],
                    'cbid' =>  $customer['billing_sdi'],
                    'cbmail' =>  $customer['billing_email'],
                    'cbtel' => $customer['billing_phone1'],
                    'cbtel2' => $customer['billing_phone2']
                ];

            } else {
                $billingCartAddress = [];
            }

            $email= $cartEmail;
            $name= $cartName;
            $surname= $cartSurname;
            $shippingAddress = $shippingCartAddress;
            $billingAddress = $billingCartAddress;

        }

        /**
         * Common Data fo Logged and Anonymous
         */
        $shippingFees = '';
        $total = '';
        $cursym = json_decode($cartData[0]['secondary_attributes'], true);

        foreach($cartData as $item){
            $items [] = $item['short_description'] . ' quantità ' . $item['quantity'] .' prezzo '. number_format($item['single_price'],2,',', '.') . ' ' . $cursym[0]['currency_value'] . ' Tot ' . number_format($item['total_price'], 2,',', '.') . ' ' . $cursym[0]['currency_value'];
            $shippingFees = $item['shipping'] . ' ' . $cursym[0]['currency_value'];
            $total = $item['total'] . ' ' . $cursym[0]['currency_value'];
        }


        $result = [
            'success' => 1,
            'error' => 0,
            'code' => '0',
            'msg' => 'OK',
            'uriHostLink' => $uriHost['uriHostLink'],
            'uriHostImg' => $uriHost['uriHostImg'],
            'cartItems' => $items,
            'shippingFees' => $shippingFees,
            'total' => $total,
            'company_name' => $company_name,
            'name' => $name,
            'surname' => $surname,
            'shippingAddress' => $shippingAddress,
            'billingAddress' => $billingAddress,
            'email' => $email,
            'cartel' => $cartTel,
            'cartel2' => $cartTel2,
        ];

        return json_encode($result);

    }

    public function stepCartStatus($typestatus) {

        $db = $this->db;
        $this->actionError = 0;
        $cartId = $this->session->get('cart.id');
        $this->actionError = 0;

        try {
            if (empty($cartId)) { throw new \Exception("UPD_STATUS_NO_CARTID",001); }

            $status_id = (int) $this->getStatusIdByValue($typestatus);

            $db->action(function($db) use ($status_id, $cartId) {
                $data = $db->update("carts_checkout", [
                    "status_id" => $status_id
                ],['id' => $cartId ]);
                if ($data->rowCount() <= 0) { $this->actionError = 2; return false; }

            });

            if ($this->actionError === 0) {
                $this->session->remove('cart.code');
                $this->session->remove('cart.id');
            } else {
                throw new \Exception("UPD_STATUS_ERROR",$this->actionError);
            }

            $result = array(
                'success' => 1,
                'error' => 0,
                'code' => '0',
                'msg' => 'OK'
            );
        }catch (\Exception $e){
            $result = array(
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            );
        }
        return json_encode($result);

    }

}