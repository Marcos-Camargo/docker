
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $fieldNew = array(
            'flag_store_migration' => array(
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'null' => FALSE,
                'default' => null,
                'after' => 'flag_antecipacao_repasse'
            )
        );

        if (!$this->dbforge->column_exists($fieldNew, 'stores'))
        {
            $this->dbforge->add_column('stores', $fieldNew);
        }

	}

	public function down()
    {
        $this->dbforge->drop_column('stores', 'flag_store_migration');
	}
};
