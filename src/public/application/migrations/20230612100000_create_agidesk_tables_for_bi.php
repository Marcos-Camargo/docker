<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        ## Create Table agidesk_clients
        $this->dbforge->add_field(array(
            'id'                            => array('type' => 'INT',      'constraint' => ('11'), 'unsigned' => true, 'null' => true),
            'title'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'fulltitle'                     => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'slug'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'initials'                      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'icon'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'color'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'active'                        => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'default'                       => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'new'                           => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'order'                         => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'system'                        => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'status_id'                     => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'type_id'                       => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'related_id'                    => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'company_id'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'parent_id'                     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'created_at'                    => array('type' => 'timestamp','null' => true),
            'created_by'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'updated_at'                    => array('type' => 'timestamp','null' => true),
            'updated_by'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'deleted_at'                    => array('type' => 'timestamp','null' => true),
            'deleted_by'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'fullname'                      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'step'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'code'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'statecode'                     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'citycode'                      => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'email'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'phone'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'cellphone'                     => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'size_id'                       => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'featured'                      => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'description'                   => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'billing'                       => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'expirationdate'                => array('type' => 'timestamp','null' => true),
            'referencecompany_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'responsible_id'                => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'monthlyfee'                    => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'monthlyhours'                  => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'hourlyrate'                    => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'phoneextension'                => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'notificationgroup_id'          => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'contractnotificationgroup_id'  => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'contractduedays'               => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'contractpastduedays'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'context_id'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'spam'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'externalcode'                  => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'requiresurvey'                 => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'blockrequiredsurvey'           => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'skipsurvey'                    => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'company'                       => array('type' => 'TEXT',     'null' => true),
            'status'                        => array('type' => 'TEXT',     'null' => true),
            'administrators'                => array('type' => 'TEXT',     'null' => true),
            'contractnotificationgroup'     => array('type' => 'TEXT',     'null' => true),
            'fee'                           => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'duedate'                       => array('type' => 'timestamp','null' => true),
            'billingduedate'                => array('type' => 'timestamp','null' => true),
            'fullcustomer'                  => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'avatar'                        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'banner'                        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'last_updated_on'               => array('type' => 'timestamp','null' => true),
        ));
        $this->dbforge->create_table("agidesk_clients", true);
        $this->db->query('ALTER TABLE `agidesk_clients` ENGINE = InnoDB');
		$this->db->query('ALTER TABLE `agidesk_clients` ADD INDEX `index_by_id` (`id`);');
        $this->db->query('ALTER TABLE `agidesk_clients` ADD INDEX `index_by_created_at` (`created_at`);');


         ## Create Table agidesk_tasks
        $this->dbforge->add_field(array(
            'id'                => array('type' => 'INT',      'constraint' => ('11'), 'unsigned' => true, 'null' => true),
            'searchid'          => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'title'             => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'created_at'        => array('type' => 'timestamp','null' => true),
            'updated_at'        => array('type' => 'timestamp','null' => true),
            'creator_id'        => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'creator'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'customer_id'       => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'customer'          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'contact_id'        => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'contact'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'contactemail'      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'contactphone'      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'contactcellphone'  => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'status_id'         => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'status'            => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'priority_id'       => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'priority'          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'type_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'type'              => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'source_id'         => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'source'            => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'service_id'        => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'service'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'servicetopic_id'   => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'servicetopic'      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'servicecategory_id'=> array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'servicecategory'   => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'servicecatalog_id' => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'servicecatalog'    => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'department_id'     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'department'        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'costcenter_id'     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'costcenter'        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'businessunit_id'   => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'businessunit'      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'tag_id'            => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'tag'               => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'closure_id'        => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'closure'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'fact_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'fact'              => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'factdescription'   => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'action_id'         => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'action'            => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'actiondescription' => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'cause_id'          => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'cause'             => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'product_id'        => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'product'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'causedescription'  => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'list_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'list'              => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'board_id'          => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'board'             => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'project_id'        => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'project'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'responsible_id'    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'responsible'       => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'team_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'team'              => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'resposedate'       => array('type' => 'timestamp','null' => true),
            'started_at'        => array('type' => 'timestamp','null' => true),
            'duedate'           => array('type' => 'timestamp','null' => true),
            'finished_at'       => array('type' => 'timestamp','null' => true),
            'effort'            => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'amount'            => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'houramount'        => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'timesheet'         => array('type' => 'VARCHAR',  'constraint' => ('50'), 'null' => true),
            'module_id'         => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'cost'              => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'teamgroup_id'      => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'teamgroup'         => array('type' => 'VARCHAR',  'constraint' => ('50'), 'null' => true),
        ));
        $this->dbforge->create_table("agidesk_tasks", true);
        $this->db->query('ALTER TABLE `agidesk_tasks` ENGINE = InnoDB');
		$this->db->query('ALTER TABLE `agidesk_tasks` ADD INDEX `index_by_id` (`id`);');
        $this->db->query('ALTER TABLE `agidesk_tasks` ADD INDEX `index_by_created_at` (`created_at`);');

        ## Create Table agidesk_status
        $this->dbforge->add_field(array(
            'id'                => array('type' => 'INT',      'constraint' => ('11'), 'unsigned' => true, 'null' => true),
            'task_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'searchid'          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'title'             => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'prevstatus_id'     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'prevstatus'        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'newstatus_id'      => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'newstatus'         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'started_at'        => array('type' => 'timestamp','null' => true),
            'finished_at'       => array('type' => 'timestamp','null' => true),
            'last_updated_on'   => array('type' => 'timestamp','null' => true),

        ));
        $this->dbforge->create_table("agidesk_status", true);
        $this->db->query('ALTER TABLE `agidesk_status` ENGINE = InnoDB');
		$this->db->query('ALTER TABLE `agidesk_status` ADD INDEX `index_by_id` (`id`);');
        $this->db->query('ALTER TABLE `agidesk_status` ADD INDEX `index_by_started_at` (`started_at`);');

        ## Create Table agidesk_teams
        $this->dbforge->add_field(array(
            'id'                => array('type' => 'INT',      'constraint' => ('11'), 'unsigned' => true, 'null' => true),
            'task_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'searchid'          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'title'             => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'prevteam_id'       => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'prevteam'          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'started_at'        => array('type' => 'timestamp','null' => true),
            'finished_at'       => array('type' => 'timestamp','null' => true),
            'last_updated_on'   => array('type' => 'timestamp','null' => true),

        ));
        $this->dbforge->create_table("agidesk_teams", true);
        $this->db->query('ALTER TABLE `agidesk_teams` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `agidesk_teams` ADD INDEX `index_by_id` (`id`);');
        $this->db->query('ALTER TABLE `agidesk_teams` ADD INDEX `index_by_started_at` (`started_at`);');
        
        ## Create Table agidesk_nps
        $this->dbforge->add_field(array(
            'id'                            => array('type' => 'INT',      'constraint' => ('11'), 'unsigned' => true, 'null' => true),
            'title'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'fulltitle'                     => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true ),
            'slug'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'initials'                      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'icon'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'color'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'active'                        => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'default'                       => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'new'                           => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'order'                         => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'system'                        => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'status_id'                     => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'type_id'                       => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'related_id'                    => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'company_id'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'parent_id'                     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'created_at'                    => array('type' => 'timestamp','null' => true),
            'created_by'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'updated_at'                    => array('type' => 'timestamp','null' => true),
            'updated_by'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'deleted_at'                    => array('type' => 'timestamp','null' => true),
            'deleted_by'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'fullname'                      => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'step'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'code'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'statecode'                     => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'citycode'                      => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'email'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'phone'                         => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'cellphone'                     => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'size_id'                       => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'featured'                      => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'description'                   => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'billing'                       => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'expirationdate'                => array('type' => 'timestamp','null' => true),
            'referencecompany_id'           => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'responsible_id'                => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'monthlyfee'                    => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'monthlyhours'                  => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'hourlyrate'                    => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'phoneextension'                => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'notificationgroup_id'          => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'contractnotificationgroup_id'  => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'contractduedays'               => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'contractpastduedays'           => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'context_id'                    => array('type' => 'int',      'constraint' => ('11'), 'null' => true),
            'spam'                          => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'externalcode'                  => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'requiresurvey'                 => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'blockrequiredsurvey'           => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'skipsurvey'                    => array('type' => 'CHAR',     'constraint' => ('1'), 'null' => true),
            'company'                       => array('type' => 'TEXT',     'null' => true),
            'status'                        => array('type' => 'TEXT',     'null' => true),
            'administrators'                => array('type' => 'TEXT',     'null' => true),
            'contractnotificationgroup'     => array('type' => 'TEXT',     'null' => true),
            'fee'                           => array('type' => 'DECIMAL',  'constraint' =>  '7,2', 'null' => true),
            'duedate'                       => array('type' => 'timestamp','null' => true),
            'billingduedate'                => array('type' => 'timestamp','null' => true),
            'fullcustomer'                  => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'avatar'                        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'banner'                        => array('type' => 'VARCHAR',  'constraint' => ('255'), 'null' => true),
            'last_updated_on'               => array('type' => 'timestamp','null' => true),

        ));
        $this->dbforge->create_table("agidesk_nps", true);
        $this->db->query('ALTER TABLE `agidesk_nps` ENGINE = InnoDB');
        $this->db->query('ALTER TABLE `agidesk_nps` ADD INDEX `index_by_id` (`id`);');
        $this->db->query('ALTER TABLE `agidesk_nps` ADD INDEX `index_by_created_at` (`created_at`);');

	 }

	public function down()	{
        $this->dbforge->drop_table("agidesk_clients", true);
        $this->dbforge->drop_table("agidesk_tasks", true);
        $this->dbforge->drop_table("agidesk_status", true);
        $this->dbforge->drop_table("agidesk_teams", true);
        $this->dbforge->drop_table("agidesk_nps", true);
	}
};