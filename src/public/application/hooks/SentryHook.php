<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * SentryHook Class
 *
 * This hook initializes Sentry for error tracking and sets up a global exception handler.
 */
class SentryHook
{
    /**
     * Initialize Sentry
     *
     * This method is called by the pre_system hook.
     */
    public function init()
    {
        // Skip Sentry initialization on local development machines
        if ($this->is_local_development()) {
            return;
        }

        // Load Composer's autoloader if not already loaded
        if (! class_exists('\Sentry\SentrySdk')) {
            require_once FCPATH . 'vendor/autoload.php';
        }

        // Get Sentry configuration from the config file
        include APPPATH . 'config/sentry.php';

        // Initialize Sentry with proper checks for each configuration value
        $options = [];

        // Add DSN if available
        if (isset($config['sentry_dsn']) && ! empty($config['sentry_dsn'])) {
            $options['dsn'] = $config['sentry_dsn'];
        }

        // Add environment if available
        if (isset($config['sentry_environment']) && ! empty($config['sentry_environment'])) {
            $options['environment'] = $config['sentry_environment'];
        }

        // Add release if available
        if (isset($config['sentry_release']) && ! empty($config['sentry_release'])) {
            $options['release'] = $config['sentry_release'];
        }

        // Add traces_sample_rate if available
        if (isset($config['sentry_options']) && is_array($config['sentry_options']) &&
            isset($config['sentry_options']['traces_sample_rate']) &&
            is_numeric($config['sentry_options']['traces_sample_rate'])) {
            $options['traces_sample_rate'] = (float)$config['sentry_options']['traces_sample_rate'];
        }

        // Add max_breadcrumbs if available
        if (isset($config['sentry_options']) && is_array($config['sentry_options']) &&
            isset($config['sentry_options']['max_breadcrumbs']) &&
            is_numeric($config['sentry_options']['max_breadcrumbs'])) {
            $options['max_breadcrumbs'] = (int)$config['sentry_options']['max_breadcrumbs'];
        }

        // Initialize Sentry
        \Sentry\init($options);

        // Add project root directory and database connection information to Sentry context
        $this->add_context_data();

        // Set up global exception handler to capture all exceptions except notices
        set_exception_handler(function ($exception) {
            try {
                // Skip notice-level errors
                if ($exception instanceof \ErrorException) {
                    $severity = $exception->getSeverity();
                    // Check if the error is a notice or user notice
                    if ($severity == E_NOTICE || $severity == E_USER_NOTICE || 
                        $severity == E_STRICT || $severity == E_DEPRECATED || 
                        $severity == E_USER_DEPRECATED) {
                        // Skip sending to Sentry for notice-level errors
                        goto handle_exception;
                    }
                }

                // Send all other exceptions to Sentry
                \Sentry\captureException($exception);
            } catch (Exception $e) {
                // If Sentry fails, log the error
                error_log('Failed to capture exception in Sentry: ' . $e->getMessage());
            }

            // Re-throw the exception to let CodeIgniter handle it
            handle_exception:
            // This ensures that the normal error handling still works
            if (function_exists('_exception_handler')) {
                _exception_handler($exception);
            } else {
                // If CodeIgniter's exception handler is not available, display a generic error
                echo '<h1>An uncaught Exception was encountered</h1>';
                echo '<p>Type: ' . get_class($exception) . '</p>';
                echo '<p>Message: ' . $exception->getMessage() . '</p>';
                echo '<p>Filename: ' . $exception->getFile() . '</p>';
                echo '<p>Line Number: ' . $exception->getLine() . '</p>';
                echo '<pre>' . $exception->getTraceAsString() . '</pre>';
            }
        });
    }

    /**
     * Add context data to Sentry
     *
     * This method adds the project's root directory and database connection information
     * to the Sentry context for better error tracking.
     */
    private function add_context_data()
    {
        // Add project root directory
        $root_dir = realpath(FCPATH);

        // Get database configuration
        include_once APPPATH . 'config/database.php';

        // Use the active_group variable from database.php
        global $active_group;

        // If $active_group is null, set it using the same logic as in database.php
        if ($active_group === null) {
            $active_group = isset($_COOKIE['connection_name']) && $_COOKIE['connection_name'] ? $_COOKIE['connection_name'] : 'default';
        }

        // Prepare database information
        $db_info = [
            'connection_name' => $active_group,
            'database_name' => $db[$active_group]['database'] ?? 'unknown',
            'hostname' => $db[$active_group]['hostname'] ?? 'unknown',
        ];

        // Add context data to Sentry
        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($root_dir, $db_info): void {
            $scope->setTag('project_root', $root_dir);
            $scope->setTag('db_connection', $db_info['connection_name']);
            $scope->setTag('db_name', $db_info['database_name']);
            $scope->setTag('db_hostname', $db_info['hostname']);

            // Add all database info as context data for more detailed information
            $scope->setContext('database', $db_info);

            // Add project root as context data
            $scope->setContext('project', [
                'root_directory' => $root_dir,
            ]);
        });
    }

    /**
     * Check if the application is running on a local development machine
     * by examining the database hostname
     *
     * @return bool True if running on a local development machine, false otherwise
     */
    private function is_local_development()
    {

        if (ENVIRONMENT === 'production') {
            return false;
        }

        // Get database configuration
        include_once APPPATH . 'config/database.php';

        // Use the active_group variable from database.php
        global $active_group;

        // If $active_group is null, set it using the same logic as in database.php
        if ($active_group === null) {
            $active_group = isset($_COOKIE['connection_name']) && $_COOKIE['connection_name'] ? $_COOKIE['connection_name'] : 'default';
        }

        // Check if the database hostname indicates a local development environment
        if (isset($db[$active_group]['hostname'])) {
            $db_hostname = $db[$active_group]['hostname'];
            $local_db_hostnames = ['localhost', '127.0.0.1', '::1'];

            if (in_array($db_hostname, $local_db_hostnames) ||
                strpos($db_hostname, '.local') !== false) {
                return true;
            }
        }

        return false;
    }
}
