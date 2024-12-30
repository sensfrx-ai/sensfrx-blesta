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
        foreach ($data["score"] as $key => $value) {
            $apiData[$key]["score_value"] = $value;
            $apiData[$key]["active"] = $data["active"][$key] == "on" ? 1 : 0;
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
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
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

    private function __curl($url, $method, $postData = NULL)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            if (!empty($this->generateHeader())) {
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->generateHeader());
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

    public function getEncryptedHash($value = null)
    {
        $ciphering = "AES-128-CTR";
        /* Use OpenSSl Encryption method */
        $iv_length = openssl_cipher_iv_length($ciphering);
        /* Non-NULL Initialization Vector for encryption */
        $encryption_iv = '1234567891011121';
        /* Store the encryption key */
        $encryption_key = "AuthSafeEncryptedHash";
        /* Use openssl_encrypt() function to encrypt the data */

        return str_replace(
            ['+', '/', '='],
            ['-', '_', ''],
            base64_encode(
                openssl_encrypt(
                    $value,
                    $ciphering,
                    $encryption_key,
                    0,
                    $encryption_iv

                )

            )

        );
    }

    public function getCompanyName(){
        $UserRecord = $this->Record->select()->from("package_meta")->where("key", "=", "default_company_name")->fetch();
        return $UserRecord->value;
    }
    
    public function sendEmail($emailtemplateKey, $emailBodyArray)
    {
        try {
            // $companyName = $this->getCompanyName();
            $emailtemplate = $this->emailTemplateArray($emailtemplateKey);
            $from = "sensfrx@mydomain.com";
            // $from_name = $companyName;
            $from_name = 'Sensfrx';
            $to = $emailBodyArray["toclient"];
            $subject = $emailtemplate["subject"];
            $messageString = $this->replacePlaceholders($emailtemplate["bodyContent"], $emailBodyArray["templateVars"]);
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
        $emailArray["loginEmail"] = [
            "subject" => "Unusual activity detected on this account",
            "bodyContent" => '<p style="font-size: 16px;">Hi <b>{$client_name}</b>,</p>
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
            <br><br>
            
            <p>{$signature}</p>',
        ];
        $emailArray["profileUpdateEmail"] = [
            "subject" => "Unusual activity - Profile Update Notification",
            "bodyContent" => '<p style="font-size: 16px;">Hi <b>{$client_name}</b>,</p>
            <p style="font-size: 14px;">
                There is some unusual activity detected on this account. Your profile information has been updated.
                If you did not make these changes or suspect unauthorized activity, please contact our support team immediately.
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
            
            <p>{$signature}</p>',
        ];
        $emailArray["restPasswordEmail"] = [
            "subject" => 'Suspicious Login Found - Password Reset',
            "bodyContent" => '<p style="font-size: 16px;">Dear {$client_full_name},</p>
        
            <p style="font-size: 14px;">
                We hope this email finds you well. We are writing to inform you about some suspicious behavior that we have detected on your account. Your security is of utmost importance to us, and we take any potential threats very seriously.
            </p>
            <p style="font-size: 14px;">
                Please choose an option: 
            </p>
            <div style="text-align: center; margin: 0 auto;">
                <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 10px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">This was me</a>
                <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">This was not me</a>
            </div>
        
            <p style="font-size: 14px;">When you visit the link above, you will have the opportunity to choose a new password.</p>
            
            <p style="font-size: 14px;">{$signature}</p>
            <br>
            <br>
            
            <p style="font-size: 14px;">
                We take the security and privacy of your account seriously, and we will continue to monitor your account for any further suspicious activities. Rest assured, we are committed to maintaining the highest level of security for our users.
            </p>
            
            <p style="font-size: 14px;">
                Thank you for your prompt attention to this matter. We appreciate your cooperation in ensuring the security of your account. Should you have any questions or need further assistance, please feel free to reach out to us.
            </p>
            
            <p style="font-size: 14px;">
                Thank you for your attention to this matter. If you have any further questions or need additional assistance, please do not hesitate to reach out to us.
            </p>
            
            ',
        ];

        $emailArray["clientRegisterEmail"] = [
            "subject" => "Registration - Unusual Activity Detected During Registration",
            "bodyContent" => '<p style="font-size: 16px;">Hi <b>{$fullname}</b>,</p>

                <p style="font-size: 14px;">
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
                </p>
                
                
                
                <p>{$signature}</p>',
        ];
        $emailArray["clientRegisterChallengeEmail"] = [
            "subject" => 'Suspicious Activity Detected During Registration - Your Account',
            "bodyContent" => '<p style="font-size: 16px;">Dear {$full_name},</p>

            <p style="font-size: 14px;">
                We hope this email finds you well. We are writing to inform you about some suspicious behavior that we have detected on your account. Your security is of utmost importance to us, and we take any potential threats very seriously.
            </p>
            <p style="font-size: 14px;">
                Please choose an option: 
            </p>
            <div style="text-align: center; margin: 0 auto;">
                <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 10px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">This was me</a>
                <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">This was not me</a>
            </div>
            
            <p style="font-size: 14px;">When you visit the link above, you will have the opportunity to choose a new password.</p>
            
            <p style="font-size: 14px;">{$signature}</p>
            <br>
            <br>
            
            <p style="font-size: 14px;">
                We take the security and privacy of your account seriously, and we will continue to monitor your account for any further suspicious activities. Rest assured, we are committed to maintaining the highest level of security for our users.
            </p>
            
            <p style="font-size: 14px;">
                Thank you for your prompt attention to this matter. We appreciate your cooperation in ensuring the security of your account. Should you have any questions or need further assistance, please feel free to reach out to us.
            </p>
            
            <p style="font-size: 14px;">
                Thank you for your attention to this matter. If you have any further questions or need additional assistance, please do not hesitate to reach out to us.
            </p>
            
            ',
        ];
        $emailArray["clientRegisterDenyEmail"] = [
            "subject" => "Suspicious Registration prevented - Reset",
            "bodyContent" => '<p style="font-size: 16px;">Dear {$client_full_name},</p>
                              <p style="font-size: 14px;">
                                  This user account has been blocked due to recent suspicious registration activity. For security purposes, we kindly request that you reset your password to regain access to your account.
                              </p>

                              <p style="font-size: 14px;">{$signature}</p>
                              <br>
                              <br>
                              <p style="font-size: 14px;">
                                  Thank you for your attention to this matter. If you have any further questions or need additional assistance, please do not hesitate to reach out to us.
                              </p>
                              ',
        ];

        $emailArray["Trans_deny_severity_Mail_Send"] = [
            "subject" => "Urgent: Highly Suspicious Transaction Detected on Your Account",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
            <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                <div style="font-size: 14px; line-height: 1.5;">
                    <p style="margin-bottom: 15px;">
                        Dear {$client_full_name},<br><br>
                        We have blocked a highly suspicious transaction from your account for {$amount} on {$date}.<br>
                        For security purposes, we kindly request that you reset your password to regain access to your account.<br>      
                    </p>
                    <p style="margin-top: 20px;">{$signature}</p>
                </div>
            </div>
        </div>
        ',
        ];
        $emailArray["Trans_challenge_severity_Mail_Send"] = [
            "subject" => "Important: Recent Transaction and Security Notification",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                    <div style="font-size: 14px; line-height: 1.5;">
                        <p style="margin-bottom: 15px;">
                            Dear {$fullname},<br><br>
                            We wanted to bring a matter to your immediate attention regarding a recent transaction activity from your account.<br> 
                            We have identified a suspicious successful transaction attempt on your account for {$amount} on {$date}.<br>
                            Click below to let us know if this was you or not. If not, we advise changing your password immediately to protect your account.      
                        </p>
                        <div style="text-align: center; margin: 0 auto;">
                            <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 10px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                This was me
                            </a>
                            <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                This was not me
                            </a>
                        </div>
                        <p style="margin-top: 20px;">{$signature}<br></p>
                    </div>
                </div>
            </div>
            ',
        ];
        $emailArray["Trans_medium_severity_Mail_Send"] = [
            "subject" => "Important: Recent Transaction and Security Notification",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
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
                        </table>
                        <br>
                        <br>  
                        To ensure your accounts security, we recommend taking prompt action. Consider changing your password and reviewing your account settings.</p>
                        <p style="margin-top: 20px;">Best regards,<br></p>
                    </div>
                </div>
            </div>
            ',
        ];
        $emailArray["Trans_challenge_severity_Mail_Send_succeeded"] = [
            "subject" => "Important: Recent Transaction and Security Notification",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                   
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="margin-bottom: 15px;">Dear {$fullname},<br><br>
                                            We wanted to bring a matter to your immediate attention regarding a recent transaction activity from your account.<br> 
                                            We have identified a suspicious successful transaction on your account for {$amount} on {$date}.<br>
                                            Currently, we have marked this order as on hold. If this was not you, please contact the administrator as soon as possible to secure your account.<br><br>
                                            Click below to let us know if this was you or not. If not, we advise changing your password immediately to protect your account.
                                        </p>
                                        <div style="text-align: center; margin: 0 auto;">
                                            <a href="{$allow_url}" style="color: #fff; background-color: #337ab7; margin-right: 10px; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                This was me
                                            </a>
                                            <a href="{$deny_url}" style="color: #fff; background-color: #c9302c; padding: 6px 12px; border-radius: 4px; text-decoration: none !important;">
                                                This was not me
                                            </a>
                                        </div>
                                        <p style="margin-top: 20px;">Best regards</p>
                                    </div>
                                </div>
                            </div>',
        ];
        
        $emailArray["Trans_medium_severity_Mail_Send_succeeded"] = [
            "subject" => "Important: Recent Transaction and Security Notification",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                       
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$fullname},<br><br>
                                            We wanted to bring to your attention a matter concerning a transaction on your account. 
                                            We have identified a successful transaction succeeded from your account for {$amount} on {$date} that appeared to have some characteristics indicative of potential suspicious activity.
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
                                            <br>  
                                            To ensure your accounts security, we recommend taking prompt action. Consider changing your password and reviewing your account settings.</p>
                                            <p style="margin-top: 20px;">Best regards</p>
                                        </div>
                                    </div>
                                </div>',
        ];
        $emailArray["Trans_deny_severity_Mail_Send_succeeded"] = [
            "subject" => "Urgent: Highly Suspicious Transaction Detected on Your Account",
            "bodyContent" => '<div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                       
                                        <div style="font-size: 14px; line-height: 1.5;">
                                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                                We have blocked a highly suspicious transaction from your account for {$amount} on {$date}.<br>
                                                We have marked this order as cancelled for security purposes. If this was not you, please contact the administrator.<br>      
                                            </p>
                                            <p style="margin-top: 20px;">Best regards</p>
                                        </div>
                                    </div>
                                </div>',
        ];
        $emailArray["sa"] = [
            "subject" => "Unusual activity - Profile Update Notification",
            "bodyContent" => '',
        ];

        //bot emails
        $emailArray["botMediumEmail"] = [
            "subject" => "Bot Medium - 'Alert: Potential Bot Activity Detected on Your Account' ",
            "bodyContent" => '
                <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                        <div style="font-size: 14px; line-height: 1.5;">
                            <p style="margin-bottom: 15px;">Dear {$client_name},<br><br>
                                Our systems have detected potential bot activity on your account on {$date}. This activity has been flagged as medium severity. 
                                Please review your account settings and consider changing your password to enhance security.</p>
                                <p style="margin-top: 20px;">Best regards,<br><a href="https://sensfrx.ai/" style="color: #007BFF; text-decoration: none;">Sensfrx</a></p>
                        </div>
                    </div>
                </div>
                ',
        ];

        $emailArray["botHighEmail"] = [
            "subject" => "Bot High - 'Warning: Suspicious Bot Activity Detected on Your Account'",
            "bodyContent" => '
                <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                    <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                        <div style="font-size: 14px; line-height: 1.5;">
                            <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                Our systems have detected suspicious bot activity on your account on {$date}. This activity has been flagged as high severity. 
                                We strongly recommend you change your password immediately and review your recent account activity to ensure no unauthorized changes have been made.</p>
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
                            <p style="margin-top: 20px;">Best regards,<br><a href="https://sensfrx.ai/" style="color: #007BFF; text-decoration: none;">sensfrx</a></p>
                        </div>
                    </div>
                </div>
                ',
        ];

        $emailArray["botCriticalEmail"] = [
            "subject" => "Bot Critical - 'Critical: Immediate Security Alert for Your Account' ",
            "bodyContent" => '
                            <div style="font-family: Arial, sans-serif; background-color: #f7f7f7; padding: 20px;">
                                <div style="background-color: #ffffff; border-radius: 5px; box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); padding: 20px;">
                                    <div style="font-size: 14px; line-height: 1.5;">
                                        <p style="margin-bottom: 15px;">Dear {$client_full_name},<br><br>
                                        Our systems have detected severe bot activity on your account on {$date}. This activity has been flagged as critical. 
                                        Immediate action is required. Please change your password, review your account settings, and contact our support team to ensure the security of your account.</p>
                                        <p style="font-size: 14px;">
                                            To reset your password, please visit the url below:<br />
                                            Click Here... <a href="{$reset_password_url}" style="color: #007BFF; text-decoration: none;">Reset Password URL</a>
                                        </p>
                                        <p style="font-size: 14px;">When you visit the link above, you will have the opportunity to choose a new password.</p>
                                        <br>
                                        <p style="margin-top: 20px;">Best regards,<br><a href="https://sensfrx.ai/" style="color: #007BFF; text-decoration: none;">sensfrx</a></p>
                                    </div>
                                </div>
                            </div>
                ',
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
