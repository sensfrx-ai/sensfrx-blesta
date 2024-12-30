<?php

/**
 * Feed Reader manage plugin controller
 *
 * @package blesta
 * @subpackage blesta.plugins.feed_reader
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */

class AdminManagePlugin extends AppController
{
    /**
     * Performs necessary initialization
     */
    private function init()
    {
        // Require login
        $this->parent->requireLogin();
        Language::loadLang('sensfrx_manage_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
        Language::loadLang('sensfrx_plugin', null, PLUGINDIR . 'sensfrx' . DS . 'language' . DS);
        $this->uses(['Sensfrx.SaveManageOptions']);
        Loader::loadComponents($this, ['Session']);
        $this->plugin_id = isset($this->get[0]) ? $this->get[0] : null;
    }
    /**
     * Returns the view to be rendered when managing this plugin
     */
    public function index()
    {
        $this->init();
        $this->parent->structure->set('page_title', Language::_('SensfrxManage.title', true));
        $vars = [
            'plugin_id' => $this->plugin_id,
            'company_id' => Configure::get('Blesta.company_id'),
        ];
        $config_options = $this->SaveManageOptions->get();
        if ($this->Session->read('sensfrx_manage_success') != null) {
            $vars["success"] = $this->Session->read('sensfrx_manage_success');
            $this->Session->write('sensfrx_manage_success', null);
        } elseif ($this->Session->read('sensfrx_manage_error') != null) {
            $vars["error"] = $this->Session->read('sensfrx_manage_error');
            $this->Session->write('sensfrx_manage_error', null);
        }
        // Set the view to render
        $this->view->setView('manage', 'sensfrx.manage');
        // Set variables all for the view at once
        $this->set(compact("vars", "config_options"));
        // Return view to be displayed
        return $this->view->fetch();
    }
    /**
     * Save Configuration options
     */



    public function save()
    {
        $this->init();

        if (
            isset($_POST["domain"], $_POST["property_id"], $_POST["property_secret"]) &&
            !empty($_POST["domain"]) &&
            !empty($_POST["property_id"]) &&
            !empty($_POST["property_secret"])
        ) {

            $domain = $_POST["domain"];
            $property_id = $_POST["property_id"];
            $property_secret = $_POST["property_secret"];

            $sendAutAPIRequest = $this->SaveManageOptions->sensfrx_CurlRequest($domain, $property_id, $property_secret, 'activate');
            $api_response = json_decode($sendAutAPIRequest, true);

            if ($api_response && $api_response["status"] == "success") {
                $this->Session->write('sensfrx_manage_success', Language::_('SensfrxManage.success', true));
            } else {
                $error_message = isset($api_response["message"]) ? $api_response["message"] : "Something went wrong";
                $this->Session->write('sensfrx_manage_error', $error_message);
            }

            $api_response = json_encode($api_response);

            $response = $this->SaveManageOptions->update($domain, $property_id, $property_secret, $api_response);

            // if ($response) {
            //     $this->Session->write('sensfrx_manage_success', Language::_('SensfrxManage.success', true));
            // } else {
            //     $this->Session->write('sensfrx_manage_error', Language::_('SensfrxManage.error', true));
            // }
        } else {
            $this->Session->write('sensfrx_manage_error', Language::_('SensfrxManage.error', true));
        }

        header("Location: " . $this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id);
        exit;
    }

    // public function save()
    // {
    //     $this->init();
    //     if (isset($_POST["domain"]) && !empty($_POST["domain"]) && isset($_POST["property_id"]) && !empty($_POST["property_id"]) && isset($_POST["property_secret"]) && !empty($_POST["property_secret"])) {
    //         $domain = $_POST["domain"];
    //         $property_id = $_POST["property_id"];
    //         $property_secret = $_POST["property_secret"];
    //         $sendAutAPIRequest = $this->SaveManageOptions->sensfrx_CurlRequest($domain, $property_id, $property_secret, 'activate');
    //         $api_response = json_decode($sendAutAPIRequest, true);

    //         if($api_response["status"] == "success"){
    //             $this->Session->write('sensfrx_manage_success', Language::_('SensfrxManage.success', true));
    //             $api_response = $api_response;
    //         } else if($api_response["status"] == "error"){
    //             $this->Session->write('sensfrx_manage_error', Language::_($api_response["message"], true));
    //             $api_response = $api_response;
    //         } else{
    //             $api_response["status"] = "error";
    //             if($api_response["message"]){
    //                 $api_response["message"] = $api_response["message"];
    //             } else{
    //                 $api_response["message"] = "Something Went Wrong";
    //             }
    //             $this->Session->write('sensfrx_manage_error', Language::_('SensfrxManage.error', true));
    //         }
    //         $api_response = json_encode($api_response);


    //         $response = $this->SaveManageOptions->update($domain, $property_id, $property_secret, $api_response);
    //         if ($response) {
    //             $this->Session->write('sensfrx_manage_success', Language::_('SensfrxManage.success', true));
    //         } else {
    //             $this->Session->write('sensfrx_manage_error', Language::_('SensfrxManage.error', true));
    //         }
    //     } else {
    //         $this->Session->write(
    //             'sensfrx_manage_error',
    //             Language::_(
    //                 'SensfrxManage.error',
    //                 true
    //             )
    //         );
    //     }
    //     header("Location: " . $this->base_uri . 'settings/company/plugins/manage/' . $this->plugin_id);
    //     exit;
    // }
}
