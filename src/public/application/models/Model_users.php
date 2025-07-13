<?php
/*
 SW Serviços de Informática 2019
 
 Model de Acesso ao BD para Usuarios
 
 */

class Model_users extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUserData($userId = null)
    {

        if ($userId) {
            $sql = "SELECT * FROM users WHERE id = ?";
            $query = $this->db->query($sql, array($userId));
            return $query->row_array();
        }

        if (($this->data['user_group_id'] == 1) || $this->data['only_admin'] == 1) {
            $sql = "SELECT u.*,j.name as pai FROM users u, company j where u.company_id = j.id";
        } else {
            $sql = "SELECT u.*,j.name as pai FROM users u, company j where u.parent_id = ? AND u.company_id = j.id";
        }
        $query = $this->db->query($sql, array($this->data['usercomp']));
        return $query->result_array();
    }

    public function getUserGroup($userId = null)
    {
        if ($userId) {
            $sql = "SELECT * FROM user_group WHERE user_id = ?";
            $query = $this->db->query($sql, array($userId));
            $result = $query->row_array();

            $group_id = $result['group_id'];
            $g_sql = "SELECT * FROM `groups` WHERE id = ?";
            $g_query = $this->db->query($g_sql, array($group_id));
            $q_result = $g_query->row_array();
            return $q_result;
        }
    }

    public function create($data = '', $group_id = null)
    {
        if ($data && $group_id) {
            $create = $this->db->insert('users', $data);

            $user_id = $this->db->insert_id();

            $group_data = array(
                'user_id' => $user_id,
                'group_id' => $group_id
            );

            $group_data = $this->db->insert('user_group', $group_data);
            $data_notification = [
                'id_user' => $user_id,
                'order_notification' => 'receive_instantly'
            ];
            $notification_config = $this->db->select("*")->from('notification_config')->where(['id_user' => $user_id])->get()->row_array();
            if (!$notification_config) {
                $notification_config = $this->db->insert('notification_config', $data_notification);
            }
            // SW - Log Create
            get_instance()->log_data('Users', 'create', json_encode($data), "I'");

            return ($create == true && $group_data) ? $user_id : false;
        }
    }

    public function edit($data = array(), $id = null, $group_id = null)
    {
        $this->db->where('id', $id);
        $update = $this->db->update('users', $data);
        // SW - Log Update
        get_instance()->log_data('Users', 'edit after', json_encode($data), "I");

        if ($group_id) {
            // user group
            $update_user_group = array('group_id' => $group_id);
            $this->db->where('user_id', $id);
            $user_group = $this->db->update('user_group', $update_user_group);
            return ($update == true && $user_group == true) ? true : false;
        }

        return ($update == true) ? true : false;
    }

    public function update($data, $id)
    {
        if ($data && $id) {
            $this->db->where('id', $id);
            $update = $this->db->update('users', $data);
            return ($update == true) ? true : false;
        }
    }

    public function login_update($id, $token_agidesk)
    {
        $sql = "UPDATE users SET token_agidesk = ?, last_login_date = NOW() WHERE id = ?";

        $cmd = $this->db->query($sql, array($token_agidesk, $id));

        return;
    }

    public function delete($id)
    {

        $this->db->where('user_id', $id);
        $delete = $this->db->delete('user_group');
        $this->db->where('id', $id);
        $delete = $this->db->delete('users');
        return ($delete == true) ? true : false;
    }

    public function countTotalUsers()
    {
        $more = ($this->data['usercomp'] == 1) ? "" : (($this->data['userstore'] == 0) ? " WHERE company_id = " . $this->data['usercomp'] : " WHERE store_id = " . $this->data['userstore']);

        $sql = "SELECT * FROM users" . $more;
        $query = $this->db->query($sql);
        return $query->num_rows();
    }

    public function getUsersWithoutAgiDeskPassword($conectala = true)
    {
        if ($conectala) {
            $sql = "SELECT * FROM users WHERE password_agidesk IS NULL OR (token_agidesk IS NULL)";
        } else {
            $sql = "SELECT * FROM users WHERE password_agidesk_conectala IS NULL OR token_agidesk_conectala IS NULL";
        }
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function createUserPass($data)
    {
        $create = $this->db->replace('users_password', $data);
        return ($create == true) ? true : false;
    }

    public function deleteUserPass($id)
    {
        $this->db->where('user_id', $id);
        $delete = $this->db->delete('users_password');
        return ($delete == true) ? true : false;
    }

    public function getUserPass($id)
    {
        $sql = "SELECT * FROM users_password WHERE user_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->row_array();
    }

    public function getUsersByCompanyId($companyId)
    {
        $sql = "SELECT * FROM users WHERE company_id = ? Order by firstname, lastname";
        $query = $this->db->query($sql, array($companyId));
        return $query->result_array();
    }
    public function getUserById($id)
    {
        $user = $this->db->select()->from('users')->where(['id' => $id])->get()->row_array();
        if (empty($user)) {
            return false;
        }
        return $user;
    }
    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = ?";
        $query = $this->db->query($sql, array($email));
        return $query->result_array();
    }
    public function getUserByEmailOrLogin($login)
    {
        return $this->db->select('*')->from('users')->or_where(['email' => $login, 'username' => $login])->get()->row_array();
    }

    public function getUsersIndicator($user_id = null)
    {
        $sql = "SELECT id, email FROM users WHERE associate_type = 5";
        if ($user_id) $sql .= " AND id = {$user_id}";
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function getUsersDataView($offset = 0, $procura = '', $orderby = '', $limit = 200)
    {
        if ($offset == '') {
            $offset = 0;
        }
        if ($limit == '') {
            $limit = 200;
        }
        if (($this->data['user_group_id'] == 1) || $this->data['only_admin'] == 1) {
            $sql = "SELECT u.*,c.name as company , g.group_name FROM company c, users u, `groups` g, user_group ug  where u.company_id = c.id AND u.id=ug.user_id and ug.group_id=g.id";
        } else {
            $sql = "SELECT u.*,c.name as company , g.group_name FROM company c, users u, `groups` g, user_group ug  where u.company_id = c.id AND u.id=ug.user_id and ug.group_id=g.id and u.parent_id = ? ";
        }
        $sql .= $procura . $orderby . " LIMIT " . $limit . " OFFSET " . $offset;

        $query = $this->db->query($sql, array($this->data['usercomp']));
        return $query->result_array();
    }

    public function getUsersDataCount($procura = '')
    {
        if ($procura == '') {
            $sql = "SELECT count(*) as qtd FROM users ";
        } else {
            if (($this->data['user_group_id'] == 1) || $this->data['only_admin'] == 1) {
                $sql = "SELECT count(*) as qtd FROM company c, users u, `groups` g, user_group ug  where u.company_id = c.id AND u.id=ug.user_id and ug.group_id=g.id";
            } else {
                $sql = "SELECT count(*) as qtd FROM company c, users u, `groups` g, user_group ug  where u.company_id = c.id AND u.id=ug.user_id and ug.group_id=g.id and u.parent_id = ?";
            }
            $sql .= $procura;
        }

        $query = $this->db->query($sql, array($this->data['usercomp']));
        $row = $query->row_array();
        return $row['qtd'];
    }

    public function inactive($id)
    {
        $activeValue = 1;
        $inactiveValue = 2;
        $sql = "update users u set active=" . $inactiveValue . " WHERE u.id = ? and u.active=" . $activeValue . ";";
        $this->db->query($sql, array($id));
    }
    public function active($id)
    {
        $activeValue = 1;
        $inactiveValue = 2;
        $sql = "update users u set active=" . $activeValue . " WHERE u.id = ? and u.active=" . $inactiveValue . ";";
        $this->db->query($sql, array($id));
    }

    public function inactiveUserForDay($days)
    {
        $sqlQuery = "UPDATE users SET active = 2 WHERE last_login_date<now()- interval $days day and active = 1";
        $this->db->query($sqlQuery);
    }
    public function inactiveUserUnloged($days)
    {
        $sqlQuery = "UPDATE users SET active = 2 WHERE date_create<now()- interval $days day and last_login_date is null and active= 1";
        $this->db->query($sqlQuery);
    }

    public function getUsersByCompany($id)
    {
        $sql = "SELECT * FROM users WHERE company_id = ?";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function getUsersByStore($id)
    {
        $sql = "SELECT * FROM users WHERE store_id = ? and active=1";
        $query = $this->db->query($sql, array($id));
        return $query->result_array();
    }

    public function getBankStoreFromUser($id = null)
    {

        $saida = array();
        $saida[0]['retorno'] = "0";
        $saida[0]['name'] = "";
        $saida[0]['bank'] = "";
        $saida[0]['agency'] = "";
        $saida[0]['account_type'] = "";
        $saida[0]['account'] = "";

        if ($id == null) {
            return $saida;
        }

        $userstore  = $this->session->userdata('userstore');
        $usercomp   = $this->session->userdata('usercomp');

        if ($usercomp == 1) {
            $sql = "SELECT distinct 1 as retorno, S.name, S.bank, S.agency, S.account_type, S.account FROM stores S order by S.name";
            $query = $this->db->query($sql);
            $result = $query->result_array();
        } else {
            if ($userstore == "0") {
                $sql = "SELECT distinct 1 as retorno, S.name, S.bank, S.agency, S.account_type, S.account 
                        FROM users U
                        INNER JOIN company C ON C.id = U.company_id
                        INNER JOIN stores S ON S.company_id = C.id
                        WHERE U.id = ?
                        order by S.name";
                $query = $this->db->query($sql, array($id));
                $result = $query->result_array();
            } else {
                $sql = "SELECT distinct 1 as retorno, S.name, S.bank, S.agency, S.account_type, S.account
                        FROM users U
                        INNER JOIN stores S ON U.store_id = S.id 
                        WHERE U.id = ?
                        order by S.name";
                $query = $this->db->query($sql, array($id));
                $result = $query->result_array();
            }
        }




        if ($result) {
            return $result;
        } else {
            return $saida;
        }
    }
    public function getNameAndIdActiveUsers()
    {
        return $this->db->select(
            [
                'u.id',
                "CONCAT(u.firstname,' ',u.lastname) as name"
            ]
        )
            ->from('users u')
            ->where('active', 1)
            ->order_by('name')
            ->get()->result_array();
    }
    public function getNameAndIdActiveUsersForPhases()
    {
        $users_phase = $this->db->select('responsable_id')->from('phases p')->group_by('responsable_id')->get()->result_array();
        $users_phase_id = [];
        foreach ($users_phase as $user_phase) {
            $users_phase_id[] = $user_phase['responsable_id'];
        }
        if(!$users_phase_id){
            return;
        }
        return $this->db->select(
            [
                'u.id',
                "CONCAT(u.firstname,' ',u.lastname) as name"
            ]
        )
            ->from('users u')
            ->where('active', 1)
            ->where_in('id',$users_phase_id)
            ->order_by('name')
            ->get()->result_array();
    }
    public function existUserById($user_id)
    {
        return $this->db->select()->from('users')->where(['id' => $user_id])->count_all_results() != 0;
    }
    public function isActiveUserById($user_id)
    {
        return $this->db->select()->from('users')->where(['id' => $user_id, 'active' => 1])->count_all_results() != 0;
    }

    public function getUsersByCompanyAndStore(int $company, int $store)
    {
        $sql = "SELECT * FROM users WHERE company_id = ? AND store_id = ? and active = 1";
        $query = $this->db->query($sql, array($company, $store));
        return $query->result_array();
    }

    public function fetchStoreManagerUser(int $storeId, int $companyId): array
    {
        $users = $this->fetchStoreManagerUsers($storeId, $companyId);
        return !empty($users) ? current($users) : [];
    }

    public function fetchStoreManagerUsers(int $storeId, int $companyId): array
    {
        $users = $this->getUsersByStore($storeId);
        if (empty($users ?? [])) {
            $users = $this->getUsersByCompanyAndStore($companyId, 0);
        }
        return is_array($users) ? $users : [];
    }

    public function getMyUsersData(bool $active = true)
    {
        if ($this->data['usercomp'] != 1) {
            if ($this->data['userstore'] == 0) {
                $this->db->where('company_id', $this->data['usercomp']);
            } else {
                $this->db->where('store_id', $this->data['userstore']);
            }
        }

        $this->db->where('active', $active ? 1 : 2);

        return $this->db->get('users')->result_array();
    }

    public function getUsersWhereMakeUserAgentIsActive()
    {
        $sql = "SELECT * FROM users WHERE make_user_agent = 1 AND agidesk_agent_id is null AND active = 1";

        $query = $this->db->query($sql);
        return $query->result_array();
    }

}
