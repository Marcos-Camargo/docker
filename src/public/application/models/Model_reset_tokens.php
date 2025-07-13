<?php
/*
 Model de Acesso ao BD para reset torkens
 
 */

class Model_reset_tokens extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
		
		$this->load->model('model_settings');
    }

    public function create($token, $uid, $reset_password_time_in_hours_to_reset_again = 6)
    {
        $now = new DateTime();
		
        $query = "SELECT used_at from reset_tokens where user_id = ? order by id desc limit 1";
        $result = $this->db->query($query,array($uid))->row_array();
		$lastUsage = null;
        if ($result) {
        	$lastUsage = $result['used_at'];
        }
        if ($lastUsage) {
            $lastUsage = new DateTime($lastUsage);
			
			$interval = $now->diff($lastUsage); 
			$hour = 0 ;
			if ($interval->format('%a') > 0){
				$hour += $interval->format('%a')*24;
			}
			if ($interval->format('%h') > 0){
				$hour += $interval->format('%h');
			}
			if ($interval->format('%i') > 0){
				$hour += $interval->format('%i')/60;
			}
			echo "hour=".$hour;
			echo "reset=".$reset_password_time_in_hours_to_reset_again; 
            if ($hour <= $reset_password_time_in_hours_to_reset_again) {
                return false;
            }
        }

        $query = "update reset_tokens set used = 1 where user_id = $uid";
        $this->db->query($query);

        $now = $now->format('Y-m-d H:i:s');
        $query = "insert into reset_tokens (token,created_at,user_id) values (?,?,?)";
        $this->db->query($query,array($token,$now, $uid));
        return true;
	}

	public function checkValid($token)
	{
	    $query = "select count(*) as qtd from reset_tokens where token = '$token' and not used";
	    return $this->db->query($query)->result_array()[0]['qtd'] == 1;
	}

	public function markAsUsed($token)
	{
	    $now = new DateTime();
	    $now = $now->format('Y-m-d H:i:s');;
	    $query = "update reset_tokens set used = 1, used_at = '$now' where token = '$token'";
	    return $this->db->query($query);
	}
	
	public function remove($token, $uid)
	{
		$this->db->where('token', $token);
		$this->db->where('user_id', $uid);
		$this->db->where('used', 0);
		$this->db->where(array('used_at' => null));
		$delete = $this->db->delete('reset_tokens');
		return ($delete == true) ? true : false;
	}
	
	public function getOldToken($uid, $reset_password_token_expiration_time_in_minutes)
	{
		$reset_password_token_expiration_time_in_minutes = -1 * $reset_password_token_expiration_time_in_minutes;
		$expiration = date('Y-m-d H:i:s', strtotime($reset_password_token_expiration_time_in_minutes.' minutes'));
		$query = "SELECT * from reset_tokens where user_id = ? AND used_at is null AND not used AND created_at >= ? ORDER BY id LIMIT 1";
        return $this->db->query($query,array($uid, $expiration))->row_array();
	}

}