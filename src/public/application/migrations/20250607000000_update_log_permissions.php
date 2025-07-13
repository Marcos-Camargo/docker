<?php defined('BASEPATH') or exit('No direct script access allowed');

return new class extends CI_Migration {

    public function up()
    {
        $config_file = APPPATH . 'config/config.php';
        
        // Check if the file exists
        if (!file_exists($config_file)) {
            log_message('error', 'Config file not found: ' . $config_file);
            return false;
        }
        
        // Read the file content
        $config_content = file_get_contents($config_file);
        
        // Check if log_file_permissions is already set to 0664
        if (preg_match('/\$config\[\'log_file_permissions\'\]\s*=\s*0664\s*;/', $config_content)) {
            log_message('info', 'log_file_permissions is already set to 0664');
            return true;
        }
        
        // Update the log_file_permissions configuration
        $config_content = preg_replace(
            '/\$config\[\'log_file_permissions\'\]\s*=\s*.*?;/', 
            '$config[\'log_file_permissions\'] = 0664;', 
            $config_content
        );
        
        // Write the updated content back to the file
        if (file_put_contents($config_file, $config_content)) {
            log_message('info', 'Successfully updated log_file_permissions to 0664');
            return true;
        } else {
            log_message('error', 'Failed to write updated config to file: ' . $config_file);
            return false;
        }
    }

    public function down()
    {
        // No need to revert this change as it's a configuration improvement
        return true;
    }
};