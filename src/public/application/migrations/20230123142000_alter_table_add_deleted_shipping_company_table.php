
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up()
    {
        if (!$this->dbforge->column_exists('deleted', 'shipping_company')) {
            $this->dbforge->add_column('shipping_company',
                array(
                    'deleted'   => array(
                        'type'      => 'TINYINT',
                        'null'      => FALSE,
                        'default'   => '0',
                        'after'     => 'freight_seller'
                    )
                )
            );
        }
	}

	public function down()
    {
        $this->dbforge->drop_column('shipping_company', 'deleted');
    }
};
