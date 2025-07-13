<?php
/*
 Model de Acesso ao BD para Externals Authentication
 */

class Model_externals_authentication extends CI_Model
{
    var $readonlydb;

    public function __construct()
    {
        parent::__construct();
        $this->readonlydb = ENVIRONMENT === 'production' || ENVIRONMENT === 'production_x' ? $this->load->database('readonly', TRUE) : get_instance()->db;
    }

    public function getData($id = null)
    {
        if ($id) {
            $sql = "SELECT * FROM externals_authentication WHERE id = ?";
            $query = $this->readonlydb->query($sql, array($id));
            return $query->row_array();
        }
        
        $sql = "SELECT * FROM externals_authentication";
        $query = $this->readonlydb->query($sql);
        return $query->result_array();
    }
    
    public function create($data)
    {
        if ($data) {
            $insert = $this->db->insert('externals_authentication', $data);
            return ($insert) ? $this->db->insert_id() : false;
        }
        return false;
    }
    
    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            try {
                $update = $this->db->update('externals_authentication', $data);
                return ($update) ? true : false;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
    
    public function remove($id)
    {
        if($id) {
            $this->db->where('id', $id);
            try {
                $delete = $this->db->delete('externals_authentication');
                return ($delete) ? true : false;
            } catch (\Exception $e) {
                return false; 
            }
        }
        return false;
    }
    
    public function getIndexDataView($offset =0, $procura='', $orderby = '', $limit = 200)
    {
    	if ($offset == '') {$offset=0;}
		if ($limit == '') {$limit=200;}
        $sql = "SELECT e.*, u.email FROM externals_authentication e, users u WHERE u.id = e.user_updated ";
		$sql .= $procura.$orderby." LIMIT ".$limit." OFFSET ".$offset;
        $query = $this->readonlydb->query($sql);
        return $query->result_array();
    }
	
	public function getIndexDataCount($procura='')
    {
        $sql = "SELECT count(*) as qtd FROM externals_authentication e, users u WHERE u.id = e.user_updated ";
		$sql .= $procura;
        $query = $this->readonlydb->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
    }
	
	public function getDatabyName($name)
    {
        $sql = "SELECT * FROM externals_authentication WHERE name = ?";
        $query = $this->readonlydb->query($sql, array($name));
        return $query->row_array();
    }

    public function getDataActive()
    {
        $sql = "SELECT * FROM externals_authentication WHERE active = 1 ORDER BY name";
        $query = $this->readonlydb->query($sql);
        return $query->result_array();
    }

    public function createConfiguration($data)
    {
        if ($data) {
            $insert = $this->db->insert('externals_authentication_configuration', $data);
            return ($insert) ? $this->db->insert_id() : false;
        }
        return false;
    }
    
    public function updateConfiguration($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            try {
                $update = $this->db->update('externals_authentication_configuration', $data);
                return ($update) ? true : false;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function getDataConfigurationbyName($external_authentication_id, $name)
    {
        $sql = "SELECT * FROM externals_authentication_configuration WHERE external_authentication_id = ? AND name = ?";
        $query = $this->readonlydb->query($sql, array((int)$external_authentication_id, (string)$name));
        return $query->row_array();
    }

    public function createOrUpdateConfiguration($data) 
    {
        $row = $this->getDataConfigurationbyName($data['external_authentication_id'], $data['name']);
        if (!$row) {
            $id =  $this->createConfiguration($data);
            if ($id) {
                $all_id = array ('id' => $id);
                get_instance()->log_data('ExternalAuthenticationConfiguration', 'create', json_encode(array_merge($all_id, $data)), "I");
            }
            return $id;
        } else {
            $update = $this->updateConfiguration($data, $row['id']);
            if ($update) {
                $all_id = array ('id' => $row['id']);
                get_instance()->log_data('ExternalAuthenticationConfiguration', 'update', json_encode(array_merge($all_id, $data)), "I");
            }
            return $update;
        }
    }

    public function getAllDataConfiguration($external_authentication_id)
    {
        $sql = "SELECT * FROM externals_authentication_configuration WHERE external_authentication_id = ? ";
        $query = $this->readonlydb->query($sql, array((int)$external_authentication_id));
        return $query->result_array();
    }

    public function VerifyNameUnique($name, $id) 
    {
        $sql = "SELECT * FROM externals_authentication WHERE id != ? AND name = ?";
        $query = $this->readonlydb->query($sql, array((int)$id, (string)$name));
        return $query->row_array();
    }

    public function getUsersExternalAuthenticationView($external_authentication_id, $offset =0, $procura='', $orderby = '', $limit = 200)
    {
    	if ($offset == '') {$offset=0;}
		if ($limit == '') {$limit=200;}
        $sql = "SELECT * FROM users WHERE external_authentication_id = ".(int)$external_authentication_id;
		$sql .= $procura.$orderby." LIMIT ".$limit." OFFSET ".$offset;
        $query = $this->readonlydb->query($sql);
        return $query->result_array();
    }
	
	public function getUsersExternalAuthenticationCount($external_authentication_id, $procura='')
    {
        $sql = "SELECT count(*) as qtd FROM users WHERE external_authentication_id = ".(int)$external_authentication_id;        
		$sql .= $procura;
        $query = $this->readonlydb->query($sql);
		$row = $query->row_array();
		return $row['qtd'];
    }

    public function externalLogin($id, $user, $pass) 
    {   
        $external = $this->getData($id);
        if ($external) {
            if ($external['type'] == 'LDAP') {
                return $this->LDAPLogin($id, $user, $pass);
            }
        }
        return array('auth' => false, 'result' => $this->lang->line('application_login_fail'));
    }

    public function LDAPLogin($id, $user, $pass) 
    {

        if (!function_exists('ldap_connect')) {
            return array('auth' => false, 'result' => $this->lang->line('message_ldap_not_installed'));
        }
        // $user pode ser um registro completo do usuário ou apenas a string com email ou nome para testar o LDSP
        if (is_array($user)) {
            $user_test = $user;
        } else {
            $user_test = array (
                'username'  => $user,
                'email'     => $user,
                'external_authentication_id' => $id,
                'user_base_dn' => ''
            );
        }
        $external = $this->getData($id);
        if (!$external) {
            return array('auth' => false, 'result' => $this->lang->line('message_no_external_ldap_definition'));
        }
        $confs = $this->getAllDataConfiguration($id);
        
        foreach ($confs as $conf) {
            $ldap_configuration[$conf['name']] = $conf['value'];
        }

        if ($ldap_configuration['ldap_requires_certificate']) {
            // LDAP - specify file that contains the client certificate.
            $tls_cert = FCPATH. $ldap_configuration['ldap_client_certificate'];					
            if (!file_exists($tls_cert)) {
                return array('auth' => false, 'result' => $tls_cert . ' '.$this->lang->line('message_ldap_certificate_file_not_exist'));
            }
            putenv("LDAPTLS_CERT=$tls_cert");

            // LDAP - specify file that contains private key w/o password for TLS_CERT.
            $tls_key = FCPATH. $ldap_configuration['ldap_certificate_key'];	
            if (!file_exists($tls_key)) {
                return array('auth' => false, 'result' => $tls_key . ' '.$this->lang->line('message_ldap_file_key_not_exist'));
            } 
            putenv("LDAPTLS_KEY=$tls_key");
            
            putenv('LDAPTLS_CIPHER_SUITE=NORMAL:!VERS-TLS1.2');
        }

        $ldapconn = ldap_connect('ldaps://'.$ldap_configuration['ldap_host_name'], $ldap_configuration['ldap_port']) ;
        if (!$ldapconn) {
            return array('auth' => false, 'result' => $this->lang->line('message_ldap_connect_error').' '.
                $ldap_configuration['ldap_host_name'].' '.$this->lang->line('message_ldap_on_port').' '.$ldap_configuration['ldap_port']);
        }
        ldap_set_option($ldapconn, LDAP_OPT_PROTOCOL_VERSION, $ldap_configuration['ldap_version']);
        ldap_set_option($ldapconn, LDAP_OPT_REFERRALS, 0);
    
        if ($ldap_configuration['ldap_user_type'] == 'email') {
            $uid = $user_test['email'];
        }else {
            if (trim($ldap_configuration['ldap_base_dn']) == '') {
                $uid = 'uid='.$user_test['username'];
            } else {
                $uid = 'uid='.$user_test['username'].','.$ldap_configuration['ldap_base_dn'] ;
            }            
        }
        $ldapbind = @ldap_bind($ldapconn, $uid, $pass);
        if ($ldapbind) {
            ldap_unbind($ldapconn);
            return array('auth' => true, 'result' => $this->lang->line('message_ldap_ok'));
        } else {
            return array('auth' => false, 'result' => ldap_errno($ldapconn).": ".ldap_error($ldapconn));
        }
        
        /*
        $ldap_host = "ldaps://ldap.jumpcloud.com";
        $ldap_port = 389;
        $ldap_version = 3;
        $uid='uid=ricardoschaffer,ou=Users,o=6245f8dbf51b163ede686adb,dc=jumpcloud,dc=com';

        $ldap_host = "ldaps://ldap.google.com";
        $ldap_port = 389;
        $ldap_version = 3;
        // $uid = 'ricardoschaffer@conectala.com.br';
        $uid = $result['email'];
        */
    }

    public function getLoginMessages()
    {
        $sql = "SELECT ea.name AS eaname, ea.type , eac.* FROM externals_authentication_configuration eac, externals_authentication ea WHERE eac.name = 'openid_message_login' AND eac.external_authentication_id = ea.id and ea.active = 1;";        
        $query = $this->readonlydb->query($sql);
		return $query->result_array();
    } 

    public function getLoginIcons()
    {
        $sql = "SELECT ea.name AS eaname, ea.type , eac.* FROM externals_authentication_configuration eac, externals_authentication ea WHERE eac.name = 'openid_icon' AND eac.external_authentication_id = ea.id and ea.active = 1;";        
        $query = $this->readonlydb->query($sql);
		return $query->result_array();
    } 

}
