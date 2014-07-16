<?php

/**
 * @pw_element array $itemStatuses
 * @pw_element boolean $success
 * @pw_element string $message
 * @pw_element int $code 
 * @pw_complex returnFundsResponse
 */
class returnFundsResponse extends general {

    public $itemStatuses;

}

/**
 * @service SoapFunds
 */
class SoapFunds extends SoapMethods {

    public function __construct($current_user = null) {
        parent::__construct();
        if (!is_null($current_user))
            $this->current_user = $current_user;
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $sourceAccountId
     * @param soapId $destinationAccount
     * @param array $data
     * @param string $type
     * @return returnFundsResponse
     */
    public function validateAccount($auth, $subscriberId, $sourceAccountId, $destinationAccount, $data, $type = 'transfer') {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $sendFunds = new returnFundsResponse();
            $sendFunds->success = false;
            $sendFunds->message = RestUtils::getStatusCodeMessage(401);
            $sendFunds->code = 401;
            return $sendFunds;
        }

        // validate accounts
        //$accountFromId = @$data['sourceAccount']['id'];
        //$accountFromReferenceId = @$data['sourceAccount']['referenceId'];
        //$accountToId = @$data['destinationAccount']['id'];
        //$accountToReferenceId = @$data['destinationAccount']['referenceId'];

        $this->accountFrom = $this->CI->accounts->isValidAccount(false, $sourceAccountId->id, $sourceAccountId->referenceId);
        if (($sourceAccountId->id || $sourceAccountId->referenceId) && ( $destinationAccount->id || $destinationAccount->referenceId )) {

            if ($type == 'transfer') {
                $this->accountTo = $this->CI->accounts->isValidAccount(false, $destinationAccount->id, $destinationAccount->referenceId);
            } else {
                $this->accountTo = $this->CI->externalAccounts->isValidAccount(false, $destinationAccount->id, $destinationAccount->referenceId);
            }

            if (!$this->accountFrom || !$this->accountTo) {
                $sendFunds = new returnFundsResponse();
                $sendFunds->success = false;
                $sendFunds->message = RestUtils::getErrorMessageCode(9020);
                $sendFunds->code = 401;
                return $sendFunds;
            }
        } else {
            $sendFunds = new returnFundsResponse();
            $sendFunds->success = false;
            $sendFunds->message = RestUtils::getErrorMessageCode(9018);
            $sendFunds->code = 401;
            return $sendFunds;
        }


        $data['clientId'] = $this->accountFrom['clientId'];

        $clientId = @$data['clientId'];
        $transferItems = @$data['items'];

        // validate that client related to that user exist
        $this->client = $this->CI->clients->getClientBy("id", $data['clientId'], $this->current_user['id']);

        if (!$this->client) {
            $sendFunds = new returnFundsResponse();
            $sendFunds->success = false;
            $sendFunds->message = RestUtils::getErrorMessageCode(9012);
            $sendFunds->code = 401;
            return $sendFunds;
        }


        $totalAmount = 0;
        $items = @$data['items'];
        if (!empty($items)) {
            foreach ($data['items'] as $item) {
                if (!$item['amount'] || !is_numeric($item['amount'])) {
                    $sendFunds = new returnFundsResponse();
                    $sendFunds->success = false;
                    $sendFunds->message = RestUtils::getErrorMessageCode(9028);
                    $sendFunds->code = 401;
                    return $sendFunds;
                }
                $scheduleDate = @$item['scheduled']['scheduleDate'];
                if ($scheduleDate && !validateDateFormat($scheduleDate)) {
                    $sendFunds = new returnFundsResponse();
                    $sendFunds->success = false;
                    $sendFunds->message = RestUtils::getErrorMessageCode(9021);
                    $sendFunds->code = 401;
                    return $sendFunds;
                }
                $itemReferneceId = @$item['referenceId'];
                if ($itemReferneceId && !$this->CI->schedules->isValidNewScheduleReference($itemReferneceId)) {
                    $sendFunds = new returnFundsResponse();
                    $sendFunds->success = false;
                    $sendFunds->message = RestUtils::getErrorMessageCode(9022);
                    $sendFunds->code = 401;
                    return $sendFunds;
                }
                $totalAmount = (float) ($totalAmount + $item['amount']);
            }
        } else {
            $sendFunds = new returnFundsResponse();
            $sendFunds->success = false;
            $sendFunds->message = RestUtils::getErrorMessageCode(9021);
            $sendFunds->code = 401;
            return $sendFunds;
        }

        if ($this->accountFrom['balance'] < $totalAmount) {
            $sendFunds = new returnFundsResponse();
            $sendFunds->success = false;
            $sendFunds->message = RestUtils::getErrorMessageCode(9019);
            $sendFunds->code = 401;
            return $sendFunds;
        }

        $returnData = array();
        if ($type == "transfer") {
            // fund transfer
            //add transactions
            foreach ($data['items'] as $item) {
                $item['amount'] = (float) (-1 * $item['amount']);
                $item['memo'] = @$data['memo'];
                $item['description'] = @$data['description'];
                $schedule = @$item['scheduled']['scheduleDate'];

                if ($schedule) {
                    $item['transactionType'] = "scheduled";
                    $item['scheduleStatus'] = "scheduled";
                } else {
                    $item['transactionType'] = "pending";
                    $item['scheduleStatus'] = "pending";
                    $item['scheduled']['scheduleDate'] = date('Y-m-d');
                }

                $item['scheduleType'] = "TRANSFER";
                $item['isTransferSchedule'] = true;
                $transactionIdFrom = $this->CI->transactions->addTransaction($this->accountFrom, $item);
                $this->accountFrom['balance'] = $transactionIdFrom['balance'];
                $item['amount'] = (float) (-1 * $item['amount']);
                $transactionIdTo = $this->CI->transactions->addTransaction($this->accountTo, $item);
                $this->accountTo['balance'] = $transactionIdTo['balance'];

                $returnData[] = $this->CI->schedules->scheduleTransfer($this->current_user, $transactionIdFrom, $transactionIdTo, $item);
                $returnData[] = array(
                    'success' => true,
                    'message' => RestUtils::getStatusCodeMessage(200),
                    'status' => $item['scheduleStatus']
                );
            }

            $sendFunds = new returnFundsResponse();
            $sendFunds->itemStatuses = $returnData;
            $sendFunds->success = true;
            $sendFunds->message = RestUtils::getStatusCodeMessage(200);
            $sendFunds->code = 200;
            return $sendFunds;
        } elseif ($type == "send") {
            foreach ($data['items'] as $item) {
                $item['amount'] = (float) (-1 * $item['amount']);
                $item['memo'] = @$data['memo'];
                $item['description'] = @$data['description'];
                $schedule = @$item['scheduled']['scheduleDate'];

                if ($schedule) {
                    $item['transactionType'] = "scheduled";
                    $item['scheduleStatus'] = "scheduled";
                } else {
                    $item['transactionType'] = "pending";
                    $item['scheduleStatus'] = "pending";
                    $item['scheduled']['scheduleDate'] = date('Y-m-d');
                }

                $item['scheduleType'] = "SEND";
                $item['isTransferSchedule'] = false;
                $transactionIdFrom = $this->CI->transactions->addTransaction($this->accountFrom, $item);
                $this->accountFrom['balance'] = $transactionIdFrom['balance'];
                $item['amount'] = (float) (-1 * $item['amount']);
                $transactionIdTo = array('accountId' => $this->accountTo['id']);

                $returnData[] = $this->CI->schedules->scheduleTransfer($this->current_user, $transactionIdFrom, $transactionIdTo, $item);
                $returnData[] = array(
                    'success' => true,
                    'message' => RestUtils::getStatusCodeMessage(200),
                    'status' => $item['scheduleStatus']
                );
            }
            $sendFunds = new returnFundsResponse();
            $sendFunds->itemStatuses = $returnData;
            $sendFunds->success = true;
            $sendFunds->message = RestUtils::getStatusCodeMessage(200);
            $sendFunds->code = 200;
            return $sendFunds;
        }
    }

    /**
     * @param authorization $auth
     * @param string $subscriberId
     * @param soapId $scheduleId 
     * @param array $data
     * @param string $type
     * @return returnFundsResponse
     */
    public function validateRefund($auth, $subscriberId, $scheduleId, $data, $type = 'refund') {
        $res = $this->_authorize($auth->username, $auth->password, $subscriberId);
        if (!$res) {
            $returnFunds = new returnFundsResponse();
            $returnFunds->success = false;
            $returnFunds->message = RestUtils::getStatusCodeMessage(401);
            $returnFunds->code = 401;
            return $returnFunds;
        }

        $item = array();
        // get schedule data by id or referenceId related to that user
        //$scheduleScheduleId = @$data['scheduleId']['id'];
        //$scheduleReferenceId = @$data['scheduleId']['referenceId'];
//        $scheduleAmount = @$data['amount'];
        $item['referenceId'] = @$data['referenceId'];

        $item['transactionType'] = "pending";
        $item['scheduleType'] = "REFUND";
        $item['scheduleStatus'] = "pending";


        $schedule = $this->CI->schedules->getSchedule($this->current_user['id'], $scheduleId->id, $scheduleId->referenceId);
        if (empty($schedule)) {
            $returnFunds = new returnFundsResponse();
            $returnFunds->success = false;
            $returnFunds->message = RestUtils::getErrorMessageCode(9023);
            $returnFunds->code = 401;
            return $returnFunds;
        }

        if ((strtotime($schedule['forDate']) + 3600 * 24) < time()) {
            $returnFunds = new returnFundsResponse();
            $returnFunds->success = false;
            $returnFunds->message = RestUtils::getErrorMessageCode(9024);
            $returnFunds->code = 401;
            return $returnFunds;
        }

//        if ($scheduleAmount != 0 && $schedule['amount'] < $scheduleAmount) {
//            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9025)));
//        }
        //database validation for account params sent
        if (!$schedule['toAccountId']) {
            $returnFunds = new returnFundsResponse();
            $returnFunds->success = false;
            $returnFunds->message = RestUtils::getErrorMessageCode(9026);
            $returnFunds->code = 401;
            return $returnFunds;
        }
        $this->accountFrom = $this->CI->accounts->isValidAccount(false, $schedule['fromAccountId'], false);
        $this->accountTo = $this->CI->accounts->isValidAccount(false, $schedule['toAccountId'], false);
        $data['scheduleOptions']['isTransferSchedule'] = true;

        if (!$item['referenceId']) {
            $item['referenceId'] = null;
        } else {
            //validate new reference for schedule
            if (!$this->CI->schedules->isValidNewScheduleReference($item['referenceId'])) {
                $returnFunds = new returnFundsResponse();
                $returnFunds->success = false;
                $returnFunds->message = RestUtils::getErrorMessageCode(9022);
                $returnFunds->code = 401;
                return $returnFunds;
            }
        }

        $item['scheduleTransfer'] = date('Y-m-d');
        $this->item = $item;
        $this->schedule = $schedule;


        // return fund
        $returnData = array();
        $this->item['description'] = @$data['reasonForRefund'];
        $this->item['memo'] = null;
        $this->item['amount'] = $this->schedule['amount'];
        $this->item['isTransferSchedule'] = true;
        $this->item['scheduled']['scheduleDate'] = date('Y-m-d');
        $item = $this->item;

        $item['amount'] = -1 * $item['amount'];
        $transactionIdFrom = $this->CI->transactions->addTransaction($this->accountTo, $item);
        $this->accountTo['balance'] = $transactionIdFrom['balance'];
        $item['amount'] = -1 * $item['amount'];
        $transactionIdTo = $this->CI->transactions->addTransaction($this->accountFrom, $item);
        $this->accountFrom['balance'] = $transactionIdTo['balance'];

        $returnData[] = $this->CI->schedules->scheduleTransfer($this->current_user, $transactionIdFrom, $transactionIdTo, $item);
        $returnData[] = array(
            'success' => true,
            'message' => RestUtils::getStatusCodeMessage(200),
            'status' => $item['scheduleStatus']
        );

        $returnFunds = new returnFundsResponse();
        $returnFunds->itemStatuses = $returnData;
        $returnFunds->success = true;
        $returnFunds->message = RestUtils::getStatusCodeMessage(200);
        $returnFunds->code = 200;
        return $returnFunds;
    }

}