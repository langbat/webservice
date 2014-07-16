<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author Ty Tran
 * Class CancelClient
 */
class CancelClient extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data = $this->request->getData();

        $clientId = new soapId(@$data['clientId']['id'], @$data['clientId']['referenceId']);

        $soapClient = new SoapClients($this->current_user);
        
        $client = $soapClient->cancelClient(new authorization(), @$data['subscriberId'], $clientId);

        $client = (array)$client;

        RestUtils::sendResponse($client['code'], $client['data']);
        
    }

}

