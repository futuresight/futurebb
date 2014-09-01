<?php
class Database {
	public $link;
	var $prefix;
	var $errorno;
	
	function __construct(array $info) {
		//error('Invalid table prefix; contact board administrator', __FILE__, __LINE__, '');
		$this->prefix = $info['prefix'];
		$this->link = @sqlite_open($info['name']);
		if (!$this->link && !isset($info['hide_errors'])) {
			error('Failed to start database: ' . mysqli_connect_error());
		}
	}
	
	function version() {
		return 'Not available';
	}
	
	function name() {
		return 'SQLite';
	}
	
	function error() {
		return sqlite_error_string($this->errorno);
	}
	
	function num_rows($result) {
		return sqlite_num_rows($result);
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
		return sqlite_escape_string($str);
	}
	
	function query($q) {
		if (gettype($q) != 'string') {
			trigger_error('Invalid data type for query. Expected string, got ' . gettype($q) . '.');
			echo "\n\n";
			print_r($q);
			echo "\n\n" . 'Debug info:';
			print_r(debug_backtrace()); die;
		}
		$this->errorno = sqlite_last_error($this->link);
		return sqlite_query($this->link,str_replace('#^', $this->prefix, $q));
	}
	
	function fetch_assoc($result) {
		return sqlite_fetch_array($result);
	}
	
	function fetch_row($result) {
		return sqlite_fetch_array($result);
	}
	
	function insert_id() {
		return sqlite_last_insert_rowid($this->link);
	}
	
	function close() {
		sqlite_close($this->link);
	}
	
	function connect_error() {
		return sqlite_connect_error();
	}
	
	function add_table($table) {
		$query = 'CREATE TABLE `' . $this->prefix . $table->name . '`(';
		$fields = array();
		foreach ($table->fields as $val) {
			$field = $val->name;
			$field .= ' ' . $val->type;
			if ($val->default_val != null) {
				$field .= ' DEFAULT ' . $val->default_val;
			}
			if (!empty($val->extra)) {
				$field .= ' ' . implode(' ', $val->extra);
			}
			if ($val->db_key != null) {
				$field .= ' ' . $val->db_key . ' KEY';
			}
			$fields[] = $field;
		}
		$query .= implode(',', $fields) . ');';
		$this->query($query) or enhanced_error('Failed to create table ' . $table->name, true);
	}
}