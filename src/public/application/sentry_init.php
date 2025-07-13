<?php
/**
 * Sentry Initialization File
 *
 * This file initializes Sentry for error tracking.
 */

// Load Composer's autoloader if not already loaded
if (!class_exists('\Sentry\SentrySdk')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Get Sentry configuration directly from the config file
// This approach works even if CodeIgniter is not yet initialized
$config = [];
include __DIR__ . '/config/sentry.php';

// Initialize Sentry with proper checks for each configuration value
$options = [];

// Add DSN if available
if (isset($config['sentry_dsn']) && !empty($config['sentry_dsn'])) {
    $options['dsn'] = $config['sentry_dsn'];
}

// Add environment if available
if (isset($config['sentry_environment']) && !empty($config['sentry_environment'])) {
    $options['environment'] = $config['sentry_environment'];
}

// Add release if available
if (isset($config['sentry_release']) && !empty($config['sentry_release'])) {
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
