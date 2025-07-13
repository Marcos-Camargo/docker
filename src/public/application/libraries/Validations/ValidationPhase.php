<?php
class ValidationPhase extends CI_Model
{
    public function __construct()
    {
        $this->load->library('form_validation');
    }
    public function validationNew($phase)
    {
        dd($this->model_phases->existPhaseByName($phase['name']));
        $this->model_phases->existPhaseByName($phase['name']);
        dd($phase);
    }
    /**
     * @param $is_new é usado para mudar a configuração quando as configuração
     * são de atualização. Devendo estar com false. default = true
     */
    public function getConfig(bool $is_new = true, array $config = []): array
    {
        if ($is_new) {
            $config[] = array(
                'field' => 'name',
                'label' => 'lang:application_phase',
                'rules' => array(
                    'trim',
                    'required',
                    array(
                        'validation_name', array($this, 'validation_name')
                    )
                ), 'errors' => array(
                    'validation_name' => $this->lang->line('messages_not_unique_phase_name')
                )
            );
        }

        $config[] = array(
            'field' => 'responsable_id',
            'label' => 'lang:application_responsible',
            'rules' => array(
                'trim',
                'required',
                array(
                    'validation_responsible_exist', array($this, 'validation_responsible_exist')
                ),
                array(
                    'validation_responsible_is_active', array($this, 'validation_responsible_is_active')
                )
            ), 'errors' => array(
                'validation_responsible_exist' => $this->lang->line('messages_responsible_not_exist'),
                'validation_responsible_is_active' => $this->lang->line('messages_responsible_not_active')
            )
        );
        return $config;
    }
    public function validation_name($name)
    {
        return !$this->model_phases->existPhaseByName($name);
    }
    public function validation_responsible_exist($user_id)
    {
        return $this->model_users->existUserById($user_id);
    }
    public function validation_responsible_is_active($user_id)
    {
        return $this->model_users->isActiveUserById($user_id);
    }
}
