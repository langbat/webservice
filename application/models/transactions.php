<?php

class Transactions extends CI_Model {

    protected $table = 'transactions';

    public function addTransaction($account, $item) {
        $data = array();
        $data['accountId'] = $account['id'];
        $data['traceId'] = generateRandomNumber(); //for now
        $data['activityDate'] = date('Y-m-d', time() + (3600 * 24 * 3)); //pending for 3 days;
        $data['description'] = $item['description'];
        if ($item['amount'] > 0) {
            $data['debitAmount'] = (float) $item['amount'];
            $data['creditAmount'] = 0;
        } else {
            $data['debitAmount'] = 0;
            $data['creditAmount'] = (float) (-1 * $item['amount']);
        }
        $data['transactionType'] = $item['transactionType'];
        if (isset($account['balance'])) {
            $data['balance'] = (float) ($account['balance'] + $item['amount']);
        }
        $data['memo'] = $item['memo'];
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['modified_at'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        $data['id'] = $this->db->insert_id();
        if (isset($account['balance'])) {
            $this->db->where('id', $account['id']);
            $this->db->update('accounts', array('balance' => $data['balance']));
        }
        return $data;
    }

    public function getTransaction($transactionId) {
        $res = $this->db->select('*', false)
                ->from($this->table)
                ->where("id", $transactionId)
                ->get();

        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

}