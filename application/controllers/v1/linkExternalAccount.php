<?php

/**
 * Class LinkedExternalAccount
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 */
class LinkExternalAccount extends MY_Controller {

    protected $data;
    protected $client;

    public function __construct() {
        parent::__construct();
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * add external linked account to the database
     */
    public function index() {
        $data = $this->request->getData();

        $soapAccount = new SoapAccounts($this->current_user);
        $clientId = new soapId(@$data['clientId']['id'], @$data['clientId']['referenceId']);
        $external = new soapExternalAccountInfo();
        $external->accountName = @$data['accountName'];
        $external->accountNumber = @$data['accountNumber'];
        $external->accountType = @$data['accountType'];
        $external->bankName = @$data['bankName'];
        $external->holderName = @$data['holderName'];
        $external->routingNumber = @$data['routingNumber'];
        $account = $soapAccount->linkExternalAccount(new authorization(), null, $clientId, $external, @$data['referenceId']);
        $account = (array)$account;
        RestUtils::sendResponse($account['code'], $account['data']);
    }

}

