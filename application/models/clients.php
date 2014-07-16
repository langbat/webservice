<?php

/**
 * this model from client table in the database
 * @author Ty Tran <it.langbat@gmail.com>
 */
class Clients extends CI_Model {

    protected $table = 'clients';

    /**
     * tthis function get the client with the column and value according to your needs
     * return 0 if no records found
     * @param String $attr
     * @param String || Number $value
     * @return Array
     */
    public function getClientBy($attr, $value, $userId = null) {
        $this->db->select('id, userId, referenceId', false)
                ->from($this->table)
                ->where($attr, $value);

        if ($userId) {
            $this->db->where('userId', $userId);
        }
        $res = $this->db->get();
        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

    /** Author Ty Tran
     * @param String clientId
     * @param String userId
     * @return Array
     */
    public function getClientById($userId, $clientId) {
        $res = $this->db->select('*', false)
                ->from($this->table)
                ->where("userId", $userId)
                ->where("id", $clientId)
                ->get();

        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

    public function getClientByReference($userId, $referenceId) {
        $res = $this->db->select('*', false)
                ->from($this->table)
                ->where("userId", $userId)
                ->where("referenceId", $referenceId)
                ->get();

        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

    public function _getClientByClientId($clientId) {
        $res = $this->db->select('*', false)
                ->from($this->table)
                ->where("id", $clientId)
                ->get();

        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

    public function createClient($data) {
        if ($this->db->insert($this->table, $data))
            return $this->_getClientByClientId($this->db->insert_id());
        return FALSE;
    }

    public function updateClient($clientId, $data) {
        if ($this->db->update($this->table, $data, array('id' => $clientId)))
            return $this->_getClientByClientId($clientId);
        return FALSE;
    }

    public function updateClientStatus($clientId, $clientStatus) {
        $data = array(
            'clientStatus' => $clientStatus,
        );

        if ($this->db->update($this->table, $data, array('id' => $clientId)))
            return $this->_getClientByClientId($clientId);
        return FALSE;
    }

    public function verifyClient($clientId) {
        $data = array(
            'cipStatus' => 'VERIFIED',
        );

        if ($this->db->update($this->table, $data, array('id' => $clientId)))
            return $this->_getClientByClientId($clientId);
        return FALSE;
    }

    public function cancelClient($clientId) {
        $data = array(
            'clientStatus' => 'CANCELLED'
        );

        if ($this->db->update($this->table, $data, array('id' => $clientId)))
            return $this->_getClientByClientId($clientId);
        return FALSE;
    }

}