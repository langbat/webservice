<?php

/**
 * Class LinkedExternalAccount
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 */
class DeleteLinkedExternalAccount extends MY_Controller {

    protected $data;
    protected $client;

    public function __construct() {
        parent::__construct();
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * delete external linked account for specific user
     */
    public function index() {
        $data = $this->request->getData();

        $soapAccount = new SoapAccounts($this->current_user);
        $linkId = new soapId(@$data['linkedExternalAccountId']['id'], @$data['linkedExternalAccountId']['referenceId']);
        $account = $soapAccount->deleteLinkExternalAccount(new authorization(), null, $linkId);
        $account = (array)$account;
        RestUtils::sendResponse($account['code'], $account['data']);
    }

}

