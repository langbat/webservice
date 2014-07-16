<?php

function array2xml($array, $xml = false) {
    if ($xml === false) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="ISO-8859-15" ?><root/>');
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (is_numeric($key))
                $key = 'row';
            $this->array2xml($value, $xml->addChild($key));
        }else {
            if (is_numeric($key))
                $key = 'row';
            if ($value === true)
                $value = 'true';
            elseif ($value === false)
                $value = 'false';
            elseif ($value === null)
                $value = 'NA';
            $value = htmlspecialchars($value, null, 'ISO-8859-15');
            $xml->addChild($key, $value);
        }
    }
    return $xml->asXML();
}

/**
 * this messag return 0 code for succes and the error number in-case of erro
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 * @param Array $data
 * @return int ErrorCode || 0
 */
function validateDataForAddingExternalLinkedAccount($data) {
    // check all parameters needed 
    if (!isset($data['holderName']))
        return 9007;
    if (!isset($data['bankName']))
        return 9008;
    if (!isset($data['routingNumber']))
        return 9009;
    if (!isset($data['accountName']))
        return 9010;
    if (!isset($data['accountType']))
        return 9011;

    // check length of every paramater
    if (strlen($data['holderName']) > 30)
        return 9001;
    if (strlen($data['bankName']) > 30)
        return 9002;
    if (strlen($data['routingNumber']) > 9)
        return 9003;
    if (strlen($data['accountNumber']) > 30)
        return 9004;
    if (isset($data['accountName']) && strlen($data['accountName']) > 30)
        return 9005;
    if ($data['accountType'] != "S" && $data['accountType'] != "C")
        return 9006;

    return 0;
}

function validateDataForUpdatingExternalLinkedAccount($data) {
    // check all parameters needed 
    if (isset($data['holderName']) && strlen($data['holderName']) > 30)
        return 9001;
    if (isset($data['bankName']) && strlen($data['bankName']) > 30)
        return 9002;
    if (isset($data['routingNumber']) && strlen($data['routingNumber']) > 9)
        return 9003;
    if (isset($data['accountName']) && strlen($data['accountNumber']) > 30)
        return 9004;
    if (isset($data['accountName']) && strlen($data['accountName']) > 30)
        return 9005;
    if (isset($data['accountType']) && $data['accountType'] != "S" && $data['accountType'] != "C")
        return 9006;

    return 0;
}

/*
 * thisfunction to print arrat in good format
 */

function pre($array) {
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}

function generateRandomNumber() {
    $length = 8;
    $characters = "0123456789";

    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString . time();
}

function validateDataClient($act, $data) {
    if ($act == "create") {
        //Validate Required
        if (!isset($data['firstName']))
            return 9200;
        if (!isset($data['lastName']))
            return 9201;
        if (!isset($data['ssn']))
            return 9202;
        if (!isset($data['dob']))
            return 9203;
    }

    if (isset($data['firstName']) && $data['firstName'] == "")
        return 9220;
    if (isset($data['lastName']) && $data['lastName'] == "")
        return 9221;
    if (isset($data['ssn']) && $data['ssn'] == "")
        return 9222;
    if (isset($data['dob']) && $data['dob'] == "")
        return 9223;

    if (isset($data['firstName']) && strlen($data['firstName']) > 45)
        return 9204;
    if (isset($data['lastName']) && strlen($data['lastName']) > 45)
        return 9205;
    if (isset($data['ssn']) && strlen($data['ssn']) > 32)
        return 9206;
    if (isset($data['dob']) && strlen($data['dob']) > 32)
        return 9207;

    if (isset($data['ssn'])) {
        if (!preg_match("/[0-9]{3}\-[0-9]{2}\-[0-9]{4}/", $data['ssn']))
            return 9215;
    }
    if (isset($data['dob'])) {
        if (!preg_match("/([0-9]{4})\-(0?[1-9]|1[012])\-([012]?[1-9]|[12]0|3[01])/", $data['dob']))
            return 9216;
    }


    return 0;
}

function validateDataFieldClient($statusClient, $data) {

    $return = array();
    if ($statusClient != 'ACTIVE') {
        if (isset($data['firstName']))
            $return['firstName'] = $data['firstName'];
        if (isset($data['lastName']))
            $return['lastName'] = $data['lastName'];
        if (isset($data['ssn']))
            $return['ssn'] = $data['ssn'];
        if (isset($data['dob']))
            $return['dob'] = $data['dob'];
    }
    if (isset($data['middleInitial']))
        $return['middleInitial'] = $data['middleInitial'];
    if (isset($data['address']['line1']))
        $return['line1'] = $data['address']['line1'];
    if (isset($data['address']['line2']))
        $return['line2'] = $data['address']['line2'];
    if (isset($data['address']['city']))
        $return['city'] = $data['address']['city'];
    if (isset($data['address']['state']))
        $return['state'] = $data['address']['state'];
    if (isset($data['address']['zipcode']))
        $return['zipcode'] = $data['address']['zipcode'];

    if (isset($data['homePhone']))
        $return['homePhone'] = $data['homePhone'];
    if (isset($data['mobilePhone']))
        $return['mobilePhone'] = $data['mobilePhone'];
    if (isset($data['workPhone']))
        $return['workPhone'] = $data['workPhone'];
    if (isset($data['email']))
        $return['email'] = $data['email'];
    return $return;
}

function validateUpdateClientStatus($clientStatus) {
    $arrayStatus = array('ACTIVE', 'INACTIVE', 'SUSPENDED', 'DELETED');
    if (!in_array($clientStatus, $arrayStatus))
        return 9217;
    return 0;
}

function validateVerifyClient($generateQuestions) {
    if (!$generateQuestions)
        return 9218;
    return 0;
}

function formatFieldOpenAccount($openAccount) {
    $dataField = array();
    foreach ($openAccount as $account) {
        $dataField[] = array(
            "accountId" => array(
                "Id" => $account['id'],
                "referenceId" => $account['referenceId']
            ),
            "current_balance" => $account['balance'],
            "accountStatus" => $account['accountStatus']
        );
    }

    return $dataField;
}

function formatFieldGetClient($client, $user) {

    $dataField = array(
        'clientId' => array(
            'Id' => $client['id'],
            'referenceId' => $client['referenceId']
        ),
        'firstName' => $client['firstName'],
        'lastName' => $client['lastName'],
        'middleInitial' => $client['middleInitial'],
        'ssn' => $client['ssn'],
        'dob' => $client['dob'],
        'address' => array(
            'line1' => $client['line1'],
            'line2' => $client['line2'],
            'city' => $client['city'],
            'state' => $client['state'],
            'zipcode' => $client['zipcode'],
        ),
        'homePhone' => $client['homePhone'],
        'mobilePhone' => $client['mobilePhone'],
        'workPhone' => $client['workPhone'],
        'email' => $client['email'],
        'clientStatus' => $client['clientStatus'],
        'cipStatus' => $client['cipStatus'],
        'username' => $user['username'],
        'created_at' => $client['created_at'],
        'modified_at' => $client['modified_at']
    );
    return $dataField;
}

//    $date="2012-09-12";
function validateDateFormat($date) {
    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date)) {
        return true;
    } else {
        return false;
    }
}