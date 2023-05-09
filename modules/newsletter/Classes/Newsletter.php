<?php


namespace Modules\newsletter\Classes;

use Odan\Session\SessionInterface;
use PerSeo\DB\DBDefault;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use PerSeo\Validator;

class Newsletter
{
    protected $db;
    protected $container;
    protected $session;
    protected $validator;
    protected $actionError;
    protected $codeConfirm;
    protected $newsId;
    protected $log;

    public function __construct(DBDefault $database, ContainerInterface $container, SessionInterface $session, LoggerInterface $logger) {

        $this->db = $database;
        $this->container = $container;
        $this->session = $session;
        $this->validator = new Validator();
        $this->log = $logger;
    }

    public function createMailNewsletter(string $email) {

        $db = $this->db;
        $this->actionError = 0;
        $emailId = 0;
        $emailValue = '';
        $newsId = 0;
        $newsConfirm = 0;

        try {
            if (!$this->validator->isEmail($email)) {
                throw new \Exception('EMAIL_NOT_VALID', '003');
            }

            // check if email already exist
            $dbMail = $db->select("emails", [
                                                "[>]newsletter_emails" => ["emails.id" => "email_id"]
                                                ],
                                                [
                                                    "emails.id(email_id)",
                                                    "emails.value(address)",
                                                    "newsletter_emails.id(newsletter_id)",
                                                    "newsletter_emails.confirmed(confirmed)"
                                                ],
                                                [
                                                    'emails.value' => $email
                                                ]);

            $emailId = (int)(!empty($dbMail) ? $dbMail[0]['email_id'] : 0);

            $db->action(function($db) use ($dbMail, $email, $emailId, $emailValue, $newsConfirm ) {
                 if (!$dbMail[0]['confirmed']) {
                    if (!$dbMail[0]['email_id']) {
                        $db->insert("emails", [
                            "value" => $email
                        ]);
                        $emailId = $db->id();
                        if ($emailId <= 0) {
                            $this->actionError = 2;
                            return false;
                        }
                        $emailValue = $email;
                    }
                    if ($dbMail[0]['newsletter_id'] <= 0) {
                        $db->insert("newsletter_emails", [
                            "email_id" => $emailId,
                            "confirmed" => 0,
                            "all_newsletter" => 1,
                            "confirmation_code" => DBDefault::raw('UUID()'),
                            "creation_date" => DBDefault::raw('NOW()')
                        ]);
                        $this->newsId = $db->id();
                        if ($this->newsId <= 0) {
                            $this->actionError = 2;
                            return false;
                        }
                        $newsConfirm = 0;
                    } else {
                        $this->actionError = 5;
                        return false;
                    }
                } else {
                    $this->actionError = 4;
                    return false;
                }
            });

            if  ($this->actionError === 0) {
                /**
                 * Code for confirmation email
                 */
                $code = $this->db->get("newsletter_emails",'confirmation_code', [ 'id' => $this->newsId ]);

                $result = [
                    'success' => 1,
                    'error' => 0,
                    'code' => '0',
                    'msg' => 'OK',
                    'email' => $email,
                    'confirm' => $code
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

    public function newsConfirm($code)
    {
        $db = $this->db;
        $this->codeConfirm = false;

        try {

            $db->action(function($db) use ($code) {

                $confirm = $this->db->get('newsletter_emails', 'confirmed',
                    [
                        'confirmation_code' => $code
                    ]);

                if ($confirm <= 0) {
                    $data = $this->db->update("newsletter_emails",
                        [
                            'confirmed' => 1,
                            'confirmation_code' => DBDefault::raw('NULL'),
                            'confirmation_date' => DBDefault::raw('NOW()')
                        ],
                        [
                            'confirmed' => 0,
                            'confirmation_code' => $code
                        ]);
                    if ($data->rowCount() > 0) {
                        $this->codeConfirm = true;
                    }

                } else {
                    $this->codeConfirm = false;
                }
            });

            return $this->codeConfirm;

        } catch (\Exception $e) {
            return false;
        }
    }

}