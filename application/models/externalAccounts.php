<?php

/**
 * this model of external accounts table
 * @author Micheal Mouner <micheal.mouner@gmail.com>
 */
class ExternalAccounts extends CI_Model {

    protected $table = 'externalAccounts';

    /**
     * add exernal linked accounts to database 
     * @param Integer $clientId
     * @param Array $data
     * @return array
     */
    public function addExternalLinkedAccount($clientId, $data) {
        $data['clientId'] = $clientId;

        $this->db->insert($this->table, $data);
        $id = $this->db->insert_id();
        $res = $this->db->from($this->table)
            ->where('id', $id)
            ->get();
        return $res->row_array();
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * @param Integer $clientId
     * @param Integer $externalAccountId
     * @return Boolean
     */
    public function deleteExternalLinkedAccount($clientId, $externalAccountId, $referenceId) {
        $this->db->where("clientId", $clientId);

        if ($externalAccountId) {
            $this->db->where("id", $externalAccountId);
        }
        if ($referenceId) {
            $this->db->where("referenceId", $referenceId);
        }
        return $this->db->update($this->table, array('is_deleted' => 'Y'));
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * @param Integer $clientId
     * @param Integer $externalLinkedAccountId
     * @param Array $data
     * @return Boolean
     */
    public function updateExternalLinkedAccount($clientId, $externalLinkedAccountId, $referenceId, $data) {
        $this->db->where('clientId', $clientId);
        if ($externalLinkedAccountId) {
            $this->db->where('id', $externalLinkedAccountId);
        }

        if ($referenceId) {
            $this->db->where('referenceId', $referenceId);
        }
        $res = $this->db->update($this->table, $data);
        if(!$res) return false;
        $this->db->from($this->table);
        if ($externalLinkedAccountId) {
            $this->db->where('id', $externalLinkedAccountId);
        }
        if ($referenceId) {
            $this->db->where('referenceId', $referenceId);
        }
        $res = $this->db->get();
        return ($res->num_rows()>0?$res->row_array():false);
    }

    /**
     * @author Micheal Mouner <micheal.mouner@gmail.com>
     * @param Integer $clientId
     * @param Integer $externalAccountId
     * @return Boolean
     */
    public function isValidAccount($clientId, $accountId, $referenceId) {

        $res = $this->db->select('*', false)
                ->from($this->table)
                ->where('is_deleted', 'N');

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

    public function isValidNewReferenceId($reference) {
        $this->db->select('*', false)
                ->from($this->table)
                ->where('referenceId', $reference);

        $res = $this->db->get();
        return ($res->num_rows() > 0) ? false : true;
    }

}