<?php if (!defined('BASEPATH')) die('No direct script access allowed');

/**
 * vBulletin password updater Extension File
 *
 * @package         VB_Password
 * @author          Arron Woods <http://www.arronwoods.com>
 * @license         MIT
 * @link            http://www.arronwoods.com
 * @see             https://docs.expressionengine.com/latest/development/extensions.html
 */
class Vb_password_ext
{
    public $version = VB_PASSWORD_VERSION;
    public $name = VB_PASSWORD_NAME;
    public $description = 'Updates the password of a vbuser when changed in EE';
    public $docs_url = '';
    public $settings_exist = true;
    public $settings = array();
    public $hooks = array(
        'updateVbPassword' => 'after_member_save'
    );

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct($settings = '')
    {
        $this->settings = is_array($settings)? $settings : array();
    }

    /**
     * @param $member
     * @param $values
     */
    public function updateVbPassword($member, $values)
    {
        $username = $member->username;
        $password = $this->getPasswordFromPost();

        if (!empty($username) && !empty($password)) {
            try{
                $this->updateVbUsersPassword($username, $password);
            }catch(\Exception $e){
                error_log($e);
            }
        }
    }

    /**
     * @param $username
     * @param $plainPassword
     * @return bool|mysqli_result
     */
    public function updateVbUsersPassword($username, $plainPassword){
        $salt = $this->getSalt();
        $vbHash = $this->hashPasswordForVb($plainPassword);

        $sql = "UPDATE `user` SET token = '$vbHash', scheme='legacy', secret='$salt' " ;
        $sql.= "WHERE username LIKE '%[" . $username . "]%'";

        $db = $this->getDbConnection();
        $result = $db->query($sql);

        if($result !== true){
            throw new \RuntimeException('VB DB Error on update, ' . $db->error);
        }

        if($db->affected_rows !== 1){
            throw new \RuntimeException("VB DB User '$username' not found");
        }

        return $result;
    }

    /**
     * @param $plainPassword
     * @return string
     */
    public function hashPasswordForVb($plainPassword){
        $salt = $this->getSalt();
        $md5 = md5($plainPassword);
        $hash = md5( $md5 . $salt) . " $salt";

        return $hash;
    }

    /**
     * @return mixed|null
     */
    public function getPasswordFromPost()
    {
        $data = $_POST;
        return $this->searchArrayForPassword($data);
    }

    /**
     * @return string
     */
    public function getSalt(){
        return $this->settings['vbp:password_salt'];
    }

    /**
     * @return mysqli
     */
    public function getDbConnection(){
        $host = $this->settings['vbp:db_host'];
        $user = $this->settings['vbp:db_user'];
        $password = $this->settings['vbp:db_password'];
        $dbname = $this->settings['vbp:db_name'];
        $port = $this->settings['vbp:db_port'];

        if(empty($port)){
            $port = '3306';
        }

        $mysqli = new mysqli($host, $user, $password, $dbname, $port);
        if ($mysqli->connect_error) {
            throw new \RuntimeException('VB DB Connect Error (' . $mysqli->connect_errno . ') '
                . $mysqli->connect_error);
        }

        return $mysqli;
    }

    /**
     * @param array $data
     * @return mixed|null
     */
    protected function searchArrayForPassword(array $data)
    {
        foreach ($data as $key => $value) {
            if ($key === 'new_password' && !empty($value)) {
                return $value;
            }

            if (is_array($value)) {
                $result = $this->searchArrayForPassword($value);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function settings(){
        $settings = array();

        $settings['vbp:db_host']      = array('i', '', "localhost");
        $settings['vbp:db_user']      = array('i', '', "dbuser");
        $settings['vbp:db_password']      = array('i', '', "");
        $settings['vbp:db_name']      = array('i', '', "forum");
        $settings['vbp:db_port']      = array('i', '', "3306");
        $settings['vbp:password_salt']      = array('i', '', "");

        return $settings;
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
                'class' => __CLASS__,
                'method' => $method,
                'hook' => $hook,
                'settings' => serialize($this->settings),
                'priority' => 100,
                'version' => $this->version,
                'enabled' => 'y'
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
    public function update_extension($current = false)
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
                'class' => __CLASS__,
                'method' => $method,
                'hook' => $hook,
                'settings' => serialize($this->settings),
                'priority' => 100,
                'version' => $this->version,
                'enabled' => 'y'
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
