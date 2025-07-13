# Sentry Integration for CodeIgniter

This document provides information about the Sentry integration in this CodeIgniter application.

## Overview

Sentry is an error tracking and performance monitoring tool that helps developers identify and fix issues in their applications. This integration allows the application to send error reports, custom messages, and performance data to Sentry.

## Configuration

The Sentry configuration is stored in `application/config/sentry.php`. The main settings are:

- **DSN**: The Data Source Name that identifies your Sentry project
- **Environment**: The environment (development, testing, production)
- **Release**: The version of your application
- **Options**: Additional options like traces_sample_rate and max_breadcrumbs

## Initialization

Sentry is initialized using CodeIgniter's hook system, specifically in the `pre_system` hook. This ensures that Sentry is available throughout the application lifecycle, without requiring modifications to the `index.php` file.

The initialization is handled by the `SentryHook` class in `application/hooks/SentryHook.php`, which loads the Sentry configuration from `application/config/sentry.php` and initializes Sentry with the appropriate options.

The Sentry helper functions are loaded automatically through CodeIgniter's autoload system, making them available in all controllers and views.

## Helper Functions

The following helper functions are available in `application/helpers/sentry_helper.php`:

- `capture_exception($exception, $context = [])`: Captures an exception
- `capture_message($message, $level = \Sentry\Severity::INFO, $context = [])`: Captures a message with a specific severity level
- `start_transaction($name, $op = 'default')`: Starts a transaction for performance monitoring
- `set_user($user)`: Sets user context for events
- `add_breadcrumb($message, $category = 'default', $type = 'default', $data = [])`: Adds a breadcrumb to the current scope

## Testing

You can test the Sentry integration by visiting the SentryTest controller:

```
http://your-site.com/sentrytest
```

This page provides links to test various Sentry features:

- Test Exception Capture
- Test Message Capture
- Test Transaction
- Test User Context
- Test Breadcrumb

## Global Exception Handling

The application includes a global exception handler that captures all uncaught exceptions and sends them to Sentry. This ensures that any exceptions thrown by the system are tracked, even if they occur in parts of the code that don't have explicit try-catch blocks.

The global exception handler is set up in the `SentryHook` class and works in all environments (development, testing, production). When an exception occurs:

1. The exception is captured and sent to Sentry
2. If Sentry fails to capture the exception, the error is logged
3. The exception is then passed to CodeIgniter's default exception handler
4. If CodeIgniter's exception handler is not available, a generic error message is displayed

This approach ensures that all exceptions are tracked, while still allowing CodeIgniter to handle the exception display based on the environment.

## Troubleshooting

If you encounter issues with the Sentry integration:

1. Check that the DSN in `application/config/sentry.php` is correct
2. Verify that the Sentry SDK is installed (`composer require sentry/sdk`)
3. Make sure the `sentry` helper is included in the `autoload.php` file
4. Check that the `SentryHook` is properly registered in the `hooks.php` file
5. Look for errors in the CodeIgniter log files (`application/logs/`)
6. Check the Sentry dashboard for any reported issues

If exceptions are not being captured:

1. Verify that the `set_exception_handler` function is working correctly in the `SentryHook` class
2. Check if there are any PHP errors that might be preventing the hook from running
3. Try manually capturing an exception using the `capture_exception()` function

## Additional Resources

- [Sentry PHP SDK Documentation](https://docs.sentry.io/platforms/php/)
- [CodeIgniter Documentation](https://codeigniter.com/userguide3/)
