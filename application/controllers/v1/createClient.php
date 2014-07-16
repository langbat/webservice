<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @author Tran Ty
 * Class CreateClient
 */
class CreateClient extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function index() {

        $data = $this->request->getData();

        $address = null;
        if(isset($data['address']) && is_array($data['address']))
        {
            $address = new soapAddress();
            $address->city = @$data['address']['city'];
            $address->line1 = @$data['address']['line1'];
            $address->line2 = @$data['address']['line2'];
            $address->state = @$data['address']['state'];
            $address->zipcode = @$data['address']['zipcode'];
        }

        $cla = new SoapClients($this->current_user);
        $client = $cla->createClient(new authorization(), @$data['subscriberId'],
            @$data['firstName'],
            @$data['lastName'],
            @$data['ssn'],
            @$data['dob'],
            @$data['referenceId'],
            @$data['middleInitial'],
            $address,
            @$data['homePhone'],
            @$data['mobilePhone'],
            @$data['workPhone'],
            @$data['email']
        );
        $client = (array)$client;
        RestUtils::sendResponse($client['code'], $client);
    }

}

