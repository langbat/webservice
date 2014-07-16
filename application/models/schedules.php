<?php

class Schedules extends CI_Model {

    protected $table = 'schedules';

    public function scheduleTransfer($user, $transactionFrom, $transactionTo, $item) {
        $data = array();
        $data['referenceId'] = @$item['referenceId'];
        $data['userId'] = $user['id'];
        $data['scheduleType'] = $item['scheduleType'];
        $data['fromAccountId'] = $transactionFrom['accountId'];
        $data['toAccountId'] = $transactionTo['accountId'];
        $data['transactionToId'] = @$transactionTo['id'];
        if (!$item['isTransferSchedule']) {
            $data['toExternalAccountId'] = $transactionTo['accountId'];
            unset($data['toAccountId'], $data['transactionToId']);
        }
        $data['transactionFromId'] = $transactionFrom['id'];

        $data['amount'] = $item['amount'];
        $data['forDate'] = $item['scheduled']['scheduleDate'];
        $data['scheduleStatus'] = $item['scheduleStatus'];
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['modified_at'] = date('Y-m-d H:i:s');


        $this->db->insert($this->table, $data);
        return array('id' => array('id' => $this->db->insert_id(), 'referenceId' => $data['referenceId']));
    }

    public function isValidNewScheduleReference($reference) {
        $this->db->select('*', false)
                ->from($this->table)
                ->where('referenceId', $reference);

        $res = $this->db->get();
        return ($res->num_rows() > 0) ? false : true;
    }

    public function getSchedule($userId, $id, $referenceId) {
        $this->db->select('*', false)
                ->from($this->table);
        $this->db->where('userId', $userId);
        $this->db->where('scheduleStatus !=', 'completed');
        $this->db->where('scheduleType !=', 'REFUND');
        if ($id) {
            $this->db->where('id', $id);
        }

        if ($referenceId) {
            $this->db->where('referenceId', $referenceId);
        }

        $res = $this->db->get();
        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

}