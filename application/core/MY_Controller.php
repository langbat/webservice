<?php

/**
 * @property Users $users
 * @property RestUtils $restUtils
 */
class MY_Controller extends CI_Controller {

    protected $request;

    /**
     * current user
     */
    protected $current_user;

    public function __construct() {
        parent::__construct();
        $this->request = RestUtils::processRequest();

        $data = $this->request->getData();
        $api_key = @$data['authentication']['apiKey'];
        $secret = @$data['authentication']['secret'];
        $subscriber_id = @$data['subscriberId'];
        if (empty($api_key) || empty($secret) || empty($subscriber_id)) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getStatusCodeMessage(401)));
            die;
        } elseif (!$this->users->validate_api($subscriber_id, $api_key, $secret)) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getStatusCodeMessage(401)));
            die;
        }
        $this->current_user = $this->users->get_by_subscriber($subscriber_id);
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * validate the external account information for add/update/delete
     * @param String $type
     */
    public function validateExternalAccount($type) {
        $clientId = @$this->data['clientId']['id'];
        $clientReferenceId = @$this->data['clientId']['referenceId'];
        $externalAccountData = @$this->data['externalAccountInfo'];

        if ((($clientId || $clientReferenceId) && !empty($externalAccountData)) || $type == 'delete') {
            if ($clientId) {
                $this->client = $this->clients->getClientBy("id", $clientId, $this->current_user['id']);
            } else {
                $this->client = $this->clients->getClientBy("referenceId", $clientReferenceId);
            }
            if (!$this->client) {
                RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9012)));
            }
        } else {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9013)));
        }
    }

    public function validateUpdateExternalAccount($type) {
        $validationResult = -1;
        if ($type != 'delete') {
            $validationResult = validateDataForUpdatingExternalLinkedAccount($this->data['externalAccountInfo']);
        }
        if ($validationResult == 0 || $type == 'delete') {
            $externalInfoId = @$this->data['linkedExternalAccountId']['id'];
            $refereceId = @$this->data['linkedExternalAccountId']['referenceId'];
            if ($externalInfoId || $refereceId) {
                $this->externalAccountTobeUpdated = $this->externalAccounts->isValidAccount(null, $externalInfoId, $refereceId);
                if ($this->externalAccountTobeUpdated) {
                    $this->data['clientId']['id'] = $this->externalAccountTobeUpdated['clientId'];
                    $this->validateExternalAccount($type);
                }
            } else {
                RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9014)));
            }
        } else {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode($validationResult)));
        }
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * validate the external account information for add/update/delete
     * @param String $type
     */
    public function validateAccount($type) {
        // validate accounts
        $accountFromId = @$this->data['sourceAccount']['id'];
        $accountFromReferenceId = @$this->data['sourceAccount']['referenceId'];
        $accountToId = @$this->data['destinationAccount']['id'];
        $accountToReferenceId = @$this->data['destinationAccount']['referenceId'];

        $this->accountFrom = $this->accounts->isValidAccount(false, $accountFromId, $accountFromReferenceId);
        if ($type == 'transfer') {
            $this->accountTo = $this->accounts->isValidAccount(false, $accountToId, $accountToReferenceId);
        } else {
            $this->accountTo = $this->externalAccounts->isValidAccount(false, $accountToId, $accountToReferenceId);
        }

        if (!$this->accountFrom || !$this->accountTo) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9020)));
        }

        $this->data['clientId'] = $this->accountFrom['clientId'];

        $clientId = @$this->data['clientId'];
        $transferItems = @$this->data['items'];

        // validate that client related to that user exist
        $this->client = $this->clients->getClientBy("id", $this->data['clientId'], $this->current_user['id']);

        if (!$this->client) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9012)));
        }


        $totalAmount = 0;
        $items = @$this->data['items'];
        if (!empty($items)) {
            foreach ($this->data['items'] as $item) {
                if (!$item['amount'] || !is_numeric($item['amount'])) {
                    RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9018)));
                }
                $scheduleDate = @$item['scheduled']['scheduleDate'];
                if ($scheduleDate && !validateDateFormat($scheduleDate)) {
                    RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9021)));
                }
                $itemReferneceId = @$item['referenceId'];
                if ($itemReferneceId && !$this->schedules->isValidNewScheduleReference($itemReferneceId)) {
                    RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9022)));
                }
                $totalAmount += $item['amount'];
            }
        } else {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9021)));
        }

        if ($this->accountFrom['balance'] < $totalAmount) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9019)));
        }
    }

    public function validateRefund() {
        $item = array();
        // get schedule data by id or referenceId related to that user
        $scheduleScheduleId = @$this->data['scheduleId']['id'];
        $scheduleReferenceId = @$this->data['scheduleId']['referenceId'];
//        $scheduleAmount = @$this->data['amount'];
        $item['referenceId'] = @$this->data['referenceId'];

        $item['transactionType'] = "pending";
        $item['scheduleType'] = "REFUND";
        $item['scheduleStatus'] = "pending";


        $schedule = $this->schedules->getSchedule($this->current_user['id'], $scheduleScheduleId, $scheduleReferenceId);
        if (empty($schedule)) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9023)));
        }

        if ((strtotime($schedule['forDate']) + 3600 * 24) < time()) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9024)));
        }

//        if ($scheduleAmount != 0 && $schedule['amount'] < $scheduleAmount) {
//            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9025)));
//        }
        //database validation for account params sent
        if (!$schedule['toAccountId']) {
            RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9026)));
        }
        $this->accountFrom = $this->accounts->isValidAccount(false, $schedule['fromAccountId'], false);
        $this->accountTo = $this->accounts->isValidAccount(false, $schedule['toAccountId'], false);
        $this->data['scheduleOptions']['isTransferSchedule'] = true;

        if (!$item['referenceId']) {
            $item['referenceId'] = null;
        } else {
            //validate new reference for schedule
            if (!$this->schedules->isValidNewScheduleReference($item['referenceId'])) {
                RestUtils::sendResponse(401, array('success' => false, 'message' => RestUtils::getErrorMessageCode(9022)));
            }
        }

        $item['scheduleTransfer'] = date('Y-m-d');
        $this->item = $item;
        $this->schedule = $schedule;
        
        
    }

}

