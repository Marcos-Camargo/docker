<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        $this->db->query("
            insert into orders_to_integration (order_id,company_id,store_id,paid_status,new_order,updated_at)
            select
                o.id,o.company_id,o.store_id,o.paid_status,0,now()
            from orders o
            left join orders_to_integration oti on oti.order_id = o.id
            join api_integrations ai on ai.store_id = o.store_id
            where oti.id is null and o.paid_status in (3,50,53,5,41,43,45)
            and o.order_id_integration is not null;");
    }

    public function down() {}

};