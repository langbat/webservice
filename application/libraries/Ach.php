<?php
class Ach {
    private $grand_routing_ctrl = 0;
    private $grand_total_debit = 0;
    private $grand_total_credit = 0;
    private $blocks_six = array();
    private $records_six = array();
    private $records_builded = array();
    /** @var CI_Controller */
    private $CI;
    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Adds a six record
     *
     * @param int $transaction_code 27 | 37: for debit. 22 | 32: for credit
     * @param string $receiving_routing Transit routing number of the receiver’s financial institution.
     * @param string $receiving_account Receiver’s account number at their financial institution. Left justify
     * @param float $amount Transaction amount in dollars.
     * @param string $receiver_id Receiver’s identification number. This number may be printed on the receiver’s bank statement by the Receiving Financial Institution.
     * @param string $individual_name Name of receiver.
     * @param string $discretionary_data
     * @param string $company_entry_description
     * @param string $addenda_indicator 0: no addenda. 1: with addenda
     * @param string $add_days add days on record 5
     */
    public function add_record_six($transaction_code,$receiving_routing,$receiving_account,$amount,$receiver_id,$individual_name,$discretionary_data,$company_entry_description,$addenda_indicator = '0',$add_days='0')
    {
        $receiver_id = substr(strtoupper($receiver_id),0,15);
        $individual_name = substr(strtoupper($individual_name),0,22);
        $this->records_six[] = array(
            'record type code' => '6', //record type code
            'transaction code' => $transaction_code, //transaction code
            'receiving dfi identification' => str_pad((substr($receiving_routing,0,8)),8,'0',STR_PAD_LEFT), //receiving DFI identification (receiving routing)
            'check digit' => str_pad((substr($receiving_routing,8,1)),1,'0',STR_PAD_LEFT), //check digit
            'dfi account number' => str_pad((substr($receiving_account,0,17)),17,' '), //DFI account number
            'amount' => $amount, //amount
            'individual identification number' => str_pad($receiver_id,15,' '), //individual identification number
            'individual name' => str_pad($individual_name,22,' '), //individual name
            'discretionary data' => str_pad($discretionary_data,2,' '), //discretionary data
            'addenda record indicator' => $addenda_indicator, //addenda record indicator
            'trace number' => '', //trace number
            'add days' => $add_days, //add days on record 5
            'company entry description' => $company_entry_description, //company entry description on record 5
        );
    }
    /**
     * Adds a seven record
     *
     * @param string $payment_related_information This field contains additional information associated with the payment. The information can be human readable or in ANSI format.
     * @param int $addenda_sequence_number This number is the same as the last seven digits of the trace number of the related Entry Detail record.
     */
    public function add_record_seven($payment_related_information,$addenda_sequence_number=1)
    {
        $this->records_six[] = array(
            'record type code' => '7', //record type code
            'addenda type code' => '05', //for PPD
            'payment related information' => str_pad((substr($payment_related_information,0,80)),80,' ',STR_PAD_RIGHT), //This field contains additional information associated with the payment. The information can be human readable or in ANSI format.
            'addenda sequence number' => str_pad((substr($addenda_sequence_number,0,4)),4,'0',STR_PAD_LEFT), //This number is the same as the last seven digits of the trace number of the related Entry Detail record.
            'entry detail sequence number' => '', //This number is the same as the last seven digits of the trace number of the related Entry Detail record.
        );
    }
    /**
     * Returns an array of each record in array format
     *
     * @param string $routing_num Bank’s transit routing number.
     * @param string $companyID Your company number. The use of an IRS Federal Tax Identification Number as a company identification is recommended. Otherwise, ABN AMRO will create a unique number for your company
     * @param string $bank_name Immediate Destination Name
     * @param mixed $bank_routing
     * @param mixed $company_name
     * @param string $reference_code
     * @param string $company_discretionary_data
     */
    public function build($routing_num,$companyID,$bank_name,$bank_routing,$company_name,$reference_code = '',
                          $company_discretionary_data = '')
    {
        $reference_code = strtoupper($reference_code);
        $company_discretionary_data = strtoupper($company_discretionary_data);
        //record one File Header Record
        $input_number = 1;
        $reference_code = substr($reference_code,0,8);
        $record_one = array(
            'record type code' => '1', //record type code
            'priority code' => '01', //priority code
            'immediate destination' => ' '.str_pad($routing_num,9,'0',STR_PAD_LEFT), //immediate destination (routing number)
            'immediate origin' => str_pad($companyID,10,'0',STR_PAD_LEFT), //immediate origin (company ID)
            'file creation date' => date('ymd'), //file creation date
            'file creation time' => date('Hi'), //file creation time
            'file id modifier' => chr($input_number+65-1), //file id modifier
            'record size' => '094', //record size
            'blocking factor' => '10', //blocking factor
            'format code' => '1', //format code
            'immediate destination name' => str_pad($bank_name,23,' '), //immediate destination name
            'immediate origin name' => str_pad($company_name,23,' '), //immediate origin name
            'reference code' => str_pad($reference_code,8,' ',STR_PAD_LEFT), //reference code
        );
        $input_number++;
        $records[] = $record_one;
        //
        $count = 0;
        $row_idx = 0;
        $add_days = '0';
        $tot = count($this->records_six);
        if($tot>0)
        {
            $last_company_entry_description = $this->records_six[0]['company entry description'];
            $last_add_days = $this->records_six[0]['add days'];
        }
        for($i=0;$i<$tot;$i++)
        {
            //$idx = ceil(($i+1)/10)-1;
            if($count==10)
            {
                $count = 0;
                $row_idx++;
            }
            if($count==9 && isset($this->records_six[$i+1]) && $this->records_six[$i+1]['record type code']==7)
            {
                $count = 0;
                $row_idx++;
            }
            //group records type 5 with same days amd comp description
            if($count!=0 && $this->records_six[$i]['record type code']==6
                && ($this->records_six[$i]['add days']!=$last_add_days || $this->records_six[$i]['company entry description']!=$last_company_entry_description))
            {
                $last_add_days = $this->records_six[$i]['add days'];
                $last_company_entry_description = $this->records_six[$i]['company entry description'];
                $count = 0;
                $row_idx++;
            }

            $this->blocks_six[$row_idx][] = $this->records_six[$i];

            $count++;
        }
        $batch_number = 0;
        $entry_count = 0;
        $trace_number = 1; //must have unique 
        $tot = count($this->blocks_six);
        for($i=0;$i<$tot;$i++)
        {
            //record five Batch Header Record
            $service_code = '';
            $total_debit = 0;
            $total_credit = 0;
            $routing_ctrl = 0;
            $batch_number = $i+1;

            //$start_date = date('m/d/Y', time()+($record['add days']*24*60*60));
            $transaction_date = date('ymd', time()+($this->blocks_six[$i][0]['add days']*24*60*60));


            $this->_block_totals($i,$service_code,$total_debit,$total_credit,$routing_ctrl);
            $company_discretionary_data = strtoupper(substr($company_discretionary_data,0,20));
            $company_entry_description = strtoupper(substr($this->blocks_six[$i][0]['company entry description'],0,10));
            $record_five = array(
                'record type code' => '5', //record type code
                'service class code' => $service_code, //service class code
                'company name' => str_pad(substr($company_name,0,16),16,' '), //company name
                'company discretionary data' => str_pad($company_discretionary_data,20,' '), //company discretionary data
                'company identification' => str_pad($companyID,10,'0',STR_PAD_LEFT), //company identification
                'standard entry class' => 'PPD', //standard entry class
                'company entry description' => str_pad($company_entry_description,10,' '), //company entry description
                'company descriptive date' => $transaction_date, //company descriptive date
                'effective entry date' => $transaction_date, //effective entry date
                'settlement date' => '   ', //settlement date (julian)
                'originator status code' => '1', //originator status code
                'originating dfi identification' => str_pad((substr($bank_routing,0,8)),8,'0',STR_PAD_LEFT), //originating DFI identification (bank routing number)
                'batch number' => str_pad($batch_number,7,'0',STR_PAD_LEFT), //batch number
            );
            //records six and seven
            $records[] = $record_five;
            $last_trace = '';
            foreach($this->blocks_six[$i] as $record_six)
            {
                if($record_six['record type code']==6)//record six
                {
                    //record six PPD Entry Detail Record
                    $record_six['amount'] = str_pad(number_format($record_six['amount'],2,'',''),10,'0',STR_PAD_LEFT);
                    $record_six['trace number'] = str_pad((substr($bank_routing,0,8)),8,'0',STR_PAD_LEFT).str_pad($trace_number,7,'0',STR_PAD_LEFT); //trace number
                    unset($record_six['add days'],$record_six['company entry description']);
                    $trace_number++;
                    $last_trace = $record_six['trace number'];
                }
                elseif($record_six['record type code']==7)//record seven
                {
                    $record_six['entry detail sequence number'] = str_pad((substr($last_trace,-7)),7,'0',STR_PAD_LEFT); //This number is the same as the last seven digits of the trace number of the related Entry Detail record.
                }

                $entry_count++;
                //
                $records[] = $record_six;
            }
            //record eight Batch Control Record
            $record_eight = array(
                'record type code' => '8', //record type code
                'service class code' => $service_code, //service class code
                'entry code' => str_pad($trace_number-1,6,'0',STR_PAD_LEFT), //entry / addenda count
                'entry hash' => str_pad(substr($routing_ctrl,-10,10),10,'0',STR_PAD_LEFT), //entry hash
                'total debit entry amount' => str_pad(number_format($total_debit,2,'',''),12,'0',STR_PAD_LEFT), //total debit entry dollar amount
                'total credit entry amount' => str_pad(number_format($total_credit,2,'',''),12,'0',STR_PAD_LEFT), //total credit entry dollar amount
                'company identification' => str_pad($companyID,10,'0',STR_PAD_LEFT), //company identification
                'message authentication code' => str_pad('',19,' '), //message authentication code
                'reserved' => str_pad('',6,' '), //reserved
                'originating dfi identification' => str_pad((substr($bank_routing,0,8)),8,'0',STR_PAD_LEFT), //originating DFI identification (bank routing number)
                'batch number' => str_pad($batch_number,7,'0',STR_PAD_LEFT), //batch number
            );
            //
            $records[] = $record_eight;
        }
        //

        //record eight Batch Control Record
        $total_records = count($records)+1;
        $record_nine = array(
            'record type code' => '9', //record type code
            'batch count' => str_pad($batch_number-1,6,'0',STR_PAD_LEFT), //batch count
            'block count' => str_pad(ceil($total_records/10),6,'0',STR_PAD_LEFT), //block count
            'entry count' => str_pad($entry_count,8,'0',STR_PAD_LEFT), //entry / addenda count
            'entry hash' => str_pad(substr($this->grand_routing_ctrl,-10,10),10,'0',STR_PAD_LEFT), //entry hash
            'total debit entry amount' => str_pad(number_format($this->grand_total_debit,2,'',''),12,'0',STR_PAD_LEFT), //total debit entry dollar amount
            'total credit entry amount' => str_pad(number_format($this->grand_total_credit,2,'',''),12,'0',STR_PAD_LEFT), //total credit entry dollar amount
            'reserved' => str_pad('',39,' '), //reserved
        );
        $records[] = $record_nine;
        for($i=$total_records;$i<ceil($total_records/10)*10;$i++)
        {
            $records[] = array(
                'reserved' => str_pad('',94,'9'), //reserved
            );
        }
        //+
        $raw = array();
        foreach($records as $record)
        {
            $raw[] = implode('',$record);
        }
        return implode("\n",$raw);
    }
    public function clean()
    {
        $this->grand_routing_ctrl = 0;
        $this->grand_total_debit = 0;
        $this->grand_total_credit = 0;
        $this->blocks_six = array();
        $this->records_six = array();
        $this->records_builded = array();
    }
    private function _block_totals($index,&$service_code,&$total_debit,&$total_credit,&$routing_ctrl)
    {
        $block = $this->blocks_six[$index];
        for($i=0;$i<count($block);$i++)
        {
            if($block[$i]['record type code']==7) continue;
            if(in_array($block[$i]['transaction code'],array('27','37')))
            {
                if($service_code=='') $service_code = '225';
                elseif($service_code=='220') $service_code = '200';
                $total_debit += (float)$block[$i]['amount'];
                $this->grand_total_debit += (float)$block[$i]['amount'];
            }
            elseif(in_array($block[$i]['transaction code'],array('22','32')))
            {
                if($service_code=='') $service_code = '220';
                elseif($service_code=='225') $service_code = '200';
                $total_credit += (float)$block[$i]['amount'];
                $this->grand_total_credit += (float)$block[$i]['amount'];
            }
            $routing_ctrl += (int)$block[$i]['receiving dfi identification'];
            $this->grand_routing_ctrl += (int)$block[$i]['receiving dfi identification'];
        }
        /*
        $amount = number_format($amount,2,'','');
        $receiver_id = substr(strtoupper($receiver_id),0,15);
        $individual_name = substr(strtoupper($individual_name),0,22);
        $record = array(
            'record type code' => '6', //record type code
            'transaction code' => $transaction_code, //transaction code
            'receiving dfi identification' => str_pad((substr($receiving_routing,0,8)),8,'0',STR_PAD_LEFT), //receiving DFI identification (receiving routing)
            'check digit' => str_pad((substr($receiving_routing,8,1)),1,'0',STR_PAD_LEFT), //check digit
            'dfi account number' => str_pad((substr($receiving_account,0,17)),17,' '), //DFI account number
            'amount' => str_pad($amount,10,'0',STR_PAD_LEFT), //amount
            'individual identification number' => str_pad($receiver_id,15,' '), //individual identification number
            'individual name' => str_pad($individual_name,22,' '), //individual name
            'discretionary data' => str_pad($discretionary_data,2,' '), //discretionary data
            'addenda record indicator' => $addenda_indicator, //addenda record indicator
            'trace number' => str_pad((substr($bank_routing,0,8)),8,'0',STR_PAD_LEFT).str_pad($this->trace_number,7,'0',STR_PAD_LEFT), //trace number
        );
        */
    }
    public function build_csv($company_discretionary_data = '',$company_entry_description = '',$separator = ',')
    {
        $company_discretionary_data = substr(strtoupper($company_discretionary_data),0,20);
        $company_entry_description = substr(strtoupper($company_entry_description),0,10);
        $prenote_date = '';
        $payment_rel_info = ''; //this is for CCD addenda only
        $standar_entry_class = 'PPD';

        $create_prenote = '0';
        $hold_transfer = '0';
        $rows = array();
        foreach($this->records_six as $record)
        {
            $start_date = date('m/d/Y', time()+($record['add days']*24*60*60));
            $last_pay_date = date('m/d/Y', time()+($record['add days']*24*60*60));
            $ttype = (in_array($record['transaction code'],array('27','37'))?'D':'C'); //debit or credit
            $atype = (in_array($record['transaction code'],array('27','22'))?'C':'S'); //checking or savings

            $row = array(
                'amount' => $this->_format_csv('$'.number_format($record['amount'],2,'.','')),
                'transaction type' => $this->_format_csv($ttype),
                'individual name' => $this->_format_csv(trim($record['individual name'])),
                'individual identification number' => $this->_format_csv(trim($record['individual identification number'])),
                'receiving bank id' => $this->_format_csv(trim($record['receiving dfi identification'].$record['check digit'])),
                'dfi account number' => $this->_format_csv(trim($record['dfi account number'])),
                'account type' => $this->_format_csv($atype),
                'payment related information' => $this->_format_csv($payment_rel_info),
                'company entry description' => $this->_format_csv($company_entry_description),
                'company discretionary data' => $this->_format_csv($company_discretionary_data),
                'start date' => $this->_format_csv($start_date),
                'prenote date' => $this->_format_csv($prenote_date),
                'last payment date' => $this->_format_csv($last_pay_date),
                'create prenote' => $this->_format_csv($create_prenote),
                'hold transfer' => $this->_format_csv($hold_transfer),
                'standard entry class' => $this->_format_csv($standar_entry_class),
            );
            $rows[] = $row;
        }
        $raw = array();
        $raw[] = implode($separator,array('Amount','Transaction Type','Company/Individual Name','Company/Individual ID','Receiving Bank ID','Receiving Bank Account No.','Account Type','Payment Related Information','Company Entry Description','Company Discretionary Data','Start Date','Prenote Date','Last Payment Date','Create Prenote','Hold Transfer','Standard Entry Class Code'));
        foreach($rows as $row)
        {
            $raw[] = implode($separator,$row);
        }
        return implode("\n",$raw);
    }
    private function _format_csv($data)
    {
        $data = str_replace('"','',$data); // no quotes
        return str_replace(',','',$data); // no commas if no quotes

        if($data=='') return '';
        return '"'.str_replace('"','""',$data).'"';
    }
}