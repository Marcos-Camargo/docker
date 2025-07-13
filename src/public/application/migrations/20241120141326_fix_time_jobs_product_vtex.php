<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

	public function up() {

        $sellercenter_setting = $this->db->get_where('settings', array('name' => 'sellercenter'))->row_array();
        $sellercenter = $sellercenter_setting['value'];

        /**
        Grupo 1 (XXh00)
            Grupo Soma
            Na Terra
            Dormed

        Grupo 2 (XXh10)
            Privalia
            Aramis
            Oscar Calcados

        Grupo 3 (XXh20)
            Epoca
            Polishop
            Lojas MM
            Angeloni

        Grupo 4 (XXh30)
            Rihappy
            Sicoob

        Grupo 5 (XXh40)
            Fastshop

        Grupo 6 (XXh50)
            Sicredi
            Decathlon
            Venture Shop
            Belgo
            Ramarim
            Comfortflex
            Mateus Mais
            Pit Stot
         */
        switch ($sellercenter) {
            case 'somaplace':
            case 'naterra':
            case 'dormed':
                $time = '00:00';
                break;
            case 'privalia':
            case 'aramis':
            case 'oscarcalcados':
                $time = '10:00';
                break;
            case 'epoca':
            case 'polishop':
            case 'lojasmm':
            case 'angeloni':
            case 'Angeloni':
                $time = '20:00';
                break;
            case 'rihappy':
            case 'sicoob':
                $time = '30:00';
                break;
            case 'fastshop':
                $time = '40:00';
                break;
            case 'sicredi':
            case 'decathlon':
            case 'ventureshop':
            case 'lojabelgo':
            case 'ramarim':
            case 'comfortflex':
            case 'mateusmais':
            case 'pitstop':
                $time = '50:00';
                break;
            default:
                return;
        }

        $this->db->where_in('module_path', array(
            'Integration_v2/Product/vtex/CreateProduct',
            'Integration_v2/Product/vtex/UpdateProduct'
        ))->update('calendar_events', array(
            'start' => "2024-11-20 06:$time",
            'end' => "2200-12-31 20:$time",
        ));

        $this->db->update('calendar_events', array(
            'module_path' => "Automation/ImportFilesViaB2B"
        ), array(
            'module_path' => 'BatchC/Automation/ImportFilesViaB2B'
        ));
	}

	public function down()	{

	}
};