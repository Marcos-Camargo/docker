<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {

        $ids = [];

        $duplicatedQuery = $this->db->query("SELECT prd_id,int_to,skumkt,variant, COUNT( skumkt ) AS Qtd FROM prd_to_integration GROUP BY prd_id,int_to,skumkt HAVING COUNT( skumkt ) > 1 ORDER BY COUNT( skumkt ) DESC");
        $duplicated = $duplicatedQuery->result_array();

        if ($duplicated){

            $this->db->query("CREATE TABLE prd_to_integration_atd_615758 AS SELECT * FROM prd_to_integration;");

            foreach ($duplicated as $duplicate){
                $qtd = $duplicate['Qtd']-1;
                if (is_null($duplicate['variant'])){
                    $sqlFinal = "LIMIT $qtd";
                }else{
                    $sqlFinal = " AND (variant > 0 OR variant IS NULL)";
                }
                $this->db->query("DELETE FROM prd_to_integration WHERE prd_id = {$duplicate['prd_id']} AND int_to = '{$duplicate['int_to']}' AND skumkt = '{$duplicate['skumkt']}' $sqlFinal");
                $ids[] = $duplicate["prd_id"];
            }

        }

        $duplicatedQuery = $this->db->query("SELECT prd_id,int_to,skumkt,variant, COUNT( skumkt ) AS Qtd FROM vtex_ult_envio GROUP BY prd_id,int_to,skumkt HAVING COUNT( skumkt ) > 1 ORDER BY COUNT( skumkt ) DESC");
        $duplicated2 = $duplicatedQuery->result_array();

        if ($duplicated2){

            $this->db->query("CREATE TABLE vtex_ult_envio_atd_615758 AS SELECT * FROM vtex_ult_envio;");

            foreach ($duplicated2 as $duplicate){
                $qtd = $duplicate['Qtd']-1;
                if (is_null($duplicate['variant'])){
                    $sqlFinal = "LIMIT $qtd";
                }else{
                    $sqlFinal = " AND (variant > 0 OR variant IS NULL)";
                }
                $this->db->query("DELETE FROM vtex_ult_envio WHERE prd_id = {$duplicate['prd_id']} AND int_to = '{$duplicate['int_to']}' AND skumkt = '{$duplicate['skumkt']}' $sqlFinal");
                $ids[] = $duplicate["prd_id"];
            }

        }

        $ids = array_unique($ids);

        foreach ($ids as $id){
            $this->db->query("INSERT INTO queue_products_marketplace (status,prd_id) VALUES (0, $id)");
        }

    }

    public function down()	{
    }
};