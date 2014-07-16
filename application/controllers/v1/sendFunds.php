<?php

/**
 * Class transferFunds
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 */
class SendFunds extends MY_Controller {

    protected $data;
    protected $client;
    protected $accountFrom;
    protected $accountTo;

    public function __construct() {
        parent::__construct();
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * add external linked account to the database
     */
    public function index() {
        $data = $this->request->getData();

        $soapFunds = new SoapFunds($this->current_user);

        $sourceAccountId = new soapId(@$data['sourceAccount']['id'], @$data['sourceAccount']['referenceId']);

        $destinationAccount = new soapId(@$data['destination']['linkedExternalAccountId']['id'], @$data['destination']['linkedExternalAccountId']['referenceId']);

        $res = $soapFunds->validateAccount(new authorization(), @$data['subscriberId'], $sourceAccountId, $destinationAccount, $data, 'send');
        $res = (array) $res;
        RestUtils::sendResponse($res['code'], $res);
    }

}

