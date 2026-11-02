<?php

use Blesta\Core\Util\Events\Common\EventInterface;

class SensfrxPlugin extends Plugin
{
    public $gateWayName;
    public $uniqueTransactionId;
    public function __construct()
    {
        Loader::loadComponents($this, ['Input', 'Record', 'Session', 'Emails', 'Clients', 'Users', 'Contacts', 'Html', 'EmailGroups', 'Logs', 'ModuleManager']);
        Loader::loadModels($this, ["PluginManager", "Contacts", "Sensfrx.SensfrxHelper", "Emails", "Clients", "GatewayManager", "Invoices", 'Sensfrx.SaveManageOptions']);
        Language::loadLang('sensfrx_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');
    }

    public function install($plugin_id)
    {
        $company_id = Configure::get('Blesta.company_id');
        Configure::load('sensfrx_config', dirname(__FILE__) . DS . 'config' . DS);
        if (!$this->tableExist("sensfrx_config")) {
            $this->Record
                ->setField("company_id", array('type' => "int", 'size' => 11))
                ->setField("domain", array('type' => "varchar", 'size' => 255))
                ->setField("property_id", array('type' => "varchar", 'size' => 255))
                ->setField("property_secret", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_status", array('type' => "varchar", 'size' => 255))
                ->create("sensfrx_config");
        }
        if (!$this->tableExist("sensfrx_policies")) {
            $this->Record
                ->setField("company_id", array('type' => "int", 'size' => 11))
                ->setField("challenge", array('type' => "int", 'size' => 11))
                ->setField("allow", array('type' => "int", 'size' => 11))
                ->setField("deny", array('type' => "int", 'size' => 11))
                ->setField("shadow", array('type' => "int", 'size' => 11))
                ->create("sensfrx_policies");
        }
        if (!$this->tableExist("sensfrx_policies_setting")) {
            $this->Record->setField("company_id", array('type' => "int", 'size' => 11))
                ->setField("key", array('type' => "varchar", 'size' => 255))
                ->setField("value", array('type' => "longtext"))
                ->create("sensfrx_policies_setting");
            $defaultSettings = [
                'allow' => 'on',
                'challenge' => 'on',
                'deny' => 'on',
                'transactionAllow' => 'on',
                'transactionChallenge' => 'on',
                'transactionDeny' => 'on',
                'registrationAllow' => 'on',
                'registrationChallenge' => 'on',
                'registrationDeny' => 'on',
                'sensfrx_webhook' => 'on'
            ];
            // inserting default settings into the table
            $this->Record->set('company_id', Configure::get('Blesta.company_id'))
                ->set('key', 'policies_settings')
                ->set('value', json_encode($defaultSettings))
                ->insert("sensfrx_policies_setting");
        }
        if (!$this->tableExist("sensfrx_real_activity")) {
            $this->Record
                ->setField("id", array('type' => "int", 'size' => 11, 'unsigned' => true, 'auto_increment' => true))
                ->setField("sensfrx_log_type", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_log1", array('type' => "varchar", 'size' => 255))
                ->setField("created_at", array('type' => "datetime", 'is_null' => true, 'default' => null))
                ->setKey(array("id"), "primary")
                ->create("sensfrx_real_activity");
        }
        if (!$this->tableExist("sensfrx_webhook")) {
            $this->Record
                ->setField("id", array('type' => "int", 'size' => 11, 'unsigned' => true, 'auto_increment' => true))
                ->setField("sensfrx_user_id", array('type' => "varchar", 'size' => 255))
                ->setKey(array("id"), "primary")
                ->create("sensfrx_webhook");
        }
        if (!$this->tableExist("sensfrx_notification_activity")) {
            $this->Record
                ->setField("id", array('type' => "int", 'size' => 11, 'unsigned' => true, 'auto_increment' => true))
                ->setField("sensfrx_user_id", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_message", array('type' => "varchar", 'size' => 255))
                ->setKey(array("id"), "primary")
                ->create("sensfrx_notification_activity");
        }
        if (!$this->tableExist("sensfrx_fraud_registration")) {
            $this->Record
                ->setField("id", array('type' => "int", 'size' => 11, 'unsigned' => true, 'auto_increment' => true))
                ->setField("email", array('type' => "varchar", 'size' => 255))
                ->setKey(array("id"), "primary")
                ->setKey(array("email"), "unique")
                ->create("sensfrx_fraud_registration");
        }
        //unique transid table
        if (!$this->tableExist("sensfrx_unique_trans_id")) {
            $this->Record
                ->setField("id", array('type' => "int", 'size' => 11, 'unsigned' => true, 'auto_increment' => true))
                ->setField("sensfrx_trans_id", array('type' => "varchar", 'size' => 50, 'null' => false))
                ->setField("sensfrx_uid", array('type' => "int", 'size' => 11, 'unsigned' => true, 'null' => false))
                ->setKey(array("id"), "primary")  // Primary key on 'id'
                ->setKey(array("sensfrx_trans_id"), "unique")  // Unique key on transaction ID
                ->create("sensfrx_unique_trans_id");
        }
        if (!$this->tableExist("sensfrx_email_templates")) {
            $this->Record
                ->setField("id", array('type' => "int", 'size' => 11, 'unsigned' => true, 'auto_increment' => true))
                // registration email fields
                ->setField("sensfrx_email_register_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_register_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_register_message", array('type' => "longtext"))
                ->setField("sensfrx_email_register_challenge_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_register_challenge_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_register_challenge_message", array('type' => "longtext"))
                ->setField("sensfrx_email_register_deny_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_register_deny_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_register_deny_message", array('type' => "longtext"))
                // ato email fields
                ->setField("sensfrx_email_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_message", array('type' => "longtext"))
                ->setField("sensfrx_email_challenge_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_challenge_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_challenge_message", array('type' => "longtext"))
                ->setField("sensfrx_email_deny_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_deny_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_deny_message", array('type' => "longtext"))
                // transaction email fields
                ->setField("sensfrx_email_trans_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_trans_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_trans_message", array('type' => "longtext"))
                ->setField("sensfrx_email_after_trans_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_after_trans_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_after_trans_message", array('type' => "longtext"))
                ->setField("sensfrx_email_trans_challenge_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_trans_challenge_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_trans_challenge_message", array('type' => "longtext"))
                ->setField("sensfrx_email_after_trans_challenge_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_after_trans_challenge_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_after_trans_challenge_message", array('type' => "longtext"))
                ->setField("sensfrx_email_trans_deny_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_trans_deny_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_trans_deny_message", array('type' => "longtext"))
                ->setField("sensfrx_email_after_trans_deny_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_after_trans_deny_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_after_trans_deny_message", array('type' => "longtext"))
                // webhook email fields
                ->setField("sensfrx_email_webhook_title", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_webhook_subject", array('type' => "varchar", 'size' => 255))
                ->setField("sensfrx_email_webhook_message", array('type' => "longtext"))
                ->setField("company_name", array('type' => "varchar", 'size' => 255, 'is_null' => true, 'default' => null))
                ->setField("date", array('type' => "datetime", 'is_null' => true, 'default' => null))
                ->setKey(array("id"), "primary")
                ->create("sensfrx_email_templates");
        }
        $this->addEmailTemplates();
    }

    public function uninstall($plugin_id, $last_instance)
    {
        Configure::load('sensfrx', dirname(__FILE__) . DS . 'config' . DS);
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $getNavID = $this->Record->select()->from("actions")->where("location", "=", "nav_staff")->where("url", "=", "plugin/sensfrx/admin/dashboard")->where("name", "=", "SensfrxPlugin.name")->fetch();

        $this->Record->from("actions")->where("location", "=", "nav_staff")->where("url", "=", "plugin/sensfrx/admin/dashboard")->where("name", "=", "SensfrxPlugin.name")->delete();
        $this->Record->from("navigation_items")->where("action_id", "=", (int) $getNavID->id)->delete();
        $this->Record->from("plugins")->where("id", "=", (int) $_REQUEST["id"])->delete();
        $this->Record->from("plugin_events")->where("plugin_id", "=", (int) $_REQUEST["id"])->delete();
        $this->Record->drop("sensfrx_policies_setting");
        $this->Record->drop("sensfrx_webhook");
        $this->Record->drop("sensfrx_notification_activity");
        $this->Record->drop("sensfrx_fraud_registration");
        $this->Record->drop("sensfrx_email_templates");
        $this->Record->drop("sensfrx_unique_trans_id");

        if (isset($sensfrxConfigData->domain) && $sensfrxConfigData->domain != "" && isset($sensfrxConfigData->property_id) && $sensfrxConfigData->property_id && $sensfrxConfigData->property_secret) {
            $api_response = $this->SaveManageOptions->sensfrx_CurlRequest($sensfrxConfigData->domain, $sensfrxConfigData->property_id, $sensfrxConfigData->property_secret, 'deactivate');
            $api_response = json_decode($api_response, true);
        }
    }

    public function getActions()
    {
        return array(
            [
                'action' => "nav_primary_staff",
                'uri' => "plugin/sensfrx/admin/dashboard",
                'name' => "SensfrxPlugin.name", 
                'options' => null,
                'enabled' => 1
            ]
        );
    }

    public function getEvents()
    {
        return [
            [
                'event' => "Appcontroller.structure",
                'callback' => array("this", "structureLoad")
            ],
            [
                'event' => "Users.logout",
                'callback' => array("this", "userLogout")
            ],
            [
                "event" => "Clients.createBefore",
                'callback' => array("this", "beforeClientRegistration")
            ],
            [
                "event" => "Clients.editBefore",
                'callback' => array("this", "editBefore")
            ],
            [
                "event" => "Transactions.addAfter",
                'callback' => array("this", "transactionsaddAfter")
            ],
            [
                "event" => "Invoices.setClosedAfter",
                'callback' => array("this", "transectionSucceeded")
            ],
            [
                "event" => "Invoices.setClosedBefore",
                'callback' => array("this", "transectionAttempt")
            ],
            [
                "event" => "Invoices.addBefore",
                'callback' => array("this", "storeItemDetail")
            ],
        ];
    }

    public function storeItemDetail(EventInterface $event)
    {
        $params = $event->getParams();
        $client_id = $params['vars']['client_id'];
        $date_billed = $params['vars']['date_billed'];
        $date_due = $params['vars']['date_due'];
        $status = $params['vars']['status'];
        $currency = $params['vars']['currency'];
        $delivery = $params['vars']['delivery'];
        $item_details = [];
        $total_amount = 0;
        foreach ($params['vars']['lines'] as $index => $line) {
            $item_details[$index] = [
                'item_id' => $line['service_id'],
                'item_quantity' => $line['qty'],
                'item_price' => $line['amount'],
                'item_name' => $line['description'],
                'item_category' => null
            ];
            $total_amount += $line['amount'];
        }
        $this->Session->write('sensfrx_client_id', $client_id);
        $this->Session->write('sensfrx_date_billed', $date_billed);
        $this->Session->write('sensfrx_date_due', $date_due);
        $this->Session->write('sensfrx_status', $status);
        $this->Session->write('sensfrx_currency', $currency);
        $this->Session->write('sensfrx_delivery', $delivery);
        $this->Session->write('sensfrx_item_details', $item_details);
        $this->Session->write('sensfrx_total_amount', $total_amount);
    }

    public function transactionsaddAfter(EventInterface $event)
    {
        $params = $event->getParams();
        if (isset($params["transaction_id"])) {
            $_SESSION["transaction_id"] = $params["transaction_id"];
        }
    }

    /* attempt_succeeded */
    public function transectionAttempt(EventInterface $event)
    {
        $params = $event->getParams();
        $deviceID = $this->getDeviceId();
        if ($deviceID) {
            $parent = $this->SensfrxHelper->findParentOfItems($_SESSION);
            if (!empty($this->gateWayName)) {
                $gatewayDetails = explode("@@", $this->gateWayName);
            } else {
                if (!empty($_SESSION["transaction_id"])) {
                    $gatewayDetails[0] = null;
                    $gatewayDetails[1] = null;
                } else {
                    if (!empty($_SESSION["gateWayName"])) {
                        $gatewayDetails = explode("@@", $_SESSION["gateWayName"]);
                    } else {
                        $gatewayDetails[0] = null;
                        $gatewayDetails[1] = null;
                    }
                }
            }
            $clientUID = isset($_SESSION["sensfrx_client_id"]) ? $_SESSION["sensfrx_client_id"] : null;
            $sensfrx_date_billed = isset($_SESSION["sensfrx_date_billed"]) ? $_SESSION["sensfrx_date_billed"] : null;
            $sensfrx_date_due = isset($_SESSION["sensfrx_date_due"]) ? $_SESSION["sensfrx_date_due"] : null;
            $sensfrx_status = isset($_SESSION["sensfrx_status"]) ? $_SESSION["sensfrx_status"] : null;
            $sensfrx_currency = isset($_SESSION["sensfrx_currency"]) ? $_SESSION["sensfrx_currency"] : null;
            $sensfrx_delivery = isset($_SESSION["sensfrx_delivery"]) ? $_SESSION["sensfrx_delivery"] : null;
            $sensfrx_item_details = isset($_SESSION["sensfrx_item_details"]) ? $_SESSION["sensfrx_item_details"] : null;
            $sensfrx_total_amount = isset($_SESSION["sensfrx_total_amount"]) ? $_SESSION["sensfrx_total_amount"] : null;
            $merged_items = [];

            foreach ($sensfrx_item_details as $item) {
                $key = $item['item_name']; // group by name, not ID

                if (!isset($merged_items[$key])) {
                    $merged_items[$key] = $item;
                    $merged_items[$key]['item_quantity'] = intval($item['item_quantity']);
                } else {
                    $merged_items[$key]['item_quantity'] += intval($item['item_quantity']);
                }
            }

            $merged_items = array_values($merged_items);            
            $clientDetails = $this->Clients->get($clientUID, true);
            $invoiceDetails = $this->Record->select()->from("invoices")->where("id", "=", $params["invoice_id"])->fetch();
            $orderDetails = $this->Record->select()->from("orders")->where("invoice_id", "=", $params["invoice_id"])->fetch();
            $contactNumber = $this->Record->select()->from("contact_numbers")->where("contact_id", "=", $clientDetails->contact_id)->fetch();
            $customer_user_agent = $_SERVER['HTTP_USER_AGENT'];
            $customer_ip_address = $_SERVER['REMOTE_ADDR'];
            $uniqueTransactionId = uniqid('', true); // Generate unique ID
            $this->uniqueTransactionId = $uniqueTransactionId;
            // Checking if an entry for this client (sensfrx_uid) already exists
            $existing = $this->Record
                ->select()
                ->from('sensfrx_unique_trans_id')
                ->where('sensfrx_uid', '=', $clientDetails->id)
                ->fetch();

            if ($existing) {
                // if entry exists – updating the transaction ID
                $this->Record
                    ->where('sensfrx_uid', '=', $clientDetails->id)
                    ->update('sensfrx_unique_trans_id', [
                        'sensfrx_trans_id' => $uniqueTransactionId
                    ]);
            } else {
                // Entry does not exist – inserting new record
                $this->Record->insert('sensfrx_unique_trans_id', [
                    'sensfrx_trans_id' => $uniqueTransactionId,
                    'sensfrx_uid' => $clientDetails->id
                ]);
            }

            try {
                $postDataArray = [
                    "transaction_id" => $this->uniqueTransactionId,
                    "transaction_type" => 'purchase',
                    "affiliate_id" => null,
                    "affliliate_name" => null,
                    "email" => $clientDetails->username,
                    "first_name" => $clientDetails->first_name,
                    "last_name" => $clientDetails->last_name,
                    "username" => $clientDetails->username,
                    "user_id" => $clientDetails->id,
                    "payment_mode" => $gatewayDetails[0],
                    "payment_provider" => $gatewayDetails[1],
                    "customer_ip_address" => $customer_ip_address,
                    "customer_user_agent" => $customer_user_agent,
                    "card_fullname" => null,
                    "card_bin" => null,
                    "card_expire" => null,
                    "card_last" => null,
                    "card_token" => null,
                    "cvv" => null,
                    "phone_no" => $contactNumber->number,
                    "transaction_amount" => $sensfrx_total_amount,
                    "total_amount" => number_format($invoiceDetails->total, 2),
                    "transaction_currency" => $invoiceDetails->currency,
                    "currency" => $invoiceDetails->currency,
                    "items" => json_encode($merged_items),
                    'shipping_cost' => '0',
                    "OrderID" => null,
                    "payment_status" => null,
                    "order_key" => $orderDetails->order_number,
                    "shipping_country" => null,
                    "shipping_state" => null,
                    "shipping_city" => null,
                    "shipping_zip" => null,
                    "shipping_phone" => null,
                    "shipping_fullname" => null,
                    "shipping_method" => null,
                    "billing_country" => $clientDetails->country,
                    "billing_state" => $clientDetails->state,
                    "billing_city" => $clientDetails->city,
                    "billing_fullname" => $clientDetails->first_name . $clientDetails->last_name,
                    "billing_email" => $clientDetails->username,
                    "billing_address" => $clientDetails->address1 . ' ' . $clientDetails->address2,
                    "billing_zip" => $clientDetails->zip,
                    "billing_phone" => $contactNumber->number,
                    "merchant_name" => null,
                    "merchant_category" => null,
                    "merchant_id" => null,
                    "merchant_country" => null,
                    "payment_date" => $invoiceDetails->date_billed,
                    "date_paid" => $invoiceDetails->date_billed,
                    "discount_amount" => null,
                    "coupon_code" => null,
                    "invoice_id" => $params["invoice_id"],
                ];
            } catch (Exception $e) {
                echo "An error occurred: " . $e->getMessage();
            }

            $mainarray = [
                "ev" => "attempt_succeeded",
                "dID" => $deviceID,
                "h" => $this->SensfrxHelper->createHArray(),
                "tfs" => $postDataArray
            ];
            $postData = json_encode($mainarray);
            $url = "https://a.sensfrx.ai/v1/transaction";
            $result = $this->SensfrxHelper->__post($url, $postData);

            if ($result['code'] == 200) {
                $resultArray = $result['result'];
                $status = $resultArray["status"];
                $severity = $resultArray["severity"];                             
                $deviceId = isset($resultArray['device']['device_id']) ? $resultArray['device']['device_id'] : '';
                $deviceName = isset($resultArray['device']['name']) ? $resultArray['device']['name'] : '';
                $deviceIp = isset($resultArray['device']['ip']) ? $resultArray['device']['ip'] : '';
                $deviceLocation = isset($resultArray['device']['location']) ? $resultArray['device']['location'] : '';
                $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
                $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';
                $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
                $allowurl = $CurPageURL . '/blesta/client/login?allow=true&' . $this->SensfrxHelper->getDeviceActionQuery('allow', $clientDetails->id, $deviceId);
                $denyurl = $CurPageURL . '/blesta/client/login?deny=true&' . $this->SensfrxHelper->getDeviceActionQuery('deny', $clientDetails->id, $deviceId);
                $reset_password_url = $CurPageURL . '/blesta/client/login/reset/';

                $emailDenyArray = [
                    "toclient" => $clientDetails->username,
                    "clientId" => $clientDetails->id,
                    "templateVars" => [
                        'client_full_name' => $clientDetails->first_name . ' ' . $clientDetails->last_name,
                        'amount' => number_format($invoiceDetails->total, 2),
                        'date' => date('Y-m-d H:i'),
                        'signature' => 'Best regards',
                        'reset_password_url' => $reset_password_url
                    ]
                ];
                $emailChallangeArray = [
                    "toclient" => $clientDetails->username,
                    "clientId" => $clientDetails->id,
                    "templateVars" => [
                        'fullname' => $clientDetails->first_name . ' ' . $clientDetails->last_name,
                        'amount' => number_format($invoiceDetails->total, 2),
                        'allow_url' => $allowurl,
                        'deny_url' => $denyurl,
                        'date' => date('Y-m-d H:i'),
                        'signature' => 'Best regards',
                    ]
                ];
                $emailMediumArray = [
                    "toclient" => $clientDetails->username,
                    "clientId" => $clientDetails->id,
                    "templateVars" => [
                        'fullname' => $clientDetails->first_name . ' ' . $clientDetails->last_name,
                        'amount' => number_format($invoiceDetails->total, 2),
                        'date' => date('Y-m-d'),
                        'device_name' => $deviceName,
                        'ip_address' => $deviceIp,
                        'location' => $deviceLocation,
                        'time' => date('H:i'),
                    ]
                ];

                if (isset($policySettings["shadow_mode"])) {
                    $insert_data = "User " . $clientDetails->username . " attempted a transaction on " . date('Y-m-d H:i:s') . ". An error occurred while fetching Shadow Mode data.";
                    $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                        ->set("sensfrx_log1", $insert_data)
                        ->set("created_at", date('Y-m-d H:i:s'))
                        ->insert("sensfrx_real_activity");
                } else {
                    if ($status == 'allow' || $status == 'challenge' || $status == 'low') {
                        $sensfrx_approve = 'approved';
                    } else {
                        $sensfrx_approve = $status;
                    }
                    $current_date_ato = date('Y-m-d H:i:s');
                    $user_email_ato = $clientDetails->username;
                    if ($status == "allow" && $severity == "low") {
                        $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";

                        $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                    if ($status == "allow" && $severity == "medium") {
                        if (isset($policySettings["transactionAllow"]) && $policySettings["transactionAllow"] == 'on') {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('Trans_medium_severity_Mail_Send', $emailMediumArray);
                        } else {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. However, since your Transaction Security policy [ Transaction Allow ] is turned off, the transaction was approved.";
                            $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                        }
                    }
                    if ($status == "challenge" && $severity == "high") {
                        if (isset($policySettings["transactionChallenge"]) && $policySettings["transactionChallenge"] == 'on') {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('Trans_challenge_severity_Mail_Send', $emailChallangeArray);
                        } else {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. However, since your Transaction Security policy [ Transaction Challenge ] is turned off, the transaction was approved.";
                            $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                        }
                    }
                    if ($status == "deny" && $severity == "critical") {
                        if (isset($policySettings["transactionDeny"]) && $policySettings["transactionDeny"] == 'on') {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('Trans_deny_severity_Mail_Send', $emailDenyArray);
                            if (!empty($uniqueTransactionId)) {
                                try {
                                    $this->Record->from("sensfrx_unique_trans_id")
                                        ->where("sensfrx_trans_id", "=", $uniqueTransactionId)
                                        ->delete();
                                    if (!session_id()) {
                                        session_start();
                                    }
                                    $this->Session->write('error_message', 'Sensfrx - We have detected unusual activity with your recent transaction, and as a precautionary measure, the order cannot be processed.');
                                    header("Location: " . $this->SensfrxHelper->getSiteBaseUrl() . "/blesta/order");
                                } catch (Exception $e) {
                                    if (!session_id()) {
                                        session_start();
                                    }
                                    $this->Session->write('error_message', 'Sensfrx - We have detected unusual activity with your recent transaction, and as a precautionary measure, the order cannot be processed.');
                                    header("Location: " . $this->SensfrxHelper->getSiteBaseUrl() . "/blesta/order");
                                }
                            } else {
                                if (!session_id()) {
                                    session_start();
                                }
                                $this->Session->write('error_message', 'Sensfrx - We have detected unusual activity with your recent transaction, and as a precautionary measure, the order cannot be processed.');
                                header("Location: " . $this->SensfrxHelper->getSiteBaseUrl() . "/blesta/order");
                            }                           
                        } else {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. However, since your Transaction Security policy [ Transaction Deny ] is turned off, the transaction was approved.";
                            $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                        }
                    }
                }
            } else {
                $insert_data = "User " . $clientDetails->username . " attempted a transaction on " . date('Y-m-d H:i:s') . ". However, Sensfrx returned a response with status code " . $result['code'] . ", causing an issue.";
                $this->Record->set("sensfrx_log_type", "Transaction (Before Payment)")
                    ->set("sensfrx_log1", $insert_data)
                    ->set("created_at", date('Y-m-d H:i:s'))
                    ->insert("sensfrx_real_activity");

                return ['error' => 'Sensfrx returned an invalid response code: ' . $result['code']];
            }
        }
    }

    /* transaction_succeeded */
    public function transectionSucceeded(EventInterface $event)
    {
        $params = $event->getParams();
        $deviceID = $this->getDeviceId();
        $invoice_id = $params["invoice_id"];
        $invoice = $this->Record->select(['client_id'])
            ->from('invoices')
            ->where('id', '=', $invoice_id)
            ->fetch();
        $client_id = $invoice->client_id;
        $clientDetails = $this->Clients->get($client_id, true);
        $payment_mode = $invoice_p->p_mode;  // Not Get
        $payment_provider = $invoice_p->p_provider;  // Not Get
        // $transectionId = $_SESSION["uniqueTransactionId"];
        // $transectionDetails = $this->Record->select()->from("transactions")->where("id", "=", $transectionId)->fetch();
        $orderDetails = $this->Record->select()->from("orders")->where("invoice_id", "=", $invoice_id)->fetch();
        $customer_user_agent = $_SERVER['HTTP_USER_AGENT'];
        $customer_ip_address = $_SERVER['REMOTE_ADDR'];
        $item_d = [];
        $i = 0;        
        $itemQuantities = []; // Store quantities mapped by pricing_id

        // First, map quantities based on `service_id`
        foreach ($params["old_invoice"]->line_items as $Item) {
            $service = $this->Record->select()->from("services")->where("id", "=", $Item->service_id)->fetch();
            
            if ($service) {
                $pricingId = $service->pricing_id;
                // If pricing_id already exists, increment quantity
                if (isset($itemQuantities[$pricingId])) {
                    $itemQuantities[$pricingId] += $Item->qty; // Sum quantities for the same item
                } else {
                    $itemQuantities[$pricingId] = $Item->qty; // Initialize quantity
                }
            }
        }

        $detaile_collection = []; // Initialize details collection

        // processing each unique pricing_id
        foreach ($itemQuantities as $pricingId => $quantity) {
            // Fetching data
            $productGroupId = $this->Record->select()->from("package_group")
                ->where("package_id", "=", $pricingId)->fetch();
            $productGroupDetails = $this->Record->select()->from("package_group_names")
                ->where("package_group_id", "=", $productGroupId->package_group_id)->fetch();            
            $productName = $this->Record->select()->from("package_names")
                ->where("package_id", "=", $pricingId)->fetch();

            // Adding to detaile_collection with correct quantity
            $detaile_collection[] = [
                'item_name' => $productName->name ?? "Unknown",
                'item_id' => $pricingId,
                'item_category' => $productGroupDetails->name ?? "Uncategorized",
                'item_quantity' => intval($quantity), // Correct quantity mapping
                'item_price' => "",
                'item_url' => ""
            ];
        }

        $item_d = $detaile_collection;
        $transaction = $this->Record->select("sensfrx_trans_id")
        ->from("sensfrx_unique_trans_id")
        ->where("sensfrx_uid", "=", $clientDetails->id)
        ->fetch();

        if ($transaction) {
            $transactionId = $transaction->sensfrx_trans_id;
        }

        // Getting all transactions applied to the invoice
        $transactionAppliedList = $this->Record
            ->select()
            ->from('transaction_applied')
            ->where('invoice_id', '=', $invoice_id)
            ->fetchAll();

        $payment_status = 'transaction_succeeded'; // default fallback

        if (!empty($transactionAppliedList)) {
            foreach ($transactionAppliedList as $applied) {
                $transaction_id = $applied->transaction_id;

                // Fetching the transaction status
                $transactionDetails = $this->Record
                    ->select(['status'])
                    ->from('transactions')
                    ->where('id', '=', $transaction_id)
                    ->fetch();

                if ($transactionDetails) {
                    $status = strtolower($transactionDetails->status);

                    if (in_array($status, ['approved', 'pending', 'processing', 'completed', 'on-hold'])) {
                        $payment_status = 'transaction_succeeded';
                        break; // one successful transaction is enough
                    } elseif (in_array($status, ['declined', 'error', 'void', 'failed', 'cancelled', 'refunded'])) {
                        $payment_status = 'transaction_failed';
                        // not breaking it yet, maybe a later one is successful
                    }
                }
            }
        }

        $postDataArray = [
            "transaction_id" => $transactionId,
            "order_id" => $orderDetails->id,
            "transaction_type" => 'purchase',
            "affiliate_id " => null,
            "affliliate_name " => null,
            "email" => $clientDetails->email,
            "first_name" => $clientDetails->first_name,
            "last_name" => $clientDetails->last_name,
            "username" => $clientDetails->username,
            "user_id" => $client_id,
            "payment_mode" => $payment_mode,
            "payment_provider" => $payment_provider,
            "customer_ip_address" => $customer_ip_address,
            "customer_user_agent" => $customer_user_agent,
            "card_fullname" => null,
            "card_bin" => null,
            "card_expire" => null,
            "card_last" => null,
            "card_token" => null,
            "cvv" => null,
            "phone_no" => $clientDetails->number,
            "transaction_amount" => number_format($params["old_invoice"]->total, 2),
            "total_amount" => number_format($params["old_invoice"]->subtotal, 2),
            "transaction_currency" => $transactionDetails->currency,
            "currency" => $transactionDetails->currency,
            "items" => json_encode($item_d),
            'shipping_cost' => '0',
            "OrderID" => null,
            "payment_status" => $payment_status,
            "shipping_country" => null,
            "shipping_state" => null,
            "shipping_city" => null,
            "shipping_zip" => null,
            "shipping_phone" => null,
            "shipping_fullname" => null,
            "shipping_method" => null,
            "billing_country" => $clientDetails->country,
            "billing_state" => $clientDetails->state,
            "billing_city" => $clientDetails->city,
            "billing_fullname" => $clientDetails->first_name . ' ' . $clientDetails->last_name,
            "billing_email" => $clientDetails->username,
            "billing_address" => $clientDetails->address1 . ' ' . $clientDetails->address2,
            "billing_zip" => $clientDetails->zip,
            "billing_phone" => $clientDetails->number,
            "merchant_name" => null,
            "merchant_category" => null,
            "merchant_id" => null,
            "merchant_country" => null,
            "payment_date" => $transactionDetails->date_added,
            "date_paid" => $transactionDetails->date_added,
            "discount_amount" => null,
            "coupon_code" => null,
            "invoice_id" => $invoice_id,
        ];        

        // deleting table data 
        if (!empty($transactionId)) {
            $this->Record->from("sensfrx_unique_trans_id")
                ->where("sensfrx_trans_id", "=", $transactionId)
                ->delete();
        }

        $mainarray = [
            "ev" => $payment_status,
            "dID" => $deviceID,
            "h" => $this->SensfrxHelper->createHArray(),
            "tfs" => $postDataArray
        ];
        
        $postData = json_encode($mainarray);
        $url = "https://a.sensfrx.ai/v1/transaction";
        $result = $this->SensfrxHelper->__post($url, $postData);
        $current_date_ato = date('Y-m-d H:i:s');
        $user_email_ato = $clientDetails->username;

        try {
            if ($result['code'] == 200) {
                $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
                $userid = $clientDetails->id;
                $reset_password_url = $CurPageURL . '/blesta/client/login/reset/';
                $resultArray = $result['result'];
                $status = $resultArray["status"];
                $severity = $resultArray["severity"];                          
                $deviceId = isset($resultArray['device']['device_id']) ? $resultArray['device']['device_id'] : '';
                $allowurl = $CurPageURL . '/blesta/client/login?allow=true&' . $this->SensfrxHelper->getDeviceActionQuery('allow', $userid, $deviceId);
                $denyurl = $CurPageURL . '/blesta/client/login?deny=true&' . $this->SensfrxHelper->getDeviceActionQuery('deny', $userid, $deviceId);
                $deviceName = isset($resultArray['device']['name']) ? $resultArray['device']['name'] : '';
                $deviceIp = isset($resultArray['device']['ip']) ? $resultArray['device']['ip'] : '';
                $deviceLocation = isset($resultArray['device']['location']) ? $resultArray['device']['location'] : '';
                $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
                $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';
                $emailChallangeArray = [
                    "toclient" => $clientDetails->username,
                    "clientId" => $clientDetails->id,
                    "templateVars" => [
                        'fullname' => $clientDetails->first_name . ' ' . $clientDetails->last_name,
                        'amount' => number_format($params["old_invoice"]->total, 2),
                        'date' => date('Y-m-d'),
                        'allow_url' => $allowurl,
                        'deny_url' => $denyurl,
                        'signature' => 'Best regards',
                    ]
                ];
                $emailMediumArray = [
                    "toclient" => $clientDetails->username,
                    "clientId" => $clientDetails->id,
                    "templateVars" => [
                        'fullname' => $clientDetails->first_name . ' ' . $clientDetails->last_name,
                        'amount' => number_format($params["old_invoice"]->total, 2),
                        'date' => date('Y-m-d'),
                        'device_name' => $deviceName,
                        'ip_address' => $deviceIp,
                        'location' => $deviceLocation,
                        'time' => date('H:i'),
                        'signature' => 'Best regards',
                    ]
                ];
                $emailDenyArray = [
                    "toclient" => $clientDetails->username,
                    "clientId" => $clientDetails->id,
                    "templateVars" => [
                        'client_full_name' => $clientDetails->first_name . ' ' . $clientDetails->last_name,
                        'amount' => number_format($params["old_invoice"]->total, 2),
                        'date' => date('Y-m-d H:i'),
                        'signature' => 'Best regards',
                        'reset_password_url' => $reset_password_url
                    ]
                ];
                $orderId = $orderDetails->id;
                if (isset($policySettings["shadow_mode"])) {
                    $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. but since Shadow Mode was enabled, it was allowed.";
                    $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                        ->set("sensfrx_log1", $insert_data)
                        ->set("created_at", date('Y-m-d H:i:s'))
                        ->insert("sensfrx_real_activity");
                } else {
                    if ($status == 'allow' || $status == 'challenge' || $status == 'low') {
                        $sensfrx_approve = 'approved';
                    } else {
                        $sensfrx_approve = $status;
                    }
                    if ($status == "allow" && $severity == "low") {
                        $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                    if ($status == "allow" && $severity == "medium") {
                        if (isset($policySettings["transactionAllow"]) && $policySettings["transactionAllow"] == 'on') {
                            $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('Trans_medium_severity_Mail_Send_succeeded', $emailMediumArray);
                        } else {
                            $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. However, since your Transaction Security policy [ Allow ] is turned off, the transaction was approved.";
                            $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                        }
                    }
                    if ($status == "challenge" && $severity == "high") {
                        if (isset($policySettings["transactionChallenge"]) && $policySettings["transactionChallenge"] == 'on') {
                            $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");                           
                            $this->Record->where("invoice_id", "=", $params["invoice_id"])->update("orders", array("status" => "pending"));

                            $this->Record->where("id", "=", $params["invoice_id"])->update("invoices", array("status" => "draft"));
                            $this->SensfrxHelper->sendEmail('Trans_challenge_severity_Mail_Send_succeeded', $emailChallangeArray);
                            if ($this->tableExist("sensfrx_notification_activity")) {
                                $data = array(
                                    'sensfrx_user_id' => $userid,
                                    'sensfrx_message' => 'Sensfrx - Unusual activity has been detected with your recent transaction. As a precaution, your order has been placed on hold and cannot be processed at this time.'
                                );
                                $this->Record->insert("sensfrx_notification_activity", $data);
                            }                            
                        } else {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. However, since your Transaction Security policy [ Challenge ] is turned off, the transaction was approved.";
                            $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                        }
                    }
                    if ($status == "deny" && $severity == "critical") {
                        if (isset($policySettings["transactionDeny"]) && $policySettings["transactionDeny"] == 'on') {
                            $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". The transaction was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";                            
                            $this->Record->where("invoice_id", "=", $params["invoice_id"])->update("orders", array("status" => "canceled"));
                            $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('Trans_deny_severity_Mail_Send_succeeded', $emailDenyArray);
                            if ($this->tableExist("sensfrx_notification_activity")) {
                                $data = array(
                                    'sensfrx_user_id' => $userid,
                                    'sensfrx_message' => 'Sensfrx - Unusual activity has been detected with your recent transaction. As a result, your order has been canceled and cannot be processed.'
                                );
                                $this->Record->insert("sensfrx_notification_activity", $data);
                            }
                        } else {
                            $insert_data = "User " . $user_email_ato . " attempted a transaction on " . $current_date_ato . ". Sensfrx flagged the transaction with a " . $severity . " risk score. However, since your Transaction Security policy [ Deny ] is turned off, the transaction was approved.";
                            $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                                ->set("sensfrx_log1", $insert_data)
                                ->set("created_at", date('Y-m-d H:i:s'))
                                ->insert("sensfrx_real_activity");
                        }
                    }
                }
            } else {
                $insert_data = "User " . $user_email_ato . " succeeded a transaction on " . $current_date_ato . ". However, Sensfrx returned a response with status code " . $result['code'] . ", causing an issue.";
                $this->Record->set("sensfrx_log_type", "Transaction (After Payment)")
                    ->set("sensfrx_log1", $insert_data)
                    ->set("created_at", date('Y-m-d H:i:s'))
                    ->insert("sensfrx_real_activity");
            }
        } catch (Exception $e) {
            echo "An error occurred: " . $e->getMessage();   
        }
    }

    public function userLogout(EventInterface $event)
    {
        $company_id = Configure::get('Blesta.company_id');
        $params = $event->getParams();
        $property_id = false;
        if ($this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->numResults()) {
            $result = $this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->fetch();
            $property_id = (isset($result->property_id) && !empty($result->property_id) ? $result->property_id : false);
        }
        if ($this->PluginManager->isInstalled('sensfrx', $company_id) && $property_id && isset($_GET["sensfrx_device_id"]) && !empty($_GET["sensfrx_device_id"])):
            $this->Session->write('device_id', $_GET["sensfrx_device_id"]);
            $this->Session->write('current_page', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $this->SensfrxHelper->getSiteHost() . $_SERVER['REQUEST_URI']);
        endif;
    }

    public function manageLogin($resultData, $email)
    {
        try {
            $UserRecord = $this->Record->select()->from("users")->where("username", "=", $email)->fetch();
            $contacts = $this->Record->select()->from("contacts")->where("email", "=", $email)->fetch();

            if ($resultData['code'] == 200) {
                $status = $resultData['result']['status'];
                $severity = $resultData['result']['severity'];  
                $deviceId = isset($resultData['result']['device']['device_id']) ? $resultData['result']['device']['device_id'] : '';
                $deviceName = isset($resultData['result']['device']['name']) ? $resultData['result']['device']['name'] : '';
                $deviceIp = isset($resultData['result']['device']['ip']) ? $resultData['result']['device']['ip'] : '';
                $deviceLocation = isset($resultData['result']['device']['location']) ? $resultData['result']['device']['location'] : '';
                $userid = isset($UserRecord->id) ? (int) $UserRecord->id : null;
                $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
                $allowurl = $CurPageURL . '/blesta/client/login?allow=true&' . $this->SensfrxHelper->getDeviceActionQuery('allow', $userid, $deviceId);
                $denyurl = $CurPageURL . '/blesta/client/login?deny=true&' . $this->SensfrxHelper->getDeviceActionQuery('deny', $userid, $deviceId);
                $reset_password_url = $CurPageURL . '/blesta/client/login/reset/';
        
                $emailBodyLoginArray = [
                    "toclient" => $UserRecord->username,
                    "clientId" => $UserRecord->id,
                    "templateVars" => [
                        'client_name' => $contacts->first_name . ' ' . $contacts->last_name,
                        'device_name' => $deviceName,
                        'ip_address' => $deviceIp,
                        'location' => $deviceLocation,
                        'date' => date('Y-m-d'),
                        'time' => date('H:i'),
                        'signature' => 'sign',
                    ]
                ];
                $emailBodyChallengeArray = [
                    "toclient" => $UserRecord->username,
                    "clientId" => $UserRecord->id,
                    "templateVars" => [
                        'client_full_name' => $UserRecord->username,
                        'allow_url' => $allowurl,
                        'deny_url' => $denyurl,
                        'signature' => 'Best regards',
                    ]
                ];
                $emailBodyDenyArray = [
                    "toclient" => $UserRecord->username,
                    "clientId" => $UserRecord->id,
                    "templateVars" => [
                        'client_full_name' => $UserRecord->username,
                        'reset_password_url' => $reset_password_url,
                        'signature' => 'Best regards',
                    ]
                ];

                $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
                $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';

                if (isset($policySettings["shadow_mode"])) {
                    $insert_data = "User " . $UserRecord->username . " attempted a login on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the login with a " . $severity . " risk score, but since Shadow Mode was enabled, it was allowed.";
                    $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                } else {
                    $sensfrx_approve = (in_array($status, ["allow", "challenge", "low"]) ? "approved" : $status);

                    if ($status == "allow" && $severity == "low") {
                        $insert_data = $UserRecord->username . " attempted to log in on " . date('Y-m-d H:i:s') . ". The login was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                    }
                    if ($status == "allow" && $severity == "medium") {
                        if (isset($policySettings["allow"]) && $policySettings["allow"] == 'on') {
                            $insert_data = $UserRecord->username . " attempted to log in on " . date('Y-m-d H:i:s') . ". The login was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('loginEmail', $emailBodyLoginArray);
                        } else {
                            $insert_data = "User " . $UserRecord->username . " attempted a login on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the login with a " . $severity . " risk score. However, since your Login Security policy [ Allow ] is turned off, the login was approved.";
                            $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        }
                    }
                    if ($status == 'challenge' && $severity == 'high') {
                        if (isset($policySettings["challenge"]) && $policySettings["challenge"] == 'on') {
                            $insert_data = $UserRecord->username . " attempted to log in on " . date('Y-m-d H:i:s') . ". The login was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('loginChallengeEmail', $emailBodyChallengeArray);
                        } else {
                            $insert_data = "User " . $UserRecord->username . " attempted to log in on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the login with a " . $severity . " risk score. However, since your Login Security policy [ Challenge ] is turned off, the login was approved.";
                            $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        }
                    }
                    if ($status == "deny" && $severity == "critical") {
                        if (isset($policySettings["deny"]) && $policySettings["deny"] == 'on') {
                            $insert_data = $UserRecord->username . " attempted to log in on " . date('Y-m-d H:i:s') . ". The login was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                            $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                            $this->SensfrxHelper->sendEmail('loginDenyEmail', $emailBodyDenyArray);

                            if (
                                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                                $_SERVER['SERVER_PORT'] == 443 ||
                                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                            ) {
                                $protocol = "https://";
                            } else {
                                $protocol = "http://";
                            }
                            $domain = $this->SensfrxHelper->getSiteHost();
                            $base_url = $protocol . $domain . "/";
                            $reset_password_url = $base_url . "blesta/client/login";
                            if (!session_id()) {
                                session_start();
                            }
                            $this->Session->clear();     
                            $this->Session->write('error_message', 'Sensfrx - We have detected unusual activity related to your account, and as a precautionary measure, your login attempt cannot be approved.');
                            header("Location: " . $reset_password_url);
                            exit;
                        } else {
                            $insert_data = "User " . $UserRecord->username . " attempted a login on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the login with a " . $severity . " risk score. However, since your Login Security policy [ Deny ] is turned off, the login was approved.";
                            $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        }
                    }
                }
                return ['success' => 'Login processed successfully'];
            } else {
                $insert_data = "User " . $UserRecord->username . " attempted a login on " . date('Y-m-d H:i:s') . ". However, Sensfrx returned a response with status code " . $resultData['code'] . ", causing an issue.";
                $this->Record->set("sensfrx_log_type", "Account Security")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");

                return ['error' => 'Sensfrx returned an invalid response code: ' . $resultData['code']];
            }
        } catch (Exception $e) {
            die("An error occurred in file " . $e->getFile() . " on line " . $e->getLine() . ": " . $e->getMessage());
        }
    }

    public function getDeviceId()
    {
        try {
            $d_id_1 = null;
            $d_id_2 = null;
            $d_id_3 = null;
            $d_id_4 = null;

            if (isset($_COOKIE['sens_di_1'])) {
                $d_id_1 = $_COOKIE['sens_di_1'];
            } else {
                $d_id_1 = null;
            }
            if (isset($_COOKIE['sens_di_2'])) {
                $d_id_2 = $_COOKIE['sens_di_2'];
            } else {
                $d_id_2 = null;
            }
            if (isset($_COOKIE['sens_di_3'])) {
                $d_id_3 = $_COOKIE['sens_di_3'];
            } else {
                $d_id_3 = null;
            }
            if (isset($_COOKIE['sens_di_4'])) {
                $d_id_4 = $_COOKIE['sens_di_4'];
            } else {
                $d_id_4 = null;
            }

            $deviceID = $d_id_1 . $d_id_2 . $d_id_3 . $d_id_4;
            return $deviceID;
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function editBefore(EventInterface $event)
    {
        $deviceID = $this->getDeviceId();
        $params = $event->getParams();
        $clientUID = isset($params['client_id']) ? (int) $params['client_id'] : null;
        $clientDetails = $this->Clients->get($clientUID, true);
        $firstName = isset($clientDetails->first_name) ? $clientDetails->first_name : null;
        $lastName = isset($clientDetails->last_name) ? $clientDetails->last_name : null;
        $email = isset($clientDetails->email) ? $clientDetails->email : null;
        $new_firstname = isset($_POST['first_name']) ? $_POST['first_name'] : null;
        $new_lastname = isset($_POST['last_name']) ? $_POST['last_name'] : null;
        $new_email = isset($_POST['email']) ? $_POST['email'] : null;
        $postDataArray = array(
            "name" => array(
                "from" => $firstName . ' ' . $lastName,
                "to" => $new_firstname . ' ' . $new_lastname
            ),
            "username" => array(
                "from" => null,
                "to" => null
            ),
            "email" => array(
                "from" => $email,
                "to" => $new_email
            ),
            "phone" => array(
                "from" => null,
                "to" => null
            ),
            "password" => array(
                "changed" => null
            )
        );

        $mainarray = [
            "ev" => "profile_update_succeeded",
            "uID" => $clientUID,
            "dID" => $deviceID,
            "h" => $this->SensfrxHelper->createHArray(),
            "uex" => $postDataArray
        ];

        $postData = json_encode($mainarray);
        $url = "https://a.sensfrx.ai/v1/update-profile";
        $result = $this->SensfrxHelper->__post($url, $postData);

        if ($result['code'] == 200) {
            $resultArray = $result['result'];
            $status = $resultArray["status"];
            $severity = $resultArray["severity"];

            $deviceId = $resultArray["device"]["device_id"];
            $deviceName = $resultArray["device"]["name"];
            $deviceIp = $resultArray["device"]["ip"];
            $deviceLocation = $resultArray["device"]["location"];
            /* Fetch policy settings */
            $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
            $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';

            /* Handle when Shadow Mode is not set */
            if (isset($policySettings["shadow_mode"])) {
                $current_date_ato = date('Y-m-d H:i:s');
                $insert_data = "User " . $params["vars"]["username"] . " attempted a profile update on " . $current_date_ato . ". An error occurred while fetching Shadow Mode data.";
                $this->Record->set("sensfrx_log_type", "Profile Update")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
            } else {
                if ($status == 'allow' || $status == 'challenge' || $status == 'low') {
                    $sensfrx_approve = 'approved';
                } else {
                    $sensfrx_approve = $status;
                }
                $current_date_ato = date('Y-m-d H:i:s');
                $user_email_ato = $params["vars"]["username"];
                if ($status == "allow" && $severity == "low") {
                    if (isset($policySettings["allow"]) && $policySettings["allow"] == 'on') {
                        $insert_data = $user_email_ato . " attempted a profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Account Security policy [Allow] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
                if ($status == "allow" && $severity == "medium") {
                    if (isset($policySettings["allow"]) && $policySettings["allow"] == 'on') {
                        $insert_data = $user_email_ato . " attempted to profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                        $emailBodyArray = [
                            "toclient" => $params["vars"]["username"],
                            "clientId" => $params["client_id"],
                            "templateVars" => [
                                'client_name' => $firstName . ' ' . $lastName,
                                'device_name' => $deviceName,
                                'ip_address' => $deviceIp,
                                'location' => $deviceLocation,
                                'date' => date('Y-m-d'),
                                'time' => date('H:i'),
                                'signature' => 'Best regards',
                            ]
                        ];
                        $this->SensfrxHelper->sendEmail('profileUpdateEmail', $emailBodyArray);
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Acoount Security policy [ Allow ] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
                if ($status == "challenge" && $severity == "high") {
                    if (isset($policySettings["challenge"]) && $policySettings["challenge"] == 'on') {
                        $insert_data = $user_email_ato . " attempted a profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                        $emailBodyArray = [
                            "toclient" => $params["vars"]["username"],
                            "clientId" => $params["client_id"],
                            "templateVars" => [
                                'client_name' => $firstName . ' ' . $lastName,
                                'device_name' => $deviceName,
                                'ip_address' => $deviceIp,
                                'location' => $deviceLocation,
                                'date' => date('Y-m-d'),
                                'time' => date('H:i'),
                                'signature' => 'Best regards',
                            ]
                        ];
                        $this->SensfrxHelper->sendEmail('profileUpdateEmail', $emailBodyArray);
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Account Security policy [Allow] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
                if ($status == "deny" && $severity == "critical") {
                    if (isset($policySettings["deny"]) && $policySettings["deny"] == 'on') {
                        $insert_data = $user_email_ato . " attempted to profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                        $emailBodyArray = [
                            "toclient" => $params["vars"]["username"],
                            "clientId" => $params["client_id"],
                            "templateVars" => [
                                'client_name' => $firstName . ' ' . $lastName,
                                'device_name' => $deviceName,
                                'ip_address' => $deviceIp,
                                'location' => $deviceLocation,
                                'date' => date('Y-m-d'),
                                'time' => date('H:i'),
                                'signature' => 'Best regards',
                            ]
                        ];
                        $this->SensfrxHelper->sendEmail('profileUpdateEmail', $emailBodyArray);
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Acoount Security policy [ Deny ] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
            }
        } else {
            $current_date_ato = date('Y-m-d H:i:s');
            $user_email_ato = $params["vars"]["username"];
            $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". However, Sensfrx returned a response with status code " . $profile_update_result['code'] . ", causing an issue.";
            $this->Record->set("sensfrx_log_type", "Profile Update")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
        }
    }

    /* for edit from the client side */
    public function clienEditBefore($sessionData, $formPostData)
    {
        $deviceID = $this->getDeviceId();
        /* $params = $event->getParams(); */
        $clientUID = isset($sessionData['blesta_client_id']) ? (int) $sessionData['blesta_client_id'] : null;
        $clientDetails = $this->Clients->get($clientUID, true);
        $firstName = isset($clientDetails->first_name) ? $clientDetails->first_name : null;
        $lastName = isset($clientDetails->last_name) ? $clientDetails->last_name : null;
        $email = isset($clientDetails->email) ? $clientDetails->email : null;
        $new_firstname = isset($formPostData['first_name']) ? $formPostData['first_name'] : null;
        $new_lastname = isset($formPostData['last_name']) ? $formPostData['last_name'] : null;
        $new_email = isset($formPostData['email']) ? $formPostData['email'] : null;
        $new_password = !empty($formPostData['new_password']) ? "1" : "0";
    
        $postDataArray = array(
            "name" => array(
                "from" => $firstName . ' ' . $lastName,
                "to" => $new_firstname . ' ' . $new_lastname
            ),
            "username" => array(
                "from" => null,
                "to" => null
            ),
            "email" => array(
                "from" => $email,
                "to" => $new_email
            ),
            "phone" => array(
                "from" => null,
                "to" => null
            ),
            "password" => array(
                "changed" => $new_password
            )
        );

        $mainarray = [
            "ev" => "profile_update_succeeded",
            "uID" => $clientUID,
            "dID" => $deviceID,
            "h" => $this->SensfrxHelper->createHArray(),
            "uex" => $postDataArray
        ];

        $postData = json_encode($mainarray);
        $url = "https://a.sensfrx.ai/v1/update-profile";
        $result = $this->SensfrxHelper->__post($url, $postData);

        if ($result['code'] == 200) {
            $resultArray = is_array($result["result"]) ? $result["result"] : json_decode($result["result"], true);
            $status = $resultArray["status"];
            $severity = $resultArray["severity"];
            $deviceId = $resultArray["device"]["device_id"];
            $deviceName = $resultArray["device"]["name"];
            $deviceIp = $resultArray["device"]["ip"];
            $deviceLocation = $resultArray["device"]["location"];
            $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
            $allowurl = $CurPageURL . '/blesta/client/login?allow=true&' . $this->SensfrxHelper->getDeviceActionQuery('allow', $clientUID, $deviceId);
            $denyurl = $CurPageURL . '/blesta/client/login?deny=true&' . $this->SensfrxHelper->getDeviceActionQuery('deny', $clientUID, $deviceId);
            $reset_password_url = $CurPageURL . '/blesta/client/login/reset/';

            /* Fetch policy settings */
            $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
            $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';
            
            /* Handle when Shadow Mode is not set */
            if (isset($policySettings["shadow_mode"])) {
                $current_date_ato = date('Y-m-d H:i:s');
                $insert_data = "User " . $new_email . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. but since Shadow Mode was enabled, it was allowed.";
                $this->Record->set("sensfrx_log_type", "Profile Update")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
            } else {
                if ($status == 'allow' || $status == 'challenge' || $status == 'low') {
                    $sensfrx_approve = 'approved';
                } else {
                    $sensfrx_approve = $status;
                }
                $current_date_ato = date('Y-m-d H:i:s');
                $user_email_ato = $new_email;
                if ($status == "allow" && $severity == "low") {
                    if (isset($policySettings["allow"]) && $policySettings["allow"] == 'on') {
                        $insert_data = $user_email_ato . " attempted a profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Account Security policy [Allow] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
                if ($status == "allow" && $severity == "medium") {
                    if (isset($policySettings["allow"]) && $policySettings["allow"] == 'on') {
                        $insert_data = $user_email_ato . " attempted to profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                        $emailBodyArray = [
                            "toclient" => $email,
                            "clientId" => $clientUID,
                            "templateVars" => [
                                'client_full_name' => $firstName . ' ' . $lastName,
                                'device_name' => $deviceName,
                                'ip_address' => $deviceIp,
                                'location' => $deviceLocation,
                                'date' => date('Y-m-d'),
                                'time' => date('H:i'),
                                'signature' => 'Best regards',
                            ]
                        ];
                        $this->SensfrxHelper->sendEmail('profileUpdateEmail', $emailBodyArray);
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Acoount Security policy [ Allow ] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
                if ($status == "challenge" && $severity == "high") {
                    if (isset($policySettings["challenge"]) && $policySettings["challenge"] == 'on') {
                        $insert_data = $user_email_ato . " attempted a profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                        $emailBodyArray = [
                            "toclient" => $email,
                            "clientId" => $clientUID,
                            "templateVars" => [
                                'client_full_name' => $firstName . ' ' . $lastName,
                                'device_name' => $deviceName,
                                'ip_address' => $deviceIp,
                                'location' => $deviceLocation,
                                'date' => date('Y-m-d'),
                                'time' => date('H:i'),
                                'allow_url' => $allowurl,
                                'deny_url' => $denyurl,
                                'signature' => 'Best regards',
                            ]
                        ];
                        $this->SensfrxHelper->sendEmail('profileUpdateChallengeEmail', $emailBodyArray);
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Account Security policy [Allow] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
                if ($status == "deny" && $severity == "critical") {
                    if (isset($policySettings["deny"]) && $policySettings["deny"] == 'on') {
                        $insert_data = $user_email_ato . " attempted to profile update on " . $current_date_ato . ". The profile update was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                        $emailBodyArray = [
                            "toclient" => $email,
                            "clientId" => $clientUID,
                            "templateVars" => [
                                'client_full_name' => $firstName . ' ' . $lastName,
                                'reset_password_url' => $reset_password_url,
                                'signature' => 'Best regards',
                            ]
                        ];
                        $this->SensfrxHelper->sendEmail('profileUpdateDenyEmail', $emailBodyArray);
                        if (
                            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                            $_SERVER['SERVER_PORT'] == 443 ||
                            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                        ) {
                            $protocol = "https://";
                        } else {
                            $protocol = "http://";
                        }
                        $domain = $this->SensfrxHelper->getSiteHost();
                        $base_url = $protocol . $domain . "/";
                        $reset_password_url = $base_url . "blesta/client/login";
                        if (!session_id()) {
                            session_start();
                        }
                        $this->Session->clear();
                        $this->Session->write('error_message', 'Sensfrx - We have detected unusual activity related to your account, and as a precautionary measure, your profile update attempt cannot be approved.');
                        header("Location: " . $reset_password_url);
                        exit;
                    } else {
                        $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". Sensfrx flagged the profile update with a " . $severity . " risk score. However, since your Acoount Security policy [ Deny ] is turned off, the profile update was approved.";
                        $this->Record->set("sensfrx_log_type", "Profile Update")
                            ->set("sensfrx_log1", $insert_data)
                            ->set("created_at", date('Y-m-d H:i:s'))
                            ->insert("sensfrx_real_activity");
                    }
                }
            }
        } else {
            $current_date_ato = date('Y-m-d H:i:s');
            $user_email_ato = $new_email;
            $insert_data = "User " . $user_email_ato . " attempted a profile update on " . $current_date_ato . ". However, Sensfrx returned a response with status code " . $result['code'] . ", causing an issue.";
            $this->Record->set("sensfrx_log_type", "Profile Update")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
        }
    }

    public function structureLoad(EventInterface $event)
    {
        $company_id = Configure::get('Blesta.company_id');
        $params = $event->getParams();
        if ($params["controller"] == "client_main" && $params["action"] == "edit" && $params["portal"] == "client" && isset($_POST["email"]) && isset($_POST["last_name"]) && isset($_POST["first_name"]) && isset($_POST["current_password"]) && isset($_POST["first_name"])) {
            $this->clienEditBefore($_SESSION, $_REQUEST);
        }

        if (isset($_SESSION["transaction_id"]) && $_SESSION["transaction_id"] != "") {
            $transectionDetails = $this->Record->select()->from("transactions")->where("id", "=", $_SESSION["transaction_id"])->fetch();
            if ($transectionDetails->gateway_id != null) {
                $gatewayDetails = $this->GatewayManager->get($transectionDetails->gateway_id, true);
                $_SESSION["gateWayName"] = $gatewayDetails->name . "@@" . $gatewayDetails->class;
                $this->gateWayName = $gatewayDetails->name . "@@" . $gatewayDetails->class;
            } else {
                $this->gateWayName = $_SESSION["gateWayName"] = "";
            }
        } else {
            $this->gateWayName = $_SESSION["gateWayName"] = "";
        }

        if (isset($params["portal"]) && $params["portal"] == "client") {           
            $deviceId = isset($_GET['deviceId']) ? $_GET['deviceId'] : '';
            $allow = isset($_GET['allow']) ? $_GET['allow'] : '';
            $deny = isset($_GET['deny']) ? $_GET['deny'] : '';
            $verify = isset($_GET['verify']) ? $_GET['verify'] : '';
            $sfxUid = isset($_GET['sfxuid']) ? $_GET['sfxuid'] : '';
            $sfxExp = isset($_GET['sfxexp']) ? $_GET['sfxexp'] : '';
            // Exactly one action per request; the signature is bound to that action.
            $deviceAction = '';
            if (isset($_GET['allow']) && isset($_GET['deny'])) {
                $deviceAction = '';
            } elseif ($allow === 'true') {
                $deviceAction = 'allow';
            } elseif ($deny === 'true') {
                $deviceAction = 'deny';
            }
            $deviceActionValid = $deviceAction !== '' && !empty($deviceId) && !empty($verify) && $this->SensfrxHelper->verifyDeviceAction($deviceAction, $sfxUid, $deviceId, $sfxExp, $verify);
            if ($deviceAction === 'allow' && $deviceActionValid) {
                $url = "https://a.sensfrx.ai/v1/devices/" . rawurlencode($deviceId) . "/approve";
                $postData = [];
                $result = $this->SensfrxHelper->__post($url, $postData);
            }
            if ($deviceAction === 'deny' && $deviceActionValid) {
                $url = "https://a.sensfrx.ai/v1/devices/" . rawurlencode($deviceId) . "/deny";
                $postData = [];
                $result = $this->SensfrxHelper->__post($url, $postData);
            }

            //Bot API implementation
            if (isset($_COOKIE['sens_di_1'])) {
                $d_id_1 = filter_var($_COOKIE['sens_di_1'], FILTER_SANITIZE_STRING);
            } else {
                $d_id_1 = null;
            }
            if (isset($_COOKIE['sens_di_2'])) {
                $d_id_2 = filter_var($_COOKIE['sens_di_2'], FILTER_SANITIZE_STRING);
            } else {
                $d_id_2 = null;
            }
            if (isset($_COOKIE['sens_di_3'])) {
                $d_id_3 = filter_var($_COOKIE['sens_di_3'], FILTER_SANITIZE_STRING);
            } else {
                $d_id_3 = null;
            }
            if (isset($_COOKIE['sens_di_4'])) {
                $d_id_4 = filter_var($_COOKIE['sens_di_4'], FILTER_SANITIZE_STRING);
            } else {
                $d_id_4 = null;
            }

            $deviceID = $d_id_1 . $d_id_2 . $d_id_3 . $d_id_4;
            $url = "https://a.sensfrx.ai/v1/bot";
            $userEmail = null;
            $id_value = null;
            $client_id = $this->Session->read('blesta_client_id');
            if ($client_id) {
                $client = $this->Record->select()
                    ->from("clients")
                    ->where("id", "=", $client_id)
                    ->fetch();
                if ($client) {
                    $id_value = $client->user_id;
                    $user = $this->Record->select()
                        ->from("users")
                        ->where("id", "=", $id_value)
                        ->fetch();
                    $userEmail = $user->username;
                }
            }
            if (empty($id_value)) {
                $id_value = null;
            }
            if (empty($deviceID)) {
                $deviceID = null;
            }
            $postDataArray = [
                "uID" => $id_value,
                'dID' => $deviceID,
                "h" => $this->SensfrxHelper->createHArray(),
            ];
            $postData = json_encode($postDataArray);
            $resultData = $this->SensfrxHelper->__post($url, $postData);
            if ($resultData['code'] == 200) {
                $status = $resultData['result']['status'];
                $severity = $resultData['result']['severity']; 
                $deviceId = isset($resultData['result']['device']['device_id']) ? $resultData['result']['device']['device_id'] : '';
                $deviceName = isset($resultData['result']['device']['name']) ? $resultData['result']['device']['name'] : '';
                $deviceIp = isset($resultData['result']['device']['ip']) ? $resultData['result']['device']['ip'] : '';
                $deviceLocation = isset($resultData['result']['device']['location']) ? $resultData['result']['device']['location'] : '';
                $userid = isset($UserRecord->id) ? (int) $UserRecord->id : null;
                $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
                $allowurl = $CurPageURL . '/blesta/client/login?allow=true&' . $this->SensfrxHelper->getDeviceActionQuery('allow', $userid, $deviceId);
                $denyurl = $CurPageURL . '/blesta/client/login?deny=true&' . $this->SensfrxHelper->getDeviceActionQuery('deny', $userid, $deviceId);
                // Determine protocol (HTTP or HTTPS)
                if (
                    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                    $_SERVER['SERVER_PORT'] == 443 ||
                    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                ) {
                    $protocol = "https://";
                } else {
                    $protocol = "http://";
                }
                $domain = $this->SensfrxHelper->getSiteHost();
                $base_url = $protocol . $domain . "/";
                $reset_password_url = $base_url . "blesta/client/login/reset";
                $emailBodyLoginArray = [
                    "toclient" => $userEmail,
                    "clientId" => $id_value,
                    "templateVars" => [
                        'client_name' => $userEmail,
                        'device_name' => $deviceName,
                        'ip_address' => $deviceIp,
                        'location' => $deviceLocation,
                        'date' => date('Y-m-d'),
                        'time' => date('H:i'),
                        'signature' => 'Best regards',
                    ]
                ];
                $emailBodyRestArray = [
                    "toclient" => $userEmail,
                    "clientId" => $id_value,
                    "templateVars" => [
                        'client_full_name' => $userEmail,
                        'allow_url' => $allowurl,
                        'deny_url' => $denyurl,
                        'signature' => 'Best regards',
                        'date' => date('Y-m-d'),
                        'reset_password_url' => $reset_password_url
                    ]
                ];

                $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
                $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';

                if (isset($policySettings["shadow_mode"])) {
                    if ($severity == 'medium' || $severity == 'challenge' || $severity == 'critical') {
                        $insert_data = "User " . $userEmail . " attempted bot activity on " . date('Y-m-d H:i:s') . " with " . $severity . " severity, but since Shadow Mode was enabled, it was allowed.";
                        $this->Record->set("sensfrx_log_type", "Bot Acitivity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                    }
                } else {
                    $sensfrx_approve = (in_array($status, ["allow", "challenge", "low"]) ? "approved" : $status);

                    if ($status == "allow" && $severity == "low") {
                        // setcookie("notification_shown", "Sensfrx - We have detected unusual activity that suggests bot-like behavior, and as a precautionary measure, your access has been denied.", time() + 30, "/");      
                        // $insert_data = $userEmail . " attempted bot activity on " . date('Y-m-d H:i:s') . ". The bot activity was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        // $this->Record->set("sensfrx_log_type", "Bot Activity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                    }
                    if ($status == "allow" && $severity == "medium") {
                        $insert_data = $userEmail . " attempted bot activity on " . date('Y-m-d H:i:s') . ". The bot activity was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Bot Activity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        $this->SensfrxHelper->sendEmail('botMediumEmail', $emailBodyLoginArray);
                    }
                    if ($status == 'challenge' && $severity == 'high') {
                        $insert_data = $userEmail . " attempted bot activity on " . date('Y-m-d H:i:s') . ". The bot activity was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Bot Activity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        $this->SensfrxHelper->sendEmail('botHighEmail', $emailBodyRestArray);
                    }
                    if ($status == "deny" && $severity == "critical") {
                        $insert_data = $userEmail . " attempted bot activity on " . date('Y-m-d H:i:s') . ". The bot activity was " . $sensfrx_approve . " due to a risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "Bot Activity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        $this->SensfrxHelper->sendEmail('botCriticalEmail', $emailBodyRestArray);
                        if (
                            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                            $_SERVER['SERVER_PORT'] == 443 ||
                            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                        ) {
                            $protocol = "https://";
                        } else {
                            $protocol = "http://";
                        }
                        $domain = $this->SensfrxHelper->getSiteHost();
                        $base_url = $protocol . $domain . "/";
                        $login_url = $base_url . "blesta/client/login";
                        if (!session_id()) {
                            session_start();
                        }
                        $this->Session->clear();                        
                        setcookie("notification_shown", "Sensfrx - We have detected unusual activity that suggests bot-like behavior, and as a precautionary measure, your access has been denied.", time() + 30, "/");
                    }
                }
            } else {
                $insert_data = "User " . $userEmail . " attempted bot activity on " . date('Y-m-d H:i:s') . ". However, Sensfrx returned a response with status code " . $resultData['code'] . ", causing an issue.";
                $this->Record->set("sensfrx_log_type", "Bot Activity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                return ['error' => 'Sensfrx returned an invalid response code: ' . $resultData['code']];
            }

            // Webhook logout
            $current_user_id = $id_value;
            $rows = $this->Record->select("sensfrx_user_id")
                ->from("sensfrx_webhook")
                ->fetchAll();
            $found_match = false;
            foreach ($rows as $row) {
                if ($row->sensfrx_user_id == $current_user_id) {
                    $found_match = true;
                    break;
                }
            }
            if ($found_match) {
                $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
                $valueDecoded = json_decode($policySettings->value);
                $sensfrx_webhook = $valueDecoded->sensfrx_webhook;
                if ($sensfrx_webhook == 'on' || $sensfrx_webhook == '1') {
                    $this->Record->from('sensfrx_webhook')
                        ->where('sensfrx_user_id', '=', $current_user_id)
                        ->delete();
                    if (
                        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                        $_SERVER['SERVER_PORT'] == 443 ||
                        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                    ) {
                        $protocol = "https://";
                    } else {
                        $protocol = "http://";
                    }
                    $domain = $this->SensfrxHelper->getSiteHost();
                    $base_url = $protocol . $domain . "/";
                    $reset_password_url = $base_url . "blesta/client/login";
                    if (!session_id()) {
                        session_start();
                    }
                    $insert_data = "Sensfrx triggered the webhook for user " . $userEmail . " on " . date('Y-m-d H:i:s') . ". The webhook action has been taken based on the assessed risk score level.";
                    $this->Record->set("sensfrx_log_type", "Webhook Activity")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                    $emailBodyWebhookArray = [
                        "toclient" => $userEmail,
                        "clientId" => $id_value,
                        "templateVars" => [
                            'client_full_name' => $userEmail,
                            'signature' => 'Best regards',
                        ]
                    ];
                    $this->SensfrxHelper->sendEmail('WebhookEmail', $emailBodyWebhookArray);
                    $this->Session->clear(); // Clear Blesta session data                    
                    $this->Session->write('error_message', 'Sensfrx - A webhook event has detected unusual activity. As a precautionary measure, your access has been denied.');
                    header("Location: " . $reset_password_url);
                    exit;
                }
            }

            //new account manual review feature - updating status of account  
            $client_id = $this->Session->read('blesta_client_id');
            if ($client_id) {
                $client = $this->Record->select()
                    ->from("clients")
                    ->where("id", "=", $client_id)
                    ->fetch();
                if ($client) {
                    $id_value = $client->user_id;
                    $user = $this->Record->select()
                        ->from("users")
                        ->where("id", "=", $id_value)
                        ->fetch();
                    $userEmail = $user->username;
                }
            }
            $rows = $this->Record->select()
                ->from("sensfrx_fraud_registration")
                ->where("email", "=", $userEmail)
                ->fetchAll();
            $found_match = false;
            foreach ($rows as $row) {
                if ($row->email == $userEmail) {
                    $found_match = true;
                    break;
                }
            }
            if ($found_match) {
                $this->Record->where("id", "=", $client_id)->update("clients", ["status" => "fraud"]);
                if (
                    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                    $_SERVER['SERVER_PORT'] == 443 ||
                    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                ) {
                    $protocol = "https://";
                } else {
                    $protocol = "http://";
                }
                $domain = $this->SensfrxHelper->getSiteHost();
                $base_url = $protocol . $domain . "/";
                $reset_password_url = $base_url . "blesta/client/login";
                if (!session_id()) {
                    session_start();
                }
                $this->Session->clear(); // Clear Blesta session data                
                $this->Session->write('error_message', 'Sensfrx - Unusual activity has been detected with your recent registration. As a precaution, your registration has been placed on hold and cannot be processed at this time.');
                header("Location: " . $reset_password_url);
                exit;
            }

            if (!session_id()) {
                session_start();
            }
            if (isset($_SESSION["error_message"]) && $_SESSION["error_message"] != "") {
                echo "<div style='color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px; text-align: center;'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
                unset($_SESSION['error_message']); // Clear the message after displaying
                return;
            }
            $notificatin_appear = $_COOKIE['notification_shown'] ?? null;
            if (isset($notificatin_appear)) {
                echo "<div style='color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px; text-align: center;'>" . htmlspecialchars($notificatin_appear) . "</div>";
                setcookie('notification_shown', '', time() - 3600, '/');
            }
            if ($this->tableExist("sensfrx_notification_activity")) {
                $rows = $this->Record->select()
                    ->from("sensfrx_notification_activity")
                    ->fetchAll();
                // Print the rows
                foreach ($rows as $row) {
                    if ($client_id == $row->sensfrx_user_id) {
                        echo "<div style='color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px; text-align: center;'>" . htmlspecialchars($row->sensfrx_message) . "</div>";
                        $this->Record->from("sensfrx_notification_activity")
                            ->where("sensfrx_user_id", "=", $client_id)
                            ->delete();
                        break;
                    }
                }
            }
        }

        $portal = (isset($params['portal']) && !empty($params['portal']) ? strtolower($params['portal']) : null);
        $property_id = false;
        $eventReturnValue = false;

        if ($this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->numResults()) {
            $result = $this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->fetch();
            $property_id = (isset($result->property_id) && !empty($result->property_id) ? $result->property_id : false);
        }
        if (isset($params["portal"]) && $params["portal"] == "admin") {  
            $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
            $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';
            $recordCheck = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->where("name", "=", "Sensfrx")->numResults();
            $connectionDataCheck = $this->Record->select()->where("company_id", "=", $company_id)->from("sensfrx_config")->fetch();
            $hideNav = "no";
            if ($connectionDataCheck->property_id == "" || $connectionDataCheck->property_secret == "" || $connectionDataCheck->domain == "") {
                $hideNav = "yes";
            } else {
                $hideNav = "no";
            }
            
            //showing notification if pid skey is empty   
            $property_secret = $connectionDataCheck->property_secret;
            if (
                (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                $_SERVER['SERVER_PORT'] == 443 ||
                (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            ) {
                $protocol = "https://";
            } else {
                $protocol = "http://";
            }

            $domain = $this->SensfrxHelper->getSiteHost();
            $base_url = $protocol . $domain . "/";
            $currentUrl = $this->SensfrxHelper->getSiteBaseUrl() . $_SERVER['REQUEST_URI'];
            $site_sensfrx_docs = 'https://docs.sensfrx.ai/en/integrations/blesta-extension';
            $eventReturnValue = $event->getReturnValue();

            if (!isset($eventReturnValue['body_start']) || is_array($eventReturnValue['body_start'])) {
                $eventReturnValue['body_start'] = '';
            }

            $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

            // Check if cookie exists 
            if (!isset($_COOKIE['sensfrx_sleep'])) {
                if (!$property_id || !$property_secret) {
                    if (strpos($currentUrl, 'blesta/admin/settings/company/plugins/manage') !== false) { 
                        $output1 = '<style>
                            .sensfrx-addon-notification-container {
                                position: fixed;
                                top: 160px;
                                left: 55%;
                                transform: translate(-50%, -50%);
                                z-index: 1000;
                                display: flex;
                                align-items: center;
                                background-color: #dc3545;
                                border: 1px solid #ddd;
                                border-radius: 3px;
                                padding: 5px;
                                box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
                            }
                            .sensfrx-addon-notification-close {
                                margin-left: auto;
                                cursor: pointer;
                            }
                            .sensfrx-addon-notification-container a {
                                color: #ffffff !important;
                                text-decoration: underline !important;
                            }
                            .sensfrx-addon-notification-container a:hover {
                                color: blue !important;
                                text-decoration: underline !important;
                            }
                        </style>';
                        $output1 .= '<div class="sensfrx-addon-notification-container">
                            <div style="color: white;">Sensfrx - Please configure your Property ID and Secret Key. You can find detailed instructions on obtaining your Property ID and Secret Key in our <a href="' . $site_sensfrx_docs . '" target="_blank" class="alert-link"> documentation.</a></div>
                            <div class="sensfrx-addon-notification-close">✂️</div>
                        </div>';
                        $output1 .= "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var closeLink = document.querySelector('.sensfrx-addon-notification-close');
                                if (closeLink) {
                                    closeLink.addEventListener('click', function() {
                                        const expiration = new Date();
                                        expiration.setTime(expiration.getTime() + 24 * 60 * 60 * 1000); // Set cookie for 24 hours
                                        const now = new Date();
                                        const formattedDate = now.toISOString().slice(0, 19).replace('T', ' '); // Format date
                                        document.cookie = 'sensfrx_sleep =' + formattedDate + '; expires=' + expiration.toUTCString() + '; path=/';
                                        closeLink.parentNode.style.display = 'none';
                                    });
                                }
                            });
                        </script>";

                        if (!isset($eventReturnValue['body_start'])) {
                            $eventReturnValue['body_start'] = '';
                        }
                        $eventReturnValue['body_start'] .= $output1;
                        $event->setReturnValue($eventReturnValue);                    
                    }
                    else {
                        $output1 = '<style>
                            .sensfrx-addon-notification-container {
                                position: fixed;
                                top: 160px;
                                left: 55%;
                                transform: translate(-50%, -50%);
                                z-index: 1000;
                                display: flex;
                                align-items: center;
                                background-color: #dc3545;
                                border: 1px solid #ddd;
                                border-radius: 3px;
                                padding: 5px;
                                box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
                            }
                            .sensfrx-addon-notification-close {
                                margin-left: auto;
                                cursor: pointer;
                            }
                            .sensfrx-addon-notification-container a {
                                color: #ffffff !important;
                                text-decoration: underline !important;
                            }
                            .sensfrx-addon-notification-container a:hover {
                                color: blue !important;
                                text-decoration: underline !important;
                            }
                        </style>';
                        $output1 .= '<div class="sensfrx-addon-notification-container">
                            <div style="color: white;">Sensfrx - Please configure your Property ID and Secret Key. <a style="color: white;" href="' . $base_url . 'blesta/admin/settings/company/plugins/manage/' . $moduleDetails->id . '" class="alert-link">Click to Configure it</a></div>
                            <div class="sensfrx-addon-notification-close">✂️</div>
                        </div>';
                        $output1 .= "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var closeLink = document.querySelector('.sensfrx-addon-notification-close');
                                if (closeLink) {
                                    closeLink.addEventListener('click', function() {
                                        const expiration = new Date();
                                        expiration.setTime(expiration.getTime() + 24 * 60 * 60 * 1000); // Set cookie for 24 hours
                                        const now = new Date();
                                        const formattedDate = now.toISOString().slice(0, 19).replace('T', ' '); // Format date
                                        document.cookie = 'sensfrx_sleep =' + formattedDate + '; expires=' + expiration.toUTCString() + '; path=/';
                                        closeLink.parentNode.style.display = 'none';
                                    });
                                }
                            });
                        </script>";

                        if (!isset($eventReturnValue['body_start'])) {
                            $eventReturnValue['body_start'] = '';
                        }
                        $eventReturnValue['body_start'] .= $output1;
                        $event->setReturnValue($eventReturnValue);                
                    }
                }
            } else {
                // Check if enough time has passed (5 minutes)
                $last_notification = $_COOKIE['sensfrx_sleep'];
                $timestamp1 = date('Y-m-d H:i:s');
                $timestamp2 = $last_notification;
                $datetime1 = new DateTime($timestamp1);
                $datetime2 = new DateTime($timestamp2);
                $interval = $datetime1->diff($datetime2);
                //$totalMinutes = ($interval->h * 60) + $interval->i;
                $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

                $totalHours = ($interval->d * 24) + $interval->h;
                if ($totalHours >= 24) {
                    if (!$property_id || !$property_secret) {
                        $output1 = '<style>
                            .sensfrx-addon-notification-container {
                                position: fixed;
                                top: 160px;
                                left: 55%;
                                transform: translate(-50%, -50%);
                                z-index: 1000;
                                display: flex;
                                align-items: center;
                                background-color: #dc3545;
                                border: 1px solid #ddd;
                                border-radius: 3px;
                                padding: 5px;
                                box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
                            }
                            .sensfrx-addon-notification-close {
                                margin-left: auto;
                                cursor: pointer;
                            }
                            .sensfrx-addon-notification-container a {
                                color: #ffffff !important;
                                text-decoration: underline !important;
                            }
                            .sensfrx-addon-notification-container a:hover {
                                color: blue !important;
                                text-decoration: underline !important;
                            }
                        </style>';
                        $output1 .= '<div class="sensfrx-addon-notification-container">
                            <div style="color: white;">Sensfrx - Missing property id or Property secret !!  <a style="color: white;" href="' . $base_url . 'blesta/admin/settings/company/plugins/manage/' . $moduleDetails->id . '" class="alert-link"> Click to Configure it </a></div>
                            <div class="sensfrx-addon-notification-close">✂️</div>
                        </div>';
                        $output1 .= "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var closeLink = document.querySelector('.sensfrx-addon-notification-close');
                                if (closeLink) {
                                    closeLink.addEventListener('click', function() {
                                        const expiration = new Date();
                                        expiration.setTime(expiration.getTime() + 24 * 60 * 60 * 1000); // Set cookie for 24 hours
                                        const now = new Date();
                                        const formattedDate = now.toISOString().slice(0, 19).replace('T', ' '); // Format date
                                        document.cookie = 'sensfrx_sleep =' + formattedDate + '; expires=' + expiration.toUTCString() + '; path=/';
                                        closeLink.parentNode.style.display = 'none';
                                    });
                                }
                            });
                        </script>";

                        if (!isset($eventReturnValue['body_start'])) {
                            $eventReturnValue['body_start'] = '';
                        }
                        $eventReturnValue['body_start'] .= $output1;
                        $event->setReturnValue($eventReturnValue);                        
                    }
                }
            }

            /* Handle when Shadow Mode is not set */
            // Register only for a signed-in staff member, and from the configured domain rather than the Host header
            if (isset($policySettings["sensfrx_webhook"]) && $policySettings["sensfrx_webhook"] == "on" && !empty($this->Session->read('blesta_staff_id'))) {
                $siteBaseUrl = $this->SensfrxHelper->getSiteBaseUrl();
                $webHookUrls = array(
                    "webhook" => $siteBaseUrl . "/blesta/plugin/sensfrx/hooks/webhook",
                    "transaction_webhook" => $siteBaseUrl . "/blesta/plugin/sensfrx/hooks/transaction_webhook",
                );
                $url = "https://a.sensfrx.ai/v1/webhooks";
                $getWebhookurls = $this->SensfrxHelper->__get($url);

                if ($getWebhookurls["code"] == 200) {
                    $allUrlsApi = $getWebhookurls["result"]["urls"];
                }
                if ($getWebhookurls["code"] == 200) {
                    $allUrlsApi = $getWebhookurls["result"]["urls"];
                    $webhookSecrets = $this->SensfrxHelper->getWebhookSecrets();
                    foreach ($webHookUrls as $webHookUrl) {
                        // Register when missing, or re-register (returns the existing secret) when no secret is stored for this URL yet
                        $secretMissing = empty($webhookSecrets[$webHookUrl]);
                        if (!in_array($webHookUrl, $allUrlsApi) || $secretMissing) {
                            $postData = array(
                                'url' => $webHookUrl,
                                "h" => $this->SensfrxHelper->createHArray()
                            );
                            $postData = json_encode($postData);
                            $result = $this->SensfrxHelper->__post($url, $postData);
                            $signingSecret = isset($result["result"]["signing_secret"]) ? $result["result"]["signing_secret"] : (isset($result["result"]["data"]["signing_secret"]) ? $result["result"]["data"]["signing_secret"] : '');
                            // Secrets are keyed by the exact URL they were issued for
                            if (isset($result["code"]) && $result["code"] == 200 && is_string($signingSecret) && $signingSecret !== '') {
                                $this->SensfrxHelper->saveWebhookSecret($webHookUrl, $signingSecret);
                            }
                        }
                    }
                }
            }
        }
        if ($this->PluginManager->isInstalled('sensfrx', $company_id) && $portal == "client" && $property_id):
            $user_id = false;
            if (!empty($this->Session->read('blesta_client_id'))) {
                $client_info = $this->Clients->get($this->Session->read('blesta_client_id'));
                $user_id = $client_info->user_id;
            }
            if (count($_POST)) {
                if (isset($_POST["username"]) && $params["controller"] == "client_login") {
                    $deviceID = $this->getDeviceId();
                    $url = "https://a.sensfrx.ai/v1/login";
                    $UserRecord = $this->Record->select()->from("users")->where("username", "=", $_POST["username"])->fetch();

                    if (!$UserRecord) {
                        // Trigger login_failed event if user does not exist
                        $postDataArrayFailed = [
                            "ev" => "login_failed",
                            "dID" => $deviceID,
                            "uex" => ["username" => $username],
                            "h" => $this->SensfrxHelper->createHArray(),
                            "reason" => "User not found"
                        ];                        
                        $postData = json_encode($postDataArrayFailed);
                        $this->SensfrxHelper->__post($url, $postData);
                        return; // Stop further execution
                    }

                    $hashed_input_password = $this->Users->systemHash($_POST['password']);
    
                    if (!password_verify($hashed_input_password, $UserRecord->password)) {
                        $postDataArrayFailed = [
                            "ev" => "login_failed",
                            "dID" => $deviceID,
                            "uex" => ["username" => $username],
                            "h" => $this->SensfrxHelper->createHArray(),
                            "reason" => "Password is incorrect"
                        ];                        
                        $postData = json_encode($postDataArrayFailed);
                        $this->SensfrxHelper->__post($url, $postData);
                        return; // Stop further execution
                    }
                    
                    $postDataArray = [
                        "ev" => "login_succeeded",
                        "dID" => $deviceID,
                        "uex" => ["email" => $UserRecord->username, "username" => $UserRecord->username],
                        "h" => $this->SensfrxHelper->createHArray(),
                        "uID" => $UserRecord->id
                    ];

                    if ($params["action"] == "reset") {
                        if ($this->Record->select()->from("users")->where("username", "=", $_POST["username"])->numResults()) {
                            $UserRecord = $this->Record->select()->from("users")->where("username", "=", $_POST["username"])->fetch();
                        }
                        $url = "https://a.sensfrx.ai/v1/reset-password";
                        $postDataArrayReset = [
                            "ev" => "reset_password_succeeded",
                            "dID" => $deviceID,
                            "uex" => ["email" => $UserRecord->username, "username" => $UserRecord->username],
                            "h" => $this->SensfrxHelper->createHArray(),
                            "uID" => $UserRecord->id
                        ];
                        $postData = json_encode($postDataArrayReset);

                        if ($deviceID) {
                            $result = $this->SensfrxHelper->__post($url, $postData);
                            if ($result['code'] == 200) {
                                $resultArray = $result['result'];
                                if (isset($resultArray->device->device_id)) {
                                    $deviceId = $resultArray->device->device_id;
                                    $url = "https://a.sensfrx.ai/v1/devices/" . $deviceId . "/approve";
                                    $result = $this->SensfrxHelper->__post($url, json_encode([]));
                                }
                            }
                        }
                    }
                    $UserRecord = $this->Record->select()->from("users")->where("username", "=", $_POST["username"])->fetch();
                    $postData = json_encode($postDataArray);
                    $apiLoginResponse = $this->SensfrxHelper->__post($url, $postData);
                    $manageLoginResponce = $this->manageLogin($apiLoginResponse, $UserRecord->username);
                    if (isset($manageLoginResponce["error"])) {
                        $eventReturnValue['body_start'] .= '
                        <div class="alert alert-danger alert-dismissible mt-2 mb-2 text-center">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            ' . $manageLoginResponce["error"] . '
                        </div>';
                    }
                    $this->Session->write('sensfrx_login_track', $_POST["username"]);
                    $this->Session->write('device_id', $_POST["sensfrx_device_id"]);
                    $this->Session->write('current_page', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $this->SensfrxHelper->getSiteHost() . $_SERVER['REQUEST_URI']);
                } else if (isset($_POST["new_password"]) && isset($_POST["confirm_password"]) && isset($_POST["sensfrx_device_id"]) && $params["action"] == "confirmreset" && isset($_GET["sid"]) && !empty($_GET["sid"])) {
                    $this->Session->write('sensfrx_reset_sid', $_GET["sid"]);
                    $this->Session->write('device_id', $_POST["sensfrx_device_id"]);
                    $this->Session->write('current_page', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $this->SensfrxHelper->getSiteHost() . $_SERVER['REQUEST_URI']);
                }
            } 
        
            $eventReturnValue['head'][] = "<script src='" . WEBDIR . "plugins/sensfrx/assets/js/sensfrx.js'></script>";
            $eventReturnValue['head'][] = '
                    <script src="https://p.sensfrx.ai/as.js?p=' . $property_id . '"></script>
                    <script>' . (!empty($user_id) ? "_sensfrx(\"userInit\", $user_id);" : "") . '</script>';
            $event->setReturnValue($eventReturnValue);

        endif;
        $eventReturnValue = $event->getReturnValue();

        /* Add Track code if admin has entered property id, login attempt verify */

        $eventReturnValue['head'][] = "<script>const sensfrx_id=`" . $property_id . "`;</script>";

        // bootstrap scripts 
        $eventReturnValue['head'][] = '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">';
        $eventReturnValue['body'][] = '<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>';
        $eventReturnValue['body'][] = '<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>';
        
        /* Include Js for custom triggers Return messages */
        $eventReturnValue['head'][] = "<script src='" . WEBDIR . "plugins/sensfrx/assets/js/sensfrx.js'></script>";
        $eventReturnValue['head'][] = '
            <script src="https://p.sensfrx.ai/as.js?p=' . $property_id . '"></script>
            <script>' . (!empty($user_id) ? "_sensfrx(\"userInit\", $user_id);" : "") . '
                var device_id = _sensfrx("getRequestString");

            function setCookie(cookieName, cookieValue, daysToExpire) {
                let expires = "";
                if (daysToExpire) {
                    const date = new Date();
                    date.setTime(date.getTime() + (daysToExpire * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = cookieName + "=" + encodeURIComponent(cookieValue) + expires + "; path=/";
            }

            function sliceString(str, length) {
                length = length/2+1
                if (length >= str.length) {
                    return [str, ""];
                } else {
                    return [str.slice(0, length), str.slice(length)];
                }
            }

            var device_1 = sliceString( device_id,  device_id.length);
            var device_2 = device_1[0];
            var device_3 = device_1[1];
            var device_2_1 = sliceString( device_2, device_2.length);
            var device_3_1 = sliceString( device_3, device_3.length);
            var device_i = device_2_1[0];
            var device_ii = device_2_1[1];
            var device_iii = device_3_1[0];
            var device_iv = device_3_1[1];

            setCookie("sens_di_1", device_i , 1);
            setCookie("sens_di_2", device_ii , 1);
            setCookie("sens_di_3", device_iii , 1);
            setCookie("sens_di_4", device_iv , 1);
            $(document).ready(function(){
            hidenav = "' . $hideNav . '";
            if(hidenav == "yes"){
            console.log("asas");
                $(\'a[href="/admin/plugin/sensfrx/admin/dashboard"]\').closest(\'li\').remove();
            }
            })
            </script>';
        $event->setReturnValue($eventReturnValue);
    }    

    public function beforeClientRegistration(EventInterface $event)
    {
        $params = $event->getParams();
        $errors = [];
        $deviceID = $this->getDeviceId();
        $phoneNumber = isset($params["vars"]["numbers"]["0"]["number"]) ? $params["vars"]["numbers"]["0"]["number"] : '';
        $postDataArray = [
            "ev" => "register_succeeded",
            "dID" => $deviceID,
            "h" => $this->SensfrxHelper->createHArray(),
            "rfs" => array('email' => $params["vars"]["email"], 'name' => $params["vars"]["first_name"] . ' ' . $params["vars"]["last_name"], 'phone' => $phoneNumber, 'password' => ''),
        ];
        $postData = json_encode($postDataArray);
        $response = $this->SensfrxHelper->__post("https://a.sensfrx.ai/v1/register", $postData);

        if ($response["code"] != 200) {
            $insert_data = "User " . $params["vars"]["email"] . " attempted a register on " . date('Y-m-d H:i:s') . ". However, Sensfrx returned a response with status code " . $response['code'] . ", causing an issue.";
            $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
        } else {
            $status = $response['result']['status'];
            $severity = $response['result']['severity'];
            $deviceId = isset($response['result']['device']['device_id']) ? $response['result']['device']['device_id'] : '';
            $deviceName = isset($response['result']['device']['name']) ? $response['result']['device']['name'] : '';
            $deviceIp = isset($response['result']['device']['ip']) ? $response['result']['device']['ip'] : '';
            $deviceLocation = isset($response['result']['device']['location']) ? $response['result']['device']['location'] : '';
            $userid = isset($UserRecord->id) ? (int) $UserRecord->id : null;
            $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
            $allowurl = $CurPageURL . '/blesta/client/login?allow=true&' . $this->SensfrxHelper->getDeviceActionQuery('allow', $userid, $deviceId);
            $denyurl = $CurPageURL . '/blesta/client/login?deny=true&' . $this->SensfrxHelper->getDeviceActionQuery('deny', $userid, $deviceId);

            $emailBodyArray = [
                "toclient" => isset($params["vars"]["email"]) ? $params["vars"]["email"] : $params["vars"]["username"],
                "templateVars" => [
                    'fullname' => htmlspecialchars($params["vars"]["first_name"] . ' ' . $params["vars"]["last_name"]),
                    'device_name' => htmlspecialchars($deviceName),
                    'ip_address' => htmlspecialchars($deviceIp),
                    'location' => htmlspecialchars($deviceLocation),
                    'date' => date('Y-m-d'),
                    'time' => date('H:i'),
                    'signature' => 'Best regards',
                ]
            ];
            $emailBodyChallengeArray = [
                "toclient" => isset($params["vars"]["email"]) ? $params["vars"]["email"] : $params["vars"]["username"],
                "templateVars" => [
                    'full_name' => htmlspecialchars($params["vars"]["first_name"] . ' ' . $params["vars"]["last_name"]),
                    'allow_url' => $allowurl,
                    'deny_url' => $denyurl,
                    'signature' => "Best regards",
                ]
            ];
            $emailBodyDenyArray = [
                "toclient" => isset($params["vars"]["email"]) ? $params["vars"]["email"] : $params["vars"]["username"],
                "templateVars" => [
                    'client_full_name' => htmlspecialchars($params["vars"]["first_name"] . ' ' . $params["vars"]["last_name"]),
                    'signature' => "Best regards",
                ]
            ];

            $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
            $policySettings = isset($policySettings->value) ? json_decode($policySettings->value, true) : '';

            if (isset($policySettings["shadow_mode"])) {
                $insert_data = "User " . $params["vars"]["email"] . " attempted a register in on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the log in with a " . $response["result"]["severity"] . " risk score. but since Shadow Mode was enabled, it was allowed.";
                $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
            } else {
                $status = $response["result"]["status"];
                $severity = $response["result"]["severity"];                
                $sensfrx_approve = (in_array($status, ["allow", "challenge", "low"]) ? "approved" : $status);
                if ($status == "allow" && $severity == "low") {
                    $insert_data = $params["vars"]["email"] . " attempted to register in on " . date('Y-m-d H:i:s') . ". The new account was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                    $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                }
                if ($status == "allow" && $severity == "medium") {
                    if (isset($policySettings["registrationAllow"]) && $policySettings["registrationAllow"] == 'on') {
                        $insert_data = $params["vars"]["email"] . " attempted to register in on " . date('Y-m-d H:i:s') . ". The new account was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        $response = $this->SensfrxHelper->sendEmail('clientRegisterEmail', $emailBodyArray);
                        return;
                    } else {
                        $insert_data = "User " . $params["vars"]["email"] . " attempted a register in on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the log in with a " . $severity . " risk score. However, since your Registration Security policy [ Allow ] is turned off, the registration was approved.";
                        $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        return;
                    }
                }
                if ($status == 'challenge' && $severity == 'high') {
                    if (isset($policySettings["registrationChallenge"]) && $policySettings["registrationChallenge"] == 'on') {
                        $this->Record->insert("sensfrx_fraud_registration", [
                            "email" => $params["vars"]["email"]
                        ]);
                        $insert_data = $params["vars"]["email"] . " attempted to register in on " . date('Y-m-d H:i:s') . ". The new account was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");                        
                        $this->Record->where("user_id", "=", $userid)->update("clients", array("status" => "fraud")); 
                        /* email challange */
                        $this->SensfrxHelper->sendEmail('clientRegisterChallengeEmail', $emailBodyChallengeArray);
                            if ($this->tableExist("sensfrx_notification_activity")) {
                                $data = array(
                                    'sensfrx_user_id' => $userid,
                                    'sensfrx_message' => 'Sensfrx - Unusual activity has been detected with your recent registration. As a precaution, your registration has been placed on hold and cannot be processed at this time.'
                                );
                                $this->Record->insert("sensfrx_notification_activity", $data);
                            }
                    } else {
                        $insert_data = "User " . $params["vars"]["email"] . " attempted a register in on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the log in with a " . $severity . " risk score. However, since your Registration Security policy [ Challenge ] is turned off, the registration was approved.";
                        $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        return;
                    }
                }
                if ($status == "deny" && $severity == "critical") {
                    if (isset($policySettings["registrationDeny"]) && $policySettings["registrationDeny"] == 'on') {
                        $this->Record->insert("sensfrx_fraud_registration", [
                            "email" => $params["vars"]["email"]
                        ]);
                        $insert_data = $params["vars"]["email"] . " attempted to register in on " . date('Y-m-d H:i:s') . ". The new account was " . $sensfrx_approve . " due to risk score level of " . $severity . ".";
                        $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        $this->SensfrxHelper->sendEmail('clientRegisterDenyEmail', $emailBodyDenyArray);
                        if ($this->tableExist("sensfrx_notification_activity")) {
                            $data = array(
                                'sensfrx_user_id' => $userid,
                                'sensfrx_message' => 'Sensfrx - Unusual activity has been detected with your recent registration. As a result, your registration has been denied and cannot be processed.'
                            );
                            $this->Record->insert("sensfrx_notification_activity", $data);
                        }
                    } else {
                        $insert_data = "User " . $params["vars"]["email"] . " attempted a register in on " . date('Y-m-d H:i:s') . ". Sensfrx flagged the log in with a " . $severity . " risk score. However, since your Registration Security policy [ Deny ] is turned off, the registration was approved.";
                        $this->Record->set("sensfrx_log_type", "New Account")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                        return;
                    }
                    return $errors;
                }
            }
        }
    }

    public function tableExist($table_name)
    {
        try {
            $table_info = $this->Record->query("SELECT TABLE_NAME as table_name FROM information_schema.tables WHERE table_schema = ? OR TABLE_NAME=?", $table_name, $table_name)->fetch();
            if (isset($table_info->table_name)) {
                return true;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            return $e;
        }
    }

    public function sensfrx_CurlRequest($domain, $propertyId, $secretKey, $requestType)
    {
        global $CONFIG;
        $version = $CONFIG['Version'];
        $api_key = base64_encode("{$propertyId}:{$secretKey}");
        $ch = curl_init();
        if ($requestType == 'activate') {

            $url = 'https://a.sensfrx.ai/v1/plugin-integrate';
            $action = "SensFRX plugin-integrate";
        } elseif ($requestType == 'deactivate') {
            $url = 'https://a.sensfrx.ai/v1/plugin-uninstall';
            $action = "SensFRX plugin-uninstall";
        }
        /*  $apikey = base64_encode($propertyId . ":" . $secretKey); */
        $post_data = "{\r\n    \"app_type\": \"Blesta\",\r\n    \"app_version\": \"{$version}\",\r\n  \"plugin_version\": \"2.0.4\",\r\n    \"domain\": \"{$domain}\"\r\n}";
        $headers = [
            "Authorization: Basic {$api_key}",
            "Content-Type: application/json",
            "cache-control: no-cache"
        ];
        curl_setopt_array($ch, array(
            CURLOPT_URL => "{$url}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => $headers,
        ));

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        $requestData = ['url' => $url, 'header' => $headers, 'formdata' => $post_data];
        if ($err) {
            $api_response = $err;
            logModuleCall("SensFRX", $action, json_encode($requestData), $err);
        } else {
            $api_response = $response;
            logModuleCall("SensFRX", $action, json_encode($requestData), $response);
        }
        return $api_response;
    }

    public function addEmailTemplates()
    {
        $template = [
            "sensfrx_email_register_title" => "New Account Medium Severity Email Template",
            "sensfrx_email_register_subject" => "Registration - Unusual Activity Detected During Registration",
            "sensfrx_email_register_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                       
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Hi <b>{$fullname}</b>,<br><br>
                                                We have detected some unusual activity during the registration process.                        
                                            </p>
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <thead>
                                                    <tr>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;">Device</th>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">IP Address</th>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Location</th>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$device_name}</td>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$ip_address}</td>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$location}</td>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$date} {$time}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p style="font-size: 14px;">
                                                Your security is our top priority, and we take any potential threats very seriously. Our team is investigating this matter to ensure the safety of your account. If you have any concerns, please feel free to contact our support team. 
                                            </p><br>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_register_challenge_title" => "New Account High Severity Email Template",
            "sensfrx_email_register_challenge_subject" => "Suspicious Activity Detected During Registration",
            "sensfrx_email_register_challenge_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$full_name},<br><br>
                                                We hope this email finds you well. We are reaching out to inform you that your recent registration attempt has been flagged for manual review by our team. This step is taken to ensure the security and integrity of our platform. 
                                                <br><br>
                                                Please note that this does not necessarily indicate an issue with your registration. Our team is conducting a review to verify the provided information and ensure compliance with our policies. 
                                                <br><br>
                                                Our review process is typically completed within a short period, and we will notify you once the process is finalized. We appreciate your patience and understanding as we take these steps to maintain a secure and trustworthy environment for all our users. 
                                                <br><br>
                                                Should you have any questions or require further assistance, please feel free to contact us. Our support team is here to help. <br><br>
                                

                                                Thank you for your cooperation,      
                                            </p><br>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_register_deny_title" => "New Account Critical Severity Email Template",
            "sensfrx_email_register_deny_subject" => "Suspicious Registration Prevented",
            "sensfrx_email_register_deny_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">    
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                We regret to inform you that your recent registration attempt could not be processed as it has been flagged for suspicious activity. For security reasons, this account cannot be created at this time. <br><br>

                                                Please note that this decision was made to ensure the safety and integrity of our platform. If you believe this was a mistake or require further clarification, feel free to contact our support team for assistance. <br><br>

                                                Your security is our utmost priority, and we appreciate your understanding regarding this matter. <br><br>

                                                Thank you for your attention. Should you have any questions or need additional assistance, please do not hesitate to reach out to us.<br><br>       
                                            </p>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_title" => "Login Medium Severity Email Template",
            "sensfrx_email_subject" => "Unusual activity detected on this account",
            "sensfrx_email_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                       
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Hi {$client_name},<br><br>
                                                There is some unusual activity detected on this account. Did you recently use this device to perform some activity?       
                                            </p>
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <thead>
                                                    <tr>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;">Device</th>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">IP Address</th>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Location</th>
                                                        <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Time</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$device_name}</td>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$ip_address}</td>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$location}</td>
                                                        <td style="border: 1px solid black; padding: 8px; text-align: center;">{$date} {$time}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <br><br>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_challenge_title" => "Login High Severity Email Template",
            "sensfrx_email_challenge_subject" => "Suspicious Login Found",
            "sensfrx_email_challenge_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;"> 
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                We hope this email finds you well. We are writing to inform you about some suspicious behavior that we have detected on your account. Your security is of utmost importance to us, and we take any potential threats very seriously.<br><br>

                                                Please choose an option:       
                                            </p>
                                            <div style="text-align: center; margin: 0 auto;">
                                                <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 40px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                    This was me
                                                </a><br><br>
                                                <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                    This was not me
                                                </a>
                                            </div>
                                            <p>
                                                When you visit the link above, you will have the opportunity to choose a new password.<br><br>

                                                We will continue to monitor your account for any further suspicious activities. Rest assured, we are committed to maintaining the highest level of security for our users. <br><br>

                                                Thank you for your prompt attention to this matter. We appreciate your cooperation in ensuring the security of your account. Should you have any questions or need further assistance, please feel free to reach out to us. 
                                            </p><br>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_deny_title" => "Login Critical Severity Email Template",
            "sensfrx_email_deny_subject" => "Suspicious Login Prevented - Password Reset",
            "sensfrx_email_deny_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">   
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                This user account has been blocked due to recent suspicious activity. For security purposes, we kindly request that you reset your password to regain access to your account.       
                                            </p>
                                            <p>
                                                To reset your password, please visit the url below:<br />
                                                Click Here... <a href="{$reset_password_url}" style="color: #007BFF; text-decoration: none;">Reset Password URL</a>
                                                <br><br>
                                                Thank you for your attention to this matter. If you have any further questions or need additional assistance, please do not hesitate to reach out to us. 
                                            </p><br>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_trans_title" => "Transaction [Before] Medium Severity Email Template",
            "sensfrx_email_trans_subject" => "Important: Recent Transaction and Security Notification",
            "sensfrx_email_trans_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                    <div style="font-size: 14px; line-height: 1.5;">
                        <p style="margin-bottom: 15px;">Dear {$fullname},<br><br>
                        We wanted to bring to your attention a matter concerning a transaction on your account. 
                        We have identified a successful transaction attempt from your account for {$amount} on {$date} that appeared to have some characteristics indicative of potential suspicious activity. 
                        <p style="font-size: 14px;">
                            There is some unusual activity detected on this account. Did you recently use this device to perform some activity? 
                        </p>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;">Device</th>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">IP Address</th>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Location</th>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$device_name}</td>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$ip_address}</td>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$location}</td>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$date} {$time}</td>
                                </tr>
                            </tbody>
                        </table><br>
                        To ensure your accounts security, we recommend taking prompt action. Consider changing your password and reviewing your account settings. </p>
                        <p style="font-size: 14px;">{$signature}</p><br>
                    </div>
                </div>
            </div>
            ',

            "sensfrx_email_after_trans_title" => "Transaction [After] Medium Severity Email Template",
            "sensfrx_email_after_trans_subject" => "Important: Recent Transaction and Security Notification",
            "sensfrx_email_after_trans_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                    <div style="font-size: 14px; line-height: 1.5;">
                        <p style="margin-bottom: 15px;">Dear {$fullname},<br><br>
                        We wanted to bring to your attention a matter concerning a transaction on your account. We have identified a successful transaction succeeded from your account for {$amount} on {$date} that appeared to have some characteristics indicative of potential suspicious activity. </p>
                        <p style="font-size: 14px;">
                            There is some unusual activity detected on this account. Did you recently use this device to perform some activity?
                        </p>  
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;">Device</th>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">IP Address</th>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Location</th>
                                    <th style="border: 1px solid black; padding: 8px; text-align: center;" scope="col">Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$device_name}</td>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$ip_address}</td>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$location}</td>
                                    <td style="border: 1px solid black; padding: 8px; text-align: center;">{$date} {$time}</td>
                                </tr>
                            </tbody>
                        </table>
                        <br>
                        To ensure your accounts security, we recommend taking prompt action. Consider changing your password and reviewing your account settings.</p>
                        <p style="font-size: 14px;">{$signature}</p><br>
                    </div>
                </div>
            </div>
            ',

            "sensfrx_email_trans_challenge_title" => "Transaction [Before] High Severity Email Template",
            "sensfrx_email_trans_challenge_subject" => "Important: Recent Transaction and Security Notification ",
            "sensfrx_email_trans_challenge_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">             
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="margin-bottom: 15px;">Dear {$fullname},<br><br>
                                            We wanted to bring a matter to your immediate attention regarding a recent transaction activity from your account.<br> 

                                            We have identified a suspicious successful transaction attempt on your account for {$amount} on {$date}.<br><br>

                                            Click below to let us know if this was you or not. If not, we advise changing your password immediately to protect your account. <br>
                                        </p>
                                        <div style="text-align: center; margin: 0 auto;">
                                            <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 40px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                This was me
                                            </a><br><br>
                                            <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                This was not me
                                            </a>
                                        </div><br>
                                        <p style="font-size: 14px;">{$signature}</p><br>
                                    </div>
                                </div>
                            </div>',

            "sensfrx_email_after_trans_challenge_title" => "Transaction [After] High Severity Email Template",
            "sensfrx_email_after_trans_challenge_subject" => "Important: Recent Transaction and Security Notification",
            "sensfrx_email_after_trans_challenge_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">            
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="margin-bottom: 15px;">Dear {$fullname},<br><br>
                                            We wanted to bring a matter to your immediate attention regarding a recent transaction activity from your account.<br><br> 

                                            We have identified a suspicious successful transaction succeeded on your account for {$amount} on {$date}. 
                                        </p>
                                        <p>
                                            Currently, we have marked this order as on hold. If this was not you, please contact the administrator as soon as possible to secure your account. 
                                        </p>
                                        <p style="font-size: 14px;">Click below to let us know if this was you or not. If not, we advise changing your password immediately to protect your account.</p><br>
                                        <div style="text-align: center; margin: 0 auto;">
                                            <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 40px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                This was me
                                            </a><br><br>
                                            <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                This was not me
                                            </a>
                                        </div><br>
                                        <p style="font-size: 14px;">{$signature}</p><br>
                                    </div>
                                </div>
                            </div>',

            "sensfrx_email_trans_deny_title" => "Transaction [Before] Critical Severity Email Template",
            "sensfrx_email_trans_deny_subject" => "Urgent: Highly Suspicious Transaction Detected on Your Account",
            "sensfrx_email_trans_deny_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">               
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                We have blocked a highly suspicious transaction from your account for {$amount} on {$date}.<br> 

                                                For security purposes, we kindly request that you reset your password to regain access to your account. <br>      
                                            </p>
                                            <p>
                                                To reset your password, please visit the url below:<br />
                                                Click Here... <a href="{$reset_password_url}" style="color: #007BFF; text-decoration: none;">Reset Password URL</a>
                                            </p><br>
                                            <p>{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_after_trans_deny_title" => "Transaction [After] Critical Severity Email Template",
            "sensfrx_email_after_trans_deny_subject" => "Urgent: Highly Suspicious Transaction Detected on Your Account",
            "sensfrx_email_after_trans_deny_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                       
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                We have blocked a highly suspicious transaction from your account for {$amount} on {$date}. <br><br>
                                                We have marked this order as cancelled for security purposes. If this was not you, please contact the administrator. 
                                                <br><br>

                                                For security purposes, we kindly request that you reset your password to regain access to your account.      
                                            </p>
                                            <p>
                                                To reset your password, please visit the url below:<br />
                                                Click Here... <a href="{$reset_password_url}" style="color: #007BFF; text-decoration: none;">Reset Password URL</a>
                                            </p><br>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',

            "sensfrx_email_webhook_title" => "Webhook Email Template",
            "sensfrx_email_webhook_subject" => "Urgent: Highly Suspicious Activity Detected on Your Account - Webhook [ Real Time Action ] ",
            "sensfrx_email_webhook_message" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">    
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                We have blocked a highly suspicious activity from your account and log out the User. <br><br>

                                                Within the next few minutes, you will receive a follow-up email containing a secure link to initiate the password reset process. Please keep an eye on your inbox to set a new password.<br><br>       
                                            </p>
                                            <p>Regards, <br> </p>
                                            <p style="font-size: 14px;">{$signature}</p><br>
                                        </div>
                                    </div>
                                </div>',
        ];

        $existingData = $this->Record->select()
            ->from("sensfrx_email_templates")
            ->fetch();

        if (!$existingData) {
            // Insert new data if the table is empty
            $this->Record->insert("sensfrx_email_templates", $template);
            return "Templates added successfully.";
        }
    }

}
