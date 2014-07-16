<?php

/**
 * Class returnClient
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_set minoccurs=0
 * @pw_element soapId $clientId
 * @pw_set minoccurs=0
 * @pw_element string $clientStatus
 * @pw_set minoccurs=0
 * @pw_element string $cipStatus
 * @pw_complex returnClient
 */
class returnClient extends general {
    public $clientId;
    public $clientStatus;
    public $cipStatus;
}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_element string $newStatus
 * @pw_complex updateClientStatusResponse
 */
class updateClientStatusResponse extends general{
    public $newStatus;
}
/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_element string $cipStatus
 * @pw_complex verifyClientResponse
 */
class verifyClientResponse extends general{
    public $cipStatus;
}

/**
 * @pw_complex soapAccountArray soapAccount
 */
/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_element soapAccountArray $openAccounts
 * @pw_complex cancelClientResponse
 */
class cancelClientResponse extends general{
    public $openAccounts;
}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_set minoccurs=0
 * @pw_element clientObj $client
 * @pw_complex getClientResponse
 */
class getClientResponse extends general{
    public $client;
}
/**
 * @pw_element soapId $clientId
 * @pw_element string $firstName
 * @pw_element string $lastName
 * @pw_set minoccurs=0
 * @pw_element string $middleInitial
 * @pw_element string $ssn
 * @pw_element string $dob
 * @pw_set minoccurs=0
 * @pw_element soapAddress $address
 * @pw_set minoccurs=0
 * @pw_element string $homePhone
 * @pw_set minoccurs=0
 * @pw_element string $mobilePhone
 * @pw_set minoccurs=0
 * @pw_element string $workPhone
 * @pw_set minoccurs=0
 * @pw_element string $email
 * @pw_set minoccurs=0
 * @pw_element string $cipStatus
 * @pw_element string $clientStatus
 * @pw_complex clientObj
 */
class clientObj{
    public $clientId;
    public $firstName;
    public $lastName;
    public $middleInitial;
    public $ssn;
    public $dob;
    public $address;
    public $homePhone;
    public $mobilePhone;
    public $workPhone;
    public $email;
    public $cipStatus;
    public $clientStatus;
}
/**
 * @service SoapClients
 */
class SoapClients extends SoapMethods {

    public function __construct($current_user = null) {
        parent::__construct();
        if (!is_null($current_user))
            $this->current_user = $current_user;
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param string $firstName
     * @param string $lastName
     * @param string $ssn
     * @param string $dob
     * @param string $referenceId
     * @param string $middleInitial
     * @param soapAddress $address
     * @param string $homePhone
     * @param string $mobilePhone
     * @param string $workPhone
     * @param string $email
     * @return returnClient
     */
    public function createClient($auth, $subscriberId, $firstName, $lastName, $ssn, $dob, $referenceId = null, $middleInitial = null, $address = null, $homePhone = null, $mobilePhone = null, $workPhone = null, $email = null) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $client = new returnClient();
            $client->success = false;
            $client->message = RestUtils::getStatusCodeMessage(401);
            $client->code = 401;
            return $client;
        }

        $data = array(
            'firstName' => $firstName,
            'lastName' => $lastName,
            'ssn' => $ssn,
            'dob' => $dob,
        );

        if (!is_null($middleInitial))
            $data['middleInitial'] = $middleInitial;
        if (!is_null($referenceId))
            $data['referenceId'] = $referenceId;
        if (!is_null($address))
            $data['address'] = (array) $address;
        if (!is_null($homePhone))
            $data['homePhone'] = $homePhone;
        if (!is_null($mobilePhone))
            $data['mobilePhone'] = $mobilePhone;
        if (!is_null($workPhone))
            $data['workPhone'] = $workPhone;
        if (!is_null($email))
            $data['email'] = $email;

        $user = $this->current_user;
        $validateStatus = validateDataClient('create', $data);
        if ($validateStatus == 0) {

            if (isset($data['referenceId']) && $data['referenceId'] != NULL)
                $validateClient = $this->CI->clients->getClientByReference($user['id'], $data['referenceId']);
            $validateClient = isset($validateClient) ? $validateClient : FALSE;

            if (!$validateClient) {
                $dataField = validateDataFieldClient('', $data);
                $dataField['cipStatus'] = 'UNVERIFIED';
                $dataField['clientStatus'] = 'ACTIVE';
                $dataField['userId'] = $user['id'];

                if (isset($data['referenceId']))
                    $dataField['referenceId'] = $data['referenceId'];

                $createClient = $this->CI->clients->createClient($dataField);
                if ($createClient) {
                    $id = new soapId($createClient['id'], $createClient['referenceId']);
                    $client = new returnClient();
                    $client->clientId = $id;
                    $client->clientStatus = $createClient['clientStatus'];
                    $client->cipStatus = $createClient['cipStatus'];
                    $client->success = true;
                    $client->message = RestUtils::getStatusCodeMessage(201);
                    $client->code = 201;

                    return $client;
                } else {
                    $client = new returnClient();
                    $client->success = false;
                    $client->message = RestUtils::getErrorMessageCode(9208);
                    $client->code = 703;
                    return $client;
                }
            } else {
                $client = new returnClient();
                $client->success = false;
                $client->message = RestUtils::getErrorMessageCode(9214);
                $client->code = 703;
                return $client;
            }
        } else {
            $client = new returnClient();
            $client->success = false;
            $client->message = RestUtils::getErrorMessageCode($validateStatus);
            $client->code = 703;
            return $client;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @param string $firstName
     * @param string $lastName
     * @param string $ssn
     * @param string $dob
     * @param string $middleInitial
     * @param soapAddress $address
     * @param string $homePhone
     * @param string $mobilePhone
     * @param string $workPhone
     * @param string $email
     * @return returnClient
     */
    public function updateClient($auth, $subscriberId, $clientId, $firstName = null, $lastName = null, $ssn = null, $dob = null, $middleInitial = null, $address = null, $homePhone = null, $mobilePhone = null, $workPhone = null, $email = null) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $client = new returnClient();
            $client->success = false;
            $client->message = RestUtils::getStatusCodeMessage(401);
            $client->code = 401;
            return $client;
        }

        $data = array();

        if (!is_null($firstName))
            $data['firstName'] = $firstName;
        if (!is_null($lastName))
            $data['lastName'] = $lastName;
        if (!is_null($ssn))
            $data['ssn'] = $ssn;
        if (!is_null($dob))
            $data['dob'] = $dob;
        if (!is_null($middleInitial))
            $data['middleInitial'] = $middleInitial;
        if (!is_null($address))
            $data['address'] = (array) $address;
        if (!is_null($homePhone))
            $data['homePhone'] = $homePhone;
        if (!is_null($mobilePhone))
            $data['mobilePhone'] = $mobilePhone;
        if (!is_null($workPhone))
            $data['workPhone'] = $workPhone;
        if (!is_null($email))
            $data['email'] = $email;

        $user = $this->current_user;
        if (is_null($clientId) || (empty($clientId->id) && empty($clientId->referenceId))) {
            $client = new returnClient();
            $client->success = false;
            $client->message = RestUtils::getErrorMessageCode(9210);
            $client->code = 404;
            return $client;
        }
        if (!empty($clientId->id))
            $client = $this->CI->clients->getClientById($user['id'], $clientId->id);
        else if (!empty($clientId->referenceId))
            $client = $this->CI->clients->getClientByReference($user['id'], $clientId->referenceId);

        if ($client) {
            $validateStatus = validateDataClient('', $data);
            if ($validateStatus == 0) {

                $dataField = validateDataFieldClient($client['clientStatus'], $data);
                $updateClient = $this->CI->clients->updateClient(
                    $client['id'], $dataField
                );
                if ($updateClient) {
                    $client = new returnClient();
                    $client->success = true;
                    $client->message = RestUtils::getStatusCodeMessage(200);
                    $client->code = 200;
                    $client->cipStatus = $updateClient['cipStatus'];
                    $client->clientStatus = $updateClient['clientStatus'];
                    return $client;
                } else {
                    $client = new returnClient();
                    $client->success = false;
                    $client->message = RestUtils::getErrorMessageCode(9209);
                    $client->code = 703;
                    return $client;
                }
            } else {
                $client = new returnClient();
                $client->success = false;
                $client->message = RestUtils::getErrorMessageCode($validateStatus);
                $client->code = 703;
                return $client;
            }
        } else {
            $client = new returnClient();
            $client->success = false;
            $client->message = RestUtils::getErrorMessageCode(9210);
            $client->code = 404;
            return $client;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @param string $clientStatus
     * @return updateClientStatusResponse
     */
    public function updateClientStatus($auth, $subscriberId, $clientId, $clientStatus) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $clientRet = new returnClient();
            $clientRet->success = false;
            $clientRet->message = RestUtils::getStatusCodeMessage(401);
            $clientRet->code = 401;
            return $clientRet;
        }


        $user = $this->current_user;
        if (is_null($clientId) || (empty($clientId->id) && empty($clientId->referenceId))) {
            $clientRet = new updateClientStatusResponse();
            $clientRet->success = false;
            $clientRet->message = RestUtils::getStatusCodeMessage(9210);
            $clientRet->code = 404;
            return $clientRet;
        }
        if (isset($clientId->id))
            $client = $this->CI->clients->getClientById($user['id'], $clientId->id);
        else if (isset($clientId->referenceId))
            $client = $this->CI->clients->getClientByReference($user['id'], $clientId->referenceId);

        if ($client) {
            $clientStatusCode = validateUpdateClientStatus($clientStatus);
            if ($clientStatusCode == 0) {

                $updateClientStatus = $this->CI->clients->updateClientStatus($client['id'], $clientStatus);
                if ($updateClientStatus) {
                    $clientRet = new updateClientStatusResponse();
                    $clientRet->success = true;
                    $clientRet->message = RestUtils::getStatusCodeMessage(200);
                    $clientRet->code = 200;
                    $clientRet->newStatus = $updateClientStatus['clientStatus'];
                    return $clientRet;
                } else {
                    $clientRet = new updateClientStatusResponse();
                    $clientRet->success = false;
                    $clientRet->message = RestUtils::getErrorMessageCode(9211);
                    $clientRet->code = 703;
                    return $clientRet;
                }
            } else {
                $clientRet = new updateClientStatusResponse();
                $clientRet->success = false;
                $clientRet->message = RestUtils::getErrorMessageCode($clientStatusCode);
                $clientRet->code = 703;
                return $clientRet;
            }
        } else {
            $clientRet = new updateClientStatusResponse();
            $clientRet->success = false;
            $clientRet->message = RestUtils::getErrorMessageCode(9210);
            $clientRet->code = 404;
            return $clientRet;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @return verifyClientResponse
     */
    public function verifyClient($auth, $subscriberId, $clientId) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $verifyRes = new verifyClientResponse();
            $verifyRes->success = false;
            $verifyRes->message = RestUtils::getStatusCodeMessage(401);
            $verifyRes->code = 401;
            return $verifyRes;
        }


        $user = $this->current_user;
        if (is_null($clientId) || (empty($clientId->id) && empty($clientId->referenceId))) {
            $verifyRes = new verifyClientResponse();
            $verifyRes->success = false;
            $verifyRes->message = RestUtils::getErrorMessageCode(9210);
            $verifyRes->code = 404;
            return $verifyRes;
        }
        if (isset($clientId->id))
            $client = $this->CI->clients->getClientById($user['id'], $clientId->id);
        else if (isset($clientId->referenceId))
            $client = $this->CI->clients->getClientByReference($user['id'], $clientId->referenceId);

        if ($client) {
            if ($client['clientStatus'] == "ACTIVE") {
                $verifyClient = $this->CI->clients->verifyClient($client['id']);

                if ($verifyClient) {
                    $verifyRes = new verifyClientResponse();
                    $verifyRes->success = true;
                    $verifyRes->message = RestUtils::getStatusCodeMessage(200);
                    $verifyRes->code = 200;
                    $verifyRes->cipStatus = $verifyClient['cipStatus'];
                    return $verifyRes;
                } else {
                    $verifyRes = new verifyClientResponse();
                    $verifyRes->success = false;
                    $verifyRes->message = RestUtils::getErrorMessageCode(9210);
                    $verifyRes->code = 703;
                    return $verifyRes;
                }
            } else {
                $verifyRes = new verifyClientResponse();
                $verifyRes->success = false;
                $verifyRes->message = RestUtils::getErrorMessageCode(9224);
                $verifyRes->code = 703;
                return $verifyRes;
            }
        } else {
            $verifyRes = new verifyClientResponse();
            $verifyRes->success = false;
            $verifyRes->message = RestUtils::getErrorMessageCode(9210);
            $verifyRes->code = 404;
            return $verifyRes;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @return cancelClientResponse
     */
    public function cancelClient($auth, $subscriberId, $clientId) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $cancelRes = new cancelClientResponse();
            $cancelRes->success = false;
            $cancelRes->message = RestUtils::getStatusCodeMessage(401);
            $cancelRes->code = 401;
            return $cancelRes;
        }


        $user = $this->current_user;
        if (is_null($clientId) || (empty($clientId->id) && empty($clientId->referenceId))) {
            $cancelRes = new cancelClientResponse();
            $cancelRes->success = false;
            $cancelRes->message = RestUtils::getErrorMessageCode(9210);
            $cancelRes->code = 404;
            return $cancelRes;
        }
        if (isset($clientId->id))
            $client = $this->CI->clients->getClientById($user['id'], $clientId->id);
        else if (isset($clientId->referenceId))
            $client = $this->CI->clients->getClientByReference($user['id'], $clientId->referenceId);

        if ($client) {
            $openAccount = $this->CI->accounts->getAccountByClientId($client['id']);
            if ($openAccount) {
                $openAccounts = array();
                foreach($openAccount as $account)
                {
                    $acc = new soapAccount();
                    $acc->routingNumber = $account['routingNumber'];
                    $acc->accountId = new soapId($account['id'], $account['referenceId']);
                    $acc->accountNumber = $account['accountNumber'];
                    $acc->accountType = $account['accountType'];
                    $acc->balance = $account['balance'];
                    $acc->created_at = $account['created_at'];
                    $openAccounts[] = $acc;
                }
                $cancelRes = new cancelClientResponse();
                $cancelRes->success = false;
                $cancelRes->message = RestUtils::getErrorMessageCode(9219);
                $cancelRes->code = 703;
                $cancelRes->openAccounts = $openAccounts;
                return $cancelRes;
            } else {
                $cancelClient = $this->CI->clients->cancelClient($client['id']);
                if ($cancelClient) {
                    $cancelRes = new cancelClientResponse();
                    $cancelRes->success = true;
                    $cancelRes->message = RestUtils::getStatusCodeMessage(200);
                    $cancelRes->code = 200;
                    return $cancelRes;
                } else {
                    $cancelRes = new cancelClientResponse();
                    $cancelRes->success = false;
                    $cancelRes->message = RestUtils::getErrorMessageCode(9213);
                    $cancelRes->code = 703;
                    return $cancelRes;
                }
            }
        } else {
            $cancelRes = new cancelClientResponse();
            $cancelRes->success = false;
            $cancelRes->message = RestUtils::getErrorMessageCode(9210);
            $cancelRes->code = 404;
            return $cancelRes;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @return getClientResponse
     */
    public function getClient($auth, $subscriberId, $clientId) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $clientRes = new getClientResponse();
            $clientRes->success = false;
            $clientRes->message = RestUtils::getStatusCodeMessage(401);
            $clientRes->code = 401;
            return $clientRes;
        }

        $user = $this->current_user;
        if (is_null($clientId) || (empty($clientId->id) && empty($clientId->referenceId))) {
            $clientRes = new getClientResponse();
            $clientRes->success = false;
            $clientRes->message = RestUtils::getErrorMessageCode(9210);
            $clientRes->code = 404;
            return $clientRes;
        }
        if (isset($clientId->id))
            $client = $this->CI->clients->getClientById($user['id'], $clientId->id);
        else if (isset($clientId->referenceId))
            $client = $this->CI->clients->getClientByReference($user['id'], $clientId->referenceId);

        if ($client) {
            $address = new soapAddress();
            $address->line1 = $client['line1'];
            $address->line2 = $client['line2'];
            $address->city = $client['city'];
            $address->state = $client['state'];
            $address->zipcode = $client['zipcode'];
            $clientRes = new getClientResponse();
            $clientRes->success = true;
            $clientRes->message = RestUtils::getStatusCodeMessage(200);
            $clientRes->code = 200;
            $clientRes->client = new clientObj();
            $clientRes->client->clientId = new soapId($client['id'], $client['referenceId']);
            $clientRes->client->firstName = $client['firstName'];
            $clientRes->client->lastName = $client['lastName'];
            $clientRes->client->middleInitial = $client['middleInitial'];
            $clientRes->client->ssn = $client['ssn'];
            $clientRes->client->dob = $client['dob'];
            $clientRes->client->address = $address;
            $clientRes->client->homePhone = $client['homePhone'];
            $clientRes->client->mobilePhone = $client['mobilePhone'];
            $clientRes->client->workPhone = $client['workPhone'];
            $clientRes->client->email = $client['email'];
            return $clientRes;
        } else {
            $clientRes = new getClientResponse();
            $clientRes->success = false;
            $clientRes->message = RestUtils::getErrorMessageCode(9210);
            $clientRes->code = 404;
            return $clientRes;
        }
    }

}

