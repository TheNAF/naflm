<?php

class Division
{
	/***************
	 * Properties 
	 ***************/
	public $did   = 0; // Division ID.
	public $f_lid = 0; // From this league ID.
	public $name  = '';
	public $league_name = '';
	 
	/***************
	 * Methods 
	 ***************/
	public function __construct($did) {
		$result = mysqli_query(mysql_up(),"SELECT * FROM divisions WHERE did = $did");
		$row = mysqli_fetch_assoc($result);
		foreach ($row as $col => $val) {
			$this->$col = ($val) ? $val : 0;
		}
		if (!$this->name) {$this->name = '';} # Make $name empty string and not zero when empty in mysql.
		$this->league_name = get_alt_col('leagues', 'lid', $this->f_lid, 'name');
	}

	public function delete() {
		return mysqli_query(mysql_up(),"DELETE FROM divisions WHERE did = $this->did");
	}

	public function setName($name) {
		$query = "UPDATE divisions SET name = '".mysqli_real_escape_string($name)."' WHERE did = $this->did";
		return (get_alt_col('divisions', 'name', $name, 'did')) ? false : mysqli_query(mysql_up(),$query);
	}

	public function getTours($onlyIds = false) {
		$tours = array();
		$result = mysqli_query(mysql_up(),"SELECT tour_id FROM tours WHERE f_did = $this->did");
		if ($result && mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				array_push($tours, ($onlyIds) ? $row['tour_id'] : new Tour($row['tour_id']));
			}
		}
		return $tours;    
	}

	public static function getDivisions($onlyIds = false) {
		$divisions = array();
		$result = mysqli_query(mysql_up(),"SELECT did FROM divisions");
		if ($result && mysqli_num_rows($result) > 0) {
			while ($row = mysqli_fetch_assoc($result)) {
				array_push($divisions, ($onlyIds) ? $row['did'] : new Division($row['did']));
			}
		}
		return $divisions;
	}

	public static function create($f_lid, $name) {
		$query = "INSERT INTO divisions (f_lid, name) VALUES ($f_lid, '".mysqli_real_escape_string($name)."')";
		return (get_alt_col('divisions', 'name', $name, 'did')) ? false : mysqli_query(mysql_up(),$query);
	}
}