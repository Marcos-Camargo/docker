<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Sentry Helper
 *
 * This helper provides functions to interact with Sentry for error tracking.
 */

if (!function_exists('capture_exception')) {
    /**
     * Capture an exception in Sentry
     *
     * @param Exception|Throwable $exception The exception to capture
     * @param array $context Additional context data
     * @return string|null The event ID or null if Sentry is not available
     */
    function capture_exception($exception) {
        try {
            // Skip notice-level errors
            if ($exception instanceof \ErrorException) {
                $severity = $exception->getSeverity();
                // Check if the error is a notice or user notice
                if ($severity == E_NOTICE || $severity == E_USER_NOTICE || 
                    $severity == E_STRICT || $severity == E_DEPRECATED || 
                    $severity == E_USER_DEPRECATED) {
                    // Skip sending to Sentry for notice-level errors
                    return null;
                }
            }

            // Check if Sentry is available
            if (class_exists('\Sentry\SentrySdk')) {
                return \Sentry\captureException($exception);
            } else {
                // If Sentry is not available, log the error
                log_message('error', 'Sentry is not available. Exception: ' . $exception->getMessage());

                // Log the exception to the error log as a fallback
                error_log('Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
                error_log('Stack trace: ' . $exception->getTraceAsString());

                return null;
            }
        } catch (Exception $e) {
            // If capturing the exception fails, log the error
            log_message('error', 'Failed to capture exception in Sentry: ' . $e->getMessage());

            // Log the original exception to the error log as a fallback
            error_log('Original exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
            error_log('Stack trace: ' . $exception->getTraceAsString());

            return null;
        }
    }
}

if (!function_exists('capture_message')) {
    /**
     * Capture a message in Sentry
     *
     * @param string $message The message to capture
     * @param int|string|\Sentry\Severity $level The severity level (Sentry\Severity::*)
     * @param array $context Additional context data
     * @return string|null The event ID or null if Sentry is not available
     */
    function capture_message($message, $level = \Sentry\Severity::INFO, array $context = []) {
        try {
            // Convert string level to Severity object if needed
            if (is_string($level) && !($level instanceof \Sentry\Severity)) {
                switch (strtolower($level)) {
                    case 'debug':
                        $level = \Sentry\Severity::DEBUG;
                        break;
                    case 'info':
                        $level = \Sentry\Severity::INFO;
                        break;
                    case 'warning':
                        $level = \Sentry\Severity::WARNING;
                        break;
                    case 'error':
                        $level = \Sentry\Severity::ERROR;
                        break;
                    case 'fatal':
                        $level = \Sentry\Severity::FATAL;
                        break;
                    default:
                        $level = \Sentry\Severity::INFO;
                }
            }

            return \Sentry\captureMessage($message, $level);
        } catch (Exception $e) {
            log_message('error', 'Failed to capture message in Sentry: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('start_transaction')) {
    /**
     * Start a new Sentry transaction
     *
     * @param string $name The transaction name
     * @param string $op The operation name
     * @return \Sentry\Tracing\Transaction|null The transaction or null if Sentry is not available
     */
    function start_transaction($name, $op = 'default') {
        try {
            $context = new \Sentry\Tracing\TransactionContext();
            $context->setName($name);
            $context->setOp($op);

            return \Sentry\startTransaction($context);
        } catch (Exception $e) {
            log_message('error', 'Failed to start transaction in Sentry: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('set_user')) {
    /**
     * Set the current user for Sentry
     *
     * @param array $user User data (id, email, etc.)
     * @return void
     */
    function set_user(array $user) {
        try {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($user) {
                $scope->setUser($user);
            });
        } catch (Exception $e) {
            log_message('error', 'Failed to set user in Sentry: ' . $e->getMessage());
        }
    }
}

if (!function_exists('add_breadcrumb')) {
    /**
     * Add a breadcrumb to the current Sentry scope
     *
     * @param string $message The breadcrumb message
     * @param string $category The breadcrumb category
     * @param string $type The breadcrumb type
     * @param array $data Additional data
     * @return void
     */
    function add_breadcrumb($message, $category = 'default', $type = 'default', array $data = []) {
        try {
            \Sentry\addBreadcrumb(new \Sentry\Breadcrumb(
                \Sentry\Severity::INFO,
                $type,
                $category,
                $message,
                $data
            ));
        } catch (Exception $e) {
            log_message('error', 'Failed to add breadcrumb in Sentry: ' . $e->getMessage());
        }
    }
}
