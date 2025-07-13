<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        ## Create column user_group.old_group
        $fields = array(
            'old_group' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'null' => TRUE
            )
        );
        $this->dbforge->add_column("user_group", $fields);

        $sellerCenter = $this->db->query("SELECT * FROM settings WHERE name = 'sellercenter'")->row_object();

        if ($sellerCenter->value === 'conectala' && ENVIRONMENT !== 'development') {
            $this->db->query("UPDATE `user_group` SET `old_group` = `group_id`");

            $logisticConectaLa = $this->db->query("SELECT * FROM (
                    select u.id user_id, ug.group_id
                    from users u
                    join integration_logistic il ON u.store_id = il.store_id and il.store_id != 0
                    JOIN user_group ug ON ug.user_id = u.id 
                    WHERE il.integration = 'sgpweb' 
                    and il.credentials IS NULL
                    and ug.group_id in (30, 11, 26, 27, 29, 23) 
                    group by u.id
                
                    UNION
                
                    select u2.id user_id, ug.group_id
                    from users u2 
                    JOIN user_group ug ON ug.user_id = u2.id 
                    where u2.company_id in (
                        select company_id from stores s where s.id in(
                            select store_id from integration_logistic il 
                            where il.store_id in (select s2.id from stores s2 where s2.company_id in (select u3.company_id from users u3 where u3.store_id = 0 group by u3.company_id))
                            and il.integration = 'sgpweb'
                            and il.credentials IS NULL
                        ) group by s.company_id
                    )
                    and ug.group_id in (30, 11, 26, 27, 29, 23)
                ) a;
            ")->result_object();

            $this->db->insert('log_history', array(
                'user_id'   => 1,
                'company_id'=> 1,
                'store_id'  => 1,
                'module'    => 'Migration',
                'action'    => 'Migration grupo logistica conecta lá',
                'ip'        => '0.0.0.0',
                'value'     => json_encode($logisticConectaLa),
                'tipo'      => 'I'
            ));

            $groupLogisticConectaLa = 35;
            // atualizar todos os usuários para o grupo logística conecta lá.
            foreach ($logisticConectaLa as $user) {
                $this->db->where('user_id', $user->user_id)->update('user_group', array('group_id' => $groupLogisticConectaLa));
            }

            $ownLogistics = $this->db->query("
                SELECT * FROM (
                    select u.id user_id, ug.group_id
                    from users u
                    join integration_logistic il ON u.store_id = il.store_id and il.store_id != 0
                    JOIN user_group ug ON ug.user_id = u.id 
                    WHERE (
                        il.integration != 'sgpweb' and il.credentials IS NOT NULL
                    )
                    and ug.group_id in (30, 11, 26, 27, 29, 23) 
                    group by u.id
                
                    UNION
                
                    select u2.id user_id, ug.group_id
                    from users u2 
                    JOIN user_group ug ON ug.user_id = u2.id 
                    where u2.company_id in (
                        select company_id from stores s where s.id in(
                            select store_id from integration_logistic il 
                            where il.store_id in (select s2.id from stores s2 where s2.company_id in (select u3.company_id from users u3 where u3.store_id = 0 group by u3.company_id))
                            and (
                                il.integration != 'sgpweb'
                                and il.credentials IS NOT NULL
                            )
                        ) group by s.company_id
                    )
                    and ug.group_id in (30, 11, 26, 27, 29, 23)
                ) a;
            ")->result_object();
            // atualizar todos os usuários para o grupo logística própria.

            $this->db->insert('log_history', array(
                'user_id'   => 1,
                'company_id'=> 1,
                'store_id'  => 1,
                'module'    => 'Migration',
                'action'    => 'Migration grupo logistica própria',
                'ip'        => '0.0.0.0',
                'value'     => json_encode($ownLogistics),
                'tipo'      => 'I'
            ));

            $groupOwnLogistic = 36;
            // atualizar todos os usuários para o grupo logística própria.
            foreach ($ownLogistics as $user) {
                $this->db->where('user_id', $user->user_id)->update('user_group', array('group_id' => $groupOwnLogistic));
            }
        }
	 }

	public function down()	{
        $this->db->query("UPDATE `user_group` SET `group_id` = `old_group` WHERE old_group IS NOT NULL");
        $this->dbforge->drop_column("user_group", 'old_group');
	}
};