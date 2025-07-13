<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        
    $this->db->like('value', 'Contrato de Antecipação');
    $this->db->from('attribute_value');
    $exists = $this->db->count_all_results();  
    if($exists == 0){
      
      $this->db->like('name', 'contract_type');
      $this->db->from('attributes');
      $attribute = $this->db->get()->row();
            
      if(!$attribute){
        $this->db->insert('attributes', [
          'name' => 'contract_type',
          'att_type' => 'atributes',          
        ]);
        $attribute_id = $this->db->insert_id();
      }else{
        $attribute_id = $attribute->id;
      }

      $data = array(
        'value' => 'Contrato de Antecipação',
        'code' => 'default',
        'attribute_parent_id' => $attribute_id
      );
      $this->db->insert('attribute_value', $data);
      $attribute_value_id = $this->db->insert_id();
      
      $this->db->set('document_type', $attribute_value_id);      
      $this->db->like('contract_title', 'Contrato de Antecipação');
      $this->db->update('contracts'); 

    }
        
	 }

	public function down()	{
    //$this->db->like('value', 'Contrato de Antecipação');
    //$this->db->delete('attribute_value');
	}
};