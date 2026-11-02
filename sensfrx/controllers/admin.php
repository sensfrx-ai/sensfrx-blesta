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
        // This controller is also routable from the client portal (/plugin/sensfrx/admin/...), where
        // requireLogin() accepts a client session. Only staff sessions carry blesta_staff_id.
        if (empty($this->Session->read('blesta_staff_id'))) {
            header("HTTP/1.1 403 Forbidden");
            exit;
        }
        $this->uses(['Sensfrx.SaveManagePolicies', 'Sensfrx.SaveManageOptions', 'Sensfrx.SensfrxHelper', "Emails"]);
        /*  Restore structure view l0ocati.on of the admin portal */
        $this->structure->setDefaultView(APPDIR);
        Language::loadLang('sensfrx_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
        Language::loadLang('sensfrx_admin_dashboard', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);

        // Loading Chart.js script
        $this->Javascript->setInline('
        var script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/chart.js?ver=1";
        script.async = true;
        document.body.appendChild(script);
        ');

        // Adding Google Fonts link 
        $this->Javascript->setInline('
            var link = document.createElement("link");
            link.rel = "stylesheet";
            link.type = "text/css";
            link.href = "https://fonts.googleapis.com/css2?family=Lato:wght@100;300;400;500;700;900&display=swap";
            document.head.appendChild(link);
        ');
    }

    // public function old_dashboard()
    // {
    //     /*  Set the page title */
    //     $this->structure->set('page_title', Language::_('Sensfrx.admin.dashboard.page_title', true));

    //     /*  Load necessary components */
    //     Loader::loadComponents($this, ['Record']);

    //     /*  Fetch policies and configuration */
    //     $policies = $this->SaveManagePolicies->get();
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
    //     $hArray = $this->createHArray();

    //     /*  Prepare API URL and data */
    //     $fromDate = date('d-m-Y');
    //     $toDate = date('d-m-Y', timestamp: strtotime('-7 days'));
    //     if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayDashboad") {
    //         if (isset($_REQUEST["date-dropdown"]) && $_REQUEST["date-dropdown"] != "") {
    //             if ($_REQUEST["date-dropdown"] == "filter-30") {
    //                 $toDate = date('d-m-Y', strtotime('-30 days'));
    //             } elseif ($_REQUEST["date-dropdown"] == "filter-365") {
    //                 $toDate = date('d-m-Y', strtotime('-365 days'));
    //             } else {
    //                 $toDate = date('d-m-Y', strtotime('-7 days'));
    //             }
    //         }
    //     }

    //     $selected_value = isset($_POST['date-dropdown']) ? $_POST['date-dropdown'] : 'filter-7';
    //     $method = 'GET';
    //     $transurl = "https://a.sensfrx.ai/v1/trans-stats?from_date={$toDate}&to_date={$fromDate}";
    //     $atourl = "https://a.sensfrx.ai/v1/ato-stats?from_date={$toDate}&to_date={$fromDate}";
    //     $regurl = "https://a.sensfrx.ai/v1/reg-stats?from_date={$toDate}&to_date={$fromDate}";

    //     $postData = ['h' => $hArray];
    //     $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") && (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "<b>Not Authorized!<b> <a target='_blank' href='" . $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/" . $moduleDetails->id . "'>click here to configure module »</a>";
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     $result_trans = $this->__sensfrxApiCallonURL($transurl, $method, $postData, $headers);
    //     $result_ato = $this->__sensfrxApiCallonURL($atourl, $method, $postData, $headers);
    //     $result_reg = $this->__sensfrxApiCallonURL($regurl, $method, $postData, $headers);
    //     $resultPrivacyUpdate = [];

    //     if ($result_trans["code"] != 200) {
    //         $ajaxResponseArray["status"] = $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $ajaxResponseArray["message"] = $resultPrivacyUpdate["result"]["message"] = "<b>Not Authorized!<b> <a target='_blank' href='" . $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/" . $moduleDetails->id . "'>click here to configure module »</a>";
    //     } else {
    //         $ajaxResponseArray["status"] = "success";
    //         if ($result_trans["code"] == 200) {
    //             $ajaxResponseArray["trans"] = $result_trans = $result_trans["result"]["data"];
    //         }
    //         if ($result_ato["code"] == 200) {
    //             $ajaxResponseArray["ato"] = $result_ato = $result_ato["result"]["data"];
    //         }
    //         if ($result_reg["code"] == 200) {
    //             $ajaxResponseArray["reg"] = $result_reg = $result_reg["result"]["data"];
    //         }
    //     }
    //     if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayDashboad") {
    //         echo json_encode($ajaxResponseArray);
    //         die;
    //     }

    //     /* Set view parameters */
    //     $this->view->setView('old_dashboard', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     /* Check and set session success or error messages */
    //     $successMessage = $this->Session->read('sensfrx_policies_success');
    //     $errorMessage = $this->Session->read('sensfrx_policies_error');

    //     if ($successMessage) {
    //         $this->set("success", $successMessage);
    //         $this->Session->write('sensfrx_policies_success', null);
    //     } elseif ($errorMessage) {
    //         $this->set("error", $errorMessage);
    //         $this->Session->write('sensfrx_policies_error', null);
    //     }

    //     /* Set assets for the view */
    //     $this->set("assets", [
    //         "stylesheet" => [
    //             $view_url . 'css/style.css',
    //         ]
    //     ]);

    //     $activePage = __FUNCTION__;
    //     /* Set variables for the view */
    //     // $this->set(compact("result_trans", "result_ato", "result_reg", "selected_value", "activePage", "resultPrivacyUpdate"));

    //     try {
    //         $this->set(compact("result_trans", "result_ato", "result_reg", "selected_value", "activePage", "resultPrivacyUpdate"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }
    //     /*  Return the view to be displayed */
    //     return $this->view->fetch();
    // }

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

    // public function order_review()
    // {
    //     $this->structure->set('page_title', Language::_('Sensfrx.admin.order_review.page_title', true));
    //     $errorMessage = "";
    //     $matching_order_ids = array();
    //     $matching_invoice_ids = array();
    //     $orderResponse["result"]["status"] = "";
    //     $orderResponse["result"]["message"] = "";
    //     $resultPrivacyUpdate = [];
    //     /*   Set View */
    //     $this->view->setView('order_review', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();

    //     $hArray = $this->createHArray();
    //     $postData = array();
    //     $postData['h'] = $hArray;
    //     $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     /* getting transaction reviews */
    //     if (isset($_POST["submitOrderReviewAll"])) {
    //         if ($_POST["submitOrderReviewAll"] == "approveAll") {
    //             $action = "approve";
    //         } elseif ($_POST["submitOrderReviewAll"] == "rejectAll") {
    //             $action = "reject";
    //         }
    //         if (in_array($action, ["approve", "reject"]) && $action != "") {
    //             foreach ($_POST as $key => $item) {
    //                 if (strpos($key, "Select_transId@@") !== false) {
    //                     $orderData[] = [
    //                         "trans_id" => $item,
    //                         "action" => $action,
    //                     ];
    //                 }
    //             }
    //         }
    //     } elseif (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "aprove/Reject") {
    //         $action = $_POST["action"];
    //         if (in_array($action, ["approve", "reject"]) && $action != "") {
    //             $orderData[] = [
    //                 "trans_id" => $_POST["trans_id"],
    //                 "action" => $_POST["action"],
    //             ];
    //         }
    //     }
    //     if (!empty($orderData)) {
    //         $trans_ids = array_column($orderData, "trans_id");
    //         $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);
    //         foreach ($response['result']['data'] as $transaction) {
    //             // Check if the transaction_id matches any ID in the first array
    //             if (in_array($transaction['transaction_id'], $trans_ids)) {
    //                 // Add the corresponding order_id to the result array
    //                 $matching_order_ids[] = $transaction['order_id'];
    //             }
    //         }
    //         foreach ($matching_order_ids as $item) {
    //             $result = $this->Record->select(array("invoice_id"))
    //                 ->from("orders")
    //                 ->where("id", "=", $item)
    //                 ->fetch();

    //             if ($result && isset($result->invoice_id)) {
    //                 $matching_invoice_ids[] = $result->invoice_id;
    //             }
    //         }
    //     }
    //     if (!empty($orderData)) {
    //         /* $orderData = json_encode($orderData); */
    //         $postDataUpdate["data"] = $orderData;
    //         $postDataUpdate["h"] = $hArray;
    //         $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "POST", json_encode($postDataUpdate), $headers);
    //         // $updateResponse = array(
    //         //     "code" => 200,
    //         //     "result" => array(
    //         //         "status" => "success",
    //         //         "message" => "Transaction approved successfully!"
    //         //     )
    //         // ); 


    //         if ($updateResponse["code"] != 200) {
    //             $orderResponse["result"]["status"] = "fail";
    //             $orderResponse["result"]["message"] = "Somthing went wrong";
    //         } else {
    //             if ($action == "approve") {
    //                 foreach ($matching_invoice_ids as $item) {
    //                     // echo '<pre>';
    //                     // print_r($postDataUpdate);
    //                     // echo '<br>';
    //                     // $invoiceId = substr($item["order_id"], 14);  /* Trim the first 14 digits */
    //                     /* echo $invoiceId; */
    //                     $this->Record->where("invoice_id", "=", $item)->update("orders", array("status" => "accepted"));
    //                     //adding in activity tab      
    //                     $current_date_ato = date('Y-m-d H:i:s');
    //                     // $trans_review_order_id = $this->Record
    //                     //     ->select("order_id") 
    //                     //     ->from("orders")
    //                     //     ->where("invoice_id", "=", $item)
    //                     //     ->fetch();      
    //                     $invoice_id = $item;
    //                     $insert_data = "The order was approved successfully on " . $current_date_ato . " for Invoice ID - " . $invoice_id . ".";
    //                     $this->Record->set("sensfrx_log_type", "Order Review")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
    //                 }
    //             }
    //             if ($action == "reject") {
    //                 foreach ($matching_invoice_ids as $item) {
    //                     // $invoiceId = substr($item["order_id"], 14);  /* Trim the first 14 digits */
    //                     /* echo $invoiceId; */
    //                     $this->Record->where("invoice_id", "=", $item)->update("orders", array("status" => "canceled"));
    //                     $current_date_ato = date('Y-m-d H:i:s');
    //                     $invoice_id = $item;
    //                     $insert_data = "The order was rejected successfully on " . $current_date_ato . " for Invoice ID - " . $invoice_id . ".";
    //                     $this->Record->set("sensfrx_log_type", "Order Review")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
    //                 }
    //             }
    //             $orderResponse["result"]["status"] = "success";
    //             $orderResponse["result"]["message"] = $updateResponse["result"]["message"];
    //             /* print_r($action);
    //             print_r("success"); */
    //         }

    //         if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "aprove/Reject") {

    //             echo json_encode($orderResponse);
    //             die;
    //         }
    //     }
    //     $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);        

    //     if ($response["code"] != 200) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
    //     }

    //     if ($response["code"] != "200") {
    //         $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //     }
    //     $transactionDetails = isset($response["result"]) ? $response["result"] : [];

    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );

    //     $activePage = __FUNCTION__;
    //     /* Set variables all for the view at once */
    //     // $this->set(compact("transactionDetails", "activePage", "errorMessage", "resultPrivacyUpdate", "orderResponse"));
    //     try {
    //         $this->set(compact("transactionDetails", "activePage", "errorMessage", "resultPrivacyUpdate", "orderResponse"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }

    //     /* Return view to be displayed */
    //     return $this->view->fetch();
    // }

    // //account review tab
    // public function account_review()
    // {
    //     $this->structure->set('page_title', Language::_('Sensfrx.admin.account_review.page_title', true));
    //     $errorMessage = "";
    //     $matching_registration_ids = array();
    //     $matching_invoice_ids = array();
    //     $accountResponse["result"]["status"] = "";
    //     $accountResponse["result"]["message"] = "";
    //     $resultPrivacyUpdate = [];
    //     $newAccountData = [];
    //     /*   Set View */
    //     $this->view->setView('account_review', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();

    //     $hArray = $this->createHArray();
    //     $postData = array();
    //     $postData['h'] = $hArray;
    //     $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();

    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];

    //     if (isset($_POST["submitAccountReviewAll"])) {
    //         if ($_POST["submitAccountReviewAll"] == "approveAll") {
    //             $action = "approve";
    //         } elseif ($_POST["submitAccountReviewAll"] == "rejectAll") {
    //             $action = "reject";
    //         }
    //         if (in_array($action, ["approve", "reject"]) && $action != "") {
    //             foreach ($_POST as $key => $item) {
    //                 if (strpos($key, "Select_regId@@") !== false) {
    //                     $newAccountData[] = [
    //                         "reg_id" => $item,
    //                         "action" => $action,
    //                     ];
    //                 }
    //             }                              
    //         }
    //     } elseif (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "aprove/Reject") {
    //         $action = $_POST["action"];
    //         if (in_array($action, ["approve", "reject"]) && $action != "") {
    //             $newAccountData[] = [
    //                 "reg_id" => $_POST["reg_id"],
    //                 "action" => $_POST["action"],
    //             ];
    //         }
    //     }        
    //     if (!empty($newAccountData)) {            
    //         $reg_ids = array_column($newAccountData, "reg_id");
    //         $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "GET", $postData, $headers);
    //         foreach ($response['result']['data'] as $registration) {  
    //             if (in_array($registration['registration_id'], $reg_ids)) { 
    //                 $matching_registration_ids[] = $registration['email'];
    //             }
    //         }                    
    //     }        
    //     if (!empty($newAccountData)) {
    //         $postDataUpdate["data"] = $newAccountData;
    //         $postDataUpdate["h"] = $hArray;
    //         $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "POST", json_encode($postDataUpdate), $headers);
    //         // $updateResponse = array(
    //         //     "code" => 200,
    //         //     "result" => array(
    //         //         "status" => "success",
    //         //         "message" => "Transaction approved successfully!"
    //         //     )
    //         // );             
    //         if ($updateResponse["code"] != 200) {
    //             $accountResponse["result"]["status"] = "fail";
    //             $accountResponse["result"]["message"] = "Somthing went wrong";
    //         } else {                
    //             if ($action == "approve") {                    
    //                 foreach ($matching_registration_ids as $item) {  
    //                     $fraud_clients = $this->Record->select(["clients.id", "clients.status", "contacts.email"])
    //                     ->from("clients")
    //                     ->innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)
    //                     ->where("clients.status", "=", "fraud")
    //                     ->fetchAll();

    //                     foreach ($fraud_clients as $fraud_client) {                            
    //                         if ($fraud_client->email == $item) {
    //                             $this->Record->where("id", "=", $fraud_client->id)
    //                             ->update("clients", ["status" => "active"]);
    //                         }
    //                     }
    
    //                     $current_date_ato = date('Y-m-d H:i:s');                           
    //                     $emailaccount = $item;
    //                     $insert_data = "The account was approved successfully on " . $current_date_ato . " for Email ID " . $emailaccount . ".";
    //                     $this->Record->set("sensfrx_log_type", "Account Review")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
    //                 }
    //             }
    //             if ($action == "reject") {
    //                 foreach ($matching_registration_ids as $item) { 
    //                     $fraud_clients = $this->Record->select(["clients.id", "clients.status", "contacts.email"])
    //                     ->from("clients")
    //                     ->innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)
    //                     ->where("clients.status", "=", "fraud")
    //                     ->fetchAll();

    //                     foreach ($fraud_clients as $fraud_client) {
                            
    //                         if ($fraud_client->email == $item) {
    //                             $this->Record->where("id", "=", $fraud_client->id)
    //                             ->update("clients", ["status" => "inactive"]);
    //                         }
    //                     }
                        
    //                     $current_date_ato = date('Y-m-d H:i:s');
    //                     $emailaccount = $item;
    //                     $insert_data = "The account was rejected successfully on " . $current_date_ato . " for Email ID " . $emailaccount . ".";
    //                     $this->Record->set("sensfrx_log_type", "Account Review")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
    //                 }
    //             }
    //             $accountResponse["result"]["status"] = "success";
    //             $accountResponse["result"]["message"] = $updateResponse["result"]["message"];
    //             /* print_r($action);
    //             print_r("success"); */
    //         }

    //         if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "aprove/Reject") {

    //             echo json_encode($accountResponse);
    //             die;
    //         }
    //     }
    //     $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "GET", $postData, $headers);
    //     //print_r($response);
    //     //print_r($updateResponse);

    //     if ($response["code"] != 200) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
    //     }

    //     if ($response["code"] != "200") {
    //         $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //     }
    //     $registrationDetails = isset($response["result"]) ? $response["result"] : [];
    //     //print_r($registrationDetails);

    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );

    //     $activePage = __FUNCTION__;
    //     /* Set variables all for the view at once */
    //     // $this->set(compact("registrationDetails", "activePage", "errorMessage", "resultPrivacyUpdate", "accountResponse"));
    //     try {
    //         $this->set(compact("registrationDetails", "activePage", "errorMessage", "resultPrivacyUpdate", "accountResponse"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }

    //     /* Return view to be displayed */
    //     return $this->view->fetch();
    // }

    // public function activity()
    // {
    //     $activity = $this->SensfrxHelper->getData("sensfrx_real_activity", [], true);
    //     /* if ($activity["code"] != 200) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
    //     } */
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $activities = array_reverse($activity);
    //     //$activePage = __FUNCTION__;
    //     /*  Set variables all for the view at once */
    //     // $this->set(compact( "activities"));
    //     try {
    //         $this->set(compact( "activities"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }
    //     /* Return view to be displayed */
    //     //return $this->view->fetch();
    // }

    // public function validation_rules()
    // {
    //     $this->structure->set('page_title', Language::_('Sensfrx.admin.validation_rules.page_title', true));
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();

    //     $hArray = $this->createHArray();
    //     $postData = array();
    //     $postData['h'] = $hArray;
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     $errorMessage = "";
    //     $successMessage = [];
    //     /* getting rules */
    //     if (!empty($_POST)) {
    //         $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);

    //         if ($response["code"] != "200") {
    //             $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //         } else {
    //             $formatedData = $this->SensfrxHelper->formateRuleData($_POST, $response["result"]["data"]);

    //             $formatedData['h'] = $hArray;
    //             $postDataArray = json_encode($formatedData);

    //             /* updating rules */
    //             $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "POST", $postDataArray, $headers);
    //             if ($response["code"] != "200") {
    //                 $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //             }
    //             $successMessage = $response["result"]["message"];
    //         }
    //     }
    //     $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);
    //     if ($response["code"] != "200") {
    //         $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //     }
    //     $rules = $response["result"];

    //     $this->view->setView('validation_rules', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );
    //     /* Set variables all for the view at once */
    //     $activePage = __FUNCTION__;
    //     // $this->set(compact("activePage", "rules", "errorMessage", "successMessage"));
    //     try {
    //         $this->set(compact("activePage", "rules", "errorMessage", "successMessage"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }
    //     return $this->view->fetch();
    // }

    // public function policies_settings()
    // {
    //     $this->structure->set('page_title', Language::_('Sensfrx.admin.policies_settings.page_title', true));
    //     $this->view->setView('policies_settings', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     /* API requred data */
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
    //     $hArray = $this->createHArray();
    //     $postData = array();
    //     $postData['h'] = $hArray;
    //     $emailResponse["result"]["status"] = "";
    //     $emailResponse["result"]["message"] = "";
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     $successMessage = "";
    //     $errorMessage = "";
    //     if (!empty($_POST)) {
    //         unset($_POST["sensfrx_device_id"]);
    //         $shadow_mode = isset($_POST["shadow_mode"]) ? 1 : 0;
    //         /* updating shadow status */
    //         $postDataProfile = $postData;
    //         $postDataProfile["shadow_mode"] = $shadow_mode;
    //         $postDataProfile = json_encode($postDataProfile);
    //         $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "POST", $postDataProfile, $headers);
    //         if ($response["code"] != "200") {
    //             $_POST["shadow_mode"] = $_POST["shadow_mode"] == "on" ? "" : "on";
    //             $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //         }

    //         /* insertin/upating data*/
    //         if (!$this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => array_key_last($_POST)])) {
    //             $result = $this->Record->set("company_id", Configure::get('Blesta.company_id'))->set("key", array_key_last($_POST))->set("value", json_encode($_POST))->insert("sensfrx_policies_setting");
    //         } else {
    //             $result = $this->Record->where("key", "=", array_key_last($_POST))->update("sensfrx_policies_setting", array("value" => json_encode($_POST)));
    //         }

    //         $successMessage = "Policies setting has been updated successfully!";
    //     }

    //     /* getting settings */
    //     $formatedData = $this->SensfrxHelper->format_policy_data($this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"], true));

    //     //getting emails 
    //     $email = $this->SensfrxHelper->getData("sensfrx_email_templates", [], true);
    //     $emails = array_reverse($email);
    //     // $this->addEmailTemplates();

    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );

    //     $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
    //     if ($response["code"] != "200") {
    //         $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
    //     }
    //     $shadowMode = isset($response["result"]["shadow_mode"]) ? $response["result"]["shadow_mode"] : 0;

    //     $activePage = __FUNCTION__;
    //     //$this->set(compact("formatedData", "activePage", "successMessage", "shadowMode", "errorMessage","emails", "emailResponse"));
    //     try {
    //         $this->set(compact("formatedData", "activePage", "successMessage", "shadowMode", "errorMessage","emails", "emailResponse"));
    //     } catch (Throwable $e) {
    //         error_log("Error in compact: " . $e->getMessage());
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }
    //     return $this->view->fetch();
    // }

    // public function notifications_alerts()
    // {
    //     $this->structure->set(
    //         'page_title',
    //         Language::_(
    //             'Sensfrx.admin.notifications_alerts.page_title',
    //             true
    //         )
    //     );

    //     Loader::loadComponents($this, ['Record']);

    //     $policies = $this->SaveManagePolicies->get();
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
    //     $hArray = $this->createHArray();

    //     $url = "https://a.sensfrx.ai/v1/alerts";
    //     $postData = ['h' => $hArray];
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];

    //     if (isset($_POST["UpdateNotificationsSetting"])) {
    //         $riskThreshold = $_POST["emailCheckbox"] == "on" ? '1' : '0';
    //         $postDataArray = [
    //             "h" => $hArray,
    //             "enabled" => $riskThreshold,
    //             "risk_threshold" => $_POST["threshold"],
    //             "email" => $_POST["emailInput"],
    //         ];

    //         $postDataProfile = json_encode($postDataArray);
    //         $method = 'POST';
    //         $resultPrivacyUpdate12 = $this->__sensfrxApiCallonURL($url, $method, $postDataProfile, $headers);
    //         // echo '<pre>';
    //         // print_r($resultPrivacyUpdate);
    //     }

    //     $method = 'GET';
    //     $result_alerts = $this->__sensfrxApiCallonURL($url, $method, $postData, $headers);
    //     $resultPrivacyUpdate = [];
    //     if ($result_alerts["code"] != 200) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
    //     } else {
    //         $AlerttArray = $result_alerts['result']['data'];
    //         $result_alerts['result']['data']['enabled'] = ($AlerttArray['enabled'] == '1') ? 'on' : 'off';
    //     }

    //     $this->view->setView('notifications_alerts', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );
    //     $activePage = __FUNCTION__;
    //     // $this->set(compact("result_alerts", "resultPrivacyUpdate", "activePage"));
    //     try {
    //         $this->set(compact("result_alerts", "resultPrivacyUpdate", "activePage"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }

    //     return $this->view->fetch();
    // }

    // public function license_information()
    // {
    //     $this->structure->set(
    //         'page_title',
    //         Language::_(
    //             'Sensfrx.admin.license_information.page_title',
    //             true
    //         )
    //     );
    //     Loader::loadComponents($this, ['Record']);
    //     $policies = $this->SaveManagePolicies->get();
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
    //     $hArray = $this->createHArray();

    //     $url = "https://a.sensfrx.ai/v1/license";
    //     $postData = ['h' => $hArray];
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     $resultlicenceData = $this->__sensfrxApiCallonURL($url, "GET", $postData, $headers);
    //     $resultPrivacyUpdate = [];
    //     if ($resultlicenceData["code"] != 200) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
    //     }

    //     /* Set View */
    //     $this->view->setView('license_information', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");
    //     if ($this->Session->read('sensfrx_policies_success') != null) {
    //         $this->set("success", $this->Session->read('sensfrx_policies_success'));
    //         $this->Session->write('sensfrx_policies_success', null);
    //     } elseif ($this->Session->read('sensfrx_policies_error') != null) {
    //         $this->set("error", $this->Session->read('sensfrx_policies_error'));
    //         $this->Session->write('sensfrx_policies_error', null);
    //     }
    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );
    //     $activePage = __FUNCTION__;
    //     /* Set variables all for the view at once */
    //     // $this->set(compact("resultlicenceData", "resultlicenceData", "activePage", "resultPrivacyUpdate"));
    //     try {
    //         $this->set(compact("resultlicenceData", "resultlicenceData", "activePage", "resultPrivacyUpdate"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }
    //     /* Return view to be displayed */
    //     return $this->view->fetch();
    // }

    // public function account_privacy()
    // {
    //     $this->structure->set(
    //         'page_title',
    //         Language::_('Sensfrx.admin.account_privacy.page_title', true)
    //     );
    //     Loader::loadComponents($this, ['Record']);
    //     $policies = $this->SaveManagePolicies->get();
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
    //     $hArray = $this->createHArray();

    //     $url = "https://a.sensfrx.ai/v1/privacy";
    //     $postData = ['h' => $hArray];
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     $resultPrivacyUpdate = [];
    //     if (isset($_POST["email"]) && isset($_POST["declaration"]) && isset($_POST["privacyUpdate"])) {
    //         $_POST["declaration"] = $_POST["declaration"] == 'on' ? 1 : 0;
    //         $postDataArray = [
    //             "h" => $hArray,
    //             "privacy_email" => $_POST["email"],
    //             "privacy_consent" => $_POST["declaration"],
    //         ];
    //         $postDataProfile = json_encode($postDataArray);
    //         $method = 'POST';
    //         $resultPrivacyUpdate = $this->__sensfrxApiCallonURL($url, $method, $postDataProfile, $headers);
    //     }

    //     $method = 'GET';
    //     $result_Privacy = $this->__sensfrxApiCallonURL($url, $method, $postData, $headers);
    //     if ($result_Privacy["code"] != 200) {
    //         $resultPrivacyUpdate["result"]["status"] = "fail";
    //         $resultPrivacyUpdate["result"]["message"] = "Not Authorized";
    //     } else {
    //         if ($result_Privacy['result']['status'] === 'success') {
    //             $Privacy_data = $result_Privacy['result']['data'];
    //             if ($Privacy_data['privacy_consent'] == '1') {
    //                 $Privacy_data['privacy_consent'] = 'on';
    //                 $result_Privacy['result']['data']['privacy_consent'] = 'on';
    //             } else {
    //                 $Privacy_data['privacy_consent'] = 'off';
    //                 $result_Privacy['result']['data']['privacy_consent'] = 'off';
    //             }
    //         }
    //     }

    //     if (is_array($result_Privacy) && isset($result_Privacy['error'])) {
    //         $this->Session->write('sensfrx_policies_error', 'Error retrieving privacy data.');
    //     } else {
    //         $this->Session->write('sensfrx_policies_success', 'Successfully retrieved privacy data.');
    //     }

    //     $this->view->setView('account_privacy', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     $this->set("assets", [
    //         "stylesheet" => [$view_url . 'css/style.css'],
    //     ]);
    //     $activePage = __FUNCTION__;
    //     // $this->set(compact("result_Privacy", "resultPrivacyUpdate", "activePage", "resultPrivacyUpdate"));
    //     try {
    //         $this->set(compact("result_Privacy", "resultPrivacyUpdate", "activePage", "resultPrivacyUpdate"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }

    //     return $this->view->fetch();
    // }

    // public function profile_info()
    // {
    //     Loader::loadComponents($this, ['Record']);
    //     $this->structure->set('page_title', Language::_('Sensfrx.admin.profile_info.page_title', true));
    //     /* $policies = $this->SaveManagePolicies->get(); */
    //     $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();


    //     $hArray = $this->createHArray();

    //     $url = "https://a.sensfrx.ai/v1/profile";
    //     $postData = array();
    //     $postData['h'] = $hArray;
    //     if ((!isset($sensfrxConfigData->property_id) || $sensfrxConfigData->property_id == "") || (!isset($sensfrxConfigData->property_secret) || $sensfrxConfigData->property_secret == "")) {
    //         header("Location: " . $this->base_url . trim($this->base_uri, "/") . "/plugin/sensfrx/admin/dashboard");
    //         exit();
    //     } else {
    //         $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
    //     }
    //     $headers = [
    //         "Authorization: Basic {$apikey}",
    //         "Content-Type: application/json"
    //     ];
    //     $responseMessage = [];
    //     if (isset($_POST["brand_url"]) && isset($_POST["name"]) && isset($_POST["email"])) {
    //         $name1 = $_POST['name'] ?? '';
    //         $email1 = $_POST['email'] ?? '';
    //         $gender = $_POST['sex'] ?? '';
    //         $brand_name1 = $_POST['brand_name'] ?? '';
    //         $Org_name1 = $_POST['org_name'] ?? '';
    //         $timezone = $_POST['timezone'] ?? '';
    //         $brand_url1 = $_POST['brand_url'] ?? '';
    //         $phone1 = $_POST['phone'] ?? '';

    //         $name = $name1;
    //         $email = $email1;
    //         $brand_name = $brand_name1;
    //         $Org_name = $Org_name1;
    //         $brand_url = $brand_url1;

    //         $phone = preg_replace('/[^\d\+]/', '', $phone1);

    //         $parts = explode(" ", $name);
    //         $fname = isset($parts[0]) ? $parts[0] : '';
    //         $lname = isset($parts[1]) ? $parts[1] : '';

    //         if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    //             echo "Invalid email address.";
    //             die();
    //         }
    //         if (!filter_var($brand_url, FILTER_VALIDATE_URL)) {
    //             echo "Invalid URL.";
    //             die();
    //         }

    //         $postDataArray = [
    //             "h" => $hArray,
    //             "fname" => $fname,
    //             "lname" => $lname,
    //             "email" => $email,
    //             "sex" => $gender,
    //             "phone" => $phone,
    //             "brand_name" => $brand_name,
    //             "timezone" => $timezone,
    //             "brand_url" => $brand_url,
    //             "org_name" => $Org_name,
    //         ];

    //         $postDataProfile = json_encode($postDataArray);

    //         $resultProfile = $this->__sensfrxApiCallonURL($url, 'POST', $postDataProfile, $headers);

    //         if (isset($resultProfile['result']['status']) && $resultProfile['result']['status'] == 'success') {
    //             $insertMessage = "updated successfully!";
    //             $responseMessage = [
    //                 "status" => "success",
    //                 "message" => "Details Update Successfuly"
    //             ];
    //         } else {
    //             $insertMessage = json_encode($resultProfile['result']);
    //             $responseMessage = [
    //                 "status" => "fail",
    //                 "message" => "Something Went Wrong"
    //             ];
    //         }
    //         $insert_data = $insertMessage;
    //         $this->Record->set("sensfrx_log_type", "Sensfrx Account Update")->set("sensfrx_log1", $insert_data)->set("created_at", date('Y-m-d H:i:s'))->insert("sensfrx_real_activity");
    //     }


    //     $method = 'GET';
    //     $result_Profile = $this->__sensfrxApiCallonURL($url, $method, $postData, $headers);
    //     /* echo "<pre>";
    //     print_r($result_Profile);
    //     die; */
    //     if ($result_Profile["code"] != 200) {
    //         $responseMessage["status"] = "fail";
    //         $responseMessage["message"] = "Not Authorized";
    //     }
    //     $this->view->setView('profile_info', 'sensfrx.admin');
    //     $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

    //     $this->set(
    //         "assets",
    //         [
    //             "stylesheet" => [
    //                 $view_url . 'css/style.css',
    //             ]
    //         ]
    //     );
    //     $activePage = __FUNCTION__;
    //     // $this->set(compact("result_Profile", "responseMessage", "activePage"));
    //     try {
    //         $this->set(compact("result_Profile", "responseMessage", "activePage"));
    //     } catch (Throwable $e) {
    //         echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
    //         // Store the error message in a variable to display it on the page
    //         if (!isset($this->error_messages)) {
    //             $this->error_messages = [];
    //         }
    //         $this->error_messages[] = $e->getMessage();

    //         // Passing all errors to the view
    //         $this->set("error_messages", $this->error_messages);
    //     }
    //     /* Return view to be displayed */
    //     return $this->view->fetch();
    // }

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
        // Only REMOTE_ADDR: forwarding headers (X-Forwarded-For, CF-Connecting-IP, ...) are client-controlled
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return $ip;
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

            if (false !== strpos($url, '/export-policies')) {
                header("Content-Type: application/json");
                header("Content-Disposition: attachment; filename=policy.json");
                header("Content-Length: " . strlen($response));
                header("Pragma: no-cache");
                header("Expires: 0");

                echo $response;
                exit;
            }

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
        if (isset($config_options->property_id) && !empty($config_options->property_id) && isset($config_options->property_secret) && !empty($config_options->property_secret)):
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
                        "message" => Language::_(
                            'Sensfrx.admin.dashboard.webhook.error.api',
                            true
                        ),
                        "httpcode" => $httpcode,
                        "api_response" => $response
                    ];
                } else {
                    $responseData = json_decode(json_decode($response, true), true);
                    if (!is_array($responseData)) {
                        return [
                            "status" => "error",
                            "message" => $response
                        ];
                    } else {
                        return $responseData;
                    }
                }
            } else {
                return [
                    "status" => "error",
                    "httpcode" => $httpcode,
                    "api_response" => $response,
                    "message" => $httpcode . ": " . $response,
                ];
            }
        else:
            return [
                "status" => "error",
                "message" => Language::_(
                    'Sensfrx.admin.dashboard.webhook.error.property',
                    true
                )
            ];
        endif;
    }
    
    //updating email templates
    public function updateEmailTemplate()
    {
        //ato email 
        if (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_title = $_POST['sensfrx_email_title'] ?? '';
            $sensfrx_email_subject = $_POST['sensfrx_email_subject'] ?? '';
            $sensfrx_email_message = $_POST['sensfrx_email_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_sendTestEmail']) && $_POST['sensfrx_email_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_title) || empty($sensfrx_email_subject) || empty($sensfrx_email_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_subject" => $sensfrx_email_subject,
                    "sensfrx_email_message" => $sensfrx_email_message,
                ];
                $res = $this->Record->where('sensfrx_email_title', '=', $sensfrx_email_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('loginEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_challenge') {
                $emailResponse['result']['status'] = "success";
                $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
                $sensfrx_email_challenge_title = $_POST['sensfrx_email_challenge_title'] ?? '';
                $sensfrx_email_challenge_subject = $_POST['sensfrx_email_challenge_subject'] ?? '';
                $sensfrx_email_challenge_message = $_POST['sensfrx_email_challenge_message'] ?? '';
                $sendTestEmail = isset($_POST['sensfrx_email_challenge_sendTestEmail']) && $_POST['sensfrx_email_challenge_sendTestEmail'] === '1';
                // Validate inputs
                if (empty($sensfrx_email_challenge_title) || empty($sensfrx_email_challenge_subject) || empty($sensfrx_email_challenge_message)) {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
                }
                // Update the database
                try {
                    $data = [
                        "sensfrx_email_challenge_subject" => $sensfrx_email_challenge_subject,
                        "sensfrx_email_challenge_message" => $sensfrx_email_challenge_message,
                    ];
                    $res = $this->Record->where('sensfrx_email_challenge_title', '=', $sensfrx_email_challenge_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('loginChallengeEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
                } catch (Exception $e) {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "database update failed";
                }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_deny') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_deny_title = $_POST['sensfrx_email_deny_title'] ?? '';
            $sensfrx_email_deny_subject = $_POST['sensfrx_email_deny_subject'] ?? '';
            $sensfrx_email_deny_message = $_POST['sensfrx_email_deny_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_deny_sendTestEmail']) && $_POST['sensfrx_email_deny_sendTestEmail'] === '1';

            if (empty($sensfrx_email_deny_title) || empty($sensfrx_email_deny_subject) || empty($sensfrx_email_deny_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            try {
                $data = [
                    "sensfrx_email_deny_subject" => $sensfrx_email_deny_subject,
                    "sensfrx_email_deny_message" => $sensfrx_email_deny_message,
                ];
                $res = $this->Record->where('sensfrx_email_deny_title', '=', $sensfrx_email_deny_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('loginDenyEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_trans') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_trans_title = $_POST['sensfrx_email_trans_title'] ?? '';
            $sensfrx_email_trans_subject = $_POST['sensfrx_email_trans_subject'] ?? '';
            $sensfrx_email_trans_message = $_POST['sensfrx_email_trans_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_trans_sendTestEmail']) && $_POST['sensfrx_email_trans_sendTestEmail'] === '1';

            if (empty($sensfrx_email_trans_title) || empty($sensfrx_email_trans_subject) || empty($sensfrx_email_trans_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }

            try {
                $data = [
                    "sensfrx_email_trans_subject" => $sensfrx_email_trans_subject,
                    "sensfrx_email_trans_message" => $sensfrx_email_trans_message,
                ];
                $res = $this->Record->where('sensfrx_email_trans_title', '=', $sensfrx_email_trans_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('Trans_medium_severity_Mail_Send', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_trans_challenge') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_trans_challenge_title = $_POST['sensfrx_email_trans_challenge_title'] ?? '';
            $sensfrx_email_trans_challenge_subject = $_POST['sensfrx_email_trans_challenge_subject'] ?? '';
            $sensfrx_email_trans_challenge_message = $_POST['sensfrx_email_trans_challenge_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_trans_challenge_sendTestEmail']) && $_POST['sensfrx_email_trans_challenge_sendTestEmail'] === '1';

            if (empty($sensfrx_email_trans_challenge_title) || empty($sensfrx_email_trans_challenge_subject) || empty($sensfrx_email_trans_challenge_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            try {
                $data = [
                    "sensfrx_email_trans_challenge_subject" => $sensfrx_email_trans_challenge_subject,
                    "sensfrx_email_trans_challenge_message" => $sensfrx_email_trans_challenge_message,
                ];
                $res = $this->Record->where('sensfrx_email_trans_challenge_title', '=', $sensfrx_email_trans_challenge_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('Trans_challenge_severity_Mail_Send', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_trans_deny') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_trans_deny_title = $_POST['sensfrx_email_trans_deny_title'] ?? '';
            $sensfrx_email_trans_deny_subject = $_POST['sensfrx_email_trans_deny_subject'] ?? '';
            $sensfrx_email_trans_deny_message = $_POST['sensfrx_email_trans_deny_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_trans_deny_sendTestEmail']) && $_POST['sensfrx_email_trans_deny_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_trans_deny_title) || empty($sensfrx_email_trans_deny_subject) || empty($sensfrx_email_trans_deny_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_trans_deny_subject" => $sensfrx_email_trans_deny_subject,
                    "sensfrx_email_trans_deny_message" => $sensfrx_email_trans_deny_message,
                ];
                $res = $this->Record->where('sensfrx_email_trans_deny_title', '=', $sensfrx_email_trans_deny_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('Trans_deny_severity_Mail_Send', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        }elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_register') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_register_title = $_POST['sensfrx_email_register_title'] ?? '';
            $sensfrx_email_register_subject = $_POST['sensfrx_email_register_subject'] ?? '';
            $sensfrx_email_register_message = $_POST['sensfrx_email_register_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_register_sendTestEmail']) && $_POST['sensfrx_email_register_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_register_title) || empty($sensfrx_email_register_subject) || empty($sensfrx_email_register_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_register_subject" => $sensfrx_email_register_subject,
                    "sensfrx_email_register_message" => $sensfrx_email_register_message,
                ];
                $res = $this->Record->where('sensfrx_email_register_title', '=', $sensfrx_email_register_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('clientRegisterEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        }elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_register_challenge') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_register_challenge_title = $_POST['sensfrx_email_register_challenge_title'] ?? '';
            $sensfrx_email_register_challenge_subject = $_POST['sensfrx_email_register_challenge_subject'] ?? '';
            $sensfrx_email_register_challenge_message = $_POST['sensfrx_email_register_challenge_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_register_challenge_sendTestEmail']) && $_POST['sensfrx_email_register_challenge_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_register_challenge_title) || empty($sensfrx_email_register_challenge_subject) || empty($sensfrx_email_register_challenge_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_register_challenge_subject" => $sensfrx_email_register_challenge_subject,
                    "sensfrx_email_register_challenge_message" => $sensfrx_email_register_challenge_message,
                ];
                $res = $this->Record->where('sensfrx_email_register_challenge_title', '=', $sensfrx_email_register_challenge_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('clientRegisterChallengeEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_register_deny') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_register_deny_title = $_POST['sensfrx_email_register_deny_title'] ?? '';
            $sensfrx_email_register_deny_subject = $_POST['sensfrx_email_register_deny_subject'] ?? '';
            $sensfrx_email_register_deny_message = $_POST['sensfrx_email_register_deny_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_register_deny_sendTestEmail']) && $_POST['sensfrx_email_register_deny_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_register_deny_title) || empty($sensfrx_email_register_deny_subject) || empty($sensfrx_email_register_deny_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_register_deny_subject" => $sensfrx_email_register_deny_subject,
                    "sensfrx_email_register_deny_message" => $sensfrx_email_register_deny_message,
                ];
                $res = $this->Record->where('sensfrx_email_register_deny_title', '=', $sensfrx_email_register_deny_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('clientRegisterDenyEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_after_trans') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_after_trans_title = $_POST['sensfrx_email_after_trans_title'] ?? '';
            $sensfrx_email_after_trans_subject = $_POST['sensfrx_email_after_trans_subject'] ?? '';
            $sensfrx_email_after_trans_message = $_POST['sensfrx_email_after_trans_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_after_trans_sendTestEmail']) && $_POST['sensfrx_email_after_trans_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_after_trans_title) || empty($sensfrx_email_after_trans_subject) || empty($sensfrx_email_after_trans_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_after_trans_subject" => $sensfrx_email_after_trans_subject,
                    "sensfrx_email_after_trans_message" => $sensfrx_email_after_trans_message,
                ];
                $res = $this->Record->where('sensfrx_email_after_trans_title', '=', $sensfrx_email_after_trans_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('Trans_medium_severity_Mail_Send_succeeded', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_after_trans_challenge') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_after_trans_challenge_title = $_POST['sensfrx_email_after_trans_challenge_title'] ?? '';
            $sensfrx_email_after_trans_challenge_subject = $_POST['sensfrx_email_after_trans_challenge_subject'] ?? '';
            $sensfrx_email_after_trans_challenge_message = $_POST['sensfrx_email_after_trans_challenge_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_after_trans_challenge_sendTestEmail']) && $_POST['sensfrx_email_after_trans_challenge_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_after_trans_challenge_title) || empty($sensfrx_email_after_trans_challenge_subject) || empty($sensfrx_email_after_trans_challenge_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_after_trans_challenge_subject" => $sensfrx_email_after_trans_challenge_subject,
                    "sensfrx_email_after_trans_challenge_message" => $sensfrx_email_after_trans_challenge_message,
                ];
                $res = $this->Record->where('sensfrx_email_after_trans_challenge_title', '=', $sensfrx_email_after_trans_challenge_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('Trans_challenge_severity_Mail_Send_succeeded', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_after_trans_deny') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_after_trans_deny_title = $_POST['sensfrx_email_after_trans_deny_title'] ?? '';
            $sensfrx_email_after_trans_deny_subject = $_POST['sensfrx_email_after_trans_deny_subject'] ?? '';
            $sensfrx_email_after_trans_deny_message = $_POST['sensfrx_email_after_trans_deny_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_after_trans_deny_sendTestEmail']) && $_POST['sensfrx_email_after_trans_deny_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_after_trans_deny_title) || empty($sensfrx_email_after_trans_deny_subject) || empty($sensfrx_email_after_trans_deny_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_after_trans_deny_subject" => $sensfrx_email_after_trans_deny_subject,
                    "sensfrx_email_after_trans_deny_message" => $sensfrx_email_after_trans_deny_message,
                ];
                $res = $this->Record->where('sensfrx_email_after_trans_deny_title', '=', $sensfrx_email_after_trans_deny_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('Trans_deny_severity_Mail_Send_succeeded', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } elseif (isset($_POST['AjaxCheck']) && $_POST['AjaxCheck'] === 'sensfrx_email_webhook') {
            $emailResponse['result']['status'] = "success";
            $emailResponse['result']['message'] = "Entered condition: AjaxCheck exists";
            $sensfrx_email_webhook_title = $_POST['sensfrx_email_webhook_title'] ?? '';
            $sensfrx_email_webhook_subject = $_POST['sensfrx_email_webhook_subject'] ?? '';
            $sensfrx_email_webhook_message = $_POST['sensfrx_email_webhook_message'] ?? '';
            $sendTestEmail = isset($_POST['sensfrx_email_webhook_sendTestEmail']) && $_POST['sensfrx_email_webhook_sendTestEmail'] === '1';
            // Validate inputs
            if (empty($sensfrx_email_webhook_title) || empty($sensfrx_email_webhook_subject) || empty($sensfrx_email_webhook_message)) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "All fields (title, subject, and message) are required.";
            }
            // Update the database
            try {
                $data = [
                    "sensfrx_email_webhook_subject" => $sensfrx_email_webhook_subject,
                    "sensfrx_email_webhook_message" => $sensfrx_email_webhook_message,
                ];
                $res = $this->Record->where('sensfrx_email_webhook_title', '=', $sensfrx_email_webhook_title)->update('sensfrx_email_templates', $data);

                if ($res) {
                    if ($sendTestEmail) {
                        $dummyEmailBodyArray = $this->generateDummyEmailBody();
                        $this->SensfrxHelper->sendEmail('WebhookEmail', $dummyEmailBodyArray);
                    }
                    $emailResponse['result']['status'] = "success";
                    $emailResponse['result']['message'] = "Email template updated successfully.";
                } else {
                    $emailResponse['result']['status'] = "fail";
                    $emailResponse['result']['message'] = "Failed to update the email template.";
                }
            } catch (Exception $e) {
                $emailResponse['result']['status'] = "fail";
                $emailResponse['result']['message'] = "database update failed";
            }
        } else {
            $emailResponse['result']['status'] = "fail";
            $emailResponse['result']['message'] = "AjaxCheck does not exist or does not match the expected value.";
        }
        echo json_encode($emailResponse);
        exit;          
    }

    private function generateDummyEmailBody()
    {
        $client_id = $this->Session->read('blesta_id');
        $userEmail = $this->Record->select('email')
            ->from('contacts')
            ->where('id', '=', $client_id)
            ->fetch();
        $company_id = Configure::get('Blesta.company_id');
        $company = $this->Companies->get($company_id);
        $signature = $company->name;

        $dummyEmailBody = [
            "toclient" => $userEmail->email,
            "clientId" => 999,  
            "templateVars" => [
                'client_name' => 'John Doe',
                'client_full_name' => 'John Doe',
                'fullname' => 'John Doe',
                'full_name' => 'John Doe',
                'device_name' => 'Test Device',  
                'ip_address' => '192.168.1.100',  
                'location' => 'Test Location',  
                'date' => date('Y-m-d'),  
                'time' => date('H:i'),
                'allow_url' => '$allowurl',
                'deny_url' => '$denyurl',
                'amount' => '$amount',
                'reset_password_url' => '$reset_password_url',
                'signature' => $signature,  
            ],
        ];
        return $dummyEmailBody;
    }

    // new designs 
    public function dashboard(): mixed
    {
        if ($this->Session->read('sensfrx_manage_error') != null) {
            $this->dashboardError();
            return $this->view->fetch();
        }
        Loader::loadComponents($this, ['Record', 'Session']);

        // Getting company_id from Blesta configuration
        $company_id = Configure::get('Blesta.company_id');
        $result = $this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->fetch();
        $property_id = $result->property_id ?? false;
        $property_secret = $result->property_secret ?? false;

        $this->view->setView('dashboard', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");

        $this->set("assets", [
            "stylesheet" => [
                $view_url . 'css/style.css',
            ],
            "js" => [
                $view_url . 'js/custom7.js',
                $view_url . 'js/custom_charts.js',
                $view_url . 'js/custom_botcharts.js',
            ],
        ]);

        // Call methods populate data
        $this->decisionPolicies();
        $this->notifications_settings();
        $this->newDashboard();
        $this->all_fraud_review();
        $this->policy_management();        

        try {
            $this->set(compact('property_id', 'property_secret'));
        } catch (Throwable $e) {
            error_log("Error in compact: " . $e->getMessage());
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();

            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }
        return $this->view->fetch();
    }

    public function dashboardError(): mixed{
        Loader::loadComponents($this, ['Record', 'Session']);
        $resultPrivacyUpdate = null;
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();
        $resultPrivacyUpdate["result"]["status"] = "fail";
        $urlConfig = $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/". $moduleDetails->id;
        $resultPrivacyUpdate["result"]["message"] = "<b>Not Authorized!<b> <a target='_blank' href='" . $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/" . $moduleDetails->id . "'>click here to configure module »</a>";
        $this->view->setView('dashboardError', 'sensfrx.admin');
        $view_url = Router::makeURI(str_replace('index.php/', '', WEBDIR) . $this->view->view_path . "assets/");
        $this->set(compact('urlConfig'));
        return $this->view->fetch();
    }

    public function dashboard_activity()
    {
        $this->structure->set('page_title', Language::_('Sensfrx.admin.activity.page_title', true));
        $this->view->setView('dashboard_activity', 'sensfrx.admin');
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
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $activities = array_reverse($activity);
        $activePage = __FUNCTION__;
        $this->set(compact( "activities"));
        try {
            $this->set(compact( "activities"));
        } catch (Throwable $e) {
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; 
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();

            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }
        /* Return view to be displayed */
        return $this->view->fetch();
    }

    public function activityLogs()
    {
        $activity = $this->SensfrxHelper->getData("sensfrx_real_activity", [], true);       
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $activities = array_reverse($activity);

        header('Content-Type: application/json');
        echo json_encode($activities);
        exit;
    }

    public function getPropertyData(){
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            if(isset($data['key']) && $data['key'] == 'sensfrx_propertyId_secretKey_get'){
                $company_id = Configure::get('Blesta.company_id');
                $result = $this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->fetch();

                if ($result) {
                    $property_id = isset($result->property_id) ? $result->property_id : false;
                    $property_secret = isset($result->property_secret) ? $result->property_secret : false;
                    $domain = isset($result->domain) ? $result->domain : false;

                    if ($property_id && $property_secret) {
                        echo json_encode([
                            'status' => 'success',
                            'propertyId' => $property_id,
                            'secretKey' => $property_secret,
                            'domain' => $domain
                        ]);
                    } else {
                        echo json_encode(['error' => 'Property ID or Secret Key is empty']);
                    }
                } else {
                    echo json_encode(['error' => 'No data found for the given company']);
                }
            } else {
                echo json_encode(['error' => 'Invalid key']);
            }
        } catch (Exception $e) {
            error_log('Exception in getPropertyData: ' . $e->getMessage());
            echo json_encode(['error' => 'An error occurred on the server']);
        }
        exit; 
    }

    public function updatePropertyData(){
        if (!$this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Not an AJAX request']);
            exit;
        }
        $data = json_decode(file_get_contents("php://input"), true);

        if (!isset($data['key']) || $data['key'] !== 'sensfrx_propertyId_secretKey_set') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
            exit;
        }
        $property_id = $data['propertyId'] ?? '';
        $secret_key = $data['secretKey'] ?? '';
        $domain = $data['domain'] ?? '';
        $company_id = Configure::get('Blesta.company_id');

        if (empty($property_id) || empty($secret_key)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Property ID and Secret Key cannot be empty']);
            exit;
        }

        $sendAutAPIRequest = $this->SaveManageOptions->sensfrx_CurlRequest($domain, $property_id, $secret_key, 'activate');
        $api_response = json_decode($sendAutAPIRequest, true);

        if ($api_response && $api_response["status"] == "success") {
            $this->Session->write('sensfrx_manage_success', Language::_('SensfrxManage.success', true));
        } else {
            $error_message = isset($api_response["message"]) ? $api_response["message"] : "Something went wrong";
            $this->Session->write('sensfrx_manage_error', $error_message);
        }
        // Updating the database
        $this->Record->where('company_id', '=', $company_id)
            ->update('sensfrx_config', ['property_id' => $property_id, 'property_secret' => $secret_key]);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success', 
            'message' => 'Property ID and Secret Key updated successfully',
            'api_response' => $api_response,
        ]);
        exit;
    }

    public function decisionPolicies(){
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = array();
        $postData['h'] = $hArray;
        $emailResponse["result"]["status"] = "";
        $emailResponse["result"]["message"] = "";
        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $successMessage = "";
        $errorMessage = "";

        /* getting settings */
        $formatedData = $this->SensfrxHelper->format_policy_data($this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"], true));

        //getting emails 
        $email = $this->SensfrxHelper->getData("sensfrx_email_templates", [], true);
        $emails = array_reverse($email);

        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
        if ($response["code"] != "200") {
            $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
        } else{
            $shadowMode = isset($response["result"]["shadow_mode"]) ? $response["result"]["shadow_mode"] : 0;
        }        

        $activePage = __FUNCTION__;

        try {
            $this->set(compact("formatedData", "activePage", "successMessage", "shadowMode", "errorMessage", "emails", "emailResponse"));
        } catch (Throwable $e) {
            error_log("Error in compact: " . $e->getMessage()); 
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; 
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();

            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }

    }

    public function updatePolicies(){
        if (!$this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Not an AJAX request."
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);

            if (empty($postData)) {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "No data received."
                ]);
                exit;
            }

            if (!isset($postData['checkKey']) || $postData['checkKey'] !== 'saveAccountSecurityPolicy') {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid or missing checkKey."
                ]);
                exit;
            }
            // Remove checkKey from the data before saving
            unset($postData['checkKey']);
            // Separating shadow_mode before processing policies
            $shadow_mode = $postData['shadow_mode'] ?? null;
            unset($postData['shadow_mode']); 
            // Fetch existing policies from the database
            $existingPolicy = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);

            if ($existingPolicy) {
                $existingData = json_decode($existingPolicy->value, true);
                $mergedData = array_merge($existingData, $postData);
                $this->Record->where("key", "=", "policies_settings")
                    ->update("sensfrx_policies_setting", ["value" => json_encode($mergedData)]);
            } else {
                $this->Record->set("company_id", Configure::get('Blesta.company_id'))
                    ->set("key", "policies_settings")
                    ->set("value", json_encode($postData))
                    ->insert("sensfrx_policies_setting");
            }
            // Updating shadow_mode
            if ($shadow_mode !== null) {
                // Convert "on"/"off" to 1/0
                $shadow_mode_value = $shadow_mode === "on" ? 1 : 0;
                // Get API credentials
                $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
                if (!$sensfrxConfigData || empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "success" => false,
                        "message" => "API credentials missing."
                    ]);
                    exit;
                }
                $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
                $headers = [
                    "Authorization: Basic {$apikey}",
                    "Content-Type: application/json"
                ];
                $shadowData = json_encode(["shadow_mode" => $shadow_mode_value]);
                $shadowResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "POST", $shadowData, $headers);

                if ($shadowResponse["code"] != 200) {
                    echo json_encode(["success" => false, "message" => "Failed to update settings."]);
                }             
                $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => true,
                    "message" => "shadow mode updated successfully!"
                ]);
                exit;
            }
            header('Content-Type: application/json');
            echo json_encode([
                "success" => true,
                "message" => "Policies settings updated successfully!"
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Invalid request method."
            ]);
            exit;
        }
    }

    public function updateTransPolicies(){
        if (!$this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Not an AJAX request."
            ]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);
            if (empty($postData)) {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "No data received."
                ]);
                exit;
            }

            if (!isset($postData['checkKey']) || $postData['checkKey'] !== 'saveTransactionSecurityPolicy') {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid or missing checkKey."
                ]);
                exit;
            }
            // Remove checkKey from the data before saving
            unset($postData['checkKey']);
            $shadow_mode = $postData['shadow_mode'] ?? null;
            unset($postData['shadow_mode']); 
            // Fetch the existing policies from the database
            $existingPolicy = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);

            if ($existingPolicy) {
                $existingData = json_decode($existingPolicy->value, true);
                $mergedData = array_merge($existingData, $postData);
                $this->Record->where("key", "=", "policies_settings")
                    ->update("sensfrx_policies_setting", ["value" => json_encode($mergedData)]);
            } else {
                // If the policy does not exist, insert a new one
                $this->Record->set("company_id", Configure::get('Blesta.company_id'))
                    ->set("key", "policies_settings")
                    ->set("value", json_encode($postData))
                    ->insert("sensfrx_policies_setting");
            }
            // Updating shadow_mode
            if ($shadow_mode !== null) {
                // Convert "on"/"off" to 1/0
                $shadow_mode_value = $shadow_mode === "on" ? 1 : 0;
                $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
                if (!$sensfrxConfigData || empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "success" => false,
                        "message" => "API credentials missing."
                    ]);
                    exit;
                }
                $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
                $headers = [
                    "Authorization: Basic {$apikey}",
                    "Content-Type: application/json"
                ];
                $shadowData = json_encode(["shadow_mode" => $shadow_mode_value]);
                $shadowResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "POST", $shadowData, $headers);

                if ($shadowResponse["code"] != 200) {
                    echo json_encode(["success" => false, "message" => "Failed to update settings."]);
                }         
                $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => true,
                    "message" => "shadow mode updated successfully!"
                ]);
                exit;
            }
            header('Content-Type: application/json');
            echo json_encode([
                "success" => true,
                "message" => "Transaction security policies have been updated successfully!"
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Invalid request method."
            ]);
            exit;
        }
    }

    public function updateNewAccPolicies(){
        if (!$this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Not an AJAX request."
            ]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);
            if (empty($postData)) {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "No data received."
                ]);
                exit;
            }

            if (!isset($postData['checkKey']) || $postData['checkKey'] !== 'saveNewAccSecurityPolicy') {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid or missing checkKey."
                ]);
                exit;
            }
            // Remove checkKey from the data before saving
            unset($postData['checkKey']);
            // Separating shadow_mode before processing policies
            $shadow_mode = $postData['shadow_mode'] ?? null;
            unset($postData['shadow_mode']); 
            $existingPolicy = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
            if ($existingPolicy) {
                $existingData = json_decode($existingPolicy->value, true);
                $mergedData = array_merge($existingData, $postData);
                $this->Record->where("key", "=", "policies_settings")
                    ->update("sensfrx_policies_setting", ["value" => json_encode($mergedData)]);
            } else {
                $this->Record->set("company_id", Configure::get('Blesta.company_id'))
                    ->set("key", "policies_settings")
                    ->set("value", json_encode($postData))
                    ->insert("sensfrx_policies_setting");
            }
            // Updating shadow_mode
            if ($shadow_mode !== null) {
                // Convert "on"/"off" to 1/0
                $shadow_mode_value = $shadow_mode === "on" ? 1 : 0;
                // Get API credentials
                $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
                if (!$sensfrxConfigData || empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "success" => false,
                        "message" => "API credentials missing."
                    ]);
                    exit;
                }
                $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
                $headers = [
                    "Authorization: Basic {$apikey}",
                    "Content-Type: application/json"
                ];
                $shadowData = json_encode(["shadow_mode" => $shadow_mode_value]);
                $shadowResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "POST", $shadowData, $headers);

                if ($shadowResponse["code"] != 200) {
                    echo json_encode(["success" => false, "message" => "Failed to update settings."]);
                }             
                $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => true,
                    "message" => "shadow mode updated successfully!"
                ]);
                exit;
            }
            header('Content-Type: application/json');
            echo json_encode([
                "success" => true,
                "message" => "Transaction security policies have been updated successfully!"
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Invalid request method."
            ]);
            exit;
        }
    }

    public function updateWebhookPolicies(){
        if (!$this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Not an AJAX request."
            ]);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postData = json_decode(file_get_contents('php://input'), true);
            if (empty($postData)) {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "No data received."
                ]);
                exit;
            }

            if (!isset($postData['checkKey']) || $postData['checkKey'] !== 'saveWebhookSecurityPolicy') {
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid or missing checkKey."
                ]);
                exit;
            }
            // Remove checkKey from the data before saving
            unset($postData['checkKey']);
            $shadow_mode = $postData['shadow_mode'] ?? null;
            unset($postData['shadow_mode']); // Prevent shadow_mode from being added to policy settings
            // Fetch the existing policies from the database
            $existingPolicy = $this->SensfrxHelper->getData("sensfrx_policies_setting", ["key" => "policies_settings"]);
            if ($existingPolicy) {
                $existingData = json_decode($existingPolicy->value, true);
                $mergedData = array_merge($existingData, $postData);
                $this->Record->where("key", "=", "policies_settings")
                    ->update("sensfrx_policies_setting", ["value" => json_encode($mergedData)]);
            } else {
                // If the policy does not exist, insert a new one
                $this->Record->set("company_id", Configure::get('Blesta.company_id'))
                    ->set("key", "policies_settings")
                    ->set("value", json_encode($postData))
                    ->insert("sensfrx_policies_setting");
            }
            // Updating shadow_mode
            if ($shadow_mode !== null) {
                // Convert "on"/"off" to 1/0
                $shadow_mode_value = $shadow_mode === "on" ? 1 : 0;
                // Get API credentials
                $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
                if (!$sensfrxConfigData || empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        "success" => false,
                        "message" => "API credentials missing."
                    ]);
                    exit;
                }
                $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
                $headers = [
                    "Authorization: Basic {$apikey}",
                    "Content-Type: application/json"
                ];
                $shadowData = json_encode(["shadow_mode" => $shadow_mode_value]);
                $shadowResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "POST", $shadowData, $headers);

                if ($shadowResponse["code"] != 200) {
                    echo json_encode(["success" => false, "message" => "Failed to update settings."]);
                } 
                $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/shadow", "GET", $postData, $headers);
                header('Content-Type: application/json');
                echo json_encode([
                    "success" => true,
                    "message" => "shadow mode updated successfully!"
                ]);
                exit;
            }
            header('Content-Type: application/json');
            echo json_encode([
                "success" => true,
                "message" => "Transaction security policies have been updated successfully!"
            ]);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                "success" => false,
                "message" => "Invalid request method."
            ]);
            exit;
        }
    }
    
    public function notifications_settings(){
        Loader::loadComponents($this, ['Record']);
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $url = "https://a.sensfrx.ai/v1/alerts";
        $postData = ['h' => $hArray];
        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
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
        $activePage = __FUNCTION__;
        try {
            $this->set(compact("result_alerts", "resultPrivacyUpdate", "activePage"));
        } catch (Throwable $e) {
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; // Logs error in browser console
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();
            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }
    }

    public function updateNotificationsSettings(){
        header("Content-Type: application/json");
        $inputData = json_decode(file_get_contents("php://input"), true);
        if (!$inputData || !isset($inputData['checkKey']) || $inputData['checkKey'] !== "saveNotificationsSetting") {
            echo json_encode(["success" => false, "message" => "Invalid request."]);
            exit();
        }
        $email = isset($inputData['emailInput']) ? trim($inputData['emailInput']) : "";
        $threshold = isset($inputData['threshold']) ? trim($inputData['threshold']) : "";
        $emailCheckbox = isset($inputData['emailCheckbox']) ? trim($inputData['emailCheckbox']) : "0";

        if (empty($email)) {
            echo json_encode(["success" => false, "message" => "Email is required."]);
            exit();
        }

        // Fetch config data
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $postDataArray = [
            "h" => $this->createHArray(),
            "enabled" => $emailCheckbox,
            "risk_threshold" => $threshold,
            "email" => $email
        ];
        $postDataProfile = json_encode($postDataArray);
        $url = "https://a.sensfrx.ai/v1/alerts";
        $resultPrivacyUpdate = $this->__sensfrxApiCallonURL($url, "POST", $postDataProfile, $headers);

        if ($resultPrivacyUpdate["code"] != 200) {
            echo json_encode(["success" => false, "message" => "Failed to update settings."]);
        } else {
            echo json_encode(["success" => true, "message" => "Settings updated successfully."]);
        }
        exit();
    }

    public function newDashboard(){
        Loader::loadComponents($this, ['Record', 'Session']);
        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $fromDate = date('d-m-Y');
        $toDate = date('d-m-Y', strtotime('-7 days'));

        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayDashboad") {
            if (isset($_REQUEST["date-dropdown"]) && $_REQUEST["date-dropdown"] != "") {
                if ($_REQUEST["date-dropdown"] == "filter-30") {
                    $toDate = date('d-m-Y', strtotime('-30 days'));
                } elseif ($_REQUEST["date-dropdown"] == "filter-15") {
                    $toDate = date('d-m-Y', strtotime('-15 days'));
                } elseif ($_REQUEST["date-dropdown"] == "filter-60") {
                    $toDate = date('d-m-Y', strtotime('-60 days'));
                } elseif ($_REQUEST["date-dropdown"] == "filter-120") {
                    $toDate = date('d-m-Y', strtotime('-120 days'));
                } else {
                    $toDate = date('d-m-Y', strtotime('-7 days'));
                }
            }
        }
        
        $selected_value = isset($_POST['date-dropdown']) ? $_POST['date-dropdown'] : 'filter-7';
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
        $resultPrivacyUpdate = [];

        // dashboard tab data
        // $stats_url = "https://a.sensfrx.ai/v1/blocked-metrics?from_date={$toDate}&to_date={$fromDate}";
        // $stats_info = $this->__sensfrxApiCallonURL($stats_url, "GET", $postData, $headers);

        $trans_url = "https://a.sensfrx.ai/v1/trans-stats?from_date={$toDate}&to_date={$fromDate}";
        $trans_info = $this->__sensfrxApiCallonURL($trans_url, "GET", $postData, $headers);

        $reg_url = "https://a.sensfrx.ai/v1/reg-stats?from_date={$toDate}&to_date={$fromDate}";
        $reg_info = $this->__sensfrxApiCallonURL($reg_url, "GET", $postData, $headers);

        $ato_url = "https://a.sensfrx.ai/v1/ato-stats?from_date={$toDate}&to_date={$fromDate}";
        $ato_info = $this->__sensfrxApiCallonURL($ato_url, "GET", $postData, $headers);

        $bot_url = "https://a.sensfrx.ai/v1/bot-stats?from_date={$toDate}&to_date={$fromDate}";
        $bot_info = $this->__sensfrxApiCallonURL($bot_url, "GET", $postData, $headers);

        $license_url = "https://a.sensfrx.ai/v1/license";
        $license_info = $this->__sensfrxApiCallonURL($license_url, "GET", $postData, $headers);

        $recent_alerts_url = "https://a.sensfrx.ai/v1/recent-alerts";
        $recent_alerts = $this->__sensfrxApiCallonURL($recent_alerts_url, "GET", $postData, $headers);

        $odometer_url = "https://a.sensfrx.ai/v1/fraud-rate";
        $odometer_data = $this->__sensfrxApiCallonURL($odometer_url, "GET", $postData, $headers);

        //fraud review tab 
        $toDate = date('d-m-Y', strtotime('-30 days'));
        $fraud_review_trans_url = "https://a.sensfrx.ai/v1/fraud-review/trans?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_trans = $this->__sensfrxApiCallonURL($fraud_review_trans_url, "GET", $postData, $headers);

        $fraud_review_reg_url = "https://a.sensfrx.ai/v1/fraud-review/reg?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_reg = $this->__sensfrxApiCallonURL($fraud_review_reg_url, "GET", $postData, $headers);

        $fraud_review_ato_url = "https://a.sensfrx.ai/v1/fraud-review/ato?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_ato = $this->__sensfrxApiCallonURL($fraud_review_ato_url, "GET", $postData, $headers);

        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);
        $transactionDetails = isset($response["result"]) ? $response["result"] : [];

        $response1 = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "GET", $postData, $headers);
        $registrationDetails = isset($response1["result"]) ? $response1["result"] : [];

        // activity tab 
        $activity = $this->SensfrxHelper->getData("sensfrx_real_activity", [], true);       
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $activities = array_reverse($activity);

        if ($trans_info["code"] != 200 && $reg_info["code"] != 200 && $ato_info["code"] != 200 && $bot_info["code"] != 200) {
            $ajaxResponseArray["status"] = $resultPrivacyUpdate["result"]["status"] = "fail";
            $ajaxResponseArray["message"] = $resultPrivacyUpdate["result"]["message"] = "<b>Not Authorized!<b> <a target='_blank' href='" . $this->base_url . ltrim($this->base_uri, "/") . "settings/company/plugins/manage/" . $moduleDetails->id . "'>click here to configure module »</a>";
        } else {
            $ajaxResponseArray["status"] = "success";
            $ajaxResponseArray["trans_info"] = isset($trans_info["result"]["data"]) ? $trans_info["result"]["data"] : [];
            $ajaxResponseArray["ato_info"] = isset($ato_info["result"]["data"]) ? $ato_info["result"]["data"] : [];
            $ajaxResponseArray["reg_info"] = isset($reg_info["result"]["data"]) ? $reg_info["result"]["data"] : [];
            $ajaxResponseArray["bot_info"] = isset($bot_info["result"]["data"]) ? $bot_info["result"]["data"] : [];
            $ajaxResponseArray["license_info"] = isset($license_info["result"]["data"]) ? $license_info["result"]["data"] : [];
        }
        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayDashboad") {
            echo json_encode($ajaxResponseArray);
            die;
        }

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

        $activePage = __FUNCTION__;

        try {
            $this->set(compact("trans_info", "ato_info", "reg_info", "bot_info",  "selected_value", "activePage", "resultPrivacyUpdate", "license_info", "recent_alerts", "odometer_data", "fraud_review_trans", "fraud_review_reg", "fraud_review_ato","transactionDetails","registrationDetails", "activities"));
        } catch (Throwable $e) {
            error_log("Error in compact: " . $e->getMessage());
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; 
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();
            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }
    }

    public function getRecentAlerts(){
        header("Content-Type: application/json");
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        // API Call
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/recent-alerts", "GET", $postData, $headers);
        if (!is_array($response) || !isset($response["code"]) || $response["code"] != "200") {
            echo json_encode(["success" => false, "message" => "Failed to fetch recent alerts", "alerts" => []]);
            exit();
        }
        $recent_alerts = isset($response["result"]["data"]["recent_alerts"]) && is_array($response["result"]["data"]["recent_alerts"]) 
            ? $response["result"]["data"]["recent_alerts"] 
            : [];
        echo json_encode(["success" => true, "alerts" => $recent_alerts]);
        exit();
    }

    public function all_fraud_review() {
        Loader::loadComponents($this, ['Record', 'Session']);
        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $fromDate = date('d-m-Y');
        $toDate = date('d-m-Y', strtotime('-7 days'));

        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "fetchFraudTransactions") {
            if (isset($_REQUEST["date-dropdown-fraud-trans"]) && $_REQUEST["date-dropdown-fraud-trans"] != "") {
                if ($_REQUEST["date-dropdown-fraud-trans"] == "filter-15") {
                    $toDate = date('d-m-Y', strtotime('-15 days'));
                } elseif ($_REQUEST["date-dropdown-fraud-trans"] == "filter-30") {
                    $toDate = date('d-m-Y', strtotime('-30 days'));
                } elseif ($_REQUEST["date-dropdown-fraud-trans"] == "filter-60") {
                    $toDate = date('d-m-Y', strtotime('-60 days'));
                } elseif ($_REQUEST["date-dropdown-fraud-trans"] == "filter-120") {
                    $toDate = date('d-m-Y', strtotime('-120 days'));
                } else {
                    $toDate = date('d-m-Y', strtotime('-7 days'));
                }
            }
        }

        $selected_value = isset($_POST['date-dropdown-fraud-trans']) ? $_POST['date-dropdown-fraud-trans'] : 'filter-7';
        $postData = ['h' => $hArray];
        $moduleDetails = $this->Record->select()->from("plugins")->where("dir", "=", "sensfrx")->fetch();
        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $resultPrivacyUpdate = [];
        // Handle the dynamic data fetching based on the request
        $dataType = isset($_POST['dataType']) ? $_POST['dataType'] : 'trans';
        // Get pagination parameters
        $page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 10;
        $offset = ($page - 1) * $limit;

        // URLs for all data types
        $urls = [
            "https://a.sensfrx.ai/v1/fraud-review/trans?from_date={$toDate}&to_date={$fromDate}",
            "https://a.sensfrx.ai/v1/fraud-review/reg?from_date={$toDate}&to_date={$fromDate}",
            "https://a.sensfrx.ai/v1/fraud-review/ato?from_date={$toDate}&to_date={$fromDate}"
        ];
        $allTransactions = [];
        $allRegistrations = [];
        $allAto = [];
        $totalCountTrans = 0;
        $totalCountRegs = 0;
        $totalCountAto = 0;

        foreach ($urls as $url) {
            $response = $this->__sensfrxApiCallonURL($url, "GET", $postData, $headers);
            if ($response["code"] == 200) {
                if (isset($response["result"]["data"]["trans_list"])) {
                    $allTransactions = array_merge($allTransactions, $response["result"]["data"]["trans_list"]);
                }
                if (isset($response["result"]["data"]["reg_list"])) {
                    $allRegistrations = array_merge($allRegistrations, $response["result"]["data"]["reg_list"]);
                }
                if (isset($response["result"]["data"]["ato_list"])) {
                    $allAto = array_merge($allAto, $response["result"]["data"]["ato_list"]);
                }
            }
        }

        $totalCountTrans = count($allTransactions);
        $totalCountRegs = count($allRegistrations);
        $totalCountAto = count($allAto);
        $totalPagesTrans = $totalCountTrans > 0 ? ceil($totalCountTrans / $limit) : 1;
        $totalPagesRegs = $totalCountRegs > 0 ? ceil($totalCountRegs / $limit) : 1;
        $totalPagesAto = $totalCountAto > 0 ? ceil($totalCountAto / $limit) : 1;

        //response
        $ajaxResponseArray["status"] = "success";
        $ajaxResponseArray["data"] = [
            "trans_list" => $allTransactions,
            "total_pages_trans" => $totalPagesTrans,
            "reg_list" => $allRegistrations,
            "total_pages_regs" => $totalPagesRegs,
            "ato_list" => $allAto,
            "total_pages_ato" => $totalPagesAto
        ];
        // If it's an AJAX request, return the response as JSON
        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "fetchFraudTransactions") {
            header("Content-Type: application/json");
            echo json_encode($ajaxResponseArray);
            exit;
        }

        $successMessage = $this->Session->read('sensfrx_policies_success');
        $errorMessage = $this->Session->read('sensfrx_policies_error');

        if ($successMessage) {
            $this->set("success", $successMessage);
            $this->Session->write('sensfrx_policies_success', null);
        } elseif ($errorMessage) {
            $this->set("error", $errorMessage);
            $this->Session->write('sensfrx_policies_error', null);
        }

        $activePage = __FUNCTION__;
        /* Set variables for the view */
        try {
            $this->set(compact("selected_value", "activePage", "resultPrivacyUpdate"));
        } catch (Throwable $e) {
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; 
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();
            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }
    }

    // default policy management
    public function policy_management(){
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];
        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $errorMessage = "";
        $successMessage = [];
        $rules = [];
        $advRules = [];
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);

        if ($response["code"] != "200") {
            $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response["result"];
        } else {
            $rules = $response["result"];
        }

        // advanced policies
        $response1 = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules/advanced", "GET", $postData, $headers);

        if ($response1["code"] != "200") {
            $errorMessage = empty($response["result"]) ? "Unexpected Error occurs!" : $response1["result"];
        } else {
            $advRules = $response1["result"];
        }

        // blacklist whitelist
        $res1 = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/blacklist", "GET", $postData, $headers);

        $res2 = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/whitelist", "GET", $postData, $headers);

        if ($res1["code"] != "200") {
            $errorMessage = empty($res1["result"]) ? "Unexpected Error occurs!" : $res1["result"];
        } else {
            $blacklistGet = $res1["result"];
        }

        if ($res2["code"] != "200") {
            $errorMessage = empty($res2["result"]) ? "Unexpected Error occurs!" : $res2["result"];
        } else {
            $whitelistGet = $res2["result"];
        }

        try {
            $this->set(compact("rules", "advRules", "errorMessage", "successMessage", "blacklistGet", "whitelistGet"));
        } catch (Throwable $e) {
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; 
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();
            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }

    }

    // update default policy
    public function updateDefaultPolicies(){
        header("Content-Type: application/json");
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);
        $defaultRules = ($response["code"] == "200") ? $response["result"] : ["data" => []];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputJSON = file_get_contents("php://input");
            $postDataArray = json_decode($inputJSON, true);            

            if (!$postDataArray) {
                echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
                exit();
            }

            if ($postDataArray["checkKey"] === "saveDefaultPolicies") {            
                $formattedData = [];

                foreach ($postDataArray["rulesData"]["score"] as $index => $score) {
                    $formattedData[$index] = [
                        "code" => $postDataArray["rulesData"]["code"][$index],
                        "score_value" => (int)$score,
                        "action" => $postDataArray["rulesData"]["action"][$index],
                        "active" => $postDataArray["rulesData"]["active"][$index] === "on" ? 1 : 0
                    ];
                }
                $formattedData['h'] = $hArray;                
                $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "POST", json_encode($formattedData), $headers);

                if ($updateResponse["code"] != "200") {
                    echo json_encode(["success" => false, "message" => $updateResponse["result"] ?? "Unexpected Error"]);
                    exit();
                }

                $updatedResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules", "GET", $postData, $headers);
                
                echo json_encode([
                    "success" => true,
                    "message" => $updateResponse["result"]["message"] ?? "Policies updated successfully",
                ]);
                exit();
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    public function updateAdvPolicies(){
        header("Content-Type: application/json");
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }
        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules/advanced", "GET", $postData, $headers);
        $advRules = ($response["code"] == "200") ? $response["result"] : ["data" => []];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputJSON = file_get_contents("php://input");
            $postDataArray = json_decode($inputJSON, true);

            if (!$postDataArray) {
                echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
                exit();
            }

            if ($postDataArray["checkKey"] === "saveAdvPolicies") {              
                $formattedData = [];

                foreach ($postDataArray["advRulesData"]["code"] as $index => $code) {
                    $formattedData[$index] = [
                        "code" => $code,
                        //"score_value" => (int)$score,
                        "action" => $postDataArray["advRulesData"]["action"][$index],
                        "active" => $postDataArray["advRulesData"]["active"][$index] === "on" ? 1 : 0
                    ];
                }
                $formattedData['h'] = $hArray;
                $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules/advanced", "POST", json_encode($formattedData), $headers);

                if ($updateResponse["code"] != "200") {
                    echo json_encode(["success" => false, "message" => $updateResponse["result"] ?? "Unexpected Error"]);
                    exit();
                }

                $updatedResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/rules/advanced", "GET", $postData, $headers);
                
                echo json_encode([
                    "success" => true,
                    "message" => $updateResponse["result"]["message"] ?? "Policies updated successfully",
                ]);
                exit();
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    //blacklist
    public function saveBlacklist(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputJSON = file_get_contents("php://input");
            $postDataArray = json_decode($inputJSON, true);

            if (!$postDataArray) {
                echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
                exit();
            }           

            if ($postDataArray["checkKey"] === "saveBlacklist") {
                $formattedData = [
                    "field" => $postDataArray["type"],
                    "value" => $postDataArray["value"],
                ];
                $formattedData['h'] = $hArray;
                $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/blacklist", "POST", json_encode($formattedData), $headers);

                if ($updateResponse["code"] != "200") {
                    echo json_encode(["success" => false, "message" => $updateResponse["result"] ?? "Unexpected Error"]);
                    exit();
                }

                $updatedResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/blacklist", "GET", $postData, $headers);
                
                echo json_encode([
                    "success" => true,
                    "message" => $updateResponse["result"]["message"] ?? "Blacklist item added successfully",
                ]);
                exit();
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    public function deleteBlacklist(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputJSON = file_get_contents("php://input");
            $postDataArray = json_decode($inputJSON, true);

            if (!$postDataArray) {
                echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
                exit();
            }            

            if ($postDataArray["checkKey"] === "deleteBlacklist") {
                $formattedData = [
                    "field" => $postDataArray["type"],
                    "value" => $postDataArray["value"],
                ];
                $formattedData['h'] = $hArray;
                $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/blacklist-delete", "POST", json_encode($formattedData), $headers);

                if ($updateResponse["code"] != "200") {
                    echo json_encode(["success" => false, "message" => $updateResponse["result"] ?? "Unexpected Error"]);
                    exit();
                }
                echo json_encode([
                    "success" => true,
                    "message" => $updateResponse["result"]["message"] ?? "Blacklist updated successfully",
                ]);
                exit();
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    public function saveWhitelist(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputJSON = file_get_contents("php://input");
            $postDataArray = json_decode($inputJSON, true);

            if (!$postDataArray) {
                echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
                exit();
            }

            if ($postDataArray["checkKey"] === "saveWhitelist") {
                $formattedData = [
                    "field" => $postDataArray["type"],
                    "value" => $postDataArray["value"],
                ];
                $formattedData['h'] = $hArray;               
                $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/whitelist", "POST", json_encode($formattedData), $headers);

                if ($updateResponse["code"] != "200") {
                    echo json_encode(["success" => false, "message" => $updateResponse["result"] ?? "Unexpected Error"]);
                    exit();
                }
                $updatedResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/whitelist", "GET", $postData, $headers);            
                echo json_encode([
                    "success" => true,
                    "message" => $updateResponse["result"]["message"] ?? "Whitelist item added successfully",
                ]);
                exit();
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    public function deleteWhitelist(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }
        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $inputJSON = file_get_contents("php://input");
            $postDataArray = json_decode($inputJSON, true);

            if (!$postDataArray) {
                echo json_encode(["success" => false, "message" => "Invalid JSON format"]);
                exit();
            }

            if ($postDataArray["checkKey"] === "deleteWhitelist") {
                $formattedData = [
                    "field" => $postDataArray["type"],
                    "value" => $postDataArray["value"],
                ];
                $formattedData['h'] = $hArray;
                $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/whitelist-delete", "POST", json_encode($formattedData), $headers);

                if ($updateResponse["code"] != "200") {
                    echo json_encode(["success" => false, "message" => $updateResponse["result"] ?? "Unexpected Error"]);
                    exit();
                }
                echo json_encode([
                    "success" => true,
                    "message" => $updateResponse["result"]["message"] ?? "Blacklist updated successfully",
                ]);
                exit();
            }
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    public function exportPolicies(){
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();

        if (empty($sensfrxConfigData->property_id) || empty($sensfrxConfigData->property_secret)) {
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $hArray = $this->createHArray();
        $postData = ["h" => $hArray];
        $export_url = "https://a.sensfrx.ai/v1/policy-management/export-policies";
        $response = $this->__sensfrxApiCallonURL($export_url, "GET", $postData, $headers);
        
        if ($response["code"] != "200") {
            header("Content-Type: application/json");
            echo json_encode(["success" => false, "message" => "Failed to export policies"]);
            exit();
        }
        // Setting headers for file download
        header("Content-Type: application/json");
        header("Content-Disposition: attachment; filename=\"policy.json\"");
        echo json_encode($response["result"]);
        exit();
    }

    public function saveImport(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        // No Content-Type: cURL sets multipart/form-data for the file upload
        $headers = [
            "Authorization: Basic {$apikey}"
        ];

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Checking if a file is uploaded
            if (!isset($_FILES["jsonFile"]) || $_FILES["jsonFile"]["error"] !== UPLOAD_ERR_OK) {
                echo json_encode(["success" => false, "message" => "No file uploaded or upload error"]);
                exit();
            }
            // Forward the uploaded file as-is (multipart field "jsonFile"), as the browser used to send it
            $formattedData = [
                "jsonFile" => new CURLFile($_FILES["jsonFile"]["tmp_name"], "application/json", "policy.json")
            ];
            $importResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/policy-management/import-policies","POST",$formattedData,$headers);

            if (!isset($importResponse["code"]) || $importResponse["code"] != "200" || !isset($importResponse["result"]["status"]) || $importResponse["result"]["status"] !== "success") {
                echo json_encode(["success" => false, "message" => $importResponse["result"] ?? "Unexpected Error"]);
                exit();
            }
            echo json_encode([
                "success" => true,
                "message" => $importResponse["result"]["message"] ?? "Policies imported successfully",
            ]);
            exit();
        }
        echo json_encode(["success" => false, "message" => "Invalid request"]);
        exit();
    }

    //fraud review
    public function fraud_review(){
        Loader::loadComponents($this, ['Record', 'Session']);
        $policies = $this->SaveManagePolicies->get();
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        $fromDate = date('d-m-Y');
        $toDate = date('d-m-Y', strtotime('-7 days'));

        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayFraudReview") {
            if (isset($_REQUEST["date-dropdown-fraud-review"]) && $_REQUEST["date-dropdown-fraud-review"] != "") {
                if ($_REQUEST["date-dropdown-fraud-review"] == "filter-30") {
                    $toDate = date('d-m-Y', strtotime('-30 days'));
                } elseif ($_REQUEST["date-dropdown-fraud-review"] == "filter-15") {
                    $toDate = date('d-m-Y', strtotime('-15 days'));
                } elseif ($_REQUEST["date-dropdown-fraud-review"] == "filter-60") {
                    $toDate = date('d-m-Y', strtotime('-60 days'));
                } elseif ($_REQUEST["date-dropdown-fraud-review"] == "filter-120") {
                    $toDate = date('d-m-Y', strtotime('-120 days'));
                } else {
                    $toDate = date('d-m-Y', strtotime('-7 days'));
                }
            }
        }
        
        $selected_value_fraud_review = isset($_POST['date-dropdown-fraud-review']) ? $_POST['date-dropdown-fraud-review'] : 'filter-7';
        $dataType = $_POST['dataType'] ?? 'trans'; 
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
        $resultPrivacyUpdate = [];

        //fraud review tab 
        $toDate = date('d-m-Y', strtotime('-30 days'));
        $fraud_review_trans_url = "https://a.sensfrx.ai/v1/trans-review?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_transaction = $this->__sensfrxApiCallonURL($fraud_review_trans_url, "GET", $postData, $headers);

        $fraud_review_reg_url = "https://a.sensfrx.ai/v1/reg-review?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_registration = $this->__sensfrxApiCallonURL($fraud_review_reg_url, "GET", $postData, $headers);

        $ajaxResponseArray = ["status" => "success"];
        $fraud_review_trans_url = "https://a.sensfrx.ai/v1/trans-review?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_transaction = $this->__sensfrxApiCallonURL($fraud_review_trans_url, "GET", $postData, $headers);
        $ajaxResponseArray["fraud_review_transaction"] = $fraud_review_transaction["result"]["data"] ?? [];
        $fraud_review_reg_url = "https://a.sensfrx.ai/v1/reg-review?from_date={$toDate}&to_date={$fromDate}";
        $fraud_review_registration = $this->__sensfrxApiCallonURL($fraud_review_reg_url, "GET", $postData, $headers);
        $ajaxResponseArray["fraud_review_registration"] = $fraud_review_registration["result"]["data"] ?? [];

        if (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "displayFraudReview") {
            echo json_encode($ajaxResponseArray);
            die;
        }
        $successMessage = $this->Session->read('sensfrx_policies_success');
        $errorMessage = $this->Session->read('sensfrx_policies_error');

        if ($successMessage) {
            $this->set("success", $successMessage);
            $this->Session->write('sensfrx_policies_success', null);
        } elseif ($errorMessage) {
            $this->set("error", $errorMessage);
            $this->Session->write('sensfrx_policies_error', null);
        }
        $activePage = __FUNCTION__;
        /* Set variables for the view */
        try {
            $this->set(compact( "selected_value_fraud_review", "activePage", "resultPrivacyUpdate", "fraud_review_transaction", "fraud_review_registration"));
        } catch (Throwable $e) {
            echo "<script>console.error('Error in compact(): " . addslashes($e->getMessage()) . "');</script>"; 
            // Store the error message in a variable to display it on the page
            if (!isset($this->error_messages)) {
                $this->error_messages = [];
            }
            $this->error_messages[] = $e->getMessage();
            // Passing all errors to the view
            $this->set("error_messages", $this->error_messages);
        }
    }

    public function transaction_review() {
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();
        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }
        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(["success" => false, "message" => "Invalid request"]);
            exit();
        }

        // Determining action type (Bulk or Individual)
        $orderData = [];
        $action = "";

        if (isset($_POST["trans_ids"]) && is_array($_POST["trans_ids"])) {
            $action = $_POST["action"] ?? "";
            if (!in_array($action, ["approve", "reject"])) {
                echo json_encode(["success" => false, "message" => "Invalid action"]);
                exit();
            }
            foreach ($_POST["trans_ids"] as $trans_id) {
                $orderData[] = ["trans_id" => $trans_id, "action" => $action];
            }
        } elseif (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "approve/Reject") {
            $action = $_POST["action"];
            if (!in_array($action, ["approve", "reject"])) {
                echo json_encode(["success" => false, "message" => "Invalid action"]);
                exit();
            }
            $orderData[] = ["trans_id" => $_POST["trans_id"], "action" => $action];
        }

        if (empty($orderData)) {
            echo json_encode(["success" => false, "message" => "No transactions selected"]);
            exit();
        }

        $postData = ["h" => $hArray];
        $trans_ids = array_column($orderData, "trans_id");
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);

        if ($response["code"] !== 200) {
            echo json_encode(["success" => false, "message" => "Error fetching transaction reviews"]);
            exit();
        }

        $matching_order_ids = [];
        $matching_invoice_ids = [];

        foreach ($response['result']['data'] as $transaction) {
            if (in_array($transaction['transaction_id'], $trans_ids)) {
                $matching_order_ids[] = $transaction['order_id'];
            }
        }

        foreach ($matching_order_ids as $orderId) {
            $result = $this->Record->select(["invoice_id"])->from("orders")->where("id", "=", $orderId)->fetch();
            if ($result && isset($result->invoice_id)) {
                $matching_invoice_ids[] = $result->invoice_id;
            }
        }

        // transaction update
        $postDataUpdate = ["data" => $orderData, "h" => $hArray];
        $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "POST", json_encode($postDataUpdate), $headers);

        if ($updateResponse["code"] !== 200) {
            echo json_encode(["success" => false, "message" => "Something went wrong"]);
            exit();
        }

        foreach ($matching_invoice_ids as $invoice_id) {
            // Set order status based on action
            $order_status = ($action == "approve") ? "accepted" : "canceled";
            $this->Record->where("invoice_id", "=", $invoice_id)->update("orders", ["status" => $order_status]);
            $invoice_status = ($action == "approve") ? "active" : "void"; // 'active' if approved and it will show in closed tab of admin, 'void' if rejected
            $this->Record->where("id", "=", $invoice_id)->update("invoices", ["status" => $invoice_status]);
            $current_date = date('Y-m-d H:i:s');
            $log_message = "The order was " . ($action == "approve" ? "approved" : "rejected") . " successfully on {$current_date} for Invoice ID - {$invoice_id}.";
            $this->Record->set("sensfrx_log_type", "Order Review")
                ->set("sensfrx_log1", $log_message)
                ->set("created_at", $current_date)
                ->insert("sensfrx_real_activity");
        }
        echo json_encode(["success" => true, "message" => $updateResponse["result"]["message"] ?? "Transaction processed successfully"]);
        exit();
    }

    public function registration_review(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(["success" => false, "message" => "Invalid request"]);
            exit();
        }

        // Determining action type (Bulk or Individual)
        $newAccountData = [];
        $action = "";

        if (isset($_POST["reg_ids"]) && is_array($_POST["reg_ids"])) {
            // Bulk actions
            $action = $_POST["action"] ?? "";
            if (!in_array($action, ["approve", "reject"])) {
                echo json_encode(["success" => false, "message" => "Invalid action"]);
                exit();
            }
            foreach ($_POST["reg_ids"] as $reg_id) {
                $newAccountData[] = ["reg_id" => $reg_id, "action" => $action];
            }
        } elseif (isset($_POST["AjaxCheck"]) && $_POST["AjaxCheck"] == "approve/Reject") {
            // Single Approval/Reject
            $action = $_POST["action"];
            if (!in_array($action, ["approve", "reject"])) {
                echo json_encode(["success" => false, "message" => "Invalid action"]);
                exit();
            }
            $newAccountData[] = ["reg_id" => $_POST["reg_id"], "action" => $action];
        }

        if (empty($newAccountData)) {
            echo json_encode(["success" => false, "message" => "No registrations selected"]);
            exit();
        }

        // Fetching registration review details
        $postData = ["h" => $hArray];
        $reg_ids = array_column($newAccountData, "reg_id");
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "GET", $postData, $headers);

        if ($response["code"] !== 200) {
            echo json_encode(["success" => false, "message" => "Error fetching registration reviews"]);
            exit();
        }

        // Extract matching emails for status update
        $matching_registration_ids = [];
        foreach ($response['result']['data'] as $registration) {
            if (in_array($registration['registration_id'], $reg_ids)) {
                $matching_registration_ids[] = $registration['email'];
            }
        }
        // Update registrations
        $postDataUpdate = ["data" => $newAccountData, "h" => $hArray];
        $updateResponse = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "POST", json_encode($postDataUpdate), $headers);

        if ($updateResponse["code"] !== 200) {
            echo json_encode(["success" => false, "message" => "Something went wrong"]);
            exit();
        }
        // Updating client statuses based on approval or rejection
        foreach ($matching_registration_ids as $email) {
            $status = ($action == "approve") ? "active" : "inactive";
            // Finding matching client with "fraud" status
            $fraud_clients = $this->Record->select(["clients.id", "clients.status", "contacts.email"])
                ->from("clients")
                ->innerJoin("contacts", "contacts.client_id", "=", "clients.id", false)
                ->where("clients.status", "=", "fraud")
                ->fetchAll();

            foreach ($fraud_clients as $fraud_client) {
                if ($fraud_client->email == $email) {
                    $this->Record->where("id", "=", $fraud_client->id)->update("clients", ["status" => $status]);
                }
            }
            // Sending Approval or Rejection Email
            $company_id = Configure::get('Blesta.company_id');
            $company = $this->Companies->get($company_id);
            $companyName = $company->name ?? 'Default Company Name';
            $CurPageURL = $this->SensfrxHelper->getSiteBaseUrl();
            $reset_password_url = $CurPageURL . '/blesta/client/login/reset/';

            // Determine email content based on action
            if ($action == "approve") {
                $subject = "Account Approved";
                $bodyContent = "Dear {$email}, <br><br> To set your password, visit the following address:                     
                <a href='{$reset_password_url}' style='color: blue; text-decoration: underline;' target='_blank'>{$reset_password_url}</a> <br><br> Best regards, <br>{$companyName}";
            } else {
                $subject = "Account Rejected";
                $bodyContent = "Dear {$email}, <br><br> We regret to inform you that your account has been rejected by the admin. Please contact support for further assistance. Thank you. <br><br> Best regards, <br>{$companyName}";
            }

            // Prepare email data
            $from = "mycompany@mydomain.com";
            $from_name = $companyName;
            $to = $email;
            $body = [
                "html" => $bodyContent,
                "text" => strip_tags($bodyContent) // Fallback for plain text emails
            ];
            $tags = [];
            $attachments = [];
            $options = [
                "to_client_id" => null, // Set if applicable
            ];
            try {
                $emailResult = $this->Emails->sendCustom($from, $from_name, $to, $subject, $body, $tags, null, null, $attachments, $options);
            } catch (Exception $e) {
                error_log("Email sending failed for {$email}: " . $e->getMessage());
            }
            $current_date = date('Y-m-d H:i:s');
            $log_message = "The account was " . ($action == "approve" ? "approved" : "rejected") . " successfully on {$current_date} for Email ID - {$email}.";
            $this->Record->set("sensfrx_log_type", "Account Review")
                ->set("sensfrx_log1", $log_message)
                ->set("created_at", $current_date)
                ->insert("sensfrx_real_activity");
        }
        echo json_encode(["success" => true, "message" => $updateResponse["result"]["message"] ?? "Registration processed successfully"]);
        exit();
    }

    public function getFraudCounts(){
        header("Content-Type: application/json");
        $configData = $this->Record->select()->from("sensfrx_config")->fetch();
        $hArray = $this->createHArray();

        if (empty($configData->property_id) || empty($configData->property_secret)) {
            echo json_encode(["success" => false, "message" => "Invalid API credentials"]);
            exit();
        }

        $apikey = base64_encode($configData->property_id . ":" . $configData->property_secret);
        $headers = [
            "Authorization: Basic {$apikey}",
            "Content-Type: application/json"
        ];
        $postData = ['h' => $hArray];
        $response = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/trans-review/", "GET", $postData, $headers);
        $transactionDetails = isset($response["result"]) ? $response["result"] : [];

        $response1 = $this->__sensfrxApiCallonURL("https://a.sensfrx.ai/v1/reg-review/", "GET", $postData, $headers);
        $registrationDetails = isset($response1["result"]) ? $response1["result"] : [];

        echo json_encode([
            "success" => true,
            "transactionCount" => isset($transactionDetails["reviewCount"]) ? $transactionDetails["reviewCount"] : 0,
            "registrationCount" => isset($registrationDetails["reviewCount"]) ? $registrationDetails["reviewCount"] : 0
        ]);
        exit;
    }    

}
