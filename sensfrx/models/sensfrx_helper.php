<?php

class SensfrxHelper extends SensfrxModel
{
    public function __construct()
    {
        parent::__construct();
        Loader::loadModels($this, ['Companies', "Emails"]);
    }

    public function formateRuleData($data, $apiData)
    {
        foreach ($data as $key => $rule) {
            if (isset($apiData[$key])) {
                $apiData[$key]["action"] = strtolower($rule["action"]); // Normalize action names
                $apiData[$key]["score_value"] = $rule["score_value"];
                $apiData[$key]["active"] = $rule["active"];
            }
        }
        return $apiData;
    }

    public function findParentOfItems($array) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (isset($value['items'])) {
                    return $value;
                }
                $result = findParentOfItems($value);
                if ($result) {
                    return $result;
                }
            }
        }
        return null;
    }
    
    public function format_policy_data($data)
    {
        $formatedData = [];
        foreach ($data as $key => $value) {
            $formatedData[$value->key] = json_decode($value->value, true);
        }
        return $formatedData;
    }

    /* 
        @param $table_name = table name.
        @param $where = where condition in associative array if any.
        @param $data = data to insert/update in associative array.
    */
    public function insert_update($table_name = '', $where = [], $data = null)
    {
        try {
            $row = $this->getData($table_name, $where);
            if ($row) {
                $result = $this->Record->set("company_id", Configure::get('Blesta.company_id'))->set("key", $where["key"])->set("value", json_encode($data))->insert($table_name);
                die($result);
                return "Data has been inserted successfully!";
            } else {
                unset($data["name"]);
                Capsule::table($table_name)->where($where)->update($data);
                $this->Record->where($where)->update("value", json_encode($data));
                return "Data has been updated successfully!";
            }
        } catch (\Exception $error) {
            die($error->getMessage());
            $this->Input->setErrors('error', ['error' => ['error' => $error->getMessage()]]);
            return;
        }
    }

    public function getData($tableName, $conditions = [], $isAll = false)
    {
        $result = $this->Record->select()->from($tableName);
        foreach ($conditions as $key => $value) {
            $result->where($key, "=", $value);
        }
        if ($isAll) {
            $result = $result->fetchAll();
        } else {
            $result = $result->fetch();
        }
        return $result;
    }

    /*
        Webhook signing secrets, stored in sensfrx_policies_setting under key "webhook_secrets" as
        {"<registered url>": "whsec_..."}, one entry per registered URL. The older per-action format
        {"<action>": {"url": "...", "secret": "..."}} is converted to this format in place when read.
    */
    public function getWebhookSecrets()
    {
        $row = $this->getData("sensfrx_policies_setting", ["key" => "webhook_secrets"]);
        $stored = isset($row->value) ? json_decode($row->value, true) : [];
        if (!is_array($stored)) {
            return [];
        }
        $secrets = [];
        $migrated = false;
        foreach ($stored as $key => $value) {
            if (is_array($value)) {
                $migrated = true;
                if (isset($value['url'], $value['secret']) && is_string($value['url']) && is_string($value['secret']) && $value['secret'] !== '') {
                    $secrets[$value['url']] = $value['secret'];
                }
            } elseif (is_string($value) && $value !== '') {
                $secrets[(string) $key] = $value;
            }
        }
        if ($migrated) {
            $this->writeWebhookSecrets($secrets, $row);
        }
        return $secrets;
    }

    public function saveWebhookSecret($url, $secret)
    {
        $secrets = $this->getWebhookSecrets();
        $secrets[$url] = $secret;
        $this->writeWebhookSecrets($secrets, $this->getData("sensfrx_policies_setting", ["key" => "webhook_secrets"]));
    }

    private function writeWebhookSecrets($secrets, $row)
    {
        if ($row) {
            $this->Record->where("key", "=", "webhook_secrets")->update("sensfrx_policies_setting", ["value" => json_encode($secrets)]);
        } else {
            $this->Record->set("company_id", Configure::get('Blesta.company_id'))
                ->set("key", "webhook_secrets")
                ->set("value", json_encode($secrets))
                ->insert("sensfrx_policies_setting");
        }
    }

    public function createHArray()
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

    private function __curl($url, $method, $postData = NULL)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if (!empty($this->generateHeader())) {
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->generateHeader());
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
            return ['code' => $httpcode, 'result' => json_decode($response, true)];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred during the API call: ' . $e->getMessage(),
                'exception' => $e->getTraceAsString()
            ];
        }
    }

    public function __get($url)
    {
        $postData['h'] = $this->createHArray();
        $response = $this->__curl($url, "GET", $postData);
        return $response;
    }

    public function __post($url, $postData)
    {
        $response = $this->__curl($url, "POST", $postData);
        return $response;
    }

    private function generateHeader()
    {
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        $headers = [];
        if ($sensfrxConfigData) {
            $apikey = base64_encode($sensfrxConfigData->property_id . ":" . $sensfrxConfigData->property_secret);
            $headers = [
                "Authorization: Basic {$apikey}",
                "Content-Type: application/json"
            ];
        }
        return $headers;
    }

    /*
        Base URL ("https://host[:port]") for links in emails and registered webhook URLs, built from the domain
        configured in the plugin settings rather than the request Host header. Falls back to SERVER_NAME only
        when no usable domain is configured.
    */
    /* Host (and optional port) of the configured site, for building redirect and
       email URLs without trusting the request's Host header. */
    public function getSiteHost()
    {
        return (string) preg_replace('#^https?://#i', '', $this->getSiteBaseUrl());
    }

    public function getSiteBaseUrl()
    {
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->where("company_id", "=", Configure::get('Blesta.company_id'))->fetch();
        $domain = isset($sensfrxConfigData->domain) ? trim((string) $sensfrxConfigData->domain) : '';
        if ($domain !== '') {
            $parts = parse_url(preg_match('#^https?://#i', $domain) ? $domain : 'https://' . $domain);
            $host = isset($parts['host']) ? strtolower($parts['host']) : '';
            if ($host !== '' && preg_match('/^[a-z0-9.-]+$/', $host)) {
                return 'https://' . $host . (isset($parts['port']) ? ':' . (int) $parts['port'] : '');
            }
        }
        return "https://" . $_SERVER["SERVER_NAME"];
    }

    /*
        Query string for the emailed "This was me" / "This was not me" device links. The link is valid for
        24 hours and is signed with the property secret over "action|uid|deviceId|exp", so an allow link and a
        deny link carry different signatures and one cannot be edited into the other.
    */
    public function getDeviceActionQuery($action, $uid, $deviceId)
    {
        $uid = (string) $uid;
        $deviceId = (string) $deviceId;
        $exp = time() + 86400;
        return 'deviceId=' . rawurlencode($deviceId) . '&sfxuid=' . rawurlencode($uid) . '&sfxexp=' . $exp . '&verify=' . $this->getDeviceActionSignature($action, $uid, $deviceId, $exp);
    }

    public function verifyDeviceAction($action, $uid, $deviceId, $exp, $verify)
    {
        if (!is_string($uid) || !is_string($deviceId) || !is_string($exp) || !is_string($verify) || !ctype_digit($exp) || (int) $exp < time()) {
            return false;
        }
        $expected = $this->getDeviceActionSignature($action, $uid, $deviceId, $exp);
        return $expected !== '' && hash_equals($expected, $verify);
    }

    private function getDeviceActionSignature($action, $uid, $deviceId, $exp)
    {
        if (!in_array($action, ['allow', 'deny'], true)) {
            return '';
        }
        $sensfrxConfigData = $this->Record->select()->from("sensfrx_config")->fetch();
        if (!$sensfrxConfigData || empty($sensfrxConfigData->property_secret)) {
            return '';
        }
        return hash_hmac('sha256', $action . '|' . $uid . '|' . $deviceId . '|' . $exp, $sensfrxConfigData->property_secret);
    }

    public function getCompanyName(){
        $UserRecord = $this->Record->select()->from("package_meta")->where("key", "=", "default_company_name")->fetch();
        return $UserRecord->value;
    }
    
    public function sendEmail($emailtemplateKey, $emailBodyArray)
    {
        try {
            $company_id = Configure::get('Blesta.company_id');
            $company = $this->Companies->get($company_id);    
            //$companyName = $company->name;
            $companyName = $company->name ?? 'Default Company Name';
            $emailBodyArray["templateVars"]["signature"] = $companyName;
            $emailtemplate = $this->emailTemplateArray($emailtemplateKey);
            $from = "mycompany@mydomain.com";
            $from_name = $companyName;
            $to = $emailBodyArray["toclient"];
            $subject = $emailtemplate["subject"];
            $messageString = $this->replacePlaceholders($emailtemplate["bodyContent"], $emailBodyArray["templateVars"]);
            //adding styles to table
            if (strpos($messageString, '<table>') !== false) {
                $messageString = str_replace(
                    '<table>',
                    '<table style="width: 100%; border-collapse: collapse;">',
                    $messageString
                );
                $messageString = str_replace(
                    '<th>',
                    '<th style="border: 1px solid black; padding: 8px; text-align: center;">',
                    $messageString
                );
                $messageString = str_replace(
                    '<td>',
                    '<td style="border: 1px solid black; padding: 8px; text-align: center;">',
                    $messageString
                );
            }
            $body = array(
                "html" => $messageString,
                "text" => ""
            );
            $tags = array();
            $attachments = array();
            $options = array(
                "to_client_id" => $emailBodyArray["clientId"],
            );
            $emailResult = $this->Emails->sendCustom($from, $from_name, $to, $subject, $body, $tags, null, null, $attachments, $options);

            return [
                'status' => 'success',
                'message' => 'Email sent successfully',
                'data' => $emailResult
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Error sending email: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function emailTemplateArray($emailtemplateKey)
    {
        $emailArray["profileUpdateEmail"] = [
            "subject" => "Unusual Profile Update Activity Detected on this Account",
            "bodyContent" => '<p style="font-size: 16px;">Dear <b>{$client_full_name}</b>,</p>
            <p style="font-size: 14px;">
                There is some unusual profile updation activity detected on this account. Did you recently use this device to perform some activity? 
            <br>    
                Did you recently use this device to perform some activity?
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
            <p>Regards.</p><br>
            <p>{$signature}</p>',
        ];

        $emailArray["profileUpdateChallengeEmail"] = [
            "subject" => "Suspicious Profile Update activity detected on this account",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;"> 
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>      
                                        </p>
                                        <p style="font-size: 14px;">
                                            We hope this email finds you well. We are writing to inform you about some suspicious profile update behavior that we have detected on your account. Your security is of utmost importance to us, and we take any potential threats very seriously.
                                        </p>
                                        <p>
                                            Please choose an option:
                                        </p>
                                        <div style="text-align: center; margin: 0 auto;">
                                            <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 10px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">This was me</a>
                                            <br><br>
                                            <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">This was not me</a>
                                        </div>
                                        <p style="color: red;">
                                            Your profile information has been updated.
                                            If you did not make these changes or suspect unauthorized activity, please contact our support team immediately.
                                        </p>
                                        <p style="font-size: 14px;">
                                            Thank you for your prompt attention to this matter. We appreciate your cooperation in ensuring the security of your account. Should you have any questions or need further assistance, please feel free to reach out to us.
                                        </p>
                                        <br><br>
                                        <p>Regards,</p><br>
                                        <p style="font-size: 14px;">{$signature}</p><br>
                                    </div>
                                </div>
                            </div>',
        ];

        $emailArray["profileUpdateDenyEmail"] = [
            "subject" => "Highly Suspicious Profile Update Prevent ",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;"> 
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="font-size: 16px;">Dear <b>{$client_full_name}</b></p>
                                        <p style="font-size: 14px;">
                                            This user account has been blocked due to recent <strong style="color: red;">suspicious profile update activity.</strong> For security purposes, we kindly request that you reset your password to regain access to your account.
                                        </p>
                                        <p style="font-size: 14px;">
                                            To reset your password, please visit the url below:<br />
                                            Click Here... <a href="{$reset_password_url}" style="color: #007BFF; text-decoration: none;">Reset Password URL</a>
                                        </p>
                                        <p style="font-size: 14px;">When you visit the link above, you will have the opportunity to choose a new password.</p>
                                        <p style="font-size: 14px;">
                                            Thank you for your attention to this matter. If you have any further questions or need
                                            additional assistance, please do not hesitate to reach out to us.
                                        </p>
                                        <br><br>
                                        <p>Regards.</p><br>
                                        <p>{$signature}</p>
                                    </div>
                                </div>',
        ];

        $emailArray["sa"] = [
            "subject" => "Unusual activity - Profile Update Notification",
            "bodyContent" => '',
        ];

        //bot emails
        $emailArray["botMediumEmail"] = [
            "subject" => "Important: Recent Activity [ Bot Behaviour ] and Security Notification",
            "bodyContent" => '
                <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                        <div style="font-size: 14px; line-height: 1.5;">
                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br></p>

                            <p>
                                We wanted to bring to your attention a matter concerning a bot activity on your account.<br>
                                We have identified a successful bot activity from your account, that appeared to have some characteristics indicative of potential suspicious activity.<br>
                                To ensure your account\'s security, we recommend taking prompt action. Consider changing your password and reviewing your account settings. 
                            </p>
                            <p style="margin-top: 20px;">Regards,<br>{$signature}</p>
                        </div>
                    </div>
                </div>
                ',
        ];

        $emailArray["botHighEmail"] = [
            "subject" => "Important: Suspicious Bot Activity Detected on Your Account",
            "bodyContent" => '
                <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                        <div style="font-size: 14px; line-height: 1.5;">
                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                We wanted to bring to your attention a matter concerning a bot activity on your account. <br>

                                We have identified a successful bot activity from your account. 
                            </p>
                            <p>
                                Click below to let us know if this was you or not. If not, we advise changing your password immediately to protect your account.     
                            </p>
                            <div style="text-align: center;margin: 0 auto;">
                                <a href="{$allow_url}" style="color: #fff;background-color: #337ab7;margin-right:10px;padding: 6px 12px;border-radius: 4px; text-decoration: none !important;">
                                This was me
                                </a>
                                <a href="{$deny_url}" style="color: #fff;background-color: #c9302c;padding: 6px 12px;border-radius: 4px; text-decoration: none !important;">
                                    This was not me
                                </a>
                            </div>
                            <br>
                            <p style="margin-top: 20px;">Regards,<br>{$signature}</p>
                        </div>
                    </div>
                </div>
                ',
        ];

        $emailArray["botCriticalEmail"] = [
            "subject" => "Urgent: Highly Suspicious Bot Activity Detected on Your Account ",
            "bodyContent" => '
                            <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                        We have blocked a highly suspicious bot activity from your account. 
                                        </p>
                                        <p style="font-size: 14px;">
                                            To reset your password, please visit the url below:<br />
                                            Click Here... <a href="{$reset_password_url}" style="color: #007BFF; text-decoration: none;">Reset Password URL</a>
                                        </p>
                                        <p style="font-size: 14px;">When you visit the link above, you will have the opportunity to choose a new password.</p>
                                        <br>
                                        <p style="margin-top: 20px;">Regards,<br>{$signature} </p>
                                    </div>
                                </div>
                            </div>
                ',
        ];

        $emailTemplateData = $this->Record->select()->from("sensfrx_email_templates")->fetch();
        if (!$emailTemplateData) {
            return []; 
        }
        // Dynamically mapping the database fields to email templates
        $emailArray["loginEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_subject ?? "Default Login Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_message ?? "Default Login Email Message",
        ];

        $emailArray["loginChallengeEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_challenge_subject ?? "Default Login Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_challenge_message ?? "Default Login Email Message",
        ];

        $emailArray["loginDenyEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_deny_subject ?? "Default Login Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_deny_message ?? "Default Login Email Message",
        ];

        $emailArray["clientRegisterEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_register_subject ?? "Default Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_register_message ?? "Default Email Message",
        ];

        $emailArray["clientRegisterChallengeEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_register_challenge_subject ?? "Default Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_register_challenge_message ?? "Default Email Message",
        ];

        $emailArray["clientRegisterDenyEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_register_deny_subject ?? "Default Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_register_deny_message ?? "Default Email Message",
        ];

        $emailArray["Trans_medium_severity_Mail_Send"] = [
            "subject" => $emailTemplateData->sensfrx_email_trans_subject ?? "Default Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_trans_message ?? "Default Email Message",
        ];

        $emailArray["Trans_challenge_severity_Mail_Send"] = [
            "subject" => $emailTemplateData->sensfrx_email_trans_challenge_subject ?? "Default Trans challenge Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_trans_challenge_message ?? "Default Trans challenge Email Message",
        ];

        $emailArray["Trans_deny_severity_Mail_Send"] = [
            "subject" => $emailTemplateData->sensfrx_email_trans_deny_subject ?? "Default Trans deny Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_trans_deny_message ?? "Default Trans deny Email Message",
        ];

        $emailArray["Trans_medium_severity_Mail_Send_succeeded"] = [
            "subject" => $emailTemplateData->sensfrx_email_after_trans_subject ?? "Default Trans allow Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_after_trans_message ?? "Default Trans allow Email Message",
        ];

        $emailArray["Trans_challenge_severity_Mail_Send_succeeded"] = [
            "subject" => $emailTemplateData->sensfrx_email_after_trans_challenge_subject ?? "Default Trans Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_after_trans_challenge_message ?? "Default Trans Email Message",
        ];

        $emailArray["Trans_deny_severity_Mail_Send_succeeded"] = [
            "subject" => $emailTemplateData->sensfrx_email_after_trans_deny_subject ?? "Default Trans Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_after_trans_deny_message ?? "Default Trans Email Message",
        ];

        $emailArray["WebhookEmail"] = [
            "subject" => $emailTemplateData->sensfrx_email_webhook_subject ?? "Default Webhook Email Subject",
            "bodyContent" => $emailTemplateData->sensfrx_email_webhook_message ?? "Default Webhook Email Message",
        ];

        $emailArray["RegistrationReviewEmail"] = [
            "subject" => "Account Approved",
            "bodyContent" => 'test email',
        ];

        return $emailArray[$emailtemplateKey];
    }

    public function replacePlaceholders($template, $data)
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{$' . $key . '}', $value, $template);
        }
        return $template;
    }    

}
