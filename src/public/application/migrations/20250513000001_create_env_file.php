<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        $env_file_path = FCPATH . '.env';

        // Get the database hostname directly from the active connection
        $hostname = $this->db->hostname;

        // Check if hostname is localhost or 127.0.0.1
        $is_localhost_ip = ($hostname === '127.0.0.1' || $hostname === 'localhost');

        // Check if .env file exists
        if (!file_exists($env_file_path)) {
            // Determine Redis host based on database hostname
            $redis_host = $is_localhost_ip ? '127.0.0.1' : (ENVIRONMENT === 'development' || ENVIRONMENT === 'production_x' ? '10.152.63.131' : 'redisprd.conectala.tec.br');

            // Determine content based on ENVIRONMENT
            if (ENVIRONMENT === 'development' || ENVIRONMENT === 'production_x') {
                $env_content = "QUEUE_DRIVER=redis\nMODE_DEBUG=true\nREDIS_HOST={$redis_host}\nREDIS_PORT=6379";
            } else {
                $env_content = "QUEUE_DRIVER=redis\nMODE_DEBUG=false\nREDIS_HOST={$redis_host}\nREDIS_PORT=6379";
            }

            // Create .env file
            file_put_contents($env_file_path, $env_content);

        }
    }

    public function down() {
        // No need to do anything in down method as we don't want to remove the .env file
        echo "No rollback action needed for .env file creation.\n";
    }
};
