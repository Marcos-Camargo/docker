<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
class Statistics {
    public function log_activity() {
        // We need an instance of CI as we will be using some CI classes
        $CI =& get_instance();
 
		if (session_status() === PHP_SESSION_NONE) { // se não tem sessão encerra
			return true;
		} 

 		if ( !empty($CI->session->userdata('id'))) {
 			// Start off with the session stuff we know
	        $data = array();
	        $data['user_id'] = $CI->session->userdata('id');
	        $data['email'] = $CI->session->userdata('email');
	        $data['company_id'] = $CI->session->userdata('usercomp');
			if (!empty($CI->session->userdata('userstore'))) {
		 		$data['store_id'] = $CI->session->userdata('userstore');
			} else {
				$data['store_id'] = 0;
			}
			
	        // Next up, we want to know what page we're on, use the router class
	        $data['section'] = $CI->router->class;
	        $data['action'] = $CI->router->method;
	 
	        // Lastly, we need to know when this is happening
	        $data['date_time'] = date('Y-m-d H:i:s');
	 
	        // We don't need it, but we'll log the URI just in case
	        $data['uri'] = uri_string();
	 
	        // And write it to the database
	        $CI->db->insert('system_utilization', $data);
 		}
       
    }
}
