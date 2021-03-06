<?php
/*
 FusionPBX
 Version: MPL 1.1

 The contents of this file are subject to the Mozilla Public License Version
 1.1 (the "License"); you may not use this file except in compliance with
 the License. You may obtain a copy of the License at
 http://www.mozilla.org/MPL/

 Software distributed under the License is distributed on an "AS IS" basis,
 WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 for the specific language governing rights and limitations under the
 License.

 The Original Code is FusionPBX

 The Initial Developer of the Original Code is
 Mark J Crane <markjcrane@fusionpbx.com>
 Portions created by the Initial Developer are Copyright (C) 2008-2010
 the Initial Developer. All Rights Reserved.

 Contributor(s):
 Mark J Crane <markjcrane@fusionpbx.com>
 */
 
//get the db connection information
	$sql = "";
	$sql .= "select * from v_database_connections ";
	$sql .= "where v_id = '$v_id' ";
	$sql .= "and database_connection_id = '".$_REQUEST['id']."' ";
	$prep_statement = $db->prepare($sql);
	$prep_statement->execute();
	$result = $prep_statement->fetchAll();
	foreach ($result as &$row) {
		$db_type = $row["db_type"];
		$db_host = $row["db_host"];
		$db_port = $row["db_port"];
		$db_name = $row["db_name"];
		$db_username = $row["db_username"];
		$db_password = $row["db_password"];
		$db_path = $row["db_path"];
		//$db_description = $row["db_description"];
		//echo "			<option value='".$row["database_connection_id"]."'>".$row["db_host"]." - ".$row["db_name"]."</option>\n";
		break;
	}
	
//unset the database connection
	unset($db);

if (!function_exists('get_db_field_names')) {
	function get_db_field_names($db, $table, $db_name='fusionpbx') {
		$query = sprintf('SELECT * FROM %s LIMIT 1', $table);
		foreach ($db->query($query, PDO::FETCH_ASSOC) as $row) {
			return array_keys($row);
		}

		// if we're still here, we need to try something else
		$fields 	= array();
		$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
		if ($driver == 'sqlite') {
			$query 		= sprintf("Pragma table_info(%s);", $table);
			$stmt 		= $db->prepare($query);
			$result 	= $stmt->execute();
			$rows 		= $stmt->fetchAll();
			//printf('<pre>%s</pre>', print_r($rows, true));
			$row_count 	= count($rows);
			//printf('<pre>%s</pre>', print_r($rows, true));
			for ($i = 0; $i < $row_count; $i++) {
				array_push($fields, $rows[$i]['name']);
			}
			return $fields;
		} else {
			$query 		= sprintf("SELECT * FROM information_schema.columns
			WHERE table_schema='%s' AND table_name='%s';"
			, $db_name, $table
			);
			$stmt 		= $db->prepare($query);
			$result 	= $stmt->execute();
			$rows 		= $stmt->fetchAll();
			$row_count 	= count($rows);
			//printf('<pre>%s</pre>', print_r($rows, true));
			for ($i = 0; $i < $row_count; $i++) {
				array_push($fields, $rows[$i]['COLUMN_NAME']);
			}
			return $fields;
		}
	}
}

if ($db_type == "sqlite") {
	if (!function_exists('phpmd5')) {
		function phpmd5($string) {
			return md5($string);
		}
	}

	if (!function_exists('phpunix_timestamp')) {
		function phpunix_timestamp($string) {
			return strtotime($string);
		}
	}

	if (!function_exists('phpnow')) {
		function phpnow() {
			//return date('r');
			return date("Y-m-d H:i:s");
		}
	}

	if (!function_exists('phpleft')) {
		function phpleft($string, $num) {
			return substr($string, 0, $num);
		}
	}

	if (!function_exists('phpright')) {
		function phpright($string, $num) {
			return substr($string, (strlen($string)-$num), strlen($string));
		}
	}

	if (!function_exists('phpsqlitedatatype')) {
		function phpsqlitedatatype($string, $field) {

			//--- Begin: Get String Between start and end characters -----
			$start = '(';
			$end = ')';
			$ini = stripos($string,$start);
			if ($ini == 0) return "";
			$ini += strlen($start);
			$len = stripos($string,$end,$ini) - $ini;
			$string = substr($string,$ini,$len);
			//--- End: Get String Between start and end characters -----

			$strdatatype = '';
			$stringarray = explode(',', $string);
			foreach($stringarray as $lnvalue) {

				//$strdatatype .= "-- ".$lnvalue ." ".strlen($lnvalue)." delim ".strrchr($lnvalue, " ")."---<br>";
				//$delimpos = stripos($lnvalue, " ");
				//$strdatatype .= substr($value,$delimpos,strlen($value))." --<br>";

				$fieldlistarray = explode (" ", $value);
				//$strdatatype .= $value ."<br>";
				//$strdatatype .= $fieldlistarray[0] ."<br>";
				//echo $fieldarray[0]."<br>\n";
				if ($fieldarray[0] == $field) {
					//$strdatatype = $fieldarray[1]." ".$fieldarray[2]." ".$fieldarray[3]." ".$fieldarray[4]; //strdatatype
				}
				unset($fieldarray, $string, $field);
			}

			//$strdatatype = $string;
			return $strdatatype;
		}
	} //end function


	//database connection
	try {
		//$db = new PDO('sqlite2:example.db'); //sqlite 2
		//$db = new PDO('sqlite::memory:'); //sqlite 3
		$db = new PDO('sqlite:'.realpath($db_path).'/'.$db_name); //sqlite 3

		//Add additional functions to SQLite so that they are accessible inside SQL
		//bool PDO::sqliteCreateFunction ( string function_name, callback callback [, int num_args] )
		$db->sqliteCreateFunction('md5', 'phpmd5', 1);
		$db->sqliteCreateFunction('unix_timestamp', 'phpunix_timestamp', 1);
		$db->sqliteCreateFunction('now', 'phpnow', 0);
		$db->sqliteCreateFunction('sqlitedatatype', 'phpsqlitedatatype', 2);
		$db->sqliteCreateFunction('strleft', 'phpleft', 2);
		$db->sqliteCreateFunction('strright', 'phpright', 2);
	}
	catch (PDOException $error) {
		print "error: " . $error->getMessage() . "<br/>";
		die();
	}
} //end if db_type sqlite


if ($db_type == "mysql") {
	//database connection
	try {
		//required for mysql_real_escape_string
			if (function_exists(mysql_connect)) {
				$mysql_connection = mysql_connect($db_host, $db_username, $db_password);
			}
		//mysql pdo connection
			if (strlen($db_host) == 0 && strlen($db_port) == 0) {
				//if both host and port are empty use the unix socket
				$db = new PDO("mysql:host=$db_host;unix_socket=/var/run/mysqld/mysqld.sock;dbname=$db_name", $db_username, $db_password);
			}
			else {
				if (strlen($db_port) == 0) {
					//leave out port if it is empty
					$db = new PDO("mysql:host=$db_host;dbname=$db_name;", $db_username, $db_password, array(
					PDO::ATTR_ERRMODE,
					PDO::ERRMODE_EXCEPTION
					));
				}
				else {
					$db = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;", $db_username, $db_password, array(
					PDO::ATTR_ERRMODE,
					PDO::ERRMODE_EXCEPTION
					));
				}
			}
	}
	catch (PDOException $error) {
		print "error: " . $error->getMessage() . "<br/>";
		die();
	}
} //end if db_type mysql


if ($db_type == "pgsql") {
	//database connection
	try {
		if (strlen($db_host) > 0) {
			if (strlen($db_port) == 0) { $db_port = "5432"; }
			$db = new PDO("pgsql:host=$db_host port=$db_port dbname=$db_name user=$db_username password=$db_password");
		}
		else {
			$db = new PDO("pgsql:dbname=$db_name user=$db_username password=$db_password");
		}
	}
	catch (PDOException $error) {
		print "error: " . $error->getMessage() . "<br/>";
		die();
	}
} //end if db_type pgsql

?>