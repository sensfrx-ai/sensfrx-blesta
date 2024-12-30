<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class Hooks extends SensfrxController
{

    public function preAction()
    {
        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        Loader::loadComponents($this, ['Session', 'Record', 'Emails', 'Clients', 'Users', 'Contacts', 'Html', 'Companies']);
        $this->uses(['Clients', 'Sensfrx.SaveManageOptions', 'Sensfrx.SensfrxHelper', 'Users', 'Sensfrx.SaveManagePolicies', 'Staff']);
        Language::loadLang('sensfrx_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
    }

    public function webhook()
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        // echo '<pre>';
        // print_r($data);       
        $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
        $row_count = $this->Record->select(["COUNT(*)" => "total_rows"])
            ->from("sensfrx_webhook")
            ->fetch();
        echo "Number of rows in the table: " . $row_count->total_rows;
        if (count($data) > 0) {
            if (isset($data["status"]) && !empty($data["status"]) && isset($data["severity"]) && !empty($data["severity"]) && isset($data["user"]) && !empty($data["user"])) {
                $status  = $data['status'];
                $severity  = $data['severity'];
                $user  = $data['user'];
                $userId = $user['user_id'];
                $client = $this->Clients->getByUserId($userId);   
                $valueDecoded = json_decode($policySettings->value);
                $sensfrx_webhook = $valueDecoded->sensfrx_webhook;   
                if ($status == "deny" && $severity == "critical" && isset($sensfrx_webhook) && ($sensfrx_webhook == 'on' || $sensfrx_webhook == '1') && isset($client->id)) {
                    $existing = $this->Record->select()
                        ->from("sensfrx_webhook")
                        ->where("sensfrx_user_id", "=", $userId)
                        ->fetch();
                    if ($existing) {
                        echo "User ID already exists. No insertion performed.";
                    } else {
                        $this->Record->set("sensfrx_user_id", $userId)
                            ->insert("sensfrx_webhook");
                        echo "User ID inserted successfully.";
                    }
                    $this->printJson(
                        [
                            "status" => ($status ? "success" : "error"),
                            "message" => "parameters not found.",
                        ]
                    );
                }
                if ($status == "allow" && isset($sensfrx_webhook) && ($sensfrx_webhook == 'on' || $sensfrx_webhook == '1') && isset($client->id)) {
                    $existing = $this->Record->select()
                        ->from("sensfrx_webhook")
                        ->where("sensfrx_user_id", "=", $userId)
                        ->fetch();
                    if ($existing) {
                        echo "User ID deleted.";
                        $this->Record->from("sensfrx_webhook")
                            ->where("sensfrx_user_id", "=", $userId)
                            ->delete();
                    } else {                        
                        echo "User ID not found.";
                    }
                    $this->printJson(
                        [
                            "status" => ($status ? "success" : "error"),
                            "message" => "parameters not found.",
                        ]
                    );
                }
            } else {
                $this->printJson(
                    [
                        "status" => "error",
                        "message" => "parameters not found.",
                    ]
                );
            }
        } else {
            $this->printJson(
                [
                    "status" => "error",
                    "message" => "Invalid",
                ]
            );
        }
    }

    public function transaction_webhook()
    {
        $pluginData = $this->Record->select()->from("plugins")->where("name", "=", "Sensfrx")->numResults();
        if ($pluginData > 0) {
            $json_input = file_get_contents('php://input');
            $data = json_decode($json_input, true);
            // $data = [
            //     "status" => "allow",
            //     "severity" => "low",
            //     "risk_score" => "0",
            //     "transaction_id" => "20241122060703218",
            // ];
            $this->Record->insert("policies_setting", array("company_id" => "500", "key" => date("Y-m-d H:i:s", time())."tranection_webhook", "value" => json_encode($data)));
            $order_id = $data['transaction_id'];
            $order_risk_score = $data['risk_score'];
            if (isset($order_risk_score) && $order_risk_score == "0" && isset($data['status']) && $data['status'] == "allow") {

                if ($order_id) {
                    $invoiceId = substr($order_id, 14);
                    
                    $countOrders = $this->Record->select()->from("orders")->where("invoice_id", "=", $invoiceId)->fetch();

                    if ($countOrders->order_number > 0) {
                        $response = $this->Record->where("invoice_id", "=", $invoiceId)->update("orders", array("status" => "accepted"));
                        $this->Record->where("invoice_id", "=", $invoiceId)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Transection Webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status successfully updated to accepted', "created_at" => date("Y-m-d H:i:s", time())));
                    } else {
                        $this->Record->where("invoice_id", "=", $invoiceId)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Transection Webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status update failed. No rows affected.', "created_at" => date("Y-m-d H:i:s", time())));
                        $this->printJson(
                            [
                                "status" => "error",
                                "message" => "order not found",
                            ]
                        );
                    }
                }
            } else if (isset($order_risk_score) && $order_risk_score == "100" && isset($data['status']) && $data['status'] == "deny") {
                if ($order_id) {
                    $invoiceId = substr($order_id, 14);
                    $countOrders = $this->Record->select()->from("orders")->where("invoice_id", "=", $invoiceId)->fetch();
                    if ($countOrders->order_number > 0) {
                        $response = $this->Record->where("invoice_id", "=", $invoiceId)->update("orders", array("status" => "canceled"));
                        $this->Record->where("invoice_id", "=", $invoiceId)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "transection webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status successfully updated to canceled', "created_at" => date("Y-m-d H:i:s", time())));
                    } else {
                        $this->Record->where("invoice_id", "=", $invoiceId)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "transection webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status update failed. No rows affected.', "created_at" => date("Y-m-d H:i:s", time())));
                        $this->printJson(
                            [
                                "status" => "error",
                                "message" => "order not found",
                            ]
                        );
                    }
                }
            } else {
                $this->printJson(
                    [
                        "status" => "error",
                        "message" => "parrameter not found",
                    ]
                );
            }
        } else {
            $this->printJson(
                [
                    "status" => "error",
                    "message" => "module Uninstalled",
                ]
            );
        }
        die;
    }

    private function printJson($args = [])
    {
        header("HTTP/1.1 200 OK");
        echo json_encode($args);
        exit;
    }
}
