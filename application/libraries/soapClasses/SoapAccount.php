<?php

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_element soapAccount $account
 * @pw_complex openAccountResponse
 */
class openAccountResponse extends general {

    public $account;

}

/**
 * @pw_element soapId $accountId
 * @pw_element string $routingNumber
 * @pw_element string $accountNumber
 * @pw_element string $accountType
 * @pw_element float $balance
 * @pw_element string $created_at
 * @pw_complex soapAccount
 */
class soapAccount {

    public $accountId;
    public $routingNumber;
    public $accountNumber;
    public $accountType;
    public $balance;
    public $created_at;

}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_element soapId $linkExternalAccountId
 * @pw_complex linkExternalAccountResponse
 */
class linkExternalAccountResponse extends general {

    public $linkExternalAccountId;

}

/**
 * @pw_element string $holderName
 * @pw_element string $bankName
 * @pw_element string $routingNumber
 * @pw_element string $accountNumber
 * @pw_element string $accountType
 * @pw_element string $accountName
 * @pw_complex soapExternalAccountInfo
 */
class soapExternalAccountInfo {

    public $holderName;
    public $bankName;
    public $routingNumber;
    public $accountNumber;
    public $accountType;
    public $accountName;

}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_complex deleteLinkedExternalAccountResponse
 */
class deleteLinkedExternalAccountResponse extends general {
    
}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_complex updateAccountResponse
 */
class updateAccountResponse extends general {
    
}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_complex closeAccountResponse
 */
class closeAccountResponse extends general {
    
}

/**
 * @pw_complex soapActivityArray soapActivity
 */

/**
 * @pw_element int $id
 * @pw_element string $traceId
 * @pw_element string $activityDate
 * @pw_element string $description
 * @pw_element float $debitAmount
 * @pw_element float $creditAmount
 * @pw_element float $balance
 * @pw_element string $transactionType
 * @pw_element string $memo
 * @pw_complex soapActivity
 */
class soapActivity {

    public $id;
    public $traceId;
    public $activityDate;
    public $description;
    public $debitAmount;
    public $creditAmount;
    public $balance;
    public $memo;

}

/**
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code
 * @pw_element soapActivityArray $activities
 * @pw_complex getAccountActivityResponse
 */
class getAccountActivityResponse extends general {

    public $activities;

}

/**
 * @service SoapAccounts
 */
class SoapAccounts extends SoapClients {

    private $externalAccountToBeUpdated;
    private $client;

    public function __construct($current_user = null) {
        parent::__construct();
        if (!is_null($current_user))
            $this->current_user = $current_user;
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @param soapExternalAccountInfo $externalAccountInfo
     * @param string $referenceId
     * @return linkExternalAccountResponse
     */
    public function linkExternalAccount($auth, $subscriberId, $clientId, $externalAccountInfo, $referenceId = null) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = false;
            $linkRes->message = RestUtils::getStatusCodeMessage(401);
            $linkRes->code = 401;
            return $linkRes;
        }


        if ((($clientId->id || $clientId->referenceId) && !empty($externalAccountInfo))) {
            if ($clientId->id) {
                $this->client = $this->CI->clients->getClientBy("id", $clientId->id, $this->current_user['id']);
            } else {
                $this->client = $this->CI->clients->getClientBy("referenceId", $clientId->referenceId);
            }
            if (!$this->client) {
                $linkRes = new linkExternalAccountResponse();
                $linkRes->success = false;
                $linkRes->message = RestUtils::getErrorMessageCode(9012);
                $linkRes->code = 401;
                return $linkRes;
            }
        } else {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = false;
            $linkRes->message = RestUtils::getErrorMessageCode(9013);
            $linkRes->code = 401;
            return $linkRes;
        }

        if (!is_null($referenceId) && !$this->CI->externalAccounts->isValidNewReferenceId($referenceId)) {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = false;
            $linkRes->message = RestUtils::getErrorMessageCode(9022);
            $linkRes->code = 401;
            return $linkRes;
        }

        $holderName = @$externalAccountInfo->holderName;
        $bankName = @$externalAccountInfo->bankName;
        $routingNumber = @$externalAccountInfo->routingNumber;
        $accountNumber = @$externalAccountInfo->accountNumber;
        $accountName = @$externalAccountInfo->accountName;
        $accountType = @$externalAccountInfo->accountType;
        $data = (array) $externalAccountInfo;
        $data['referenceId'] = $referenceId;
        if ($holderName && $bankName && $routingNumber && $accountNumber && $accountName && $accountType) {
            $validationResult = validateDataForAddingExternalLinkedAccount($data);
            if ($validationResult == 0) {
                $externalAccountObj = $this->CI->externalAccounts->addExternalLinkedAccount($this->client['id'], $data);
                $linkRes = new linkExternalAccountResponse();
                $linkRes->success = true;
                $linkRes->message = RestUtils::getStatusCodeMessage(201);
                $linkRes->code = 201;
                $linkRes->linkExternalAccountId = new soapId($externalAccountObj['id'], $externalAccountObj['referenceId']);
                return $linkRes;
            } else {
                $linkRes = new linkExternalAccountResponse();
                $linkRes->success = false;
                $linkRes->message = RestUtils::getErrorMessageCode($validationResult);
                $linkRes->code = 401;
                return $linkRes;
            }
        } else {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = false;
            $linkRes->message = RestUtils::getErrorMessageCode(9013);
            $linkRes->code = 401;
            return $linkRes;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $linkedExternalAccountId
     * @param soapExternalAccountInfo $externalAccountInfo
     * @return linkExternalAccountResponse
     */
    public function updateLinkExternalAccount($auth, $subscriberId, $linkedExternalAccountId, $externalAccountInfo) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = false;
            $linkRes->message = RestUtils::getStatusCodeMessage(401);
            $linkRes->code = 401;
            return $linkRes;
        }

        $validationResult = validateDataForUpdatingExternalLinkedAccount((array) $externalAccountInfo);

        if ($validationResult == 0) {
            $externalInfoId = @$linkedExternalAccountId->id;
            $referenceId = @$linkedExternalAccountId->referenceId;
            if ($externalInfoId || $referenceId) {
                $this->externalAccountToBeUpdated = $this->CI->externalAccounts->isValidAccount(null, $externalInfoId, $referenceId);
                if ($this->externalAccountToBeUpdated) {
                    $clientId = $this->externalAccountToBeUpdated['clientId'];

                    if ($clientId && !empty($externalAccountInfo)) {
                        $this->client = $this->CI->clients->getClientBy("id", $clientId, $this->current_user['id']);
                        if (!$this->client) {
                            $linkRes = new linkExternalAccountResponse();
                            $linkRes->success = false;
                            $linkRes->message = RestUtils::getErrorMessageCode(9012);
                            $linkRes->code = 401;
                            return $linkRes;
                        }
                    } else {
                        $linkRes = new linkExternalAccountResponse();
                        $linkRes->success = false;
                        $linkRes->message = RestUtils::getErrorMessageCode(9013);
                        $linkRes->code = 401;
                        return $linkRes;
                    }
                }
            } else {
                $linkRes = new linkExternalAccountResponse();
                $linkRes->success = false;
                $linkRes->message = RestUtils::getErrorMessageCode(9014);
                $linkRes->code = 401;
                return $linkRes;
            }
        } else {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = false;
            $linkRes->message = RestUtils::getErrorMessageCode($validationResult);
            $linkRes->code = 401;
            return $linkRes;
        }


        $externalAccountUpdate = $this->CI->externalAccounts->updateExternalLinkedAccount($this->client['id'], $externalInfoId, $referenceId, (array) $externalAccountInfo);
        if ($externalAccountUpdate) {
            $linkRes = new linkExternalAccountResponse();
            $linkRes->success = true;
            $linkRes->message = RestUtils::getStatusCodeMessage(200);
            $linkRes->code = 200;
            $linkRes->linkExternalAccountId = new soapId($externalAccountUpdate['id'], $externalAccountUpdate['referenceId']);
            return $linkRes;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $linkedExternalAccountId
     * @return deleteLinkedExternalAccountResponse
     */
    public function deleteLinkExternalAccount($auth, $subscriberId, $linkedExternalAccountId) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $deleteRes = new deleteLinkedExternalAccountResponse();
            $deleteRes->success = false;
            $deleteRes->message = RestUtils::getStatusCodeMessage(401);
            $deleteRes->code = 401;
            return $deleteRes;
        }


        if ($linkedExternalAccountId->id || $linkedExternalAccountId->referenceId) {
            $this->externalAccountToBeUpdated = $this->CI->externalAccounts->isValidAccount(null, $linkedExternalAccountId->id, $linkedExternalAccountId->referenceId);
            if ($this->externalAccountToBeUpdated) {
                $clientId = $this->externalAccountToBeUpdated['clientId'];

                $this->client = $this->CI->clients->getClientBy("id", $clientId, $this->current_user['id']);

                if (!$this->client) {
                    $deleteRes = new deleteLinkedExternalAccountResponse();
                    $deleteRes->success = false;
                    $deleteRes->message = RestUtils::getErrorMessageCode(9012);
                    $deleteRes->code = 401;
                    return $deleteRes;
                }
            } else {
                $deleteRes = new deleteLinkedExternalAccountResponse();
                $deleteRes->success = false;
                $deleteRes->message = RestUtils::getErrorMessageCode(9027);
                $deleteRes->code = 401;
                return $deleteRes;
            }
        } else {
            $deleteRes = new deleteLinkedExternalAccountResponse();
            $deleteRes->success = false;
            $deleteRes->message = RestUtils::getErrorMessageCode(9014);
            $deleteRes->code = 401;
            return $deleteRes;
        }


        // delete functionality
        if ($linkedExternalAccountId->id || $linkedExternalAccountId->referenceId) {
            $deleteExternalAccount = $this->CI->externalAccounts->deleteExternalLinkedAccount($this->client['id'], $linkedExternalAccountId->id, $linkedExternalAccountId->referenceId);
            if ($deleteExternalAccount) {
                $deleteRes = new deleteLinkedExternalAccountResponse();
                $deleteRes->success = true;
                $deleteRes->message = RestUtils::getStatusCodeMessage(200);
                $deleteRes->code = 200;
                return $deleteRes;
            }
        } else {
            $deleteRes = new deleteLinkedExternalAccountResponse();
            $deleteRes->success = false;
            $deleteRes->message = RestUtils::getErrorMessageCode(9014);
            $deleteRes->code = 401;
            return $deleteRes;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $clientId
     * @param string $referenceId
     * @param string $title
     * @return openAccountResponse
     */
    public function openAccount($auth, $subscriberId, $clientId, $referenceId = null, $title = null) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $openRes = new openAccountResponse();
            $openRes->success = false;
            $openRes->message = RestUtils::getStatusCodeMessage(401);
            $openRes->code = 401;
            return $openRes;
        }
        $data = array(
            'clientId' => (array) $clientId,
            'referenceId' => $referenceId,
            'title' => $title,
        );

        $userId = $this->current_user;
        $clientReferenceId = (isset($data['clientId']['referenceId'])) ? $data['clientId']['referenceId'] : '';
        $clientId = (isset($data['clientId']['id'])) ? $data['clientId']['id'] : '';
        $referenceId = @$data['referenceId'];
        $title = @$data['title'];

        if (empty($clientId) && empty($clientReferenceId) || empty($title)) {
            $openRes = new openAccountResponse();
            $openRes->success = false;
            $openRes->message = 'Invalid data request. Not filled in the required information';
            $openRes->code = 401;
            return $openRes;
        } else {
            if (empty($clientId)) {
                $clientId = $this->CI->clients->getClientByReference($userId['id'], $clientReferenceId);
            } elseif (empty($clientReferenceId)) {
                $clientId = $this->CI->clients->getClientById($userId['id'], $clientId);
            }
            if ($clientId) {
                if (isset($referenceId)) {
                    $verifyReference = $this->CI->accounts->verif_reference($referenceId, $clientId['id']);
                    $verifyStatus = $this->CI->accounts->verif_status($clientId['id']);

                    if ($verifyReference == true) {
                        if ($verifyStatus == true) {
                            //generate routing and account number
                            $data['routingNumber'] = $this->current_user['masterRouting'];
                            $data['accountNumber'] = $this->CI->accounts->gen_account($this->current_user['masterAccount']);
                            $data['accountType'] = $this->current_user['masterType'];
                            $open = $this->CI->accounts->open_account($data, $clientId['id']);
                        } else {
                            $openRes = new openAccountResponse();
                            $openRes->success = false;
                            $openRes->message = 'Client status inactive';
                            $openRes->code = 401;
                            return $openRes;
                        }
                    } else {
                        $openRes = new openAccountResponse();
                        $openRes->success = false;
                        $openRes->message = 'Invalid data request. Not correct referenceId';
                        $openRes->code = 401;
                        return $openRes;
                    }
                } else {
                    //generate routing and account number
                    $data['routingNumber'] = $this->current_user['masterRouting'];
                    $data['accountNumber'] = $this->CI->accounts->gen_account($this->current_user['masterAccount']);
                    $data['accountType'] = $this->current_user['masterType'];

                    $open = $this->CI->accounts->open_account($data, $clientId['id']);
                }
            } else {
                $openRes = new openAccountResponse();
                $openRes->success = false;
                $openRes->message = 'Invalid data request.';
                $openRes->code = 401;
                return $openRes;
            }
        }

        if (!empty($open)) {
            $openRes = new openAccountResponse();
            $openRes->success = true;
            $openRes->message = RestUtils::getStatusCodeMessage(201);
            $openRes->code = 201;
            $openRes->account = new soapAccount();
            $openRes->account->accountId = new soapId($open['id'], $open['referenceId']);
            $openRes->account->accountNumber = $open['accountNumber'];
            $openRes->account->accountType = $open['accountType'];
            $openRes->account->balance = $open['balance'];
            $openRes->account->created_at = $open['created_at'];
            $openRes->account->routingNumber = $open['routingNumber'];

            return $openRes;
        } else {
            $openRes = new openAccountResponse();
            $openRes->success = false;
            $openRes->message = RestUtils::getStatusCodeMessage(401);
            $openRes->code = 401;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $accountId
     * @param string $title
     * @return updateAccountResponse
     */
    public function updateAccount($auth, $subscriberId, $accountId, $title) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $accountRes = new updateAccountResponse();
            $accountRes->success = false;
            $accountRes->message = RestUtils::getStatusCodeMessage(401);
            $accountRes->code = 401;
            return $accountRes;
        }


        if ((empty($accountId->id) && empty($accountId->referenceId)) || empty($title)) {
            $accountRes = new updateAccountResponse();
            $accountRes->success = false;
            $accountRes->message = 'Invalid data request. Not filled in the required information';
            $accountRes->code = 401;
            return $accountRes;
        } else {

            $verifyAccount = $this->CI->accounts->isValidAccount(null, $accountId->id, $accountId->referenceId);

            if (!empty($verifyAccount)) {

                $client = $this->CI->clients->getClientById($this->current_user['id'], $verifyAccount["clientId"]);

                if ($client) {
                    $update = $this->CI->accounts->update_account($accountId->id, $accountId->referenceId, $title);
                } else {
                    $accountRes = new updateAccountResponse();
                    $accountRes->success = false;
                    $accountRes->message = 'Invalid data request. Incorrect accountId';
                    $accountRes->code = 401;
                    return $accountRes;
                }
            } else {
                $accountRes = new updateAccountResponse();
                $accountRes->success = false;
                $accountRes->message = 'Invalid data request. Incorrect accountId';
                $accountRes->code = 401;
                return $accountRes;
            }
        }

        if ($update == true) {
            $accountRes = new updateAccountResponse();
            $accountRes->success = true;
            $accountRes->message = RestUtils::getStatusCodeMessage(200);
            $accountRes->code = 200;
            return $accountRes;
        } else {
            $accountRes = new updateAccountResponse();
            $accountRes->success = false;
            $accountRes->message = RestUtils::getStatusCodeMessage(400);
            $accountRes->code = 401;
            return $accountRes;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $accountId
     * @param string $fromDate
     * @param string $toDate
     * @return getAccountActivityResponse
     */
    public function getAccountActivity($auth, $subscriberId, $accountId, $fromDate = null, $toDate = null) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $activityRes = new getAccountActivityResponse();
            $activityRes->success = false;
            $activityRes->message = RestUtils::getStatusCodeMessage(401);
            $activityRes->code = 401;
            return $activityRes;
        }


        $userId = $this->current_user['id'];

        if (empty($accountId->id) && empty($accountId->referenceId)) {
            $activityRes = new getAccountActivityResponse();
            $activityRes->success = false;
            $activityRes->message = 'Invalid data request. Not filled in the required information';
            $activityRes->code = 401;
            return $activityRes;
        } else {
            $account = $this->CI->accounts->getAccountByReference($userId, $accountId->id, $accountId->referenceId);


            if ($account) {
                $getActivity = $this->CI->accounts->get_activity_account($accountId->referenceId, $accountId->id, $fromDate, $toDate);

                $activityRes = new getAccountActivityResponse();
                $activityRes->success = true;
                $activityRes->message = RestUtils::getStatusCodeMessage(200);
                $activityRes->code = 200;

                $activityRes->activities = array();
                foreach ($getActivity as $activity) {
                    $act = new soapActivity();
                    $act->id = $activity['id'];
                    $act->activityDate = $activity['activityDate'];
                    $act->balance = $activity['balance'];
                    $act->creditAmount = $activity['creditAmount'];
                    $act->debitAmount = $activity['debitAmount'];
                    $act->description = $activity['description'];
                    $act->memo = $activity['memo'];
                    $act->traceId = $activity['traceId'];
                    array_push($activityRes->activities, $act);
                }
                return $activityRes;
            } else {
                $activityRes = new getAccountActivityResponse();
                $activityRes->success = false;
                $activityRes->message = 'Invalid data request. Incorrect accountId';
                $activityRes->code = 401;
                return $activityRes;
            }
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $accountId 
     * @return closeAccountResponse
     */
    public function closeAccount($auth, $accountId, $subscriberId) {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $closRes = new closeAccountResponse();
            $closRes->success = false;
            $closRes->message = RestUtils::getStatusCodeMessage(401);
            $closRes->code = 401;
            return $closRes;
        }


        $userId = $this->current_user['id'];
        //$accountId = (@$data['accountId']['id']) ? @$data['accountId']['id'] : '';
        //$referenceId = (@$data['accountId']['referenceId']) ? @$data['accountId']['referenceId'] : ''; 

        if (is_null($accountId) || (empty($accountId->id) && empty($accountId->referenceId))) {

            $closRes = new closeAccountResponse();
            $closRes->success = false;
            $closRes->message = 'Invalid data request. Required information missing.';
            $closRes->code = 401;
            return $closRes;
        } else {
            //$type = ($accountId) ? 'id' : 'referenceId';
            //$value = ($accountId) ? $accountId : $referenceId;

            if (empty($accountId->id)) {
                $validation = $this->CI->accounts->getAccountByReference($userId, $accountId->referenceId);
            }
            if (empty($accountId->referenceId)) {
                $validation = $this->CI->accounts->getAccountById($userId, $accountId->id);
            }

            if ($validation != false) {
                $close = $this->CI->accounts->close_account($accountId->referenceId);

                if ($close == true) {
                    $closRes = new closeAccountResponse();
                    $closRes->success = true;
                    $closRes->message = RestUtils::getStatusCodeMessage(200);
                    $closRes->code = 200;
                    return $closRes;
                }
            } else {
                $closRes = new closeAccountResponse();
                $closRes->success = false;
                $closRes->message = 'Invalid data request. Incorrect accountId or referenceId';
                $closRes->code = 401;
                return $closRes;
            }
        }
    }

}
