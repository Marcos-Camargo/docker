<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->down();
        $this->db->query('CREATE TRIGGER `campaign_v2_vtex_products_flag_update` AFTER UPDATE ON `campaign_v2_products` FOR EACH ROW BEGIN
                            IF NEW.campaign_v2_id = (
                                select id
                                from
                                    campaign_v2
                                where
                                    active = 1
                                and
                                    vtex_campaign_update > 0
                                and
                                    end_date > NOW()
                                and
                                    id = NEW.campaign_v2_id
                                )
                            THEN
                                UPDATE campaign_v2 SET vtex_campaign_update = 1 WHERE id = NEW.campaign_v2_id;
                            END IF;
                         END;');

        $this->db->query('CREATE TRIGGER `campaign_v2_vtex_products_flag_insert` AFTER INSERT ON `campaign_v2_products` FOR EACH ROW BEGIN
                                IF NEW.campaign_v2_id = (
                                    select id
                                    from
                                        campaign_v2
                                    where
                                        active = 1
                                    and
                                        vtex_campaign_update > 0
                                    and
                                        end_date > NOW()
                                    and
                                        id = NEW.campaign_v2_id
                                    )
                                THEN
                                    UPDATE campaign_v2 SET vtex_campaign_update = 1 WHERE id = NEW.campaign_v2_id;
                                END IF;
                             END;');

	}

	public function down()	{
        $this->db->query('DROP TRIGGER `campaign_v2_vtex_products_flag_insert`');
        $this->db->query('DROP TRIGGER `campaign_v2_vtex_products_flag_update`');
	}

};