<?php
/**
 * Eclair Migration Shell
 *
 * Basic functionality for migrating databases between schema versions.
 *
 * Copyright 2012, Bret Barkelew
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2012, Bret Barkelew
 * @link          http://www.corthon.com
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

if (!defined('MIGRATION_DIR'))
	define('MIGRATION_DIR', ROOT . DS . APP_DIR . DS . 'Migration');
	
class MigrationShell extends AppShell {
	public $uses = array('SchemaMigration');
	
	private $_ds;
	
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		
		$connectionOption = array(
	    'short' => 'c',
	    'help' => 'Database connection to use.',
	    'default' => 'default'
		);
		
		$parser->addSubcommand('create', array(
			'help' => 'Create a new migration file from a template.',
			'parser' => array(
				'arguments' => array(
					'name' => array(
						'help' => 'Name of the migration class and filename (i.e. AddAvailabilityFlagToProducts).',
						'required' => true
					)
				)
			)
		))->addSubcommand('run', array(
			'help' => 'Execute a single migration by migration ID.',
			'parser' => array(
				'arguments' => array(
					'id' => array(
						'help' => 'ID of the migration to be executed. In 98765432987654-SampleMigration.php, \'98765432987654\' is the ID.',
						'required' => true
					)
				),
				'options' => array(
					'connection' => $connectionOption
				)
			)
		))->addSubcommand('upgradeAll', array(
			'help' => 'Executes all migrations more recent than the current database.',
			'parser' => array(
				'options' => array(
					'connection' => $connectionOption
				)
			)
		));
		
		return $parser;
	}
	
	public function startup() {
		// Make sure that the banner displays itself.
		parent::startup();
		
		// If this command is not in the whitelist
		// force database initialization.
		$whitelist = array('create');
		if (!in_array($this->command, $whitelist)) {
			// Assign the requested datasource.
			$this->_ds = ConnectionManager::getDataSource($this->params['connection']);
		
			// Make sure that the SchemaMigration model is installed on the current datasource.
			if (!$this->SchemaMigration->install($this->_ds)) {
				$this->out('<error>Fatal Error:</error> Failed to install the SchemaMigration model!');
				exit();
			}
			
			// Make sure that the SchemaMigration model is configured to current datasource.
			$this->SchemaMigration->setDataSource($this->params['connection']);
		}
	}
	
	/*
	* Create
	* Constructs a new migration file from a pre-defined template and
	* save it into the Migrations directory.
	*/
	public function create() {
		// Determine the migration name.
		$migrationName = $this->args[0];
		// Determine the migration time.
		$migrationId = date('Ymdhis');
		
		//
		// Make sure that no migration name is duplicated.
		$migrationFiles = scandir(MIGRATION_DIR);
		// Cycle through each file.
		foreach ($migrationFiles as $file) {
			// Obtain the migration ID from the file name.
			// If this file doesn't match the expected value, continue.
			$matchArray = array();
			if (!preg_match('/([0-9]{14})\-(.+)\.php/', $file, $matchArray)) continue;
			$matchArray = array_combine(array('file_name', 'migration_id', 'migration_name'), $matchArray);
			
			// If this migration name is already used, fail.
			if ($matchArray['migration_name'] == $migrationName) {
				$this->out("<error>Error:</error> Migration '$migrationName' already exists!");
				return false;
			}
		}
		
		// Build the template string.
		$templateString = "<?php\n" .
		"class {$migrationName}Migration {\n" .
		"	public function upgrade(\$datasource) {\n" .
		"		// Code to perform the migration on the database.\n" .
		"	}\n" .
    "\n" .
		"	public function downgrade(\$datasource) {\n" .
		"		// Code to undo the migration from the database.\n" .
		"	}\n" .
		"}\n";
		
		// Attempt to save the file.
		$migrationPath = MIGRATION_DIR . "/$migrationId-$migrationName.php";
		if (!file_put_contents($migrationPath, $templateString)) {
			$this->out('<error>Error:</error> Failed to create the new migration file!');
			return;
		}
		
		$this->out("Created migration file at: '$migrationPath'");
	}

	/*
	* Run
	* Executes a single migration on the current datasource.
	*/
	public function run() {
		// Get the ID of the migration to be executed.
		$migrationId = $this->args[0];
		
		// First, make sure that this migration has not already been executed.
		if ($this->SchemaMigration->find('first', array('conditions' => array('migration_id' => $migrationId)))) {
			$this->out("<warning>Warning:</warning> Migration '$migrationId' has already been executed!");
			return false;
		}
		
		// Locate the file and determine the migration name.
		$migrationFiles = scandir(MIGRATION_DIR);
		$matchArray = array();
		foreach ($migrationFiles as $file)
			if (preg_match("/$migrationId\-(.+)\.php/", $file, $matchArray)) break;
		if (empty($matchArray) || !file_exists(MIGRATION_DIR . "/{$matchArray[0]}")) {
			$this->out("<error>Error:</error> Migration '$migrationId' cannot be found!");
			return false;
		}
		$matchArray = array_combine(array('file_name', 'migration_name'), $matchArray);
		
		// Load the file, instantiate the class, and attempt to execute the migration.
		$migrationPath = MIGRATION_DIR . "/{$matchArray['file_name']}";
		$migrationClass = "{$matchArray['migration_name']}Migration";
		include_once($migrationPath);
		$migrationObject = new $migrationClass;
		try {
			$migrationObject->upgrade($this->_ds);
		} catch (Exception $e) {
			$this->out("<error>Error:</error> " . $e->getMessage());
			return false;
		}
		
		// If we've made it this far, add a record of the migration.
		$this->SchemaMigration->create(array('migration_id' => $migrationId));
		$this->SchemaMigration->save();
		
		$this->out("<info>Migration Applied:</info> {$matchArray['file_name']}");
		return true;
	}

	/*
	* UpgradeAll
	* Executes all migrations more recent than the current database.
	*/
	public function upgradeAll() {
		// First, attempt to determine the last executed migration.
		$lastMigrationId = $this->SchemaMigration->field('migration_id', null, 'migration_id DESC');
		if (!$lastMigrationId) $lastMigrationId = 0;
		
		// Next, retrive the list of files from the Migrations directory.
		$migrationFiles = scandir(MIGRATION_DIR);
		
		// Cycle through each file.
		foreach ($migrationFiles as $file) {
			// Obtain the migration ID from the file name.
			// If this file doesn't match the expected value, continue.
			$matchArray = array();
			if (!preg_match('/([0-9]{14})\-(.+)\.php/', $file, $matchArray)) continue;
			$matchArray = array_combine(array('file_name', 'migration_id', 'migration_name'), $matchArray);
			
			// If this migration is less recent than the last, skip it.
			if ($lastMigrationId >= $matchArray['migration_id']) continue;
			
			//
			// Apply the current migration.
			$this->out("Attempting to apply migration '{$matchArray['migration_name']}'...");
			// Set the argument so it may be passed to the 'run' function.
			$this->args[0] = $matchArray['migration_id'];
			// Attempt to apply the migration and terminate if failed.
			if (!$this->run()) return false;
		}
		
		$this->out("<info>Database successfully migrated!</info>");
		return true;
	}
}