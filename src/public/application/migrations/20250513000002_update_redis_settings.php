<?php defined('BASEPATH') OR exit('No direct script access allowed');

return new class extends CI_Migration
{
    public function up() {
        // Get the database hostname directly from the active connection
        $hostname = $this->db->hostname;

        // Check if hostname is localhost or 127.0.0.1
        $is_localhost_ip = ($hostname === '127.0.0.1' || $hostname === 'localhost');

        // Only update settings for development or production_x environments
        if (ENVIRONMENT === 'development' || ENVIRONMENT === 'production_x') {
            // Determine Redis host based on database hostname
            $redis_host = $is_localhost_ip ? '127.0.0.1' : '10.152.63.131';

            // Update endpoint_redis_quote: set value based on database hostname and status to 1
            $this->db->update('settings', 
                array('value' => $redis_host, 'status' => 1), 
                array('name' => 'endpoint_redis_quote')
            );

            // Update enable_redis_quote: set status to 1
            $this->db->update('settings', 
                array('status' => 1), 
                array('name' => 'enable_redis_quote')
            );

            // Update port_redis_quote: set status to 1
            $this->db->update('settings', 
                array('status' => 1), 
                array('name' => 'port_redis_quote')
            );

            // Update time_exp_redis_s: set status to 1
            $this->db->update('settings', 
                array('status' => 1), 
                array('name' => 'time_exp_redis_s')
            );

        }
    }

    public function down() {
        // No specific rollback action defined as we don't know the previous values
        echo "No specific rollback action defined for Redis settings update.\n";
    }
};
