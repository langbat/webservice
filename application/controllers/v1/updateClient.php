`<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author Ty Tran
 * Class UpdateClient
 */
class UpdateClient extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $data = $this->request->getData();

        $address = null;
        if (isset($data['address']) && is_array($data['address'])) {
            $address = new soapAddress();
            $address->city = @$data['address']['city'];
            $address->line1 = @$data['address']['line1'];
            $address->line2 = @$data['address']['line2'];
            $address->state = @$data['address']['state'];
            $address->zipcode = @$data['address']['zipcode'];
        }

        $clientId = new soapId(@$data['clientId']['id'], @$data['clientId']['referenceId']);

        $soapClient = new SoapClients($this->current_user);
        
        $client = $soapClient->updateClient(new authorization(), @$data['subscriberId'], $clientId, @$data['firstName'], @$data['lastName'], @$data['ssn'], @$data['dob'], @$data['middleInitial'], $address, @$data['homePhone'], @$data['mobilePhone'], @$data['workPhone'], @$data['email']);

        $client = (array)$client;
        RestUtils::sendResponse($client['code'], $client['data']);
    }

}

