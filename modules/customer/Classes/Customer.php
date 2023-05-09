<?php

namespace Modules\customer\Classes;

use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Psr\Log\LoggerInterface;
use Slim\App;
use Psr\Container\ContainerInterface;
use PerSeo\Validator;

class Customer
{

    protected App $app;
    protected DBDefault $db;
    protected SessionInterface $session;
    protected ContainerInterface $container;
    protected LoggerInterface $log;
    protected Validator $validator;
    protected int $actionError;
    protected int $custEmailId;
    protected bool $confirmCode;
    protected $recoverPassword;
    protected $addmail;


    public function __construct(DBDefault $database, ContainerInterface $container, SessionInterface $session)
    {
        $this->container = $container;
        $this->db = $database;
        $this->session = $session;
        $this->validator = new Validator();

    }

    public function create(string $email, string $username, string $password)
    {
        $db = $this->db;
        $this->custEmailId = 0;
        $this->actionError = 0;

        try {
            if (empty($email) || empty($username) || empty($password)) {
                throw new \Exception('MISSING_PARAMETERS', 001);
            }
            if (!preg_match('/^[a-zA-Z]+[a-zA-Z0-9_]+$/', $username)) {
                throw new \Exception('USERNAME_NOT_VALID', 002);
            }
            if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) {
                throw new \Exception('EMAIL_NOT_VALID', 003);
            }
            $dbemail = $db->select("emails", [
                "[>]customer_emails" => ["emails.id" => "email_id"],
                "[>]customers" => ["customer_emails.customer_id" => "id"]
            ], [
                    "emails.id(email_id)",
                    "emails.value(address)",
                    "customer_emails.customer_id(customer_id)",
                    "customer_emails.confirmed(confirmed)",
                    "customers.user(user)"
                ], [
                    'emails.value' => $email,
                ]);

            $db->action(function ($db) use ($dbemail, $email, $username, $password) {
                if (!$dbemail) {
                    $db->insert("emails", [
                        "value" => $email
                    ]);
                    $emailId = $db->id();
                    $db->insert("customers", [
                        "user" => $username,
                        "password" => $this->cryptPassword($password)
                    ]);
                    $userId = $db->id();
                    if ($userId <= 0) {
                        return false;
                    }
                    $db->insert("customer_emails", [
                        "customer_id" => $userId,
                        "email_id" => $emailId,
                        "confirmed" => 0,
                        "is_login" => 1,
                        "confirmation_code" => DBDefault::RAW('UUID()'),
                        "creation_date" => DBDefault::raw('NOW()')
                    ]);
                    $this->custEmailId = $db->id();
                    if ($this->custEmailId <= 0) {
                        $this->actionError = 2;
                        return false;
                    }
                } else {
                    if ($dbemail[0]['customer_id'] == NULL) {
                        $db->insert("customers", [
                            "user" => $username,
                            "password" => $this->cryptPassword($password)
                        ]);
                        $userId = $db->id();
                        if ($userId <= 0) {
                            $this->actionError = 2;
                            return false;
                        }
                        $db->insert("customer_emails", [
                            "customer_id" => $userId,
                            "email_id" => $dbemail[0]['email_id'],
                            "confirmed" => 0,
                            //DEV
                            "is_login" => 1,
                            "confirmation_code" => DBDefault::raw('UUID()'),
                            "creation_date" => DBDefault::raw('NOW()')
                        ]);
                        $this->custEmailId = $db->id();
                        if ($this->custEmailId <= 0) {
                            $this->actionError = 2;
                            return false;
                        }
                    } else {
                        if ($dbemail[0]['confirmed'] == 0) {
                            $this->actionError = 4;
                        } else {
                            $this->actionError = 5;
                        }

                    }
                }
            });

            if ($this->actionError === 0) {
                /**
                 * Code for confirmation email
                 */
                $code = $this->db->get("customer_emails", 'confirmation_code', ['id' => $this->custEmailId]);

                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => '0',
                    'msg' => 'OK',
                    'username' => $username,
                    'email' => $email,
                    'confirm' => $code
                ];
            } else {
                throw new \Exception('ERROR_CREATE', $this->actionError);
            }

        } catch (\Exception $e) {
            //LOG THIS
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        return json_encode($result);
    }



    protected function cryptPassword($password)
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * @param int $custId
     * @param string|string $password
     * @param string $confirm
     * @return false|string
     * Modify Password Customer
     */

    public function updatePasswordCustomer(int $custId, string $password = '', string $confirm = '')
    {

        $db = $this->db;
        $this->actionError = 0;

        try {

            if (empty($password)) {
                throw new \Exception('PWD_EMPTY', '007');
            }

            if (empty($confirm)) {

                $data = $db->update("customers", [
                    "password" => $this->cryptPassword($password)
                ], ['id' => $custId]);

                $countData = $data->rowCount();

                if ($countData <= 0) {
                    throw new \Exception('NO_AFFECTED_DATA', '002');
                }

            } else {

                $dbconfirm = $db->select("emails", [
                    "[>]customer_emails" => ["emails.id" => "email_id"],
                    "[>]customers" => ["customer_emails.customer_id" => "id"]
                ], [
                        "emails.id(email_id)",
                        "emails.value(address)",
                        "customer_emails.id(id)",
                        "customer_emails.customer_id(customer_id)",
                        "customer_emails.confirmed(confirmed)",
                        "customer_emails.is_login(is_login)",
                        "customer_emails.confirmation_code(confirmation_code)",
                        "customers.user(user)"
                    ], [
                        'customer_emails.confirmation_code' => $confirm,
                    ]);

                if (!$dbconfirm) {
                    throw new \Exception('NO_AFFECTED_DATA', '002');
                }

                $db->action(function ($db) use ($dbconfirm, $confirm, $password) {

                    $data = $db->update("customers", [
                        "password" => $this->cryptPassword($password)
                    ], ['id' => $dbconfirm[0]['customer_id']]);

                    $countData = $data->rowCount();

                    if ($countData <= 0) {
                        $this->actionError = 2;
                        return false;
                    }

                    $removecode = $this->db->update(
                        "customer_emails",
                        [
                            'confirmation_code' => DBDefault::RAW('NULL')
                        ],
                        [
                            'id' => $dbconfirm[0]['id']
                        ]
                    );
                    if ($removecode->rowCount() <= 0) {
                        $this->actionError = 2;
                        return false;
                    }
                });

            }

            if ($this->actionError != 0) {
                throw new \Exception('ERROR_CREATE', $this->actionError);
            } else {
                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => 0,
                    'msg' => 'OK'
                ];
            }

        } catch (\Exception $e) {
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        return json_encode($result);

    }


    public function recoverPasswordCustomer(string $email)
    {
        $db = $this->db;
        $this->actionError = 0;
        $this->recoverPassword = [];

        try {
            if (empty($email) || !preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) {
                throw new \Exception('EMAIL_NOT_VALID', 003);
            }

            $dbemail = $db->select("emails", [
                "[>]customer_emails" => ["emails.id" => "email_id"],
                "[>]customers" => ["customer_emails.customer_id" => "id"]
            ], [
                    "emails.id(email_id)",
                    "emails.value(address)",
                    "customer_emails.id(id)",
                    "customer_emails.customer_id(customer_id)",
                    "customer_emails.confirmed(confirmed)",
                    "customer_emails.is_login(is_login)",
                    "customer_emails.confirmation_code(confirmation_code)",
                    "customers.user(user)"
                ], [
                    'emails.value' => $email,
                ]);

            if (!$dbemail) {
                throw new \Exception('EMAIL_NOT_VALID', 003);
            }
            if ((int) $dbemail[0]['is_login'] <= 0) {
                throw new \Exception('EMAIL_NOT_LOGIN', 005);
            }
            if ((int) $dbemail[0]['confirmed'] <= 0) {
                throw new \Exception('EMAIL_NOT_CONFIRMED', 004);
            }
            $db->action(function ($db) use ($dbemail, $email) {

                $data = $db->update(
                    "customer_emails",
                    [
                        "confirmation_code" => DBDefault::RAW('UUID()')
                    ],
                    [
                        'email_id' => $dbemail[0]['email_id'],
                        'customer_id' => $dbemail[0]['customer_id']
                    ]
                );
                $countData = $data->rowCount();
                if ($countData <= 0) {
                    $this->actionError = 2;
                    return false;
                }
                $code = $db->get(
                    "customer_emails",
                    'confirmation_code',
                    [
                        'id' => $dbemail[0]['id']
                    ]
                );
                if (!$code) {
                    $this->actionError = 2;
                    return false;
                }

                $this->recoverPassword = [
                    'success' => 1,
                    'error' => 0,
                    'code' => '0',
                    'msg' => 'OK',
                    'username' => $dbemail[0]['user'],
                    'email' => $email,
                    'confirm' => $code
                ];

            });

            if ($this->actionError === 0) {

                $result = $this->recoverPassword;

            } else {
                throw new \Exception('ERROR_CREATE', $this->actionError);
            }

        } catch (\Exception $e) {

            $result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        return json_encode($result);
    }


    /**
     *
     *
     *
     * SECTION EMAIL CUSTOMER
     *
     *
     *
     *
     */

    public function resendEmailConfirm($email)
    {
        $db = $this->db;

        try {
            if (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $email)) {
                throw new \Exception('EMAIL_NOT_VALID', 003);
            }
            $dbemail = $db->select("emails", [
                "[>]customer_emails" => ["emails.id" => "email_id"],
                "[>]customers" => ["customer_emails.customer_id" => "id"]
            ], [
                    "emails.id(email_id)",
                    "emails.value(address)",
                    "customer_emails.id(id)",
                    "customer_emails.customer_id(customer_id)",
                    "customer_emails.confirmed(confirmed)",
                    "customer_emails.confirmation_code(confirmation_code)",
                    "customers.user(user)"
                ], [
                    'emails.value' => $email,
                ]);

            if ($dbemail) {
                if ($dbemail[0]['confirmed'] === 0) {
                    $result = [
                        'success' => 1,
                        'error' => 0,
                        'code' => '0',
                        'msg' => 'OK',
                        'username' => $dbemail[0]['user'],
                        'email' => $dbemail[0]['address'],
                        'confirm' => $dbemail[0]['confirmation_code']
                    ];
                } else {
                    throw new \Exception('ALREADY_CONFIRMED', 004);
                }

            } else {
                throw new \Exception('EMAIL_NOT_VALID', 003);
            }

        } catch (\Exception $e) {
            json_encode($result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ]);
        }
        return json_encode($result);
    }


    public function emailConfirm($code)
    {
        $db = $this->db;
        $this->confirmCode = false;

        try {

            $db->action(function ($db) use ($code) {

                $confirm = $this->db->get(
                    'customer_emails',
                    'confirmed',
                    [
                        'confirmation_code' => $code
                    ]
                );

                if ($confirm <= 0) {
                    $data = $this->db->update(
                        "customer_emails",
                        [
                            'confirmed' => 1,
                            'confirmation_code' => DBDefault::raw('NULL'),
                            'confirmation_date' => DBDefault::raw('NOW()')
                        ],
                        [
                            'confirmed' => 0,
                            'confirmation_code' => $code
                        ]
                    );
                    if ($data->rowCount() > 0) {
                        $this->confirmCode = true;
                    }

                } else {
                    $this->confirmCode = false;
                }
            });

            return $this->confirmCode;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function passwordRecover($code)
    {
        $db = $this->db;

        try {

            $db->action(function ($db) use ($code) {

                $confirm = $this->db->get(
                    'customer_emails',
                    'confirmed',
                    [
                        'confirmation_code' => $code
                    ]
                );

                if (!$confirm) {
                    $this->confirmCode = false;
                } else {
                    $this->confirmCode = true;
                }
            });
            return $this->confirmCode;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $custId
     * @param $emailnew
     * @param $emailold
     * @return false|string
     * Add new Email Customer
     */
    public function createMailAdditional(int $custId, string $emailnew = '')
    {

        $db = $this->db;
        $this->custEmailId = 0;
        $this->actionError = 0;
        $this->addMail = [];

        try {
            if (!$this->validator->isEmail($emailnew)) {
                throw new \Exception('EMAIL_NOT_VALID', '003');
            }
            // check if email already exist
            $dbemailnew = $db->select("emails", [
                "[>]customer_emails" => ["emails.id" => "email_id"],
                "[>]customers" => ["customer_emails.customer_id" => "id"]
            ], [
                    "emails.id(email_id)",
                    "emails.value(address)",
                    "customer_emails.customer_id(customer_id)",
                    "customer_emails.confirmed(confirmed)",
                    "customer_emails.is_login(is_login)",
                    "customers.user(user)"
                ], [
                    'emails.value' => $emailnew,
                ]);

            $emailId = (int) (!empty($dbemailnew) ? $dbemailnew[0]['email_id'] : 0);
            $emailconfirm = (int) (!empty($dbemailnew) ? $dbemailnew[0]['confirmed'] : 0);

            $db->action(function ($db) use ($emailId, $emailnew, $custId, $emailconfirm, $dbemailnew) {
                if ($emailId == 0) {
                    $db->insert("emails", [
                        "value" => $emailnew
                    ]);
                    $emailId = (int) $db->id();
                    if ($emailId <= 0) {
                        $this->actionError = 1;
                        return false;
                    }

                    $db->insert("customer_emails", [
                        "customer_id" => $custId,
                        "email_id" => $emailId,
                        "confirmed" => 0,
                        "is_login" => 0,
                        "confirmation_code" => DBDefault::raw('UUID()'),
                        "creation_date" => DBDefault::raw('NOW()')
                    ]);
                    $this->custEmailId = (int) $db->id();
                    if ($this->custEmailId <= 0) {
                        $this->actionError = 2;
                        return false;
                    }
                    /**
                    / get the emails with new added to return the frontend for display
                    */

                    $this->addmail = $db->select("emails", [
                        "[>]customer_emails" => ["emails.id" => "email_id"],
                        "[>]customers" => ["customer_emails.customer_id" => "id"]
                    ], [
                            "emails.id(email_id)",
                            "emails.value(address)",
                            "customer_emails.customer_id(customer_id)",
                            "customer_emails.confirmed(confirmed)",
                            "customer_emails.is_login(is_login)",
                            "customers.user(user)"
                        ], [
                            'customer_emails.customer_id' => $custId,
                        ]);

                } else {
                    if ($emailconfirm === 0) {
                        $this->actionError = 4;
                    } else {
                        $this->actionError = 5;
                    }

                }
            });

            if ($this->actionError === 0) {
                /**
                 * Code for confirmation email
                 */
                $code = $this->db->get("customer_emails", 'confirmation_code', ['id' => $this->custEmailId]);

                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => '0',
                    'msg' => 'OK',
                    'username' => $this->addmail[0]['user'],
                    'email' => $emailnew,
                    'confirm' => $code,
                    'emails' => $this->addmail
                ];
            } else {
                throw new \Exception('ERROR_CREATE', $this->actionError);
            }

        } catch (\Exception $e) {
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage()
            ];
        }
        return json_encode($result);

    }

    /**
     * @param $custId
     * @param $emailnew
     * @param $emailold
     * @return false|string
     * Add new Email Customer
     */
    public function updateMailLogin(int $custId, int $emailId)
    {

        $db = $this->db;
        $this->updmailogin = [];
        $this->actionError = 0;
        $idactual = 0;

        $dbactual = $db->select("emails", [
            "[>]customer_emails" => ["emails.id" => "email_id"],
            "[>]customers" => ["customer_emails.customer_id" => "id"]
        ], [
                "emails.id(email_id)",
                "emails.value(address)",
                "customer_emails.id(id)",
                "customer_emails.customer_id(customer_id)",
                "customer_emails.confirmed(confirmed)",
                "customer_emails.is_login(is_login)",
                "customers.user(user)"
            ], [
                'customer_emails.confirmed' => 1,
                'customer_emails.is_login' => 1,
                'customer_emails.customer_id' => $custId
            ]);
        if (!$dbactual) {
            throw new \Exception('NO_AFFECTED_DATA', '002');
        }

        $idactual = $dbactual[0]['email_id'];

        try {
            if ($emailId === 0) {
                throw new \Exception('EMAIL_NOT_VALID', '003');
            }

            $db->action(function ($db) use ($dbactual, $idactual, $emailId, $custId) {

                /**
                 * if exist login and confirmed
                 */
                $dbcontrol = $db->select("emails", [
                    "[>]customer_emails" => ["emails.id" => "email_id"],
                    "[>]customers" => ["customer_emails.customer_id" => "id"]
                ], [
                        "emails.id(email_id)",
                        "emails.value(address)",
                        "customer_emails.id(id)",
                        "customer_emails.customer_id(customer_id)",
                        "customer_emails.confirmed(confirmed)",
                        "customer_emails.is_login(is_login)",
                        "customers.user(user)"
                    ], [
                        'emails.id' => $emailId,
                        'customer_emails.confirmed' => 1,
                        'customer_emails.customer_id' => $custId
                    ]);

                if (!$dbcontrol) {
                    $this->actionError = 5;
                    return false;
                }

                $updold = $db->update("customer_emails", [
                    "is_login" => 0
                ], [
                        'customer_id' => $custId,
                        'email_id' => $idactual,
                    ]);
                $countOld = $updold->rowCount();

                if ($countOld <= 0) {
                    $this->actionError = 2;
                    return false;
                }

                $updnew = $db->update("customer_emails", [
                    "is_login" => 1
                ], [
                        'customer_id' => $custId,
                        'email_id' => $emailId,
                    ]);

                $countNew = $updnew->rowCount();

                if ($countNew <= 0) {
                    $this->actionError = 2;
                    return false;
                }

            });

            if ($this->actionError === 0) {
                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => 0,
                    'msg' => "OK",
                ];

            } else {
                throw new \Exception('ERROR_CREATE', $this->actionError);
            }

        } catch (\Exception $e) {
            $result = [
                'success' => 0,
                'error' => 1,
                'code' => $e->getCode(),
                'msg' => $e->getMessage(),
                'id' => $idactual
            ];
        }
        return json_encode($result);

    }


    /**
     * @param $custId
     * @return false|string
     */
    public function readEmailsAdditional($custId)
    {

        $result = [];
        $db = $this->db;

        try {

            $result = $db->select("customers", [
                "[><]customer_emails" => ["customers.id" => "customer_id"],
                "[><]emails" => ["customer_emails.email_id" => "id"]
            ], [
                    "emails.id(email_id)",
                    "emails.value(address)",
                    "customer_emails.customer_id(customer_id)",
                    "customer_emails.confirmed(confirmed)",
                    "customer_emails.is_login(is_login)",
                    "customers.user(user)"
                ], [
                    'customers.id' => $custId,
                ]);


            if (empty($result)) {
                throw new \Exception("NO_DATA_EXIST", '001');
            }

            // errore db
            if ($this->db->error) {
                throw new \Exception("DB_ERROR", '002');
            }


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