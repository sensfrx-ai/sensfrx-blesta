<?php
/**
 * Sensfrx parent controller
 *
 * @link https://whmcsglobalservices.com/ WHMCS Global Services
 */
class SensfrxController extends AppController
{
    /**
     * Require admin to be login and setup the view
     */
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();
        // Override default view directory
        $this->view->view = "default";
        $this->requireLogin();
        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this))],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
        Language::loadLang(
            'sensfrx_controller',
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );
    }
}
