<?php

class Accounts extends CI_Model {

    protected $table = 'accounts';
    protected $tableClients = 'clients';
    protected $transactions = 'transactions';

    /**
     * Open account
     * @param array $data
     * @return obj | bool
     */
    public function open_account($data, $clientId) {
        $referenceId = (isset($data['referenceId'])) ? $data['referenceId'] : NULL;
        $title = @$data['title'];

        $data = array('clientId' => $clientId,
            'accountNumber' => $data['accountNumber'],
            'routingNumber' => $data['routingNumber'],
            'accountType' => $data['accountType'],
            'referenceId' => $referenceId,
            'title' => $title
        );

        $str = $this->db->insert_string($this->table, $data);
        $query = $this->db->query($str);

        $lastInsert = $this->db->insert_id();

        $opened = $this->db->select('*')
                ->from($this->table)
                ->where('id', $lastInsert)
                ->get();

        return $opened->row_array();
    }

    /**
     * Update account
     * @param int $id
     * @param string $referenceId
     * @param string $title
     * @return bool
     */
    public function update_account($id, $referenceId, $title) {
        $modified_at = date("Y-m-d H:i:s");

        $data = array(
            'title' => $title,
            'modified_at' => $modified_at
        );

        if (!empty($referenceId)) {
            $data += array('referenceId' => $referenceId);
        }
        if (!empty($id))
            $this->db->where('referenceId', $referenceId);
        else
            $this->db->where('id', $id);

        return $this->db->update($this->table, $data);
    }

    /**
     * Close account
     * @param string $type
     * @param int $value
     * @return bool 
     */
    public function close_account($referenceId) {
        $modified_at = date("Y-m-d H-i-s");

        $data = array('accountStatus' => 'CLOSED',
            'modified_at' => $modified_at);

        $this->db->where('referenceId', $referenceId);
        $this->db->update($this->table, $data);

        return true;
    }

    /**
     * @param string $referenceId
     * @param int $accountId
     * @param string $from
     * @param string $to
     * @return array
     */
    public function get_activity_account($referenceId, $accountId, $from, $to) {
        if (!empty($referenceId)) {
            $res = $this->db->select('*', false)
                    ->from($this->table);
            $this->db->where('referenceId', $referenceId);
            $res = $this->db->get();
            $res = $res->row_array();

            $accountId = $res['id'];
        }
        $res = $this->db->select('*', false)
                ->from($this->transactions);
        $this->db->where('accountId', $accountId);
        if (!empty($from))
            $this->db->where('activityDate >=', $from);
        if (!empty($to))
            $this->db->where('activityDate <=', $to);

        $res = $this->db->get();
        return $res->result_array();
    }

    /**
     * @param int $userId
     * @param int $accountId
     * @return Array | bool
     */
    public function getAccountById($userId, $accountId) {
        $account = $this->db->select('*', false)
                ->from($this->table)
                ->where("id", $accountId)
                ->get();
        $account = $account->row_array();

        if (!empty($account)) {
            $client = $this->db->select('*', false)
                    ->from($this->tableClients)
                    ->where("id", $account['clientId'])
                    ->get();
            $client = $client->row_array();

            return ($userId == $client['userId']) ? $account : false;
        } else {
            return false;
        }
    }

    /**
     * @param int $userId
     * @param int $id
     * @param string $referenceId
     * @return array|bool
     */
    public function getAccountByReference($userId, $id, $referenceId) {
        $this->db->select('*', false)
                ->from($this->table);
        if (!empty($referenceId))
            $this->db->where("referenceId", $referenceId);
        if (!empty($id))
            $this->db->where("id", $id);
        $res = $this->db->get();
        $account = $res->row_array();

        if (!empty($account)) {
            $client = $this->db->select('*', false)
                    ->from($this->tableClients)
                    ->where("id", $account['clientId'])
                    ->get();
            $client = $client->row_array();

            return ($userId == $client['userId']) ? $account : false;
        } else {
            return false;
        }
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * @param Integer $clientId
     * @param Integer $accountId
     * @return Boolean
     */
    public function isValidAccount($clientId, $accountId, $referenceId) {

        $res = $this->db->select('*', false)
                ->from($this->table);
        if ($clientId) {
            $this->db->where('clientId', $clientId);
        }
        if (!empty($accountId)) {
            $this->db->where('id', $accountId);
        }
        if (!empty($referenceId)) {
            $this->db->where('referenceId', $referenceId);
        }
        $res = $this->db->get();

        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

    /**
     * @author LenLay
     * @param Integer $referenceId
     * @return str $referenceId
     */
    public function verif_reference($referenceId, $clientId) {
        $res = $this->db->select('*', false)
                ->from($this->table);
        $this->db->where('referenceId', $referenceId);
        $res = $this->db->get();


        return ($res->num_rows() > 0) ? false : true;
    }

    /**
     * @author LenLay
     * @param Integer $clientId
     * @return bool
     */
    public function verif_status($clientId) {
        $res = $this->db->select('*', false)
                ->from($this->tableClients);
        $this->db->where('id', $clientId);
        $this->db->where('cipStatus', 'VERIFIED');
        $this->db->where('clientStatus', 'ACTIVE');
        $res = $this->db->get();


        return ($res->num_rows() > 0) ? true : false;
    }

    /*
     * @author Ty Tran
     */

    public function getAccountByClientId($clientId) {
        $res = $this->db->select('*', false)
                ->from($this->table)
                ->where("id", $clientId)
                ->where("accountStatus", "OPEN")
                ->get();

        return ($res->num_rows() > 0) ? $res->result_array() : false;
    }

    /**
     * Generate a random number
     * @param $prefix
     * @return string
     */
    public function gen_account($prefix) {
        do {
            $seed = '10990000000000001';
            $account = $prefix . random_string('numeric', strlen($seed) - strlen($prefix));
            if (((double) $seed > (double) $account))
                continue;
            $res = $this->db->select('1', false)
                    ->from($this->table)
                    ->where('accountNumber', $account)
                    ->get();
        }while ($res->num_rows() > 0);
        return $account;
    }

}
