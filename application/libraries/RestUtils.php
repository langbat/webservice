<?php

class RestUtils {

    static $time_ini;

    /**
     * @return Rest_Request
     */
    public static function processRequest() {
        self::$time_ini = microtime(true);
        //log_api();
        // get our verb
        $request_method = strtolower($_SERVER['REQUEST_METHOD']);
        $return_obj = new Rest_Request();
        // we'll store our data here
        $data = file_get_contents('php://input');

        // store the method
        $return_obj->setMethod($request_method);

        $pr_data = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_SYNTAX) {
            $data = $pr_data;
        }
        // set the raw data, so we can access it if needed (there may be
        // other pieces to your requests)
        $return_obj->setData($data);

        //try to parse the json

        return $return_obj;
    }

    //public static function sendResponse($status = 200, $body = '', $content_type = 'text/html')
    public static function sendResponse($status = 200, $body = '', $content_type = 'json') {
        $CI = & get_instance();
        //$CI->load->library('converter');
        $status_header = 'HTTP/1.1 ' . $status . ' ' . RestUtils::getStatusCodeMessage($status);
        // set the status
        header($status_header);
        // set the content type
        if ($content_type == 'xml')
            $content_type = 'application/xml';
        elseif ($content_type == 'json')
            $content_type = 'application/json';
        elseif ($content_type == 'html')
            $content_type = 'text/html';
        header('Access-Control-Allow-Origin: *');
        header('Content-type: ' . $content_type);

        // pages with body are easy
        if ($body != '') {
            $body['time'] = microtime(true) - self::$time_ini;
            // encode the body according the conten type
            if ($content_type == 'application/json') {
                $body = json_encode($body);
            } elseif ($content_type == 'application/xml') {
                $body = array2xml($body);
            }
            // send the body
            echo $body;
            exit;
        }
        // we need to create the body if none is passed
        else {
            // create some body messages
            $message = '';

            // this is purely optional, but makes the pages a little nicer to read
            // for your users.  Since you won't likely send a lot of different status codes,
            // this also shouldn't be too ponderous to maintain
            switch ($status) {
                case 401:
                    $message = 'You must be authorized to view this page.';
                    break;
                case 404:
                    $message = 'The requested URL ' . $_SERVER['REQUEST_URI'] . ' was not found.';
                    break;
                case 500:
                    $message = 'The server encountered an error processing your request.';
                    break;
                case 501:
                    $message = 'The requested method is not implemented.';
                    break;
            }

            // servers don't always have a signature turned on (this is an apache directive "ServerSignature On")
            $signature = ($_SERVER['SERVER_SIGNATURE'] == '') ? $_SERVER['SERVER_SOFTWARE'] . ' Server at ' . $_SERVER['SERVER_NAME'] . ' Port ' . $_SERVER['SERVER_PORT'] : $_SERVER['SERVER_SIGNATURE'];

            // this should be templatized in a real-world solution
            $body = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
                    <title>' . $status . ' ' . RestUtils::getStatusCodeMessage($status) . '</title>
                </head>
                <body>
                    <h1>' . RestUtils::getStatusCodeMessage($status) . '</h1>
                    <p>' . $message . '</p>
                    <hr />
                    <address>' . $signature . '</address>
                </body>
                </html>';

            echo $body;
            exit;
        }
    }

    public static function getStatusCodeMessage($status) {
        // these could be stored in a .ini file and loaded  
        // via parse_ini_file()... however, this will suffice  
        // for an example  
        $codes = Array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            600 => 'Get Card Details Error',
            601 => 'Unknown',
            602 => 'The PIN Is Not Verified',
            603 => 'New Cards Error',
            604 => 'Instant Issue Card Error',
            605 => 'Card Already Registered',
            606 => 'Two Cards Have Same RPID',
            607 => 'Error Linking Card And User',
            608 => 'Error Creating Card',
            609 => 'Error Creating User',
            610 => 'Get Card Report Error',
            611 => 'Activate Card Error',
            612 => 'User Does Not Exist',
            613 => 'Card Does Not Belong To User',
            614 => 'Card Does Not Exist',
            615 => 'Category Does Not Exist',
            616 => 'Change PIN Error',
            617 => 'Transaction Does Not Exist',
            618 => 'System Category Not Editable',
            619 => 'Country Not Supported Yet',
            620 => 'Transfer Error',
            621 => 'Geo Quota Over Limit',
            622 => 'Verify Card Pin error',
            623 => 'Incorrect Password',
            624 => 'Budget Not Found',
            625 => 'Unlink card error',
            626 => 'Error Deleting Budgets',
            627 => 'Tag Does Not Exist',
            628 => 'Alert Setting Error',
            629 => 'Change Primary Card Error',
            630 => 'Edit Card Error',
            631 => 'Alert Error',
            632 => 'Alert Does Not Exist',
            633 => 'IP Not Allowed',
            634 => 'Server Not Allowed',
            635 => 'There is No Data',
            636 => 'Incorrect ZIP code or Email',
            637 => 'Error Changing Password',
            638 => 'Edit User Error',
            639 => 'Money Request Error',
            640 => 'Error registering device',
            641 => 'Device Type Error',
            642 => 'Error linking device',
            643 => 'Refundo account error',
            644 => 'Load Error',
            645 => 'Refundo Account Error',
            646 => 'Stripe Error',
            647 => 'Incorrect Keypad',
            648 => 'Error Changing Keypad',
            649 => 'Password Security Error',
            700 => 'SSN Does Not Match',
            701 => 'Account Does Not Exist',
            702 => 'Deprecated Function',
            703 => 'Incorrect Information',
        );

        return (isset($codes[$status])) ? $codes[$status] : '';
    }

    /**
     * message codes and text for the Error response
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * @param Int $status
     * @return String
     */
    public static function getErrorMessageCode($status) {
        // these could be stored in a .ini file and loaded  
        // via parse_ini_file()... however, this will suffice  
        // for an example  
        $codes = Array(
            //Micheal
            9001 => 'Max length for account holder name is 30 chars',
            9002 => 'Max length for bank name is 30 chars',
            9003 => 'Max length for routing number is 9 chars',
            9004 => 'Max length for account number is 30 chars',
            9005 => 'Max length for account name is 30 chars',
            9006 => 'Account type should be "C" or "S"',
            9007 => 'Account holder name required',
            9008 => 'Bank name required',
            9009 => 'Routing number required',
            9010 => 'Account name required',
            9011 => 'Account type required',
            9012 => 'Client authentication error',
            9013 => 'Missing parameters error',
            9014 => 'You must send referenceId or externalLinkedAccountId',
            9015 => 'You must send referenceId or AccountId for each account',
            9016 => 'You must send information for both accounts',
            9017 => 'You must send clientId ot referenceId for client',
            9018 => 'You must send information (accountId or referneceId) for both accounts',
            9019 => 'Insufficient funds in your account to complete transfer',
            9020 => 'Account (from-to) authentication error ',
            9021 => 'Date format should be YYYY-MM-DD and in the future',
            9022 => 'referenceId already exist',
            9023 => 'Incorrect scheduleId or referenceId or the transaction has already been completed',
            9024 => 'This transaction can\'t be refunded (refund can only be done within 24hrs of original transaction)',
            9025 => 'Refund amount is bigger than the existing scheduled amount',
            9026 => 'Refunds from external accounts is not supported',
            9027 => 'External account error please check the referenceId or id to fix this',
            9028 => '"amount" required and should be integer',
            //Tran Ty
            9200 => 'First name required',
            9201 => 'Last name required',
            9202 => 'SSN required',
            9203 => 'DOB required',
            9204 => 'Max length for FirstName is 45 chars',
            9205 => 'Max length for LastName is 45 chars',
            9206 => 'Max length for SSN is 32 chars',
            9207 => 'Max length for DOB is 32 chars',
            9208 => 'Create client error',
            9209 => 'Update client error',
            9210 => 'Client not found',
            9211 => 'Update Client Status Error',
            9212 => 'Verify Client error',
            9213 => 'Cancel client error',
            9214 => 'referenceId already exist',
            9215 => 'SSN is formatted incorrectly',
            9216 => 'DOB is formatted incorrectly',
            9217 => 'Client status is incorrect',
            9218 => 'Incorrect answer',
            9219 => 'Exist accounts opening',
            9220 => 'First name cannot be empty',
            9221 => 'Last name cannot be empty',
            9222 => 'SSN cannot be empty',
            9223 => 'DOB cannot be empty',
            9224 => 'Client status must be ACTIVE.'
            
        );

        return (isset($codes[$status])) ? $codes[$status] : 'Unknown error..';
    }

}

class Rest_Request {

    private $data;
    private $method;

    public function setData($data) {
        $this->data = $data;
    }

    public function setMethod($method) {
        $this->method = $method;
    }

    public function getData() {
        return $this->data;
    }

    public function getMethod() {
        return $this->method;
    }

}
