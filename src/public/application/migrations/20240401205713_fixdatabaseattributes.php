<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {
        if (!$this->dbforge->column_exists('name_md5', 'atributos_categorias_marketplaces')) {
            $this->db->query('ALTER TABLE atributos_categorias_marketplaces ADD name_md5 varchar(32) NULL;');
        }
        if (!$this->dbforge->column_exists('name_md5', 'attributes_products')) {
            $this->db->query('ALTER TABLE attributes_products ADD name_md5 varchar(32) NULL;');
        }

        if(!$this->dbforge->index_exists('attributes_products_value_prd_id_IDX', 'attributes_products_value')) {
            $this->db->query('CREATE INDEX attributes_products_value_prd_id_IDX USING BTREE ON attributes_products_value (prd_id);');
        }
        if(!$this->dbforge->index_exists('attributes_products_name_md5_IDX', 'attributes_products')) {
            $this->db->query('CREATE INDEX attributes_products_name_md5_IDX USING BTREE ON attributes_products (name_md5);');
        }
        if(!$this->dbforge->index_exists('atributos_categorias_marketplaces_name_md5_IDX', 'atributos_categorias_marketplaces')) {
            $this->db->query('CREATE INDEX atributos_categorias_marketplaces_name_md5_IDX USING BTREE ON atributos_categorias_marketplaces (name_md5);');
        }
        if(!$this->dbforge->index_exists('atributos_categorias_marketplaces_id_atributo_IDX', 'atributos_categorias_marketplaces')) {
            $this->db->query('CREATE INDEX atributos_categorias_marketplaces_id_atributo_IDX USING BTREE ON atributos_categorias_marketplaces (id_atributo,int_to);');
        }
        if(!$this->dbforge->index_exists('produtos_atributos_marketplaces_id_product_IDX', 'produtos_atributos_marketplaces')) {
            $this->db->query('CREATE INDEX produtos_atributos_marketplaces_id_product_IDX USING BTREE ON produtos_atributos_marketplaces (id_product);');
        }
        if(!$this->dbforge->index_exists('produtos_atributos_marketplaces_id_atributo_IDX', 'produtos_atributos_marketplaces')) {
            $this->db->query('CREATE INDEX produtos_atributos_marketplaces_id_atributo_IDX USING BTREE ON produtos_atributos_marketplaces (id_atributo,int_to);');
        }

        $this->db->query('CREATE TRIGGER `atributos_categorias_marketplaces_AFTER_INSERT` BEFORE INSERT ON `atributos_categorias_marketplaces` FOR EACH ROW BEGIN
            set NEW.name_md5 = md5(NEW.nome);
        END');

        $this->db->query('CREATE TRIGGER `atributos_categorias_marketplaces_AFTER_UPDATE` BEFORE UPDATE ON `atributos_categorias_marketplaces` FOR EACH ROW BEGIN
            if(OLD.nome <> NEW.nome) then
                set NEW.name_md5 = md5(NEW.nome);
            end if;
        end');

        $this->db->query('CREATE TRIGGER `attributes_products_AFTER_INSERT` BEFORE INSERT ON `attributes_products` FOR EACH ROW BEGIN
                set NEW.name_md5 = md5(NEW.name);
        END');

        $this->db->query('CREATE TRIGGER `attributes_products_AFTER_UPDATE` BEFORE UPDATE ON `attributes_products` FOR EACH ROW BEGIN
            if(OLD.name <> NEW.name) then
                set NEW.name_md5 = md5(NEW.name);
            end if;
        end');

         $this->db->query('UPDATE atributos_categorias_marketplaces SET name_md5 = md5(nome);');
         $this->db->query('UPDATE attributes_products SET name_md5 = md5(name);');
    }

    public function down()	{

    }

};