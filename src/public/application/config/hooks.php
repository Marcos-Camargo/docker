<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/
//$hook['post_controller_constructor'] = array(
//    'class'    => 'Statistics',
//    'function' => 'log_activity',
//    'filename' => 'Statistics.php',
//    'filepath' => 'hooks'
//);


// Security improvments when using SSL
//$hook['post_controller'][] = function()
//{
//	// Check if the base url starts with HTTPS
//	if(substr(base_url(), 0, 5) !== 'https'){
//		return;
//	}
//
//	// If we are not using HTTPS and not in CLI
//	if(!is_https() && !is_cli()){
//		// Redirect to the HTTPS version
//		// redirect(base_url(uri_string()));
//	}
//
//	// Get CI instance
//	$CI =& get_instance();
//
//	// Only allow HTTPS cookies (no JS)
//	$CI->config->set_item('cookie_secure', TRUE);
//	$CI->config->set_item('cookie_httponly', TRUE);

	// Set headers - movido para o apache
//	$CI->output->set_header("Strict-Transport-Security: max-age=2629800")// Force future requests to be over HTTPS (max-age is set to 1 month
//			   ->set_header("X-Content-Type-Options: nosniff") // Disable MIME type sniffing
//			   ->set_header("Referrer-Policy: strict-origin") // Only allow referrers to be sent withing the website
//			   ->set_header("X-Frame-Options: DENY") // Frames are not allowed
//			   ->set_header("X-XSS-Protection: 1; mode=block"); // Enable XSS protection in browser
//};

// Initialize Sentry for error tracking
$hook['pre_system'][] = [
   'class'    => 'SentryHook',
   'function' => 'init',
   'filename' => 'SentryHook.php',
   'filepath' => 'hooks',
];

//$hook['pre_system'][] = [
//    'class'    => 'PageCacheHook',
//    'function' => 'checkCache',
//    'filename' => 'PageCacheHook.php',
//    'filepath' => 'hooks',
//];
//$hook['pre_system'][] = [ //@todo quanto ativar, retirar database de $autoload['libraries'] em autoload.php
//    'class'    => 'DatabasePreloader',
//    'function' => 'init',
//    'filename' => 'DatabasePreloader.php',
//    'filepath' => 'hooks'
//];

//$hook['post_system'][] = [
//    'class'    => 'PageCacheHook',
//    'function' => 'saveCache',
//    'filename' => 'PageCacheHook.php',
//    'filepath' => 'hooks',
//];
