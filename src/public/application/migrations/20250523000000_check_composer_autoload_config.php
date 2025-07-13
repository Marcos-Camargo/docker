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
        
        // Check if composer_autoload is already set correctly
        if (preg_match('/\$config\[\'composer_autoload\'\]\s*=\s*[\'"]vendor\/autoload\.php[\'"]\s*;/', $config_content)) {
            log_message('info', 'composer_autoload is already set to vendor/autoload.php');
            return true;
        }
        
        // Update the composer_autoload configuration
        $config_content = preg_replace(
            '/\$config\[\'composer_autoload\'\]\s*=\s*.*?;/', 
            '$config[\'composer_autoload\'] = \'vendor/autoload.php\';', 
            $config_content
        );
        
        // Write the updated content back to the file
        if (file_put_contents($config_file, $config_content)) {
            log_message('info', 'Successfully updated composer_autoload to vendor/autoload.php');
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