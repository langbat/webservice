<?php

/**
 * Class OpenAccount
 */
class OpenAccount extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data = $this->request->getData();

        $soapAccount = new SoapAccounts($this->current_user);
        $clientId = new soapId(@$data['clientId']['id'], @$data['clientId']['referenceId']);
        $res = $soapAccount->OpenAccount(new authorization(), null, $clientId, @$data['referenceId'], @$data['title']);
        $res = (array)$res;
        RestUtils::sendResponse($res['code'], $res);
    }

}

