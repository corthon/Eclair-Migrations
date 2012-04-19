<?php
/**
 * SchemaMigration Model
 *
 * ActiveRecord model for managing migration records. Capable of installing itself on a clean database.
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

class SchemaMigration extends AppModel {
	public $name = "SchemaMigration";
	
	public function isInstalled($datasource) {
		// Query to determine whether table exists.
		$prefix = $this->tablePrefix ? $this->tablePrefix : $datasource->config['prefix'];
		$query = "SHOW TABLES LIKE '{$prefix}{$this->useTable}';";
		$result = $datasource->query($query);
		
		// If the query returns nothing, we need to roll out...
		return !empty($result);
	}
	
	public function install($datasource) {
		$result = true;
		
		// If this model isn't installed, giddy-up.
		if (!$this->isInstalled($datasource)) {
			// Define the table creation query.
			$prefix = $this->tablePrefix ? $this->tablePrefix : $datasource->config['prefix'];
			$query = "CREATE TABLE `{$prefix}{$this->useTable}` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `migration_id` char(14) NOT NULL DEFAULT '',
			  `created` datetime DEFAULT NULL,
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `migration_id` (`migration_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
		
			// Make sure that the query succeeds.
			$result = $datasource->query($query);
		}
		
		return $result;
	}
}