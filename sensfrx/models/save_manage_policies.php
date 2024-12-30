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



class SaveManagePolicies extends SensfrxModel

{

    /**

     * Initialize

     */

    public function __construct()

    {

        parent::__construct();

    }



    public function update($challenge=false, $allow=false, $deny=false, $shadow=false)

    {

        $company_id=Configure::get('Blesta.company_id');

        if ($this->Record->select()->from("sensfrx_policies")->where("company_id", "=", $company_id)->numResults()) {

            return ($this->Record->where("company_id", "=", $company_id)->update("sensfrx_policies", array('challenge' => $challenge,'allow' => $allow,'deny' => $deny,'shadow' => $shadow))?true:false);

        } else {

            $this->Record->insert("sensfrx_policies", array('challenge' => $challenge,'allow' => $allow,'deny' => $deny,'shadow' => $shadow,'company_id' => $company_id));

            return $this->Record->lastInsertId();

        }

        return false;

    }

    public function get()

    {

        $company_id=Configure::get('Blesta.company_id');

        if ($this->Record->select()->from("sensfrx_policies")->where("company_id", "=", $company_id)->numResults()) {

            return $this->Record->select()->from("sensfrx_policies")->where("company_id", "=", $company_id)->fetch();

        } else {

            return false;

        }

    }

}

