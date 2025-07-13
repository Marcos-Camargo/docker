# PHP CS Fixer Setup

This project uses PHP CS Fixer to maintain consistent code style across the codebase.

## Installation

PHP CS Fixer is installed as a development dependency via Composer. To install it, run:

```bash
composer install
```

## Usage

The following Composer scripts are available to check and fix code style:

### Check Code Style

To check code style without making changes (dry run):

```bash
composer cs:check
```

This command will show a list of files that would be modified if you ran the fix command.

### Fix Code Style

To automatically fix code style issues:

```bash
composer cs:fix
```

This command will modify files to conform to the defined coding standards.

## Configuration

The PHP CS Fixer configuration is defined in `.php-cs-fixer.dist.php` in the root of the project. It applies:

- PSR-2 coding standards as a base
- Short array syntax
- Ordered imports
- No unused imports
- Consistent spacing
- Proper PHPDoc formatting
- And more

The configuration targets PHP files in the following directories:
- `application/`
- `tests/`

## Integrating with Your Workflow

It's recommended to:

1. Run `composer cs:fix` before committing code
2. Consider setting up a pre-commit hook to automatically check or fix code style
3. Run PHP CS Fixer in your CI/CD pipeline to ensure code style consistency

## Customizing Rules

If you need to customize the coding standards, edit the `.php-cs-fixer.dist.php` file and modify the rules array.