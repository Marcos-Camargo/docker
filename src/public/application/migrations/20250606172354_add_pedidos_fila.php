<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
		#inserir registros perdidos na fila novamente
		$this->db->query("
		insert into orders_to_integration (order_id,company_id,store_id,paid_status,new_order,updated_at)
		select 
			o.id,o.company_id,o.store_id,o.paid_status,0,now() 
			#o.id,o.company_id,o.store_id,o.paid_status,o.order_id_integration ,ai.integration 
		from orders o
		left join orders_to_integration oti on oti.order_id = o.id
		join api_integrations ai on ai.store_id = o.store_id 
		where oti.id is null and o.paid_status in (3,50,53,5,41,43,45) 
		and o.order_id_integration is not null
		and o.order_id_integration is not null
		order by o.id;");
	}

	public function down()	{
	}
};