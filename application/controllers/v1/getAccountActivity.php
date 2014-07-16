<?php

/**
 * Class UpdateAccount
 */
class GetAccountActivity extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data = $this->request->getData();

        $soapAccount = new SoapAccounts($this->current_user);
        $accountId = new soapId(@$data['accountId']['id'], @$data['accountId']['referenceId']);
        $account = $soapAccount->getAccountactivity(new authorization(), null, $accountId, @$data['fromDate'], @$data['toDate']);
        $account = (array)$account;
        RestUtils::sendResponse($account['code'], $account['data']);
    }

}

