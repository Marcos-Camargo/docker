<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('getLastQuery')) {
    /**
     * @param string|null $database_connection
     * @return string
     */
    function getLastQuery(string $database_connection = null): string
    {
        if (!is_null($database_connection)) {
            get_instance()->load->database($database_connection, TRUE);
        }
        return get_instance()->db->last_query();
    }
}

if (!function_exists('getAllQueries')) {
    /**
     * @param string|null $database_connection
     * @return array
     */
    function getAllQueries(string $database_connection = null): array
    {
        if (!is_null($database_connection)) {
            get_instance()->load->database($database_connection, TRUE);
        }

        return get_instance()->db->queries;
    }
}