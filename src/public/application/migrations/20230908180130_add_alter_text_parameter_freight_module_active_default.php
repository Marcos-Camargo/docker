<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up() {

        if ($this->dbforge->register_exists('settings', 'name', 'freight_module_active_default')) {

            $data = [
                'value' => 'Se ativado, sempre que um seller novo for cadastrado o módulo de frete será ativado automaticamente.',
                'description' => 'Quando esse parâmetro estiver ativo, sempre que um novo seller for cadastrado o módulo de frete será ativado automaticamente. Quando esse parâmetro estiver ativo, a seção "Logística" do cadastro de sellers ficará oculta. Novo Parâmetro -  Ativar correios automaticamente ao cadastrar um novo seller. Qualquer seller center pode ativar, hoje está ativo somente para a Conecta Lá.'
            ];

            $this->db->where('name', 'freight_module_active_default');
            $this->db->update('settings', $data);

        }
    }

    public function down()	{

    }
};