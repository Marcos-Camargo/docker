<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2017, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2017, British Columbia Institute of Technology (http://bcit.ca/)
 * @license	http://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 3.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration Class
 *
 * All migrations should implement this, forces up() and down() and gives
 * access to the CI super-global.
 *
 * @property CI_DB_forge $dbforge
 * @property CI_DB_query_builder $db
 * @property CI_Loader $load
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Libraries
 * @author		Reactor Engineers
 * @link
 */
class CI_Migration {

	/**
	 * Whether the library is enabled
	 *
	 * @var bool
	 */
	protected $_migration_enabled = FALSE;

	/**
	 * Migration numbering type
	 *
	 * @var	bool
	 */
	protected $_migration_type = 'sequential';

	/**
	 * Path to migration classes
	 *
	 * @var string
	 */
	protected $_migration_path = NULL;

	/**
	 * Current migration version
	 *
	 * @var mixed
	 */
	protected $_migration_version = 0;

	/**
	 * Database table with migration info
	 *
	 * @var string
	 */
	protected $_migration_table = 'migrations';

	/**
	 * Whether to automatically run migrations
	 *
	 * @var	bool
	 */
	protected $_migration_auto_latest = FALSE;

	/**
	 * Migration basename regex
	 *
	 * @var string
	 */
	protected $_migration_regex;

	/**
	 * Error message
	 *
	 * @var string
	 */
	protected $_error_string = '';

	/**
	 * Initialize Migration Class
	 *
	 * @param	array	$config
	 * @return	void
	 */
	public function __construct($config = array())
	{
		// Only run this constructor on main library load
		if ( ! in_array(get_class($this), array('CI_Migration', config_item('subclass_prefix').'Migration'), TRUE))
		{
			return;
		}

		foreach ($config as $key => $val)
		{
			$this->{'_'.$key} = $val;
		}

		log_message('info', 'Migrations Class Initialized');

		// Are they trying to use migrations while it is disabled?
		if ($this->_migration_enabled !== TRUE)
		{
			show_error('Migrations has been loaded but is disabled or set up incorrectly.');
		}

		// If not set, set it
		$this->_migration_path !== '' OR $this->_migration_path = APPPATH.'migrations/';

		// Add trailing slash if not set
		$this->_migration_path = rtrim($this->_migration_path, '/').'/';

		// Load migration language
		$this->lang->load('migration');

		// They'll probably be using dbforge
		$this->load->dbforge();

		// Make sure the migration table name was set.
		if (empty($this->_migration_table))
		{
			show_error('Migrations configuration file (migration.php) must have "migration_table" set.');
		}

		// Migration basename regex
		$this->_migration_regex = ($this->_migration_type === 'timestamp')
			? '/^\d{14}_(\w+)$/'
			: '/^\d{3}_(\w+)$/';

		// Make sure a valid migration numbering type was set.
		if ( ! in_array($this->_migration_type, array('sequential', 'timestamp')))
		{
			show_error('An invalid migration numbering type was specified: '.$this->_migration_type);
		}

		// If the migrations table is missing, make it
		if ( ! $this->db->table_exists($this->_migration_table)) {

			$this->dbforge->add_field('id');
			$this->dbforge->add_field(array(
				'migration' => array('type' => 'VARCHAR', 'constraint' => 255),
			));
			$this->dbforge->add_field(array(
				'batch' => array('type' => 'int', 'constraint' => 10),
			));
			$this->dbforge->add_field(array(
                '`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ',
			));

			$this->dbforge->create_table($this->_migration_table, TRUE);

		}

	}

	// --------------------------------------------------------------------

    /**
     * Migrate to a schema version
     *
     * Calls each migration step required to get to the schema version of
     * choice
     *
     * @return    bool    TRUE if no migrations are found, current version string on success, FALSE on failure
     */
	public function updateVersion(): bool
	{
		// Note: We use strings, so that timestamp versions work on 32-bit systems
		$current_version = $this->_get_runned_migrations();
        $lastBatch = $this->_getLastBatch();

		$migrations = $this->find_migrations();

        $migrationsToRun = array_diff($migrations, $current_version);

        if (!$migrationsToRun){
            return true;
        }

        $this->db->db_debug = true;

        $batch = $lastBatch+1;

		foreach ($migrationsToRun as $file) {

            $fileToInclude = $this->_migration_path.$file;

			$class = include_once($fileToInclude);

			log_message('debug', 'Migrating '.$file);
            echo 'Migrating '.$file;

            $class->up();

			$this->_update_version($file, $batch);

            log_message('debug', 'Migration Success '.$file);

            echo " - SUCCESS".PHP_EOL;

		}

		log_message('debug', 'Finished migrations');

        echo 'Finished migrations'.PHP_EOL;

        return true;

	}

	public function rollbackVersion(): bool
	{

		// Note: We use strings, so that timestamp versions work on 32-bit systems
        $lastBatch = $this->_getLastBatch();

        log_message('debug', "Rolling back to $lastBatch");
        echo "Rolling back to batch: $lastBatch".PHP_EOL;

		$migrations_batch = $this->_get_runned_migrations_by_batch($lastBatch);

        if (!$migrations_batch){
            log_message('debug', 'No migration found to rollback');
            echo 'No migration found to rollback ';
            return true;
        }

		foreach ($migrations_batch as $file) {

            $fileToInclude = $this->_migration_path.$file;

            if (is_file($fileToInclude)){

                $class = include_once($fileToInclude);

                log_message('debug', 'Rolling back migration '.$file);
                echo 'Rolling back migration '.$file;

                $class->down();

                log_message('debug', 'Migration Successfull rolled back '.$file);

                echo " - SUCCESS".PHP_EOL;

            }else{
                log_message('debug', 'Migration not found: '.$file);
                echo 'Migration not found: '.$file.PHP_EOL;
            }

			$this->_delete_version($file);

		}

		log_message('debug', 'Finished migrations rollback');

        echo 'Finished migrations rollback'.PHP_EOL;

        return true;

	}

	/**
	 * Error string
	 *
	 * @return	string	Error message returned as a string
	 */
	public function error_string()
	{
		return $this->_error_string;
	}

	// --------------------------------------------------------------------

	/**
	 * Retrieves list of available migration scripts
	 *
	 * @return	array	list of migration file paths sorted by version
	 */
	public function find_migrations()
	{
		$migrations = array();

		// Load all *_*.php files in the migrations path
		foreach (glob($this->_migration_path.'*_*.php') as $file)
		{
			$name = basename($file, '.php');

			// Filter out non-migration files
			if (preg_match($this->_migration_regex, $name))
			{
				$number = $this->_get_migration_number($name);

				// There cannot be duplicate migration numbers
				if (isset($migrations[$number]))
				{
					$this->_error_string = sprintf($this->lang->line('migration_multiple_version'), $number);
					show_error($this->_error_string);
				}

                $fileExplode = explode('/', $file);
                $lastName = end($fileExplode);
                $fileExplode = explode('\\', $lastName);
                $lastName = end($fileExplode);

				$migrations[] = $lastName;
			}
		}

		ksort($migrations);
		return $migrations;
	}

	// --------------------------------------------------------------------

	/**
	 * Extracts the migration number from a filename
	 *
	 * @param	string	$migration
	 * @return	string	Numeric portion of a migration filename
	 */
	protected function _get_migration_number($migration)
	{
		return sscanf($migration, '%[0-9]+', $number)
			? $number : '0';
	}

	// --------------------------------------------------------------------

	/**
	 * Extracts the migration class name from a filename
	 *
	 * @param	string	$migration
	 * @return	string	text portion of a migration filename
	 */
	protected function _get_migration_name($migration)
	{
		$parts = explode('_', $migration);
		array_shift($parts);
		return implode('_', $parts);
	}

	// --------------------------------------------------------------------

    /**
     * Retrieves current schema version
     *
     * @return array already runned migrations
     */
	protected function _get_runned_migrations(): array
	{
		$return = $this->db->select('migration')->get($this->_migration_table)->result_array();
        if (!$return){
            return [];
        }

        return array_column($return,"migration");

    }

	protected function _get_runned_migrations_by_batch(int $batch): array
	{
		$return = $this->db->select('migration')->where('batch', $batch)->order_by('id', 'DESC')->get($this->_migration_table)->result_array();
        if (!$return){
            return [];
        }

        return array_column($return,"migration");

    }

	protected function _getLastBatch(): int
	{
		$last = $this->db->select('batch')->order_by('batch', 'DESC')->limit(1)->get($this->_migration_table)->row();
        if (!$last){
            return 0;
        }
        return (int)$last->batch;
	}

	// --------------------------------------------------------------------

	/**
	 * Stores the current schema version
	 *
	 * @param	string	$migration	Migration reached
	 * @return	void
	 */
	protected function _update_version($migration, $batch)
	{
		$this->db->insert($this->_migration_table, array(
			'migration' => $migration,
            'batch' => $batch
		));
	}

	protected function _delete_version($migration)
	{

        $this->db->where('migration', $migration);
        $this->db->delete($this->_migration_table);

	}

	// --------------------------------------------------------------------

	/**
	 * Enable the use of CI super-global
	 *
	 * @param	string	$var
	 * @return	mixed
	 */
	public function __get($var)
	{
		return get_instance()->$var;
	}

}
