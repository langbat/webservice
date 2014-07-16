<?php

/**
 * Class transferFunds
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 */
class ReturnFunds extends MY_Controller {

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

        $scheduleId = new soapId(@$data['scheduleId']['id'], @$data['scheduleId']['referenceId']);

        $res = $soapFunds->validateRefund(new authorization(), @$data['subscriberId'], $scheduleId, $data, 'refund');
        $res = (array) $res;
        RestUtils::sendResponse($res['code'], $res);
    }

}

