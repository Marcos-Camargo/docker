<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    function escape_string($data)
    {
        $result = array();
        foreach ($data as $row) {
            $result[] = str_replace('"', '', $row);
        }
        return $result;
    }

    function cleanString($string){
        $string = strtr(utf8_decode($string), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        return strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $string));
    }

    public function up()
    {
        $fieldUpdate = array(
            'description' => array(
                'type' => 'VARCHAR',
                'constraint' => ('255'),
                'default' => "",
                'after' => 'user_id'
            ),
            'friendly_name' => array(
                'type' => 'VARCHAR',
                'constraint' => ('125'),
                'default' => "",
                'after' => 'user_id'
            ),
            'setting_category_id' => array(
                'type' => 'INT',
                'constraint' => ('11'),
                'default' => 1,
                'after' => 'user_id'
            )
        );

        if (!$this->dbforge->column_exists('setting_category_id', 'settings')) {
            $this->dbforge->add_column('settings', $fieldUpdate);
        }

        $file = fopen(FCPATH . "assets/files/settings.csv", 'r');
        $fields = fgetcsv($file, 120400, "\n", '"');
        $keys_values = explode(';', $fields[0]);
        $content = array();
        $keys = $this->escape_string($keys_values);

        $i = 1;
        while (($row = fgetcsv($file, 120400, "\n", '"')) != false) {
            if ($row != null) { // skip empty lines
                $values = explode(';', $row[0]);
                if (count($keys) == count($values)) {
                    $arr = array();
                    $new_values = $this->escape_string($values);
                    for ($j = 0; $j < count($keys); $j++) {
                        if ($keys[$j] != "") {
                            $arr[] = $new_values[$j];
                        }
                    }
                    $content[$i] = $arr;
                    $i++;
                }
            }
        }

        fclose($file);
        foreach ($content as $key => $row) {

            $name = $row[1];
            $description = $row[2];
            $friendly_name = $row[0];
            $category = $row[3];
            if(empty($friendly_name)){
                $friendly_name = $name;
            }

            $setting = $this->db->like("name", $category)->get('settings_categories')->row_array();
            if(!$setting){
                $this->db->insert('settings_categories', ['name' => $category, 'icon' => $this->cleanString($category)]);
                $id = $this->db->insert_id();
            }else{
                $id = $setting['id'];
            }

            $setting = $this->db->where("name", $name)->get('settings')->row_array();
            if($setting){
                $this->db->where('id', $setting['id']);
                $this->db->update('settings', ['description' => $description, 'setting_category_id' => $id, 'friendly_name' => $friendly_name]);
            }else{
                $this->db->insert('settings', ['name' => $name, 'description' => $description, 'friendly_name' => $friendly_name, 'setting_category_id' => $id, 'status' => 2]);
            }

        }

        $category = $this->db->like("name", 'Personalizado')->get('settings_categories')->row_array();
        $settings = $this->db->where('setting_category_id', 1)->get('settings')->result_array();
        foreach($settings as $setting){
            $this->db->where('id', $setting['id']);
            $this->db->update('settings', ['setting_category_id' => $category['id'], 'friendly_name' => $setting['name']]);
        }

    }

    public function down()
    {
        $this->dbforge->drop_column('settings', 'business_rules');
        $this->dbforge->drop_column('settings', 'friendly_name');
        $this->dbforge->drop_column('settings', 'setting_category_id');
        $this->dbforge->drop_column('settings', 'description');
    }
};