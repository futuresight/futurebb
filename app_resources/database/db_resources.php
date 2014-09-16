<?php
//Experimental Database Abstraction Layer

//simple insert
class DBInsert {
	var $table;
	var $fields;
	var $error;
	
	function __construct($table, $fields, $error) {
		$this->table = $table;
		$this->fields = $fields;
		$this->error = $error;
	}
	
	function commit() {
		global $db;
		$keys = array();
		foreach ($this->fields as $key => $val) {
			$keys[] = $key;
		}
		$vals = array();
		foreach ($this->fields as $key => $val) {
			if ($val === intval($val)) {
				$vals[] = intval($val);
			} else {
				$vals[] = '\'' . $db->escape($val) . '\'';
			}
		}
		$db->query('INSERT INTO `' . $db->prefix . $this->table . '`(' . implode(',', $keys) . ') VALUES(' . implode(',', $vals) . ')') or enhanced_error($this->error, true);
	}
}

//simple update
class DBUpdate {
	var $table;
	var $error;
	var $set;
	var $where;
	function __construct($table, $set, $where, $error) {
		$this->table = $table;
		$this->error = $error;
		$this->set = $set;
		$this->where = $where;
	}
	
	function commit() {
		global $db;
		
		$set_sql = '';
		foreach ($this->set as $key => $val) {
			if ($set_sql != '') {
				$set_sql .= ',';
			}
			if ($val == (string)intval($val)) {
				$set_sql .= $key . '=' . intval($val);
			} else {
				$set_sql .= $key . '=\'' . $db->escape($val) . '\'';
			}
		}
		$db->query('UPDATE `' . $db->prefix . $this->table . '` SET ' . $set_sql . ' WHERE ' . $this->where) or enhanced_error($this->error . '<br />Query: ' . $set_sql, true);
	}
}

//simple delete
class DBDelete {
	var $table;
	var $error;
	var $where;
	function __construct($table, $where, $error) {
		$this->table = $table;
		$this->error = $error;
		$this->where = $where;
	}
	
	function commit() {
		global $db;
		$db->query('DELETE FROM `' . $db->prefix . $this->table . '` WHERE ' . $this->where) or enhanced_error($this->error, true);
	}
}

//select - a lot more complex
class DBSelect {
	var $table;
	var $fields;
	var $where = '';
	var $error;
	var $joins = array();
	var $order = '';
	var $limit = '';
	var $table_as = '';
	
	function __construct($table, $fields, $where, $error) {
		$this->table = $table;
		$this->fields = $fields;
		$this->where = $where;
		$this->error = $error;
	}
	
	function add_join($join) {
		$this->joins[] = $join;
	}
	
	function set_order($order_by) {
		$this->order = $order_by;
	}
	
	function set_limit($limit) {
		$this->limit = $limit;
	}
	
	function table_as($table_as) {
		$this->table_as = $table_as;
	}
	
	function commit() {	
		global $db;	
		$sql = 'SELECT ';
		$sql .= implode(',', $this->fields);
		$sql .= ' FROM `' . $db->prefix . $this->table . '` ';
		if ($this->table_as != '') {
			$sql .= ' AS ' . $this->table_as;
		}
		if (!empty($this->joins)) {
			foreach ($this->joins as $join) {
				$sql .= ' ' . $join->type() . ' JOIN `' . $db->prefix . $join->table() . '` AS ' . $join->join_as() . ' ON ' . $join->getOn();
			}
		}
		if ($this->where != '') {
			$sql .= ' WHERE ' . $this->where;
		}
		if ($this->order != '') {
			$sql .= ' ORDER BY ' . $this->order;
		}
		if ($this->limit != '') {
			$sql .= ' LIMIT ' . $this->limit;
		}
		$result = $db->query($sql) or enhanced_error($this->error . '<br />Query: ' . $sql, true);
		return $result;
	}
}

class DBLeftJoin extends DBJoin {
}

class DBJoin {
	var $table;
	var $on;
	var $join_as;
	var $type;
	
	function __construct($table, $join_as, $on, $type = 'left') {
		$this->table = $table;
		$this->join_as = $join_as;
		$this->on = $on;
		$this->type = $type;
	}
	
	function table() {
		return $this->table;
	}
	
	function getOn() {
		return $this->on;
	}
	
	function join_as() {
		return $this->join_as;
	}
	
	function type() {
		return $this->type;
	}
}

//table creation tools
class DBTable {
	public $fields = array();
	public $name;
	
	function __construct($name) {
		$this->name = $name;
	}
	
	function add_field($field) {
		$this->fields[] = $field;
	}
	
	function commit() {
		global $db;
		$db->add_table($this);
	}
}

class DBField {
	public $name;
	public $type;
	public $default_val = null;
	public $db_key = null;
	public $extra = array();
	
	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
	}
	
	function set_default($default) {
		$this->default_val = $default;
	}
	
	function add_extra($text) {
		$this->extra[] = $text;
	}
	
	function add_key($key) {
		$this->db_key = $key;
	}
}