<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
    if (!$this->dbforge->column_exists('teamresponsible_id', 'agidesk_tasks')){      
      $fields = array(
          'teamresponsible_id' => array(
            'type' => 'INT',
            'constraint' => ('11'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('teamresponsible', 'agidesk_tasks')){      
      $fields = array(
          'teamresponsible' => array(
            'type' => 'VARCHAR',
            'constraint' => ('255'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('solutioncomment', 'agidesk_tasks')){      
      $fields = array(
          'solutioncomment' => array(
            'type' => 'VARCHAR',
            'constraint' => ('255'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('contactcostcenter_id', 'agidesk_tasks')){      
      $fields = array(
          'contactcostcenter_id' => array(
            'type' => 'INT',
            'constraint' => ('11'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('contactcostcenter', 'agidesk_tasks')){      
      $fields = array(
          'contactcostcenter' => array(
            'type' => 'VARCHAR',
            'constraint' => ('255'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('contactdepartment_id', 'agidesk_tasks')){      
      $fields = array(
          'contactdepartment_id' => array(
            'type' => 'INT',
            'constraint' => ('11'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('contactdepartment', 'agidesk_tasks')){      
      $fields = array(
          'contactdepartment' => array(
            'type' => 'VARCHAR',
            'constraint' => ('255'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('contactbusinessunit_id', 'agidesk_tasks')){      
      $fields = array(
          'contactbusinessunit_id' => array(
            'type' => 'INT',
            'constraint' => ('11'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
    if (!$this->dbforge->column_exists('contactbusinessunit', 'agidesk_tasks')){      
      $fields = array(
          'contactbusinessunit' => array(
            'type' => 'VARCHAR',
            'constraint' => ('255'),
            'null' => TRUE,
            'default' => null
          )
      );
      $this->dbforge->add_column("agidesk_tasks", $fields);
    }
  }

	public function down()	{		
		$this->dbforge->drop_column("agidesk_tasks", 'teamresponsible_id');
    $this->dbforge->drop_column("agidesk_tasks", 'teamresponsible');
    $this->dbforge->drop_column("agidesk_tasks", 'solutioncomment');
    $this->dbforge->drop_column("agidesk_tasks", 'contactcostcenter');
    $this->dbforge->drop_column("agidesk_tasks", 'contactdepartment_id');
    $this->dbforge->drop_column("agidesk_tasks", 'contactdepartment');
    $this->dbforge->drop_column("agidesk_tasks", 'contactbusinessunit_id');
    $this->dbforge->drop_column("agidesk_tasks", 'contactbusinessunit');
	}
};