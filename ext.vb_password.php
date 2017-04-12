<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * Visitor Module Extension File
 *
 * @package         DevDemon_Visitor
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2016 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com
 * @see             https://docs.expressionengine.com/latest/development/extensions.html
 */
class Vb_password_ext
{
    public $version         = VB_PASSWORD_VERSION;
    public $name            = VB_PASSWORD_NAME;
    public $description     = 'Updates the password of a vbuser when changed in EE';
    public $docs_url        = '';
    public $settings_exist  = false;
    public $settings        = array();
    public $hooks           = array(
        'updateVbPassword' => 'after_member_save'
    );

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    { }

    public function updateVbPassword($member, $values){
        $username = $member->username;
	$password = $this->getPasswordFromPost();

        if(!empty($username) && !empty($password)){
            var_dump('update', $username, $password);
            exit;
        }
    }

    public function getPasswordFromPost(){
        $data = $_POST;
        return $this->searchArrayForPassword($data);
    }

    protected function searchArrayForPassword(array $data){
        foreach($data as $key => $value){
	    if($key === 'new_password' && !empty($value)){
                return $value;
            }

            if(is_array($value)){
                $result = $this->searchArrayForPassword($value);
                if($result !== null){
                    return $result;
                }
            }
	}

        return null;
    }

    /**
     * Called by ExpressionEngine when the user activates the extension.
     *
     * @access      public
     * @return      void
     **/
    public function activate_extension()
    {
        foreach ($this->hooks as $method => $hook) {
             $data = array(
                'class'     =>  __CLASS__,
                'method'    =>  $method,
                'hook'      =>  $hook,
                'settings'  =>  serialize($this->settings),
                'priority'  =>  100,
                'version'   =>  $this->version,
                'enabled'   =>  'y'
            );

            // insert in database
            ee()->db->insert('extensions', $data);
        }
    }

    /**
     * Called by ExpressionEngine when the user disables the extension.
     *
     * @access      public
     * @return      void
     **/
    public function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }


    /**
     * Called by ExpressionEngine updates the extension
     *
     * @access public
     * @return void
     **/
    public function update_extension($current=false)
    {
        if ($current == $this->version) return false;

        // Get all existing ones
        $dbexts = array();
        $query = ee()->db->select('*')->from('extensions')->where('class', __CLASS__)->get();

        foreach ($query->result() as $row) {
            $dbexts[$row->hook] = $row;
        }

        // Add the new ones
        foreach ($this->hooks as $method => $hook) {
            if (isset($dbexts[$hook]) === true) continue;

            $data = array(
                'class'     =>  __CLASS__,
                'method'    =>  $method,
                'hook'      =>  $hook,
                'settings'  =>  serialize($this->settings),
                'priority'  =>  100,
                'version'   =>  $this->version,
                'enabled'   =>  'y'
            );

            // insert in database
            ee()->db->insert('extensions', $data);
        }

        // Delete old ones
        foreach ($dbexts as $hook => $ext) {
            if (in_array($hook, $this->hooks) === true) continue;

            ee()->db->where('hook', $hook);
            ee()->db->where('class', __CLASS__);
            ee()->db->delete('extensions');
        }

        // Update the version number for all remaining hooks
        ee()->db->where('class', __CLASS__)->update('extensions', array('version' => $this->version));

    }

}
