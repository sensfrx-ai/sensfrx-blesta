<?php
/**
 * Feed Reader Feeds
 *
 * Manages feeds, articles, and subscribers
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SensfrxApi extends SensfrxModel
{
    private $client_uri;
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
        Loader::loadComponents($this, ['Session', 'Emails', 'Clients', 'Users', 'Contacts', 'Html', 'Companies']);
        Loader::loadModels($this, ['Clients', 'Sensfrx.SaveManageOptions', 'Users', 'Sensfrx.SaveManagePolicies', 'Staff', 'Logs', 'Settings']);
        Language::loadLang('sensfrx_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
        // Set client uri
        $webdir=WEBDIR;
        $root_web = $this->Settings->getSetting('root_web_dir');
        if ($root_web) {
            $webdir = str_replace(DS, '/', str_replace(rtrim($root_web->value, DS), '', ROOTWEBDIR));
            if (!HTACCESS) {
                $webdir .= 'index.php/';
            }
        }
        $company=$this->Companies->get($this->getCompanyId());
        // Get the company hostname
        $hostname = isset($company->hostname)
            ? $company->hostname
            : '';
        $this->client_uri=(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ? 'https://' : 'http://').$company->hostname . $webdir . Configure::get('Route.client') . '/';
        
    }
    public function logoutTrack()
    {
        $device_id=false;
        if ($this->Session->read('device_id')!=null) {
            $device_id=  $this->Session->read('device_id');
        } else {
            return
                [
                    "status" => "error",
                    "message"  => "Invalid Device Id"
                ]
            ;
        }
        try {
            $login_status=false;
            $client_info=[];
            
            $user_info=false;
            $response=[];
            $param=[];
            if (empty($this->Session->read('blesta_client_id'))) {
                return 
                    [
                        "status" => "error",
                        "message"  => "Client not found"
                    ]
                ;
            }
            $client_info = $this->Clients->get($this->Session->read('blesta_client_id'));
            if (!isset($client_info->user_id)) {
                return
                    [
                        "status" => "error",
                        "message"  => "Client not found"
                    ]
                ;
            } 
            $param=[
                'ev' => "logout",
                'uID' => $client_info->user_id,
                'dID' => $device_id,
                'uex' => ["username"=>$client_info->username,"email"=>$client_info->email],
            ];
            if ($this->Session->read('current_page')!=null) {
                $param["h"]["url"]= $this->Session->read('current_page');
            } else {
                $param["h"]["url"]= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
            }
            // Destroy Session
            $this->Session->clear('device_id');
            $this->Session->clear('current_page');
            $result=$this->apiCall($param, "https://a.sensfrx.ai/v1/login");
            if (isset($result["status"]) && !empty($result["status"])) {
                $response["status"]="success";
            } else {
                return
                    [
                        "status" => "error",
                        "message"  => "Api response status not found.",
                        "response"  => $result,
                    ]
                ;
            }
            $response["response"]=$result;
            return $response;
        } catch(Exception $e) {
            return [
                "status" => "error",
                "message"  => $e->getMessage()
            ];
        }
    }
    public function resetPassword()
    {
        if ($this->Session->read('device_id')!=null && $this->Session->read('sensfrx_reset_sid')!=null) {
            $device_id=  $this->Session->read('device_id');
        } else {
            return [
                "status" => "error",
                "message"  => "Invalid parameters."
            ];
        }
        $response=[];
        $resetParams = [];
        $temp = explode('|', $this->Clients->systemDecrypt($this->Session->read('sensfrx_reset_sid')));
        foreach ($temp as $field) {
            $field = explode('=', $field, 2);
            $resetParams[$field[0]] = $field[1];
        }
        $client = $this->Clients->getByUserId((isset($resetParams['u'])?$resetParams['u']:false));
        $user = $this->Users->get((isset($resetParams['u'])?$resetParams['u']:false));
        try { 
            if (isset($client->id)) {
                $param=[
                    'ev' => "reset_password_failed",
                    'uID' => $client->user_id,
                    'dID' => $device_id,
                    'uex' => ["username"=>$client->username,"email"=>$client->email],
                ];
                if ($this->Session->read('current_page')!=null) {
                    $param["h"]["url"]= $this->Session->read('current_page');
                } else {
                    $param["h"]["url"]= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                } 
                if (!empty($this->Session->read('blesta_client_id'))) {
                    $param["ev"]="reset_password_succeeded";
                }
                $result=$this->apiCall($param, "https://a.sensfrx.ai/v1/reset-password");
                $response["status"]=(isset($result["status"]) ?"success":"error");
                $response["response"]=$result;
            } else {
                $response= [
                    "status" => "error",
                    "message"  => "Client not found"
                ];
            }
        } catch(Exception $e) {
            $response= [
                "status" => "error",
                "message"  => $e->getMessage()
            ];
        }
        $this->Session->clear('sensfrx_reset_sid');
        $this->Session->clear('device_id');
        $this->Session->clear('current_page');
        return $response;
    }
    public function apiCall($param=[], $url="")
    {

        $config_options=$this->SaveManageOptions->get();
        $response=[];
        $response["httpcode"] = 0;
        if (isset($config_options->property_id) && !empty($config_options->property_id) && isset($config_options->property_secret) && !empty($config_options->property_secret)) :
            $requestor = $this->getFromContainer('requestor');
            $param["h"]['ip'] = $requestor->ip_address;
            $param["h"]['ua'] = (isset($_SERVER['HTTP_USER_AGENT'])?$_SERVER['HTTP_USER_AGENT']:"");
            $param["h"]['ho'] = (isset($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:"");
            $param["h"]['rf'] = (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"");
            $param["h"]['ac'] = [
                "a" => (isset($_SERVER['HTTP_ACCEPT'])?$_SERVER['HTTP_ACCEPT']:""),
                "ac" => isset($_SERVER['HTTP_ACCEPT_CHARSET'])?$_SERVER['HTTP_ACCEPT_CHARSET']:"",
                "ae" => isset($_SERVER['HTTP_ACCEPT_ENCODING'])?$_SERVER['HTTP_ACCEPT_ENCODING']:"",
                "al" => isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?$_SERVER['HTTP_ACCEPT_LANGUAGE']:"",
            ];
            if (!isset($param["h"]["url"])) {
                $param["h"]['url'] = (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['REQUEST_URI'])?$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']:"");
            }
            $curl = curl_init();
            $curl_fields=array(
              CURLOPT_URL => $url,
              CURLOPT_RETURNTRANSFER => true,
              CURLOPT_ENCODING => '',
              CURLOPT_MAXREDIRS => 10,
              CURLOPT_TIMEOUT => 0,
              CURLOPT_FOLLOWLOCATION => true,
              CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
              CURLOPT_CUSTOMREQUEST => 'POST',
              CURLOPT_POSTFIELDS => json_encode($param),
              CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic '.base64_encode($config_options->property_id.":".$config_options->property_secret)
              ),
            );
            curl_setopt_array($curl, $curl_fields);
            $response = curl_exec($curl);
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $inputlogging=[
                "module_id" => 1,
                "direction" => 'input',
                "url" => $url,
                "data" => json_encode($param),
                "status" => 'success',
                "group" => '1',
            ];
            $this->Logs->addModule($inputlogging);
            $outputLogging=[
                "module_id" => 1,
                "direction" => 'output',
                "url" => $url,
                "data" => $response,
                "status" => ($httpcode==200?'success':'error'),
                "group" => '1',
            ];
            $this->Logs->addModule($outputLogging);
        
            $policies=$this->SaveManagePolicies->get();
            if (isset($policies->shadow) && ($policies->shadow == 1 || $policies->shadow =='1')) {
                return ['status' => "shadow", "message"=> "Shadow Mode Enabled"];
            }
            if ($httpcode==200 || $httpcode=="200") {
                if ($response=="" || empty($response)) {
                    return[
                        "status" => "error",
                        "message"  => "Api Response not found",
                        "httpcode"  => $httpcode,
                        "api_response" => $response
                    ];
                } else {
                    $responseData=json_decode($response, true);
                    if (!is_array($responseData)) {
                        $responseData=json_decode($responseData, true);
                    } 
                    if (!is_array($responseData)) {
                        return [
                            "status" => "error",
                            "message"  => "Invalid json",
                            "api_response" => $response
                        ];
                    }
                    return $responseData;
                }
            } else {
                return [
                    "status" => "error",
                    "httpcode"  => $httpcode,
                    "api_response" => $response
                ];
            }
            
        else :
            return [
                "status" => "error",
                "stats"  => "Sensfrx configuration not found."
            ];
        endif;
    }
    public function resetPasswordEmail($client_id=false, $params=[])
    {
        $client = $this->Clients->get($client_id);
        if ($client && $client->status == 'active') {
            $user_id = $client->user_id;
            $company=$this->Companies->get($client->company_id);
            $contact = null;
            if (!($contact = $this->Contacts->getByUserId($user_id, $client->id))) {
                $contact = $client;
            }
            $time = time();
            $hash = $this->Clients->systemHash('u=' . $user_id . '|t=' . $time);
            $requestor = $this->getFromContainer('requestor');
            $tags = [
                'name' => $client->first_name. " ". $client->last_name,
                'client' => $client,
                'contact' => $contact,
                'ip_address' => (isset($params["ip_address"])?$params["ip_address"]:$requestor->ip_address),
                'password_reset_url' => $this->Html->safe(
                    $this->client_uri . 'login/confirmreset/?sid=' .
                    rawurlencode(
                        $this->Clients->systemEncrypt(
                            'u=' . $user_id . '|t=' . $time . '|h=' . substr($hash, -16)
                        )
                    )
                )
            ];
            
            $sent=$this->Emails->send(
                'Sensfrx.reset_password',
                $this->getCompanyId(),
                Configure::get('Blesta.language'),
                $contact->email,
                $tags,
                null,
                null,
                null,
                ['to_client_id' => $client->id]
            );
            return $sent;
        }
        return false;
    }
    private function getEncryptedHash($value='')
    {
        $ciphering = "AES-128-CTR";
  
        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';
        // Store the encryption key
        $encryption_key = "SensfrxEncryptedHash";
        // Use openssl_encrypt() function to encrypt the data
        return str_replace(['+','/','='], ['-','_',''], base64_encode(
                openssl_encrypt(
                    $value, $ciphering, $encryption_key, 0, $encryption_iv
                )
            )
        );
    }
    private function getDecryptedHash($value='')
    {
        $ciphering = "AES-128-CTR";
        $decryption_iv = '1234567891011121';
          
        // Store the decryption key
        $decryption_key = "SensfrxEncryptedHash";
          
        // Use openssl_decrypt() function to decrypt the data
        return openssl_decrypt(
            base64_decode($value), $ciphering, $decryption_key, 0, $decryption_iv
        );
    }   
    public function getCompanyId()
    {
        if (!empty(Configure::get('Blesta.company_id'))) {
            return Configure::get('Blesta.company_id');
        } else {
            $company_details=$this->Companies->getByHostname($_SERVER['HTTP_HOST']);
            if (isset($company_details->id)) {
                return $company_details->id;
            } else {
                return false;
            }
        }
    }    
}
