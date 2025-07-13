
<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        if ($this->dbforge->register_exists('calendar_events', 'module_path', 'SendProductsWithTransformationError')) {
            $this->db->where('module_path', 'SendProductsWithTransformationError')
                ->update('calendar_events', array(
                    'module_path' => 'Publication/SendProductsWithTransformationError'
                ));
        }
    }

    public function down()
    {
        $this->db->where('module_path', 'Publication/SendProductsWithTransformationError')
            ->update('calendar_events', array(
                'module_path' => 'SendProductsWithTransformationError'
            ));
    }
};
