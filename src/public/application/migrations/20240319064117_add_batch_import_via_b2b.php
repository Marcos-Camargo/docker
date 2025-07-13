<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration
{

    public function up()
    {
        $this->db->query("
        INSERT INTO calendar_events
            (title, event_type, `start`, `end`, module_path, module_method, params, alert_after)
        VALUES('Importação automática dos arquivos de cadastro de produtos, disponibilidade, estoque e preço da Via Varejo', 72, '2024-02-19 18:22:00', '2200-12-31 23:59:00', 'BatchC/Automation/ImportFilesViaB2B', 'run', '', 60)");
    }

    public function down()
    {
        $this->db->query('DELETE FROM calendar_events WHERE module_path like "BatchC/Automation/ImportFilesViaB2B";');
    }
};
