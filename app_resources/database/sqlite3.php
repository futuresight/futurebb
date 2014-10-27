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
			if (strpos(strtoupper($val->type), 'ENUM') === 0 || strpos(strtoupper($val->type), 'SET') === 0) {
				$field .= ' TEXT';
			} else if (strstr($val->type, 'INT')) {
				$field .= ' INTEGER';
			} else {
				$field .= ' ' . $val->type;
			}
			
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
	
	function add_field($table, DBField $field, $after) {
		if ($this->field_exists($table, $field->name)) {
			return true;
		}
		
		$default = '';
		if ($field->default_val != null) {
			if (stristr($field->type, 'int')) {
				$default = ' DEFAULT ' . $field->default_val;
			} else {
				$default = ' DEFAULT \'' . $this->escape($field->default_val) . '\'';
			}
		}
		
		$field = $field->name . ' ';
		if (strpos(strtoupper($val->type), 'ENUM') === 0 || strpos(strtoupper($val->type), 'SET') === 0) {
			$field .= ' TEXT';
		} else if (strstr($val->type, 'INT')) {
			$field .= ' INTEGER';
		} else {
			$field .= ' ' . $field->type;
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
		
		$field .= ' ' .  $default . ' AFTER ' . $after;
		
		$q = 'ALTER TABLE `' . $this->prefix . $table . '` ADD ' . $field;
		return ($this->query($q) or enhanced_error('Failed to add field<br />' . $q, true));
	}
	
	function truncate($table) {
		return ($this->query('DELETE FROM `' . $this->prefix . $table . '`') or enhanced_error('Failed to truncate table'));
	}
}