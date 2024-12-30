<?php
/**
 * Sensfrx Parent Model
 *
 * @link https://whmcsglobalservices.com/ WHMCS Global Services
 */
class SensfrxModel extends AppModel
{
    public function __construct()
    {
        parent::__construct();
        // Auto load language for these models
        Language::loadLang([Loader::fromCamelCase(get_class($this))], null, dirname(__FILE__) . DS . 'language' . DS);
    }
}
