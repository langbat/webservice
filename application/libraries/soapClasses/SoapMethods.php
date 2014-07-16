<?php






/**
 * @pw_set minoccurs=0
 * @pw_element string $referenceId
 * @pw_set minoccurs=0
 * @pw_set nillable=true
 * @pw_element int $id
 * @pw_complex soapId
 */
class soapId{
    public $referenceId;
    public $id;
    public function __construct($id, $referenceId)
    {
        if(!is_null($id)) $id = (int)$id;
        $this->id = $id;
        $this->referenceId = $referenceId;
    }
}

/**
 * @pw_set nillable=false
 * @pw_element string $line1
 * @pw_set minoccurs=0
 * @pw_element string $line2
 * @pw_set nillable=false
 * @pw_element string $city
 * @pw_set nillable=false
 * @pw_element string $state
 * @pw_set nillable=false
 * @pw_element string $zipcode
 * @pw_complex soapAddress
 */
class soapAddress{
    public $line1;
    public $line2;
    public $city;
    public $state;
    public $zipcode;
}

/**
 * @property bool $success
 * @property string $message
 * @property int $code
 */
class general{
    public $success;
    public $message;
    public $code;
}

/**
 * @pw_set nillable=false The next element can't be NULL
 * @pw_element string $username User name
 * @pw_set nillable=false
 * @pw_element string $password User name
 * @pw_complex authorization The complex type name definition
 */
class authorization{
    public $username;
    public $password;
}

/**
 * X project methods
 * @service SoapMethods
 */
class SoapMethods {
    protected $CI;
    public $current_user=null;
    public function __construct()
    {
        $this->CI =& get_instance();
    }

    protected function _authorize($username, $password, $subscriber_id)
    {
        if(!is_null($this->current_user)) return true;
        if (empty($username) || empty($password) || empty($subscriber_id)) {
            return false;
        }
        elseif (!$this->CI->users->validate_user($subscriber_id, $username, $password)) {
            return false;
        }
        $this->current_user = $this->CI->users->get_by_subscriber($subscriber_id);
        return true;

    }
}