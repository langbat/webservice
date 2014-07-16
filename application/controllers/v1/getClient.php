<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author Ty Tran
 * Class getClient
 */
class GetClient extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $data = $this->request->getData();

        $clientId = new soapId(@$data['clientId']['id'], @$data['clientId']['referenceId']);

        $soapClient = new SoapClients($this->current_user);
        
        $client = $soapClient->getClient(new authorization(), @$data['subscriberId'], $clientId);

        $client = (array)$client;

        RestUtils::sendResponse($client['code'], $client['data']);
        
    }

}

