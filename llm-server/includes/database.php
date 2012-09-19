<?php

class database {
	
	/**
	@var string Internal variable to hold the query sql*/
	var $_sql = '';
	/**
	@var int Internal variable to hold the database error number*/
	var $_errorNum = 0;
	/**
	@var string Internal variable to hold the database error message*/
	var $_errorMsg = '';
	/**
	@var string Internal variable to hold the prefix used on all database tables*/
	var $_table_prefix = '';
	/**
	@var Internal variable to hold the connector resource*/
	var $_resource = '';
	/**
	@var Internal variable to hold the last query cursor*/
	var $_cursor = null;
	/**
	@var boolean Debug option*/
	var $_debug = 0;
	/**
	@var int The limit for the query*/
	var $_limit = 0;
	/**
	@var int The for offset for the limit*/
	var $_offset = 0;
	/**
	@var int A counter for the number of queries performed by the object instance*/
	var $_ticker = 0;
	/**
	@var array A log of queries*/
	var $_log = null;
	/**
	@var string The null/zero date string*/
	var $_nullDate = '0000-00-00 00:00:00';
	/**
	@var string Quote for named objects*/
	var $_nameQuote = '`';
	/**
	* @var obj кэширование запросов базы данных
	**/
	var $dbcache = null;
	/**
	* Database object constructor
	* @param string Database host
	* @param string Database user name
	* @param string Database user password
	* @param string Database name
	* @param string Common prefix for all tables
	* @param boolean If true and there is an error, go offline
	*/
	function database($host = 'localhost',$user,$pass,$db = '',$table_prefix = '',$goOffline = true) {
		global $mosConfig_dbold;
		// perform a number of fatality checks, then die gracefully
		if(!function_exists('mysql_connect')) {
			$mosSystemError = 1;
			if($goOffline) {
				llmServer::showError("DBO::No mysql_connect function");
			}
		}
		
		if(phpversion() < '4.2.0') {
			if(!($this->_resource = mysql_connect($host,$user,$pass))) {
				$mosSystemError = 2;
				if($goOffline) {
					llmServer::showError("DBO::Cannot connect to DB");
				}
			}
			
		} else {
			if(!($this->_resource = mysql_connect($host,$user,$pass,true))) {
				$mosSystemError = 2;
				if($goOffline) {
					llmServer::showError("DBO::Cannot connect to DB");
				}
			}
			
		}
		if($db != '' && !mysql_select_db($db,$this->_resource)) {
			$mosSystemError = 3;
			if($goOffline) {
				llmServer::showError("DBO::Cannot select DB ({$db})");
			}
		}
		$this->_table_prefix = $table_prefix;
		@mysql_query('SET NAMES \'cp1251\'',$this->_resource);

		$this->_cursor = mysql_query( "set session character_set_server=cp1251;", $this->_resource );
		$this->_cursor = mysql_query( "set session character_set_database=cp1251;", $this->_resource );
		$this->_cursor = mysql_query( "set session character_set_connection=cp1251;", $this->_resource );
		$this->_cursor = mysql_query( "set session character_set_results=cp1251;", $this->_resource );
		$this->_cursor = mysql_query( "set session character_set_client=cp1251;", $this->_resource );

		$this->_ticker = 0;
		$this->_log = array();
	}
	/**
	* @param int
	*/
	function debug($level) {
		$this->_debug = intval($level);
	}
	/**
	* @return int The error number for the most recent query
	*/
	function getErrorNum() {
		return $this->_errorNum;
	}
	/**
	* @return string The error message for the most recent query
	*/
	function getErrorMsg() {
		return str_replace(array("\n","'"),array('\n',"\'"),$this->_errorMsg);
	}
	/**
	* Get a database escaped string
	* @return string
	*/
	function getEscaped( $text, $extra = false ) {
		// Use the appropriate escape string depending upon which version of php
		// you are running
		if (version_compare(phpversion(), '4.3.0', '<')) {
			$string = mysql_escape_string($text);
		} else 	{
			$string = mysql_real_escape_string($text, $this->_resource);
		}
		if ($extra) {
			$string = addcslashes( $string, '%_' );
		}
		return $string;
	}

	/**
	 * Get a quoted database escaped string
	 *
	 * @param	string	A string
	 * @param	boolean	Default true to escape string, false to leave the string unchanged
	 * @return	string
	 * @access public
	 */
	function Quote( $text, $escaped = true )
	{
		return '\''.($escaped ? $this->getEscaped( $text ) : $text).'\'';
	}
	/**
	* Quote an identifier name (field, table, etc)
	* @param string The name
	* @return string The quoted name
	*/
	function NameQuote($s) {
		$q = $this->_nameQuote;
		if(strlen($q) == 1) {
			return $q.$s.$q;
		} else {
			return $q{0}.$s.$q{1};
		}
	}
	/**
	* @return string The database prefix
	*/
	function getPrefix() {
		return $this->_table_prefix;
	}
	/**
	* @return string Quoted null/zero date string
	*/
	function getNullDate() {
		return $this->_nullDate;
	}
	/**
	* Sets the SQL query string for later execution.
	*
	* This function replaces a string identifier <var>$prefix</var> with the
	* string held is the <var>_table_prefix</var> class variable.
	*
	* @param string The SQL query
	* @param string The offset to start selection
	* @param string The number of results to return
	* @param string The common table prefix
	*/
	function setQuery($sql,$offset = 0,$limit = 0,$prefix = '#__') {
		$this->_sql = $this->replacePrefix($sql,$prefix);
		$this->_limit = intval($limit);
		$this->_offset = intval($offset);
	}

	/**
	* This function replaces a string identifier <var>$prefix</var> with the
	* string held is the <var>_table_prefix</var> class variable.
	*
	* @param string The SQL query
	* @param string The common table prefix
	* @author thede, David McKinnis
	*/
	function replacePrefix($sql,$prefix = '#__') {
		$sql = trim($sql);

		$escaped = false;
		$quoteChar = '';

		$n = strlen($sql);

		$startPos = 0;
		$literal = '';
		while($startPos < $n) {
			$ip = strpos($sql,$prefix,$startPos);
			if($ip === false) {
				break;
			}

			$j = strpos($sql,"'",$startPos);
			$k = strpos($sql,'"',$startPos);
			if(($k !== false) && (($k < $j) || ($j === false))) {
				$quoteChar = '"';
				$j = $k;
			} else {
				$quoteChar = "'";
			}

			if($j === false) {
				$j = $n;
			}

			$literal .= str_replace($prefix,$this->_table_prefix,substr($sql,$startPos,$j -
				$startPos));
			$startPos = $j;

			$j = $startPos + 1;

			if($j >= $n) {
				break;
			}

			// quote comes first, find end of quote
			while(true) {
				$k = strpos($sql,$quoteChar,$j);
				$escaped = false;
				if($k === false) {
					break;
				}
				$l = $k - 1;
				while($l >= 0 && $sql{$l} == '\\') {
					$l--;
					$escaped = !$escaped;
				}
				if($escaped) {
					$j = $k + 1;
					continue;
				}
				break;
			}
			if($k === false) {
				// error in the query - no end quote; ignore it
				break;
			}
			$literal .= substr($sql,$startPos,$k - $startPos + 1);
			$startPos = $k + 1;
		}
		if($startPos < $n) {
			$literal .= substr($sql,$startPos,$n - $startPos);
		}
		return $literal;
	}
	/**
	* @return string The current value of the internal SQL vairable
	*/
	function getQuery() {
		return "<pre>".htmlspecialchars($this->_sql)."</pre>";
	}
	/**
	* Execute the query
	* @return mixed A database resource if successful, FALSE if not.
	*/
	function query() {
		if($this->_limit > 0 && $this->_offset == 0) {
			$this->_sql .= "\nLIMIT $this->_limit";
		} else
			if($this->_limit > 0 || $this->_offset > 0) {
				$this->_sql .= "\nLIMIT $this->_offset, $this->_limit";
			}
		if($this->_debug) {
			$this->_ticker++;
			$this->_log[] = $this->_sql;
		}
		$this->_errorNum = 0;
		$this->_errorMsg = '';
		$this->_cursor = mysql_query($this->_sql,$this->_resource);

		if(!$this->_cursor) {

			$this->_errorNum = mysql_errno($this->_resource);
			$this->_errorMsg = mysql_error($this->_resource)." SQL=$this->_sql";

				//echo "<pre>" . $this->_sql . "</pre>\n";
				echo $this->_errorMsg."<br>";
				if(function_exists('debug_backtrace')) {
					foreach(debug_backtrace() as $back) {
						if(@$back['file']) {
							echo '<br />'.$back['file'].':'.$back['line'];
						}
					}
				}
			
			die;//opa
			
			return false;
			
			
		}
		return $this->_cursor;
	}

	/**
	* @return int The number of affected rows in the previous operation
	*/
	function getAffectedRows() {
		return mysql_affected_rows($this->_resource);
	}

	/**
	* @return int The number of rows returned from the most recent query.
	*/
	function getNumRows($cur = null) {
		return mysql_num_rows($cur?$cur:$this->_cursor);
	}

	/**
	* This method loads the first field of the first row returned by the query.
	*
	* @return The value returned in the query or null if the query failed.
	*/
	function loadResult() {
		if(!($cur = $this->query())) {
			return null;
		}
		$ret = null;
		if($row = mysql_fetch_row($cur)) {
			$ret = $row[0];
		}
		mysql_free_result($cur);
		return $ret;
	}
	/**
	* Load an array of single field results into an array
	*/
	function loadResultArray($numinarray = 0) {
		if(!($cur = $this->query())) {
			return null;
		}
		$array = array();
		while($row = mysql_fetch_row($cur)) {
			$array[] = $row[$numinarray];
		}
		mysql_free_result($cur);
		return $array;
	}
	/**
	* Load a assoc list of database rows
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	*/
	function loadAssocList($key = '') {
		if(!($cur = $this->query())) {
			return null;
		}
		$array = array();
		while($row = mysql_fetch_assoc($cur)) {
			if($key) {
				$array[$row[$key]] = $row;
			} else {
				$array[] = $row;
			}
		}
		mysql_free_result($cur);
		return $array;
	}
	/**
	* This global function loads the first row of a query into an object
	*
	* If an object is passed to this function, the returned row is bound to the existing elements of <var>object</var>.
	* If <var>object</var> has a value of null, then all of the returned query fields returned in the object.
	* @param string The SQL query
	* @param object The address of variable
	*/
	function loadObject(&$object) {
		if($object != null) {
			if(!($cur = $this->query())) {
				return false;
			}
			if($array = mysql_fetch_assoc($cur)) {
				mysql_free_result($cur);
				mosBindArrayToObject($array,$object,null,null,false);
				return true;
			} else {
				return false;
			}
		} else {
			if($cur = $this->query()) {
				if($object = mysql_fetch_object($cur)) {
					mysql_free_result($cur);
					return true;
				} else {
					$object = null;
					return false;
				}
			} else {
				return false;
			}
		}
	}
	/**
	* Load a list of database objects
	* @param string The field name of a primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	* If <var>key</var> is not empty then the returned array is indexed by the value
	* the database key.  Returns <var>null</var> if the query fails.
	*/
	function loadObjectList($key = '') {
		if(!($cur = $this->query())) {
			return null;
		}
		$array = array();
		while($row = mysql_fetch_object($cur)) {
			if($key) {
				$array[$row->$key] = $row;
			} else {
				$array[] = $row;
			}
		}
		mysql_free_result($cur);
		return $array;
	}
	/**
	* @return The first row of the query.
	*/
	function loadRow() {
		if(!($cur = $this->query())) {
			return null;
		}
		$ret = null;
		if($row = mysql_fetch_row($cur)) {
			$ret = $row;
		}
		mysql_free_result($cur);
		return $ret;
	}
	/**
	* Load a list of database rows (numeric column indexing)
	* @param int Value of the primary key
	* @return array If <var>key</var> is empty as sequential list of returned records.
	* If <var>key</var> is not empty then the returned array is indexed by the value
	* the database key.  Returns <var>null</var> if the query fails.
	*/
	function loadRowList($key = null) {
		if(!($cur = $this->query())) {
			return null;
		}
		$array = array();
		while($row = mysql_fetch_row($cur)) {
			if(!is_null($key)) {
				$array[$row[$key]] = $row;
			} else {
				$array[] = $row;
			}
		}
		mysql_free_result($cur);
		return $array;
	}

	/**
	* @param boolean If TRUE, displays the last SQL statement sent to the database
	* @return string A standised error message
	*/
	function stderr($showSQL = false) {
		return "DB function failed with error number $this->_errorNum".
			"<br /><font color=\"red\">$this->_errorMsg</font>".($showSQL?
			"<br />SQL = <pre>$this->_sql</pre>":'');
	}

	function insertid() {
		return mysql_insert_id($this->_resource);
	}

	function getVersion() {
		return mysql_get_server_info($this->_resource);
	}
	
	function ping() {
	
		return mysql_ping($this->_resource);
	}
}
?>
