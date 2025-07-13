
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
   public function up()
    {
        $field = array(
            'use_exclusive_cycle' => array(
                'type' => 'tinyint',
                'constraint' => ('1'),
                'null' => false,
            )
        );

        if (!$this->dbforge->column_exists('use_exclusive_cycle', 'stores')) {
            $this->dbforge->add_column('stores', $field);
        }

	}

	public function down() {
        $this->dbforge->drop_column('stores', 'use_exclusive_cycle');
    }
};
