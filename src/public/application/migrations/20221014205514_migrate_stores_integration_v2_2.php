<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {
        $this->db->query("UPDATE job_integration
            SET job_path = 
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            job_path, 
                                            'Integration/AnyMarket/Order', 
                                            'Integration_v2/Order'
                                        ), 
                                        'Integration/LojaIntegrada/Order', 
                                        'Integration_v2/Order'
                                    ),
                                    'Integration/AnyMarket/Product', 
                                    'Integration_v2/Product/anymarket'
                                ), 
                                'Integration/LojaIntegrada/Product', 
                                'Integration_v2/Product/lojaintegrada'
                            ),  
                            '/UpdateStock', 
                            '/UpdatePriceStock'
                         ), 
                        '/UpdatePrice', 
                        '/UpdatePriceStock'
                    ), 
                    '/UpdatePriceStockStock', 
                    '/UpdatePriceStock'
                ),
                last_run = null
            WHERE job_path like 'Integration/%'
            AND integration in ('anymarket', 'lojaintegrada');"
        );

        $this->db->query("DELETE FROM job_integration
            WHERE job_integration.id in (
                SELECT id
                FROM (
                    SELECT ji.id 
                    FROM job_integration as ji 
                    GROUP BY ji.job_path,ji.store_id 
                    HAVING COUNT(ji.job_path) > 1 
                    ORDER BY COUNT(ji.job_path) DESC
                ) AS t
                WHERE t.id = job_integration.id
            );"
        );

        $this->db->query("UPDATE job_integration 
            SET job = 'UpdatePriceStock'
            WHERE integration in ('anymarket', 'lojaintegrada') 
            and job in ('UpdateStock', 'UpdatePrice');"
        );

        $this->db->query("UPDATE calendar_events 
            SET module_path  = 
                REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            module_path, 
                                            'Integration/AnyMarket/Order', 
                                            'Integration_v2/Order'
                                        ), 
                                        'Integration/LojaIntegrada/Order', 
                                        'Integration_v2/Order'
                                    ), 
                                    'Integration/AnyMarket/Product', 
                                    'Integration_v2/Product/anymarket'
                                ), 
                                'Integration/LojaIntegrada/Product', 
                                'Integration_v2/Product/lojaintegrada'
                            ),                                    
                            '/UpdateStock', 
                            '/UpdatePriceStock'
                        ), 
                        '/UpdatePrice', 
                        '/UpdatePriceStock'
                    ), 
                    '/UpdatePriceStockStock', 
                    '/UpdatePriceStock'
                )
            WHERE module_path like 'Integration/AnyMarket/%' 
            OR module_path like 'Integration/LojaIntegrada/%';"
        );

        $this->db->query("DELETE FROM calendar_events 
            WHERE calendar_events.id in (
                SELECT id
                FROM (
                    SELECT ce.id 
                    FROM calendar_events as ce 
                    WHERE ce.module_path like 'Integration_v2/%'
                    GROUP BY ce.module_path,ce.params 
                    HAVING COUNT(ce.module_path) > 1 
                    ORDER BY COUNT(ce.module_path) DESC
                ) AS t
                WHERE t.id = calendar_events.id
            );"
        );

        $this->db->query("UPDATE calendar_events 
            SET title = 
                REPLACE(
                    REPLACE(
                        title, 
                        'Atualização de Preço -', 
                        'Atualização de Preço e Estoque -'
                    ), 
                    'Atualização de Estoque -', 
                    'Atualização de Preço e Estoque -'
                )
            WHERE module_path like 'Integration_v2/Product/anymarket/%' 
            OR module_path like 'Integration_v2/Product/lojaintegrada/%';"
        );

        $this->db->query("UPDATE settings SET `status` = 1 WHERE name IN 
            ('stores_module_integration_v2', 
            'store_initial_code_module_v2_integration', 
            'integration_new_module')"
        );
	 }

	public function down()	{

	}
};