<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
	public function up()
	{
		if (!$this->dbforge->register_exists('settings', 'name', 'use_datadog_rum'))
		{
			$this->db->insert('settings', array(
				'name'                  => "use_datadog_rum",
				'value'                 => 'Quando ativo será enviado metricas para o Datadog referente ao Remote User Monitor',
                'description'           => 'Quando ativo será enviado metricas para o Datadog referente ao Remote User Monitor',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Ativar Datadog RUM'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'datadog_rum_client_token'))
		{
			$this->db->insert('settings', array(
				'name'                  => "datadog_rum_client_token",
				'value'                 => '',
                'description'           => 'Token de autenticação do Datadog',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Token do Datadog'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'datadog_rum_application_id'))
		{
			$this->db->insert('settings', array(
				'name'                  => "datadog_rum_application_id",
				'value'                 => '',
                'description'           => 'Identificador da aplicação do Datadog no RUM.',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Identificador da aplicação no Datadog'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'datadog_rum_service'))
		{
			$this->db->insert('settings', array(
				'name'                  => "datadog_rum_service",
				'value'                 => '',
                'description'           => 'Identificação do serviço no Datadog.',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Identificação do serviço no Datadog.'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'datadog_rum_env'))
		{
			$this->db->insert('settings', array(
				'name'                  => "datadog_rum_env",
				'value'                 => '',
                'description'           => 'Identificação do enviroment no Datadog.',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Identificação do enviroment no Datadog.'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'datadog_rum_session_sample_rate'))
		{
			$this->db->insert('settings', array(
				'name'                  => "datadog_rum_session_sample_rate",
				'value'                 => '',
                'description'           => 'Valor referente ao Session Sample Rate do Datadog.',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Valor referente ao Session Sample Rate do Datadog.'
			));
		}

		if (!$this->dbforge->register_exists('settings', 'name', 'datadog_rum_session_replay_sample_rate'))
		{
			$this->db->insert('settings', array(
				'name'                  => "datadog_rum_session_replay_sample_rate",
				'value'                 => '',
                'description'           => 'Valor referente ao Session Sample Replay Rate do Datadog.',
				'status'                => 2,
                'setting_category_id'   => 7,
				'user_id'               => 1,
                'friendly_name'         => 'Valor referente ao Session Sample Replay Rate do Datadog.'
			));
		}
	}

	public function down()
	{
		if ($this->dbforge->register_exists('settings', 'name', 'use_datadog_rum')) {
			$this->db->delete('settings', array('name' => 'use_datadog_rum'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'datadog_rum_client_token')) {
			$this->db->delete('settings', array('name' => 'datadog_rum_client_token'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'datadog_rum_application_id')) {
			$this->db->delete('settings', array('name' => 'datadog_rum_application_id'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'datadog_rum_service')) {
			$this->db->delete('settings', array('name' => 'datadog_rum_service'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'datadog_rum_env')) {
			$this->db->delete('settings', array('name' => 'datadog_rum_env'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'datadog_rum_session_sample_rate')) {
			$this->db->delete('settings', array('name' => 'datadog_rum_session_sample_rate'));
		}

		if ($this->dbforge->register_exists('settings', 'name', 'datadog_rum_session_replay_sample_rate')) {
			$this->db->delete('settings', array('name' => 'datadog_rum_session_replay_sample_rate'));
		}
	}
};