<?php
class Ini_classes{
    public function __construct(){
        require_once(FCPATH.'application/libraries/soapClasses/SoapMethods.php');
        require_once(FCPATH.'application/libraries/soapClasses/SoapClients.php');
        require_once(FCPATH.'application/libraries/soapClasses/SoapFunds.php');
        require_once(FCPATH.'application/libraries/soapClasses/SoapAccount.php');
    }
}