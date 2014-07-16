<?php
/**
 * Created by PhpStorm.
 * User: Sergio
 * Date: 26-12-13
 * Time: 11:45 AM
 */

class Soap extends CI_Controller {
    function index()
    {
        require_once(FCPATH.'application/libraries/wsdl-creator/class.phpwsdl.php');
        if(!class_exists('PhpWsdlServers'))
            require_once(FCPATH.'application/libraries/wsdl-creator/class.phpwsdl.servers.php');
        if(!class_exists('PhpWsdlJavaScriptPacker'))
            require_once(FCPATH.'application/libraries/wsdl-creator/class.phpwsdl.servers-jspacker.php');
        PhpWsdlServers::$EnableHttp = false;// Disable the http webservice
        PhpWsdlServers::$EnableJson = false;// Disable the JSON webservice
        PhpWsdlServers::$EnableRest = false;// Disable the REST webservice
        PhpWsdlServers::$EnableRpc = false;// Disable the XML RPC webservice
        $server = PhpWsdl::CreateInstance(null, site_url('soap'));
        $server->Files = array(
            FCPATH.'application/libraries/soapClasses/SoapMethods.php',
            FCPATH.'application/libraries/soapClasses/SoapClients.php',
            FCPATH.'application/libraries/soapClasses/SoapFunds.php',
            FCPATH.'application/libraries/soapClasses/SoapAccount.php',
        );
        $server->RunServer();
    }
}
