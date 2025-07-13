<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SentryTest Controller
 *
 * This controller is used to test the Sentry integration.
 */
class SentryTest extends CI_Controller {

    /**
     * Index method
     *
     * Tests basic Sentry functionality
     */
    public function index() {

        echo '<h1>Sentry Test</h1>';
        echo '<p>A test message has been sent to Sentry.</p>';
        echo '<p>Check your Sentry dashboard to verify that the message was received.</p>';
        
        echo '<h2>Available Sentry Helper Functions:</h2>';
        echo '<ul>';
        echo '<li><a href="' . site_url('sentrytest/test_exception') . '">Test Exception Capture</a></li>';
        echo '<li><a href="' . site_url('sentrytest/test_message') . '">Test Message Capture</a></li>';
        echo '<li><a href="' . site_url('sentrytest/test_transaction') . '">Test Transaction</a></li>';
        echo '<li><a href="' . site_url('sentrytest/test_user') . '">Test User Context</a></li>';
        echo '<li><a href="' . site_url('sentrytest/test_breadcrumb') . '">Test Breadcrumb</a></li>';
        echo '</ul>';
    }
    
    /**
     * Test Exception method
     *
     * Tests capturing an exception
     */
    public function test_exception() {
        try {
            // Generate a test exception
            throw new Exception('This is a test exception for Sentry');
        } catch (Exception $e) {
            // Capture the exception
            $eventId = capture_exception($e);
            
            echo '<h1>Exception Test</h1>';
            echo '<p>A test exception has been sent to Sentry.</p>';
            echo '<p>Event ID: ' . $eventId . '</p>';
            echo '<p><a href="' . site_url('sentrytest') . '">Back to Sentry Test</a></p>';
        }
    }
    
    /**
     * Test Message method
     *
     * Tests capturing a message with different severity levels
     */
    public function test_message() {
        // Capture messages with different severity levels
        $infoId = capture_message('Info level message', \Sentry\Severity::INFO);
        $warningId = capture_message('Warning level message', \Sentry\Severity::WARNING);
        $errorId = capture_message('Error level message', \Sentry\Severity::ERROR);
        
        echo '<h1>Message Test</h1>';
        echo '<p>Test messages with different severity levels have been sent to Sentry.</p>';
        echo '<p>Info Event ID: ' . $infoId . '</p>';
        echo '<p>Warning Event ID: ' . $warningId . '</p>';
        echo '<p>Error Event ID: ' . $errorId . '</p>';
        echo '<p><a href="' . site_url('sentrytest') . '">Back to Sentry Test</a></p>';
    }
    
    /**
     * Test Transaction method
     *
     * Tests creating a transaction
     */
    public function test_transaction() {
        // Start a transaction
        $transaction = start_transaction('test_transaction', 'test');
        
        // Simulate some work
        sleep(1);
        
        // Finish the transaction
        if ($transaction) {
            $transaction->finish();
        }
        
        echo '<h1>Transaction Test</h1>';
        echo '<p>A test transaction has been sent to Sentry.</p>';
        echo '<p><a href="' . site_url('sentrytest') . '">Back to Sentry Test</a></p>';
    }
    
    /**
     * Test User Context method
     *
     * Tests setting user context
     */
    public function test_user() {
        // Set user context
        set_user([
            'id' => '1234',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'ip_address' => '127.0.0.1'
        ]);
        
        // Capture a message with user context
        $eventId = capture_message('Test message with user context', \Sentry\Severity::INFO);
        
        echo '<h1>User Context Test</h1>';
        echo '<p>A test message with user context has been sent to Sentry.</p>';
        echo '<p>Event ID: ' . $eventId . '</p>';
        echo '<p><a href="' . site_url('sentrytest') . '">Back to Sentry Test</a></p>';
    }
    
    /**
     * Test Breadcrumb method
     *
     * Tests adding breadcrumbs
     */
    public function test_breadcrumb() {
        // Add breadcrumbs
        add_breadcrumb('User visited page A', 'navigation', 'user');
        add_breadcrumb('User clicked button B', 'ui.click', 'user');
        add_breadcrumb('API call to endpoint C', 'http', 'info', [
            'method' => 'GET',
            'url' => 'https://api.example.com/endpoint'
        ]);
        
        // Capture a message with breadcrumbs
        $eventId = capture_message('Test message with breadcrumbs', \Sentry\Severity::INFO);
        
        echo '<h1>Breadcrumb Test</h1>';
        echo '<p>A test message with breadcrumbs has been sent to Sentry.</p>';
        echo '<p>Event ID: ' . $eventId . '</p>';
        echo '<p><a href="' . site_url('sentrytest') . '">Back to Sentry Test</a></p>';
    }
}