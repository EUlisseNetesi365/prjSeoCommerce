<?php

namespace Modules\customer\Classes;

use Odan\Session\SessionInterface;
use PerSeo\DB;
use Modules\customer\Classes\CustomerData;

class Login
{
	protected $db;
	protected $cookie;
	protected $container;
	protected $session;
    protected $defaultAddresses;

	public function __construct(DB $database, SessionInterface $session)
	{
		$this->session = $session;
		$this->db = $database;
	}

	public function verify(string $user_email, string $pass )
	{
		try {
			if (empty($user_email) || empty($pass)) {
				throw new \Exception('MISSING_PARAMETERS', 001);
			}
			if (strpos($user_email, '@') === false) {
				if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]+$/', $user_email)) {
					throw new \Exception('USERNAME_NOT_VALID', 002);
				}
				$login_info = $this->db->select('customers', [
					"[><]customer_emails" => ["customers.id" => "customer_id"],
					"[><]emails" => ["customer_emails.email_id" => "id"],
                    "[>]customer_mkt_chan_pivot" => ["customers.id" => "customer_id"]
				], [
						'customers.id',
						'customers.user',
						'customers.password',
						'customer_mkt_chan_pivot.mkt_chan_id',
						'customer_emails.confirmed'
					], [
						'customers.user' => $user_email,
						'customer_emails.is_login' => 1
					]);

				if (empty($login_info)) {
					throw new \Exception("USR_PASS_ERR", 004);
				}
				$error = isset($this->db->error) ? $this->db->error : null;
                if ($error != null) {
                    if (($error[1] != null) && ($error[2] != null)) {
                        throw new \Exception($error[2], 1);
                    }
                }
				if ($login_info[0]['confirmed'] == 0) {
					throw new \Exception("EMAIL_NOT_CONFIRMED", 005);
				}
				if (password_verify($pass, $login_info[0]['password'])) {
					$this->session->set('customer.login', true);
					$this->session->set('customer.id', (int) $login_info[0]['id']);
					$this->session->set('customer.user', (string) $login_info[0]['user']);
					if (!$login_info[0]['mkt_chan_id']) {
                        $this->session->set('customer.mkt_chan', 0);
                    } else {
                        $this->session->set('customer.mkt_chan', (int) $login_info[0]['mkt_chan_id']);
                    }

				} else {
					throw new \Exception("USR_PASS_ERR", 004);
				}
			} else {
				if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $user_email)) {
					throw new \Exception('EMAIL_NOT_VALID', 003);
				}
				$login_info = $this->db->select('emails', [
					"[><]customer_emails" => ["emails.id" => "email_id"],
					"[><]customers" => ["customer_emails.customer_id" => "id"],
                    "[>]customer_mkt_chan_pivot" => ["customers.id" => "customer_id"]
				], [
						'customers.id',
						'customers.user',
                        'customer_mkt_chan_pivot.mkt_chan_id',
						'customers.password'
					], [
						'emails.value' => $user_email,
						'customer_emails.confirmed' => 1,
						'customer_emails.is_login' => 1
					]);

                if (empty($login_info)) {
					throw new \Exception("USR_PASS_ERR", 004);
				}
				$error = $this->db->error;
				if (($error[1] != null) && ($error[2] != null)) {
					throw new \Exception($error[2], 1);
				}

				if (password_verify($pass, $login_info[0]['password'])) {
					$this->session->set('customer.login', true);
					$this->session->set('customer.id', (int) $login_info[0]['id']);
					$this->session->set('customer.user', (string) $login_info[0]['user']);
                    if (!$login_info[0]['mkt_chan_id']) {
                        $this->session->set('customer.mkt_chan', 0);
                    } else {
                        $this->session->set('customer.mkt_chan', (int) $login_info[0]['mkt_chan_id']);
                    }

				} else {
					throw new \Exception("USR_PASS_ERR", 004);
				}
			}

			$result = array(
				'success' => 1,
				'error' => 0,
				'code' => '0',
				'msg' => 'OK'
			);
		} catch (\Exception $e) {
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