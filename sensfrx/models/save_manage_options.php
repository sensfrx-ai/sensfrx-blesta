<?php
/**
 * Feed Reader Feeds
 *
 * Manages feeds, articles, and subscribers
 *
 * @package blesta SensfrxModel
 * @subpackage blesta.plugins.feed_reader.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SaveManageOptions extends SensfrxModel
{
    /**
     * Initialize
     */
    public function __construct()
    {
        parent::__construct();
    }
    public function update($domain = "", $property_id = "", $property_secret = "", $api_response = "")
    {
        $company_id = $this->getCompanyId();
        if ($this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->numResults()) {
            return ($this->Record->where("company_id", "=", $company_id)->update("sensfrx_config", array('domain' => $domain, 'property_id' => $property_id, 'property_secret' => $property_secret, "sensfrx_status" => $api_response)) ? true : false);
        } else {
            $this->Record->insert("sensfrx_config", array('domain' => $domain, 'property_id' => $property_id, 'property_secret' => $property_secret, 'company_id' => $company_id, "sensfrx_status" => $api_response));
            return $this->Record->lastInsertId();
        }
        return false;
    }
    public function get()
    {
        $company_id = $this->getCompanyId();
        if ($this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->numResults()) {
            return $this->Record->select()->from("sensfrx_config")->where("company_id", "=", $company_id)->fetch();
        } else {
            return false;
        }
    }
    public function getCompanyId()
    {
        if (isset($this->company_id)) {
            return $this->company_id;
        } else {
            $companies = new Companies;
            $company_details = $companies->getByHostname($_SERVER['HTTP_HOST']);
            if (isset($company_details->id)) {
                return $company_details->id;
            } else {
                return false;
            }
        }
    }

    public function sensfrx_CurlRequest($domain, $propertyId, $secretKey, $requestType)
    {
        $version = BLESTA_VERSION;
        $api_key = base64_encode("{$propertyId}:{$secretKey}");
        $ch = curl_init();
        
        try {
            if ($requestType == 'activate') {
                $url = 'https://a.sensfrx.ai/v1/plugin-integrate';
                $action = "SensFRX plugin-integrate";
            } elseif ($requestType == 'deactivate') {
                $url = 'https://a.sensfrx.ai/v1/plugin-uninstall';
                $action = "SensFRX plugin-uninstall";
            } else {
                throw new Exception("Invalid request type: {$requestType}");
            }

            $post_data = "{\r\n    \"app_type\": \"WHMCS\",\r\n    \"app_version\": \"{$version}\",\r\n    \"domain\": \"{$domain}\"\r\n}";
            $headers = [
                "Authorization: Basic {$api_key}",
                "Content-Type: application/json",
                "cache-control: no-cache"
            ];

            curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
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

            if ($response === false) {
                throw new Exception('cURL Error: ' . curl_error($ch));
            }
            curl_close($ch);

            return $response;
        } catch (Exception $e) {
            $error_message = $e->getMessage();

            if (isset($ch)) {
                curl_close($ch);
            }

            return $error_message;
        }
    }

}
