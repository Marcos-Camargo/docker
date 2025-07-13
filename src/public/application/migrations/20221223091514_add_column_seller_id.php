
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
			'seller_id' => array(
				'type' => 'VARCHAR',
				'constraint' => ('45'),
				'null' => TRUE,
			),

        );

        if (!$this->dbforge->column_exists('seller_id', 'prd_to_integration'))
        {
            $this->dbforge->add_column('prd_to_integration', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('prd_to_integration', 'seller_id');
	}
};
