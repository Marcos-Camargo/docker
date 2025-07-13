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
        
        // Check if enable_hooks is already set to true
        if (preg_match('/\$config\[\'enable_hooks\'\]\s*=\s*true\s*;/', $config_content)) {
            log_message('info', 'enable_hooks is already set to true');
            return true;
        }
        
        // Update the enable_hooks configuration
        $config_content = preg_replace(
            '/\$config\[\'enable_hooks\'\]\s*=\s*.*?;/', 
            '$config[\'enable_hooks\'] = true;', 
            $config_content
        );
        
        // Write the updated content back to the file
        if (file_put_contents($config_file, $config_content)) {
            log_message('info', 'Successfully updated enable_hooks to true');
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