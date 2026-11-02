<?php

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
        $this->verifySignature('webhook', $input);
        $data = json_decode($input, true);
        $policySettings = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
        if (is_array($data) && count($data) > 0) {
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
                    } else {
                        $this->Record->set("sensfrx_user_id", $userId)
                            ->insert("sensfrx_webhook");
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
                        $this->Record->from("sensfrx_webhook")
                            ->where("sensfrx_user_id", "=", $userId)
                            ->delete();
                    } else {                        
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
        $json_input = file_get_contents('php://input');
        $this->verifySignature('transaction_webhook', $json_input);
        $data = json_decode($json_input, true);
        if ( isset($data['status']) && isset($data['severity']) && isset($data['order_id']) && isset($data['risk_score']) ) {

            $order_id = $data['order_id'];
            $order_risk_score = $data['risk_score'];
            if (isset($order_risk_score) && $order_risk_score == "0" && isset($data['status']) && $data['status'] == "allow") {

                if ($order_id) {
                    $countOrders = $this->Record->select()->from("orders")->where("id", "=", $order_id)->fetch();

                    if ($countOrders->order_number > 0) {
                        $response = $this->Record->where("id", "=", $order_id)->update("orders", array("status" => "accepted"));
                        $invoice_id = $this->Record->select("invoice_id")->from("orders")->where("id", "=", $order_id)->fetch();
                        $this->Record->where("id", "=", $invoice_id->invoice_id)->update("invoices", ["status" => "active"]);
                        $this->Record->where("id", "=", $order_id)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Transaction Webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status successfully updated to accepted', "created_at" => date("Y-m-d H:i:s", time())));
                    } else {
                        $this->Record->where("id", "=", $order_id)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Transaction Webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status update failed. No rows affected.', "created_at" => date("Y-m-d H:i:s", time())));
                        $this->printJson(
                            [
                                "status" => "error",
                                "message" => "order not found",
                            ]
                        );
                    }
                }
            } elseif (isset($order_risk_score) && $order_risk_score == "100" && isset($data['status']) && $data['status'] == "deny") {
                if ($order_id) {
                    $countOrders = $this->Record->select()->from("orders")->where("id", "=", $order_id)->fetch();
                    if ($countOrders->order_number > 0) {
                        $response = $this->Record->where("id", "=", $order_id)->update("orders", array("status" => "canceled"));
                        $invoice_id = $this->Record->select("invoice_id")->from("orders")->where("id", "=", $order_id)->fetch();
                        $this->Record->where("id", "=", $invoice_id->invoice_id)->update("invoices", ["status" => "void"]);
                        $this->Record->where("id", "=", $order_id)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "transaction webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status successfully updated to canceled', "created_at" => date("Y-m-d H:i:s", time())));
                    } else {
                        $this->Record->where("id", "=", $order_id)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "transaction webhook", "sensfrx_log1" => 'Order Number ' . $countOrders->order_number . ' status update failed. No rows affected.', "created_at" => date("Y-m-d H:i:s", time())));
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
            die;
        }
        if ( isset($data['status']) && isset($data['severity']) && isset($data['reg_id']) && isset($data['risk_score']) ) {
            $email = $data['email'];
            $reg_risk_score = $data['risk_score'];
            if (isset($reg_risk_score) && $reg_risk_score == "0" && isset($data['status']) && $data['status'] == "allow") {
                if ($email) {
                    // Fetch client details using email
                    $client = $this->Record
                        ->select(["clients.id", "clients.status", "contacts.email"])
                        ->from("clients")
                        ->innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)
                        ->where("contacts.email", "=", $email)
                        ->fetch();

                    if ($client) { // If client exists
                        // Update client status
                        $this->Record->where("id", "=", $client->id)->update("clients", ["status" => "active"]);
                        $this->Record->where("id", "=", $client->id)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Registration Webhook", "sensfrx_log1" => 'Client ID ' . $client->id . ' status successfully updated to active', "created_at" => date("Y-m-d H:i:s", time())));

                        // Return success response
                        $this->printJson([
                            "status" => "success",
                            "message" => "Client status updated successfully."
                        ]);
                    }
                } else {
                    $this->Record->where("email", "=", $email)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Registration Webhook", "sensfrx_log1" => 'Client Email ' . $email . ' status update failed. No rows affected.', "created_at" => date("Y-m-d H:i:s", time())));
                    $this->printJson(
                    [
                            "status" => "error",
                            "message" => "client not found",
                        ]
                    );
                }            
            } else if (isset($reg_risk_score) && $reg_risk_score == "100" && isset($data['status']) && $data['status'] == "deny") {
                if ($email) {
                    // Fetch client details using email
                    $client = $this->Record
                        ->select(["clients.id", "clients.status", "contacts.email"])
                        ->from("clients")
                        ->innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)
                        ->where("contacts.email", "=", $email)
                        ->fetch();

                    if ($client) { // If client exists
                        // Update client status
                        $this->Record->where("id", "=", $client->id)->update("clients", ["status" => "inactive"]);
                        $this->Record->where("id", "=", $client->id)->insert("sensfrx_real_activity", array("sensfrx_log_type" => "Registration Webhook", "sensfrx_log1" => 'Client Email ' . $client->id . ' status successfully updated to inactive', "created_at" => date("Y-m-d H:i:s", time())));

                        // Return success response
                        $this->printJson([
                            "status" => "success",
                            "message" => "Client status updated successfully."
                        ]);
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
            die;
        }
    }

    /*
        Rejects the request unless x-sfx-timestamp (Unix seconds) is within 300 s of now and x-sfx-signature
        is the HMAC-SHA256 (hex) of timestamp . "." . raw body keyed with a signing secret Sensfrx issued for
        a URL ending in this action's path (stored by SensfrxPlugin::structureLoad). Replay protection: the
        body-only x-signature header is not accepted.
    */
    private function verifySignature($action, $rawBody)
    {
        $timestamp = isset($_SERVER['HTTP_X_SFX_TIMESTAMP']) ? (string) $_SERVER['HTTP_X_SFX_TIMESTAMP'] : '';
        $signature = isset($_SERVER['HTTP_X_SFX_SIGNATURE']) ? strtolower(trim((string) $_SERVER['HTTP_X_SFX_SIGNATURE'])) : '';
        $suffix = '/hooks/' . $action;
        $valid = false;
        if ($signature !== '' && preg_match('/^\d{1,12}$/D', $timestamp) && abs(time() - (int) $timestamp) <= 300) {
            foreach ($this->SensfrxHelper->getWebhookSecrets() as $url => $secret) {
                $url = (string) $url;
                if (substr($url, -strlen($suffix)) !== $suffix || !is_string($secret) || $secret === '') {
                    continue;
                }
                if (hash_equals(hash_hmac('sha256', $timestamp . '.' . (string) $rawBody, $secret), $signature)) {
                    $valid = true;
                    break;
                }
            }
        }
        if (!$valid) {
            header("HTTP/1.1 401 Unauthorized");
            echo json_encode(["status" => "error", "message" => "Invalid signature"]);
            exit;
        }
    }

    private function printJson($args = [])
    {
        header("HTTP/1.1 200 OK");
        echo json_encode($args);
        exit;
    }
}
