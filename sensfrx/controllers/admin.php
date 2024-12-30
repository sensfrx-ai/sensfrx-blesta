<?php
class Admin extends SensfrxController
{
    public function preAction()
    {
        parent::preAction();
        if (!$this->PluginManager->isInstalled('sensfrx', $this->company_id)) {
            $this->redirect($this->client_uri);
        }
        $this->requireLogin();
        Loader::loadComponents($this, ['Session', "Record"]);
        $this->uses(['Sensfrx.SaveManagePolicies', 'Sensfrx.SaveManageOptions',  'Sensfrx.SensfrxHelper', "Emails"]);
        /*  Restore structure view l0ocati.on of the admin portal */
        $this->structure->setDefaultView(APPDIR);
        Language::loadLang('sensfrx_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
        Language::loadLang('sensfrx_admin_dashboard', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
    }

    public function dashboard()
    {
        /*  Set the page title */
        $this->structure->set('page_title', Language::_('Sensfrx.admin.dashboard.page_title', true));

        /*  Load necessary components */
        Loader::loadComponents($this, ['Record']);

        /*  Fetch policies and configuration */
        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();

        /*  Prepare API URL and data */
        $fromDate = date('d-m-Y');
        $toDate = date('d-m-Y', strtotime('-7 days'));
        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayDashboad") {
            if (isset($_REQUEST["date-dropdown"]) && $_REQUEST["date-dropdown"] != "") {
                if ($_REQUEST["date-dropdown"] == "filter-30") {
                    $toDate = date('d-m-Y', strtotime('-30 days'));
                } elseif ($_REQUEST["date-dropdown"] == "filter-365") {
                    $toDate = date('d-m-Y', strtotime('-365 days'));
                } else {
                    $toDate = date('d-m-Y', strtotime('-7 days'));
                }
            }
        }

        $selected_value = isset($_POST['date-dropdown']) ? $_POST['date-dropdown'] : 'filter-7';
        $method = 'GET';
        $transurl = "https://a.sensfrx.ai/v1/trans-stats?from_date={$toDate}&to_date={$fromDate}";
        $atourl = "https://a.sensfrx.ai/v1/ato-stats?from_date={$toDate}&to_date={$fromDate}";
        $regurl = "https://a.sensfrx.ai/v1/reg-stats?from_date={$toDate}&to_date={$fromDate}";

        $postData = ['h' => $hArray];
        $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") && (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            $resultPrivacyUpdate["result"]["status"] = "fail";
            $resultPrivacyUpdate["result"]["message"] = "<b>Not Authorized!<b> <a target='_blank' href='" . $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/" . $moduleDetails->id . "'>click here to configure module »</a>";
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $result_trans = $this->__sensfrxApiCallonURL($transurl, $method, $postData, $headers);
        $result_ato = $this->__sensfrxApiCallonURL($atourl, $method, $postData, $headers);
        $result_reg = $this->__sensfrxApiCallonURL($regurl, $method, $postData, $headers);

        if ($result_trans["code"] != 200) {
            $ajaxResponseArray["status"] = $resultPrivacyUpdate["result"]["status"] = "fail";
            $ajaxResponseArray["message"] = $resultPrivacyUpdate["result"]["message"] = "<b>Not Authorized!<b> <a target='_blank' href='" . $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/" . $moduleDetails->id . "'>click here to configure module »</a>";
        } else {
            $ajaxResponseArray["status"] = "success";
            if ($result_trans["code"] == 200) {
                $ajaxResponseArray["trans"] = $result_trans = $result_trans["result"]["data"];
            }
            if ($result_ato["code"] == 200) {
                $ajaxResponseArray["ato"] = $result_ato = $result_ato["result"]["data"];
            }
            if ($result_reg["code"] == 200) {
                $ajaxResponseArray["reg"] = $result_reg = $result_reg["result"]["data"];
            }
        }
        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayDashboad") {
            echo json_encode($ajaxResponseArray);
            die;
        }

        /* Set view parameters */
        $this->view->setView('dashboard', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        /* Check and set session success or error messages */
        $successMessage = $this->Session->read('sensfrx_policies_success');
        $errorMessage = $this->Session->read('sensfrx_policies_error');

        if ($successMessage) {
            $this->set("success", $successMessage);
            $this->Session->write('sensfrx_policies_success', null);
        } elseif ($errorMessage) {
            $this->set("error", $errorMessage);
            $this->Session->write('sensfrx_policies_error', null);
        }

        /* Set assets for the view */
        $this->set("assets", [
            "stylesheet" => [
                $view_url . 'css/style.css',
            ]
        ]);

        $activePage = __FUNCTION__;
        /* Set variables for the view */
        $this->set(compact("result_trans", "result_ato", "result_reg", "selected_value", "activePage"));

        /*  Return the view to be displayed */
        return $this->view->fetch();
    }

    public function update()
    {

        if (isset($_POST["save_policies"])) {
            $challenge = (isset($_POST["challenge"]) ? "1" : "0");
            $allow = (isset($_POST["allow"]) ? "1" : "0");
            $deny = (isset($_POST["deny"]) ? "1" : "0");
            $shadow = (isset($_POST["shadow"]) ? "1" : "0");
            $response = $this->SaveManagePolicies->update($challenge, $allow, $deny, $shadow);
            if ($response) {
                $this->Session->write(
                    'sensfrx_policies_success',
                    Language::_(
                        'SensfrxPolicies.success',
                        true
                    )
                );
            } else {
                $this->Session->write(
                    'sensfrx_policies_error',
                    Language::_(
                        'SensfrxPolicies.error',
                        true
                    )
                );
            }
        } else {
            $this->Session->write(
                'sensfrx_policies_error',
                Language::_(
                    'SensfrxPolicies.error',
                    true
                )
            );
        }
        $this->redirect($this->admin_uri . 'plugin/sensfrx/admin/dashboard');
        exit;
    }

    public function webhook_update()
    {
        $config_options = $this->SaveManageOptions->get();
        if (isset($config_options->domain) && !empty($config_options->domain) && isset($config_options->property_id) && !empty($config_options->property_id) && isset($config_options->property_secret) && !empty($config_options->property_secret)) {
            $webhook_url = $this->base_url . (substr($this->client_uri, 0, 1) == "/" ? substr($this->client_uri, 1) : $this->client_uri) . "plugin/sensfrx/hooks/webhook";

            $result = $this->apiCall("https://a.sensfrx.ai/v1/webhooks/?url=" . $webhook_url);

            if (isset($result["status"]) && $result["status"] == "success") {
                $this->Session->write(
                    'sensfrx_policies_success',
                    (isset($result["message"]) ? $result["message"] : Language::_(
                        'Sensfrx.admin.dashboard.webhook.success',
                        true
                    ))
                );
            } elseif (isset($result["status"]) && $result["status"] == "error") {
                $this->Session->write(
                    'sensfrx_policies_error',
                    (isset($result["message"]) ? $result["message"] : Language::_(
                        'Sensfrx.admin.dashboard.webhook.success',
                        true
                    ))
                );
            } else {
                $this->Session->write(
                    'sensfrx_policies_error',
                    Language::_(
                        'Sensfrx.admin.dashboard.webhook.error.save',
                        true
                    )
                );
            }
        } else {
            $this->Session->write(
                'sensfrx_policies_error',
                Language::_(
                    'Sensfrx.admin.dashboard.webhook.error.property',
                    true
                )
            );
        }
        $this->redirect($this->admin_uri . 'plugin/sensfrx/admin/dashboard');
        exit;
    }

    public function order_review()
    {
        $this->structure->set('page_title', Language::_('Sensfrx.admin.order_review.page_title', true));
        $errorMessage = "";
        $matching_order_ids = array();
        $matching_invoice_ids = array();
        $orderResponse["result"]["status"]  = "";
        $orderResponse["result"]["message"]  = "";
        $resultPrivacyUpdate = [];
        /*   Set View */
        $this->view->setView('order_review', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();

        $hArray = $this->createHArray();
        $postData = array();
        $postData['h'] = $hArray;
        $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        /* getting transaction reviews */
        if (isset($_POST["submitOrderReviewAll"])) {
            if ($_POST["submitOrderReviewAll"] == "approveAll") {
                $action = "approve";
            } elseif ($_POST["submitOrderReviewAll"] == "rejectAll") {
                $action = "reject";
            }
            if (in_array($action, ["approve", "reject"]) && $action != "") {
                foreach ($_POST as $key => $item) {
                    if (strpos($key, "Select_transId@@") !== false) {
                        $orderData[] = [
                            "trans_id" => $item,
                            "action" => $action,
                        ];
                    }
                }               
            }
        } elseif (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "aprove/Reject") {
            $action = $_POST["action"];
            if (in_array($action, ["approve", "reject"]) && $action != "") {
                $orderData[] = [
                    "trans_id" => $_POST["trans_id"],
                    "action" => $_POST["action"],
                ];
            }
        }
        if (!empty($orderData)) {
            $trans_ids = array_column($orderData, "trans_id");
            $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);
            foreach ($response['result']['data'] as $transaction) {
                // Check if the transaction_id matches any ID in the first array
                if (in_array($transaction['transaction_id'], $trans_ids)) {
                    // Add the corresponding order_id to the result array
                    $matching_order_ids[] = $transaction['order_id'];
                }
            }
            foreach ($matching_order_ids as $item) {
                $result = $this->Record->select(array("invoice_id"))
                    ->from("orders")
                    ->where("id", "=", $item)
                    ->fetch();
            
                if ($result && isset($result->invoice_id)) {
                    $matching_invoice_ids[] = $result->invoice_id;
                }
            }
        }
        if (!empty($orderData)) {
            /* $orderData = json_encode($orderData); */
            $postDataUpdate["data"] = $orderData;
            $postDataUpdate["h"] = $hArray;
            $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "POST", json_encode($postDataUpdate), $headers);
            // $updateResponse = array(
            //     "code" => 200,
            //     "result" => array(
            //         "status" => "success",
            //         "message" => "Transaction approved successfully!"
            //     )
            // ); 


            if ($updateResponse["code"] != 200) {
                $orderResponse["result"]["status"] = "fail";
                $orderResponse["result"]["message"] = "Somthing went wrong";
            } else {
                if ($action == "approve") {
                    foreach ($matching_invoice_ids as $item) {
                        // echo '<pre>';
                        // print_r($postDataUpdate);
                        // echo '<br>';
                        // $invoiceId = substr($item["order_id"], 14);  /* Trim the first 14 digits */
                        /* echo $invoiceId; */                        
                        $this->Record->where("invoice_id", "=", $item)->update("orders", array("status" => "accepted"));
                        //adding in activity tab      
                        $current_date_ato = date('Y-m-d H:i:s'); 
                        // $trans_review_order_id = $this->Record
                        //     ->select("order_id") 
                        //     ->from("orders")
                        //     ->where("invoice_id", "=", $item)
                        //     ->fetch();      
                        $invoice_id = $item;                 
                        $insert_data = "The order was approved successfully on " . $current_date_ato . " for Invoice ID - " . $invoice_id . ".";
                        $this->Record->set("sensfrx_log_type", "Order Review")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");                     
                    }
                }
                if ($action == "reject") {
                    foreach ($matching_invoice_ids as $item) {
                        // $invoiceId = substr($item["order_id"], 14);  /* Trim the first 14 digits */
                        /* echo $invoiceId; */
                        $this->Record->where("invoice_id", "=", $item)->update("orders", array("status" => "canceled"));
                        $current_date_ato = date('Y-m-d H:i:s');                        
                        $invoice_id = $item;
                        $insert_data = "The order was rejected successfully on " . $current_date_ato . " for Invoice ID - " . $invoice_id . ".";
                        $this->Record->set("sensfrx_log_type", "Order Review")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
                    }
                }
                $orderResponse["result"]["status"] = "success";
                $orderResponse["result"]["message"] = $updateResponse["result"]["message"];
                /* print_r($action);
                print_r("success"); */
            }

            if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "aprove/Reject") {

                echo json_encode($orderResponse);
                die;
            }
        }
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);

        if ($response["code"] != 200) {
            $resultPrivacyUpdate["result"]["status"] = "fail";
            $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
        }

        if ($response["code"] != "200") {
            $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
        }
        $transactionDetails = isset($response["result"]) ? $response["result"] : [];

        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );

        $activePage = __FUNCTION__;
        /* Set variables all for the view at once */
        $this->set(compact("transactionDetails", "activePage", "errorMessage", "resultPrivacyUpdate", "orderResponse"));
        /* Return view to be displayed */
        return $this->view->fetch();
    }

    public function activity()
    {
        $this->structure->set('page_title', Language::_('Sensfrx.admin.activity.page_title', true));
        $this->view->setView('activity', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );

        $activity = $this->SensfrxHelper->getData("sensfrx_real_activity", [], true);
        /* if ($activity["code"] != 200) {
            $resultPrivacyUpdate["result"]["status"] = "fail";
            $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
        } */
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $activities = array_reverse($activity);
        $activePage = __FUNCTION__;
        /*  Set variables all for the view at once */
        $this->set(compact("activePage", "activities"));
        /* Return view to be displayed */
        return $this->view->fetch();
    }

    public function validation_rules()
    {
        $this->structure->set('page_title', Language::_('Sensfrx.admin.validation_rules.page_title', true));
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();

        $hArray = $this->createHArray();
        $postData = array();
        $postData['h'] = $hArray;
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $errorMessage = "";
        $successMessage = [];
        /* getting rules */
        if (!empty($_POST)) {
            $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);

            if ($response["code"] != "200") {
                $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
            } else {
                $formatedData = $this->SensfrxHelper->formateRuleData($_POST, $response["result"]["data"]);

                $formatedData['h'] = $hArray;
                $postDataArray = json_encode($formatedData);

                /* updating rules */
                $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "POST", $postDataArray, $headers);
                if ($response["code"] != "200") {
                    $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
                }
                $successMessage = $response["result"]["message"];
            }
        }
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);
        if ($response["code"] != "200") {
            $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
        }
        $rules = $response["result"];

        $this->view->setView('validation_rules', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );
        /* Set variables all for the view at once */
        $activePage = __FUNCTION__;
        $this->set(compact("activePage", "rules", "errorMessage", "successMessage"));
        return $this->view->fetch();
    }

    public function policies_settings()
    {
        $this->structure->set('page_title', Language::_('Sensfrx.admin.policies_settings.page_title', true));
        $this->view->setView('policies_settings', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        /* API requred data */
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = array();
        $postData['h'] = $hArray;
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $successMessage = "";
        $errorMessage = "";
        if (!empty($_POST)) {
            unset($_POST["sensfrx_device_id"]);
            $shadow_mode = isset($_POST["shadow_mode"]) ? 1 : 0;
            /* updating shadow status */
            $postDataProfile = $postData;
            $postDataProfile["shadow_mode"] = $shadow_mode;
            $postDataProfile = json_encode($postDataProfile);
            $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "POST", $postDataProfile, $headers);
            if ($response["code"] != "200") {
                $_POST["shadow_mode"] =  $_POST["shadow_mode"] == "on" ? "" : "on";
                $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
            }

            /* insertin/upating data*/
            if (!$this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => array_key_last($_POST)])) {
                $result = $this->Record->set("company_id", Configure::get('Blesta.company_id'))->set("key", array_key_last($_POST))->set("value", json_encode($_POST))->insert("sensfrx_policies_setting");
            } else {
                $result = $this->Record->where("key", "=", array_key_last($_POST))->update("sensfrx_policies_setting", array("value" => json_encode($_POST)));
            }

            $successMessage = "Policies setting has been updated successfully!";
        }

        /* getting settings */
        $formatedData = $this->SensfrxHelper->format_policy_data($this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"], true));
        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );

        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
        if ($response["code"] != "200") {
            $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
        }
        $shadowMode = isset($response["result"]["shadow_mode"]) ? $response["result"]["shadow_mode"] : 0;

        $activePage = __FUNCTION__;
        $this->set(compact("formatedData", "activePage", "successMessage", "shadowMode", "errorMessage"));
        return $this->view->fetch();
    }

    public function notifications_alerts()
    {
        $this->structure->set(
            'page_title',
            Language::_(
                'Sensfrx.admin.notifications_alerts.page_title',
                true
            )
        );

        Loader::loadComponents($this, ['Record']);

        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();

        $url = "https://a.sensfrx.ai/v1/alerts";
        $postData = ['h' => $hArray];
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if (isset($_POST["UpdateNotificationsSetting"])) {
            $riskThreshold = $_POST["emailCheckbox"] == "on" ? '1' : '0';
            $postDataArray = [
                "h"  => $hArray,
                "enabled" => $riskThreshold,
                "risk_threshold" => $_POST["threshold"],
                "email" => $_POST["emailInput"],
            ];

            $postDataProfile = json_encode($postDataArray);
            $method = 'POST';
            $resultPrivacyUpdate12 = $this->__sensfrxApiCallonURL($url, $method, $postDataProfile, $headers);
            // echo '<pre>';
            // print_r($resultPrivacyUpdate);
        }

        $method = 'GET';
        $result_alerts = $this->__sensfrxApiCallonURL($url, $method, $postData, $headers);
        $resultPrivacyUpdate = [];
        if ($result_alerts["code"] != 200) {
            $resultPrivacyUpdate["result"]["status"] = "fail";
            $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
        } else {
            $AlerttArray = $result_alerts['result']['data'];
            $result_alerts['result']['data']['enabled'] = ($AlerttArray['enabled'] == '1') ? 'on' : 'off';
        }

        $this->view->setView('notifications_alerts', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );
        $activePage = __FUNCTION__;
        $this->set(compact("result_alerts", "resultPrivacyUpdate12", "activePage"));

        return $this->view->fetch();
    }

    public function license_information()
    {
        $this->structure->set(
            'page_title',
            Language::_(
                'Sensfrx.admin.license_information.page_title',
                true
            )
        );
        Loader::loadComponents($this, ['Record']);
        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();

        $url = "https://a.sensfrx.ai/v1/license";
        $postData = ['h' => $hArray];
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $resultlicenceData = $this->__sensfrxApiCallonURL($url, "GET", $postData, $headers);
        $resultPrivacyUpdate = [];
        if ($resultlicenceData["code"] != 200) {
            $resultPrivacyUpdate["result"]["status"] = "fail";
            $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
        }

        /* Set View */
        $this->view->setView('license_information', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");
        if ($this->Session->read('sensfrx_policies_success') != null) {
            $this->set("success", $this->Session->read('sensfrx_policies_success'));
            $this->Session->write('sensfrx_policies_success', null);
        } elseif ($this->Session->read('sensfrx_policies_error') != null) {
            $this->set("error", $this->Session->read('sensfrx_policies_error'));
            $this->Session->write('sensfrx_policies_error', null);
        }
        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );
        $activePage = __FUNCTION__;
        /* Set variables all for the view at once */
        $this->set(compact("resultlicenceData", "resultlicenceData", "activePage", "resultPrivacyUpdate"));
        /* Return view to be displayed */
        return $this->view->fetch();
    }

    public function account_privacy()
    {
        $this->structure->set(
            'page_title',
            Language::_('Sensfrx.admin.account_privacy.page_title', true)
        );
        Loader::loadComponents($this, ['Record']);
        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();

        $url = "https://a.sensfrx.ai/v1/privacy";
        $postData = ['h' => $hArray];
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $resultPrivacyUpdate = [];
        if (isset($_POST["email"]) && isset($_POST["declaration"]) && isset($_POST["privacyUpdate"])) {
            $_POST["declaration"] = $_POST["declaration"] == 'on' ? 1 : 0;
            $postDataArray = [
                "h"  => $hArray,
                "privacy_email" => $_POST["email"],
                "privacy_consent" => $_POST["declaration"],
            ];
            $postDataProfile = json_encode($postDataArray);
            $method = 'POST';
            $resultPrivacyUpdate = $this->__sensfrxApiCallonURL($url, $method, $postDataProfile, $headers);
        }

        $method = 'GET';
        $result_Privacy = $this->__sensfrxApiCallonURL($url, $method, $postData, $headers);
        if ($result_Privacy["code"] != 200) {
            $resultPrivacyUpdate["result"]["status"] = "fail";
            $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
        } else {
            if ($result_Privacy['result']['status'] === 'success') {
                $Privacy_data = $result_Privacy['result']['data'];
                if ($Privacy_data['privacy_consent'] == '1') {
                    $Privacy_data['privacy_consent'] = 'on';
                    $result_Privacy['result']['data']['privacy_consent'] = 'on';
                } else {
                    $Privacy_data['privacy_consent'] = 'off';
                    $result_Privacy['result']['data']['privacy_consent'] = 'off';
                }
            }
        }

        if (is_array($result_Privacy) && isset($result_Privacy['error'])) {
            $this->Session->write('sensfrx_policies_error', 'Error retrieving privacy data.');
        } else {
            $this->Session->write('sensfrx_policies_success', 'Successfully retrieved privacy data.');
        }

        $this->view->setView('account_privacy', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $this->set("assets", [
            "stylesheet" => [$view_url . 'css/style.css'],
        ]);
        $activePage = __FUNCTION__;
        $this->set(compact("result_Privacy", "resultPrivacyUpdate", "activePage", "resultPrivacyUpdate"));

        return $this->view->fetch();
    }

    public function profile_info()
    {
        Loader::loadComponents($this, ['Record']);
        $this->structure->set('page_title', Language::_('Sensfrx.admin.profile_info.page_title', true));
        /* $policies = $this->SaveManagePolicies->get(); */
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();


        $hArray = $this->createHArray();

        $url = "https://a.sensfrx.ai/v1/profile";
        $postData = array();
        $postData['h'] = $hArray;
        if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
            header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
            exit();
        } else {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        }
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $responseMessage = [];
        if (isset($_POST["brand_url"]) && isset($_POST["name"]) && isset($_POST["email"])) {
            $name1 = $_POST['name'] ?? '';
            $email1 = $_POST['email'] ?? '';
            $gender = $_POST['sex'] ?? '';
            $brand_name1 = $_POST['brand_name'] ?? '';
            $Org_name1 = $_POST['org_name'] ?? '';
            $timezone = $_POST['timezone'] ?? '';
            $brand_url1 = $_POST['brand_url'] ?? '';
            $phone1 = $_POST['phone'] ?? '';

            $name = $name1;
            $email = $email1;
            $brand_name = $brand_name1;
            $Org_name = $Org_name1;
            $brand_url = $brand_url1;

            $phone = preg_replace('/[^\d\+]/', '', $phone1);

            $parts = explode(" ", $name);
            $fname = isset($parts[0]) ? $parts[0] : '';
            $lname = isset($parts[1]) ? $parts[1] : '';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo "Invalid email address.";
                die();
            }
            if (!filter_var($brand_url, FILTER_VALIDATE_URL)) {
                echo "Invalid URL.";
                die();
            }

            $postDataArray = [
                "h" => $hArray,
                "fname" => $fname,
                "lname" => $lname,
                "email" => $email,
                "sex" => $gender,
                "phone" => $phone,
                "brand_name" => $brand_name,
                "timezone" => $timezone,
                "brand_url" => $brand_url,
                "org_name" => $Org_name,
            ];

            $postDataProfile = json_encode($postDataArray);

            $resultProfile = $this->__sensfrxApiCallonURL($url, 'POST', $postDataProfile, $headers);

            if (isset($resultProfile['result']['status']) && $resultProfile['result']['status'] == 'success') {
                $insertMessage = "updated successfully!";
                $responseMessage = [
                    "status" => "success",
                    "message" => "Details Update Successfuly"
                ];
            } else {
                $insertMessage = json_encode($resultProfile['result']);
                $responseMessage = [
                    "status" => "fail",
                    "message" => "Something Went Wrong"
                ];
            }
            $insert_data = $insertMessage;
            $this->Record->set("sensfrx_log_type", "Sensfrx Account Update")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
        }


        $method = 'GET';
        $result_Profile = $this->__sensfrxApiCallonURL($url, $method, $postData, $headers);
        /* echo "<pre>";
        print_r($result_Profile);
        die; */
        if ($result_Profile["code"] != 200) {
            $responseMessage["status"] = "fail";
            $responseMessage["message"] = "Not Authorized";
        }
        $this->view->setView('profile_info', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $this->set(
            "assets",
            [
                "stylesheet" => [
                    $view_url . 'css/style.css',
                ]
            ]
        );
        $activePage = __FUNCTION__;
        $this->set(compact("result_Profile", "responseMessage", "activePage"));
        /* Return view to be displayed */
        return $this->view->fetch();
    }

    private function createHArray()
    {
        $hArray = [

            'ip' => $this->sensfrx_get_client_ip(),
            'ua' => (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ""),
            'ho' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ""),
            'rf' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ""),
            'ac' => [
                "a" => (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : ""),
                "ac" => isset($_SERVER['HTTP_ACCEPT_CHARSET']) ? $_SERVER['HTTP_ACCEPT_CHARSET'] : "",
                "ae" => isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : "",
                "al" => isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : "",
            ],
            'url' => (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI']) ? $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'] : ""),
        ];
        return $hArray;
    }

    private function sensfrx_get_client_ip()
    {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',    /* Cloudflare */
            'HTTP_X_FORWARDED_FOR',     /* Common for proxies */
            'HTTP_CLIENT_IP',           /* Some proxies */
            'HTTP_X_FORWARDED',         /* Some proxies */
            'HTTP_X_CLUSTER_CLIENT_IP', /* Some load balancers */
            'HTTP_FORWARDED_FOR',       /* RFC 7239 */
            'HTTP_FORWARDED',           /* RFC 7239 */
            'REMOTE_ADDR',              /* Fallback */
        ];

        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
        return 'UNKNOWN';
    }

    private function __sensfrxApiCallonURL($url, $method, $postData = NULL, $headers = false)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            if ($headers) {
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            }

            if ($method == 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                if ($postData) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
                }
            }

            $response = curl_exec($ch);

            if ($response === false) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }

            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ['code' => $httpcode, 'result' => json_decode($response, true)];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred during the API call: ' . $e->getMessage(),
                'exception' => $e->getTraceAsString()
            ];
        }
    }

    private function apiCall($url = "")
    {
        $config_options = $this->SaveManageOptions->get();
        if (isset($config_options->property_id) && !empty($config_options->property_id) && isset($config_options->property_secret) && !empty($config_options->property_secret)) :
            $curl = curl_init();
            $curl_fields = array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([]),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'Authorization: Basic ' . base64_encode($config_options->property_id . ":" . $config_options->property_secret)
                ),
            );
            curl_setopt_array($curl, $curl_fields);
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if ($httpcode == 200 || $httpcode == "200") {
                if ($response == "" || empty($response)) {
                    return [
                        "status" => "error",
                        "message"  => Language::_(
                            'Sensfrx.admin.dashboard.webhook.error.api',
                            true
                        ),
                        "httpcode"  => $httpcode,
                        "api_response" => $response
                    ];
                } else {
                    $responseData = json_decode(json_decode($response, true), true);
                    if (!is_array($responseData)) {
                        return [
                            "status" => "error",
                            "message"  => $response
                        ];
                    } else {
                        return $responseData;
                    }
                }
            } else {
                return   [
                    "status" => "error",
                    "httpcode"  => $httpcode,
                    "api_response" => $response,
                    "message"  => $httpcode . ": " . $response,
                ];
            }
        else :
            return [
                "status" => "error",
                "message"  => Language::_(
                    'Sensfrx.admin.dashboard.webhook.error.property',
                    true
                )
            ];
        endif;
    }
}
