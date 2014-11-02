<?php
/*
FutureBB Database Spec - DO NOT REMOVE
Name<SQLite 3>
Extension<sqlite3>
*/

/* to-do:
* drop_field
*/
class Database {
	public $link;
	var $prefix;
	var $errorno;
	
	function __construct(array $info) {
		//error('Invalid table prefix; contact board administrator', __FILE__, __LINE__, '');
		$this->prefix = $info['prefix'];
		if (file_exists($info['name']) && !is_readable($info['name'])) {
			error('Unreadable database');
		}
		if (!file_exists($info['name']) && !writable($info['name'])) {
			error('Could not write to database');
		}
		$this->link = @new SQLite3($info['name']);
		if (!$this->link && !isset($info['hide_errors'])) {
			error('Failed to open SQLite database');
		}
	}
	
	function version() {
		$ver = $this->link->version();
		return $ver['versionString'];
	}
	
	function name() {
		return 'SQLite 3';
	}
	
	function error() {
		return $this->link->lastErrorMsg();
	}
	
	function num_rows(SQLite3Result $result) {
		$i = 0;
		while ($result->fetchArray()) {
			$i++;
		}
		$result->reset();
		return $i;
	}
	
	function escape($str) {
		if (gettype($str) == 'integer') {
			$str = (string)$str;
		} else if (gettype($str) != 'string') {
			trigger_error('Invalid data type for escape. Expected string, got ' . gettype($str) . '.');
			echo "\n\n";
			print_r($str);
			echo "\n\n" . 'Debug info:';
			print_r(debug_backtrace()); die;
		}
		return $this->link->escapeString($str);
	}
	
	function query($q) {
		if (gettype($q) != 'string') {
			trigger_error('Invalid data type for query. Expected string, got ' . gettype($q) . '.');
			echo "\n\n";
			print_r($q);
			echo "\n\n" . 'Debug info:';
			print_r(debug_backtrace()); die;
		}
		$q = str_replace('RAND', 'RANDOM', $q);
		return $this->link->query(str_replace('#^', $this->prefix, $q));
	}
	
	function fetch_assoc($result) {
		return $result->fetchArray(SQLITE3_ASSOC);
	}
	
	function fetch_row($result) {
		return $result->fetchArray(SQLITE3_NUM);
	}
	
	function insert_id() {
		$this->link->lastInsertRowId();
	}
	
	function close() {
		$this->link->close();
	}
	
	function connect_error() {
		return 'Unavailable';
	}
	
	function add_table($table) {
		$query = 'CREATE TABLE `' . $this->prefix . $table->name . '`(';
		$fields = array();
		foreach ($table->fields as $val) {
			$field = $val->name;
			
			$field .= ' ' . self::parse_datatype($val->type);
			
			if ($val->db_key != null) {
				if ($val->db_key == 'UNIQUE') {
					$field .= ' UNIQUE';
				} else {
					$field .= ' ' . $val->db_key . ' KEY';
				}
			}
			
			if ($val->default_val != null) {
				$field .= ' DEFAULT ' . $val->default_val;
			}
			if (!empty($val->extra)) {
				foreach ($val->extra as $key => &$extra) {
					if (strtoupper($extra) == 'AUTO_INCREMENT') {
						$extra = 'AUTOINCREMENT';
					}
					if (stristr($extra, 'NULL')) {
						$val->extra[sizeof($val->extra)] = $extra;
						unset($val->extra[$key]);
					}
				}
				$field .= ' ' . implode(' ', $val->extra);
			}
			
			$fields[] = $field;
		}
		$query .= implode(',', $fields) . ');';
		$this->query($query) or enhanced_error('Failed to create table ' . $table->name . "\n" . $query, true);
	}
	
	function alter_field($table, DBField $field, $after = '') {
		//no need in SQLite
		return;
	}
	
	function add_field($table, DBField $field, $after) {
		if ($this->field_exists($table, $field->name)) {
			return true;
		}
		
		$default = '';
		if ($field->default_val == null) {
			$default = ' DEFAULT ' . $field->default_val;
		}
		
		$fieldtext = $field->name . ' ';
		$fieldtext .= ' ' . self::parse_datatype($field->type);
		
		if ($field->db_key != null) {
			if ($field->db_key == 'UNIQUE') {
				$field .= ' UNIQUE';
			} else {
				$field .= ' ' . $field->db_key . ' KEY';
			}
		}
		
		if (!empty($field->extra)) {
			foreach ($field->extra as $key => &$extra) {
				if (strtoupper($extra) == 'AUTO_INCREMENT') {
					$extra = 'AUTOINCREMENT';
				}
				if (stristr($extra, 'NULL')) {
					$val->extra[sizeof($field->extra)] = $extra;
					unset($val->extra[$key]);
				}
			}
			$fieldtext .= ' ' . implode(' ', $field->extra);
		}
				
		if ($default == '') {
			if (stristr($fieldtext, 'NULL') && !stristr($fieldtext, 'NOT NULL')) {
				$default = ' DEFAULT NULL';
			} else {
				if (stristr($field->type, 'INT')) {
					$default = ' DEFAULT 0';
				} else {
					$default = ' DEFAULT \'\'';
				}
			}
		}
		
		$fieldtext .= $default;
		
		//$fieldtext .= ' ' .  $default . ' AFTER ' . $after;
		
		$q = 'ALTER TABLE `' . $this->prefix . $table . '` ADD ' . $fieldtext;
		return ($this->query($q) or enhanced_error('Failed to add field<br />' . $q, true));
	}
	
	function truncate($table) {
		return ($this->query('DELETE FROM `' . $this->prefix . $table . '`') or enhanced_error('Failed to truncate table'));
	}
	
	function table_exists($table) {
		$result = $this->query('SELECT 1 FROM sqlite_master WHERE name=\'' . $this->escape($this->prefix . $table) . '\' AND type=\'table\'') or enhanced_error('Failed to search tables', true);
		
		return ($this->num_rows($result) > 0);
	}
	
	function field_exists($table, $field) {
		$result = $this->query('SELECT sql FROM sqlite_master WHERE name=\'' . $this->escape($this->prefix . $table) . '\' AND type=\'table\'') or enhanced_error('Failed to search tables', true);
		if (!$this->num_rows($result)) {
			return false;
		}
		list($sql) = $this->fetch_row($result);
				
		return preg_match('%(\(|,|[\r\n]|"| )' . preg_quote($field, '%') . '("| )%', $sql);
	}
	
	function rename_table($oldname, $newname) {
		if ($this->table_exists($oldname) && !$this->table_exists($oldname)) {
			return true;
		}
		
		return ($this->query('ALTER TABLE `' . $this->prefix . $oldname . '` RENAME TO `' . $this->prefix . $newname . '`') or enhanced_error('Failed to rename table', true));
	}
	
	function drop_table($table) {
		if (!$this->table_exists($table)) {
			return;
		}
		
		$this->query('DROP TABLE `'.  $this->prefix . $table . '`') or enhanced_error('Failed to drop table', true);
	}
	
	function index_exists($table, $index) {
		$result = $this->query('SELECT 1 FROM sqlite_master WHERE tbl_name=\'' . $this->escape($this->prefix . $table) . '\' AND name=\'' . $this->escape($this->prefix . $table . '_' . $index) . '\' AND type=\'index\'') or enhanced_error('Failed to find index', true);
		return ($this->num_rows($result) > 0);
	}
	
	function add_index($table, $name, $fields, $unique) {
		if ($this->index_exists($table, $name)) {
			return;
		}
		
		$q = 'CREATE ' . ($unique ? 'UNIQUE ' : '') . 'INDEX ' . $this->prefix . $table . '_' . $name . ' ON ' . $this->prefix . $table . '(' . implode(',', $fields) . ')';
		
		return ($this->query($q) or enhanced_error('Failed to add index<br />' . $q, true));
	}
	
	function drop_index($table, $name) {
		if (!$this->index_exists($table, $name)) {
			return;
		}
		
		return ($this->query('DROP INDEX ' . $this->prefix . $table . '_' . $name) or enhanced_error('Failed to drop index'));
	}
	
	function drop_field($table, $field) {
		if (!$this->field_exists($table, $field)) {
			return;
		}
		
		$result = $this->query('SELECT sql FROM sqlite_master WHERE name=\'' . $this->escape($this->prefix . $table) . '\' AND type=\'table\'') or enhanced_error('Failed to search tables', true);
		list($sql) = $this->fetch_row($result);
		$fields = explode(',', preg_replace('%CREATE TABLE ("|`)' . $this->prefix . $table . '\1 ?\(%', '', $sql));
		$plain_fields = array();
		foreach($fields as $key => &$cur_field) {
			$cur_field = preg_replace('%\)$%', '', trim($cur_field));
			if (strpos($cur_field, $field) === 0) {
				unset($fields[$key]);
			} else {
				preg_match('%^"?(.*?)"? %', $cur_field, $matches);
				if (!in_array($matches[1], array('PRIMARY', 'UNIQUE'))) {
					$plain_fields[] = $matches[1];
				}
			}
		}
		
		$rand = rand(1, 1000000000);
		$this->query('CREATE TEMPORARY TABLE temp_' . $rand . '(' . implode(',', $fields) . ')') or enhanced_error('Failed to create temporary table<br />' . $sql, true);
		$result = $this->query('INSERT INTO temp_' . $rand . ' SELECT ' . implode(',', $plain_fields) . ' FROM ' . $this->prefix . $table) or enhanced_error('Failed to move into temporary table', true);
		$result->finalize();
		$this->drop_table($table);
		$this->query('CREATE TABLE ' . $this->prefix . $table . '(' . implode(',', $fields) . ')') or enhanced_error('Failed to create new table', true);
		$this->query('INSERT INTO ' . $this->prefix . $table . ' SELECT ' . implode(',', $plain_fields) . ' FROM temp_' . $rand) or enhanced_error('Failed to move out of temporary table', true);
		$this->query('DROP TABLE temp_' . $rand) or enhanced_error('Failed to drop temp table', true);
	}
	
	private static function parse_datatype($type) {
		if (strpos(strtoupper($type), 'ENUM') === 0 || strpos(strtoupper($type), 'SET') === 0) {
			$newtype = 'TEXT';
		} else if (strstr($type, 'INT')) {
			$newtype = 'INTEGER';
		} else {
			$newtype = $type;
		}
		return $newtype;
	}
}