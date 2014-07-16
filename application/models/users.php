<?php

class Users extends CI_Model {

    protected $table = 'users';

    /**
     * Verify if an api is valid and assigned to a subscriber
     * @param int $subscriber_id
     * @param string $key
     * @param string $secret
     * @return bool
     */
    public function validate_api($subscriber_id, $key, $secret) {
        $res = $this->db->select('*')
                ->from($this->table)
                ->where('subscriberId', $subscriber_id)
                ->where('api_key', $key)
                ->where('api_secret', $secret)
                ->where('userStatus', 'ACTIVE')
                ->get();
        return $res->num_rows() > 0;
    }

    /**
     * Verify if an api is valid and assigned to a subscriber
     * @param int $subscriber_id
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function validate_user($subscriber_id, $username, $password) {
        $res = $this->db->select('*')
                ->from($this->table)
                ->where('subscriberId', $subscriber_id)
                ->where('username', $username)
                ->where('pass', md5($password))
                ->where('userStatus', 'ACTIVE')
                ->get();
        return $res->num_rows() > 0;
    }

    /**
     * Gets one user by subscriber
     * @param int $subscriber_id
     * @return array|bool
     */
    public function get_by_subscriber($subscriber_id) {
        $res = $this->db->select('*')
                ->from($this->table)
                ->where('subscriberId', $subscriber_id)
                ->where('userStatus', 'ACTIVE')
                ->get();
        return ($res->num_rows() > 0) ? $res->row_array() : false;
    }

    /**
     * Returns a unique api key and secret
     * @return array
     */
    public function generate_api() {
        do {
            $key = $this->_generate_rand();
            $res = $this->db->select('*')
                    ->from($this->table)
                    ->where('api_key', $key)
                    ->get();
        } while ($res->num_rows() > 0);
        do {
            $secret = $this->_generate_rand();
            $res = $this->db->select('*')
                    ->from($this->table)
                    ->where('api_secret', $secret)
                    ->get();
        } while ($res->num_rows() > 0);
        return array('api_key' => $key, 'api_secret' => $secret);
    }

    private function _generate_rand() {
        $ran = random_string('alnum', 32);
        return strtolower($ran);
    }

}

