<?php

/**
 * Class UpdateAccount
 * 
 */
class UpdateAccount extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data = $this->request->getData();

        $soapAccount = new SoapAccounts($this->current_user);
        $accountId = new soapId(@$data['accountId']['id'], @$data['accountId']['referenceId']);
        $res = $soapAccount->updateAccount(new authorization(), null, $accountId, @$data['title']);
        $res = (array)$res;
        RestUtils::sendResponse($res['code'], $res['data']);
    }

}

