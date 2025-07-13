<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Sentry Configuration
|--------------------------------------------------------------------------
|
| This file contains the configuration settings for Sentry error tracking.
|
*/

// Sentry DSN (Data Source Name)
$config['sentry_dsn'] = 'http://5b0faa2a95fa1c1cc3035f3be6f289f0@10.150.24.138:9000/2';

// Environment (development, testing, production)
$config['sentry_environment'] = ENVIRONMENT;

// Release version (optional)
$config['sentry_release'] = '1.0.0'; // You can set this to your application version

// Additional Sentry options
$config['sentry_options'] = [
    'traces_sample_rate' => 0, // Capture 100% of transactions for performance monitoring
    'max_breadcrumbs' => 50,     // Maximum number of breadcrumbs
];