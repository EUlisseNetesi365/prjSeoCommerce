<?php

namespace Modules\customer\Classes;

use Odan\Session\SessionInterface;
use PerSeo\DB;

class Logout
{
	protected $db;
	protected $cookie;
	protected $container;
	protected $session;

	public function __construct(DB $database, SessionInterface $session)
	{
		$this->session = $session;
		$this->db = $database;
	}

	public function clear()
	{
 		$this->session->clear();
 	}
}