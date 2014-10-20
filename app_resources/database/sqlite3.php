<?php
/*
FutureBB Database Spec - DO NOT REMOVE
Name<SQLite 3>
Extension<sqlite3>
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
		return $this->link->version();
	}
	
	function name() {
		return 'SQLite 3';
	}
	
	function error() {
		return $this->link->lastErrorMsg();
	}
	
	function num_rows($result) {
		return $result->numRows();
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
		return $link->escapeString($str);
	}
	
	function query($q) {
		if (gettype($q) != 'string') {
			trigger_error('Invalid data type for query. Expected string, got ' . gettype($q) . '.');
			echo "\n\n";
			print_r($q);
			echo "\n\n" . 'Debug info:';
			print_r(debug_backtrace()); die;
		}
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
			if (strpos($val->type, 'ENUM') === 0 || strpos($val->type, 'SET') === 0) {
				$field .= 'TEXT';
			} else {
				$field .= ' ' . $val->type;
			}
			if ($val->default_val != null) {
				$field .= ' DEFAULT ' . $val->default_val;
			}
			if (!empty($val->extra)) {
				foreach ($val->extra as $extra) {
					if ($extra == 'AUTO_INCREMENT') {
						$extra = 'AUTOINCREMENT';
					}
				}
				$field .= ' ' . implode(' ', $val->extra);
			}
			if ($val->db_key != null) {
				if ($val->db_key == 'UNIQUE') {
					$field .= ' UNIQUE';
				} else {
					$field .= ' ' . $val->db_key . ' KEY';
				}
			}
			$fields[] = $field;
		}
		$query .= implode(',', $fields) . ');';
		$this->query($query) or enhanced_error('Failed to create table ' . $table->name . "\n" . $query, true);
	}
}