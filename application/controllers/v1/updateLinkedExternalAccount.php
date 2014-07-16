<?php

/**
 * Class LinkedExternalAccount
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 */
class UpdateLinkedExternalAccount extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * update external linked account for specific user
     */
    public function index() {
        $data = $this->request->getData();

        $soapAccount = new SoapAccounts($this->current_user);
        $linkId = new soapId(@$data['linkedExternalAccountId']['id'], @$data['linkedExternalAccountId']['referenceId']);
        $external = new soapExternalAccountInfo();
        $external->accountName = @$data['accountName'];
        $external->accountNumber = @$data['accountNumber'];
        $external->accountType = @$data['accountType'];
        $external->bankName = @$data['bankName'];
        $external->holderName = @$data['holderName'];
        $external->routingNumber = @$data['routingNumber'];
        $account = $soapAccount->updateLinkExternalAccount(new authorization(), null, $linkId, $external);
        $account = (array)$account;
        RestUtils::sendResponse($account['code'], $account['data']);
    }

}

