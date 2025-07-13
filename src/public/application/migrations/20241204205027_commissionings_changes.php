<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("
            CREATE TRIGGER trg_update_commissioning_products
            AFTER UPDATE ON commissioning_products
            FOR EACH ROW
            BEGIN
                UPDATE commissionings
                SET updated_at = NOW()
                WHERE id = NEW.commissioning_id;
            END;
        ");

        // Trigger para commissioning_trade_policies
        $this->db->query("
            CREATE TRIGGER trg_update_commissioning_trade_policies
            AFTER UPDATE ON commissioning_trade_policies
            FOR EACH ROW
            BEGIN
                UPDATE commissionings
                SET updated_at = NOW()
                WHERE id = NEW.commissioning_id;
            END;
        ");

        // Trigger para commissioning_categories
        $this->db->query("
            CREATE TRIGGER trg_update_commissioning_categories
            AFTER UPDATE ON commissioning_categories
            FOR EACH ROW
            BEGIN
                UPDATE commissionings
                SET updated_at = NOW()
                WHERE id = NEW.commissioning_id;
            END;
        ");

        // Trigger para commissioning_stores
        $this->db->query("
            CREATE TRIGGER trg_update_commissioning_stores
            AFTER UPDATE ON commissioning_stores
            FOR EACH ROW
            BEGIN
                UPDATE commissionings
                SET updated_at = NOW()
                WHERE id = NEW.commissioning_id;
            END;
        ");

        // Trigger para commissioning_brands
        $this->db->query("
            CREATE TRIGGER trg_update_commissioning_brands
            AFTER UPDATE ON commissioning_brands
            FOR EACH ROW
            BEGIN
                UPDATE commissionings
                SET updated_at = NOW()
                WHERE id = NEW.commissioning_id;
            END;
        ");

        // Adicionar a coluna 'last_checked_at' na tabela 'commissionings'
        $fields = [
            'last_checked_at' => [
                'type' => 'DATETIME',
                'null' => TRUE, // Permitir valores nulos inicialmente
            ]
        ];

        $this->dbforge->add_column('commissionings', $fields);

        $fields = [
            'auto_removed' => [
                'type' => 'TINYINT',
                'constraint' => ('1'),
                'default' => 0,
                'null' => false,
            ]
        ];

        $this->dbforge->add_column('campaign_v2_products', $fields);
	}

	public function down()	{
        $this->db->query("DROP TRIGGER IF EXISTS trg_update_commissioning_products;");
        $this->db->query("DROP TRIGGER IF EXISTS trg_update_commissioning_trade_policies;");
        $this->db->query("DROP TRIGGER IF EXISTS trg_update_commissioning_categories;");
        $this->db->query("DROP TRIGGER IF EXISTS trg_update_commissioning_stores;");
        $this->db->query("DROP TRIGGER IF EXISTS trg_update_commissioning_brands;");
        $this->dbforge->drop_column('commissionings', 'last_checked_at');
        $this->dbforge->drop_column('campaign_v2_products', 'auto_removed');
	}
};