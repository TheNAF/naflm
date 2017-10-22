<?php
/*
 *  Copyright (c) Ryan Williams <email protected> 2010. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


//class tester

//	$file = sys_get_temp_dir().'MatchReport.sqlite';
//	$cymatch = new cy_match_db($file);
//

class cy_match_db {
	var $_db_read;
	var $file;
	var $error;
	var $db_status;
	var $db_engine;
	var $homeid;
	var $awayid;
	private $sql;
	public $winner = '';
	public $concession = false;
	public $gate = 0;
	public $hash = '';
	public $hometeam = '';
	public $homescore = 0; //set
	public $homewinnings = 0;
	public $homeff = 0;
	public $homefame = 0;
	public $hometransferedGold = 0;
	public $homeplayers;
	public $tv_home = 0;
	public $homefans = 0;
	public $awayteam = '';
	public $awayscore = 0; // set
	public $awaywinnings = 0;
	public $awayff = 0;
	public $awayfame = 0;
	public $awaytransferedGold = 0;
	public $awayplayers;
	public $awayfans = 0;
	public $tv_away = 0;

	public $hometeam_id = 0;
	public $awayteam_id = 0;
	public $match_id = 0;


	function __construct($file) {
		$this->file = $file;
		$this->db_engine = 2; //PDO = 2 SQLite3 = 1
		//echo $file;
		if(file_exists($this->file)) {
			//open the DB
			$this->db_status = $this->load_match_db($this->db_engine);
			//set home team
			$this->set_home_team();
			//set away team
			$this->set_away_team();
			//Set team id's
			$this->set_team_ids();
			//set home score
			$this->set_home_score();
			//set away score
			$this->set_away_score();
			//set winner
			$this->set_winner();
			//set concession true false
			$this->set_concession();
			//set home ff and TV
			$this->set_home_tvff();
			//set away ff and TV
			$this->set_away_tvff();
			//Fans and Fame
			$this->set_home_fans();
			$this->set_away_fans();
			// Set home Fame
			$this->set_home_fame();
			//set away fame
			$this->set_away_fame();
			//set home winningd();
			$this->set_home_winnings();
			//set away winnings
			$this->set_away_winnings();
			//set gate
			// set new fan factor home;
			$this->set_home_ff_new();
			// set new fan factor away;
			$this->set_away_ff_new();
			// set home players array
			$this->set_players('Home');
			// set away players array
			$this->set_players('Away');
			// set away players array
			$this->set_gate();
			//close db
			$this->close_match_db($this->db_engine);
		} else {
			$this->error = "Unable to locate file in ".$this->file;
		}
		//echo $this->error;
		//echo $this->db_status;
	}
	private function load_match_db($db_type){
		if ($db_type == 1) {// use SQLite3 class
			$this->_db_read = new SQLite3($this->file);
		}
		if ($db_type == 2) {// use PDO class
			//echo $this->file_loc.$this->team['name'].'.db';
			$this->_db_read = new PDO('sqlite:'.$this->file);
		}
	}
	private function close_match_db($db_type) {
		if($db_type == 1) {
			$this->_db_read->close();
		}
		if($db_type == 2){
			$this->_db_read = null;
		}
	}
	//private function query_db() {
	//	return $this->_db_read->query($this->sql);
	//}
	private function set_team_ids() {
		$this->set_sql(11);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->homeid = $row['idTeam_Listing_Home'];
			$this->awayid = $row['idTeam_Listing_Away'];
		}
	}
	private function set_home_team() {
		$this->set_sql(3);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->hometeam = $row['strName'];
		}
		echo "<table border=1><tr><td>Step</td><td>Home Team</td><td>Away Team</td></tr><tr>";
		echo "<tr><td>Name</td><td>".$this->hometeam."</td>";
	}
	private function set_away_team() {
		$this->set_sql(4);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->awayteam = $row['strName'];
		}
		echo "<td>".$this->awayteam."</td></tr>";
	}
	private function set_home_score() {
		$this->set_sql(1);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->homescore = (int)$row['Home_iScore'];
		}
		echo "<tr><td>Score</td><td>".$this->homescore."</td>";

	}
	private function set_away_score() {
		$this->set_sql(2);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->awayscore = (int)$row['Away_iScore'];
		}
		echo "<td>".$this->awayscore."</td></tr>";
	}
	private function set_winner() {
		if($this->homescore > $this->awayscore) {
			//winer is home
			$this->winner = $this->hometeam;
		} elseif($this->awayscore > $this->homescore) {
			$this->winner = $this->awayteam;
		} elseif ($this->awayscore == $this->homescore) {
			//Tie
			$this->winner = '';
		} else {
			$this->error = "There has been a problem match
			winner could not be set";
		}
		echo "<tr><td>Winner</td><td colspan=2>".$this->winner."</td></tr>";

	}
	private function set_concession() {
		//get home_team_mvp
		$this->set_sql(5);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$result = $row['count'];
			//if 0 or 2 set concession true
			if($result == 0 or $result == 2) {
				$this->concession = true;
			} else {
				$this->concession = false;
			}
		}
		echo "<tr><td>Concession?</td><td colspan=2>".$this->concession."</td></tr>";
	}
	private function set_gate(){
		//$this->set_sql(6);
		//foreach ($this->_db_read->query($this->sql) as $row) {
		//	$this->gate = $row['iSpectators'];
		//}
		$this->gate = $this->awayfans + $this->homefans;
		echo "<tr><td>Gate</td><td colspan=2>".$this->gate."</td></tr>";
	}
	private function set_home_tvff() {
		$this->set_sql(7);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->homeff = $row['iPopularity'];
			$this->tv_home = $row['iValue'];
		}
		echo "<tr><td>Current FF</td><td>".$this->homeff."</td>";
	}
	private function set_away_tvff() {
		$this->set_sql(8);
		foreach ($this->_db_read->query($this->sql) as $row) {
			$this->awayff = $row['iPopularity'];
			$this->tv_away = $row['iValue'];
		}
		echo "<td>".$this->awayff."</td></tr>";
	}
	private function set_home_fans() {
		$d1 = rand(1,6);
		$d2 = rand(1,6);
		$this->homefans = ($d1 + $d2 + $this->homeff)*1000;
		echo "<tr><td>Fans</td><td>".$this->homefans."</td>";
	}
	private function set_away_fans() {
		$d1 = rand(1,6);
		$d2 = rand(1,6);
		$this->awayfans = ($d1 + $d2 + $this->awayff)*1000;
		echo "<td>".$this->awayfans."</td></tr>";
	}
	private function set_home_fame() {
		if($this->homefans <= $this->awayfans) {
			$this->homefame = 0;
		} elseif ($this->homefans > (2 * $this->awayfans)) {
			$this->homefame = 2;
		} elseif ($this->homefans > $this->awayfans) {
			$this->homefame = 1;
		} else {
			$this->error = "There has been a error gathering home team fame. This error should not occrue";
		}
		echo "<tr><td>Fame</td><td>".$this->homefame."</td>";
	}
	private function set_away_fame() {
		if($this->awayfans <= $this->homefans) {
			$this->awayfame = 0;
		} elseif ($this->awayfans > (2 * $this->homefans)) {
			$this->awayfame = 2;
		} elseif ($this->awayfans > $this->homefans) {
			$this->awayfame = 1;
		} else {
			$this->error = "There has been a error gathering home team fame. This error should not occrue";
		}
		echo "<td>".$this->awayfame."</td></tr>";
	}
	private function set_home_winnings() {
		$d1 = rand(1,6);
		$this->homewinnings = (($d1 + $this->homefame) * 10000) +  (( $this->winner != $this->awayteam ) ? 10000 : 0);
		echo "<tr><td>winnings</td><td>".$this->homewinnings."</td>";
	}

	private function set_away_winnings() {
		$d1 = rand(1,6);
		$this->awaywinnings = (($d1 + $this->awayfame) * 10000) +  (( $this->winner != $this->hometeam ) ? 10000 : 0);
		echo "<td>".$this->awaywinnings."</td></tr>";
	}
	private function set_home_ff_new() {
		echo "<tr><td>FF Roles Home Team</td><td>";
		$cff = $this->homeff;
		$d1  = rand(1,6);
		echo "[".$d1."]";
		$d2  = rand(1,6);
		echo "[".$d2."]";
		$win = false;
		if($this->winner == $this->hometeam) {
			$d3 = rand(1,6);
			echo "[".$d3."]</td>";
			$win = true;
		} else {
			$d3 = 0;
			echo "</td>";
		}
		$rr = $d1 + $d2 +$d3 ;
		if ($win == true) {
			if($rr > $cff) {
				$this->homeff = +1;
			} else {
				$this->homeff = 0;
			}
		} else {
			if($this->winner != $this->awayteam) {
				if ($rr > $cff) {
					$this->homeff = +1;
				} elseif ($rr < $cff) {
					$this->homeff = -1;
				} else {
					$this->homeff = 0;
				}
			} else {
				if($rr < $cff) {
					$this->homeff = -1;
				} else {
					$this->homeff = 0;
				}
			}
		}
		$win = false;
		echo "<td>Adjust: ".$this->homeff."</td></tr>";
	}
	private function set_away_ff_new() {
		echo "<tr><td>FF Roles Away Team</td><td>";
		$cff = $this->awayff;
		$d1  = rand(1,6);
		echo "[".$d1."]";
		$d2  = rand(1,6);
		echo "[".$d2."]";
		$win = false;
		if($this->winner == $this->awayteam) {
			$d3 = rand(1,6);
			echo "[".$d3."]</td>";
			$win = true;
		} else {
			$d3 = 0;
			echo "</td>";
		}
		$rr = $d1 + $d2 +$d3 ;
		if ($win == true) {
			if($rr > $cff) {
				$this->awayff = +1;
			} else {
				$this->awayff = 0;
			}
		} else {
			if($this->winner != $this->hometeam) {
				if ($rr > $cff) {
					$this->awayff = +1;
				} elseif ($rr < $cff) {
					$this->awayff = -1;
				} else {
					$this->awayff = 0;
				}
			} else {
				if($rr < $cff) {
					$this->awayff = -1;
				} else {
					$this->awayff = 0;
				}
			}
		}
		$win = false;
		echo "<td>Adjust: ".$this->awayff."</td></tr>";
	}
	public function init_player_array() {
			
	}
	private function set_players($t) {
		$cspp = 0;
		$nspp = 0;
		$tspp = 0;
		$clevel = 0;
		$nlevel = 0;
		$tcp	= 0; //total casualties for player
		$cspp = 0;
		$nspp = 0;
		$tspp = 0;
		$clevel = 0;
		$nlevel = 0;
		$tcp	= 0; //total casualties for player
		// Init player storage
		$players = array();
		$stats   = array();
		$cas	 = array();

		if ($t == 'Home') {
			$this->set_sql(9);
			foreach ($this->_db_read->query($this->sql) as $row) {

				$stats   = array();
				$cas	 = array();
				$inflicted_pass = (int) 0;
				
				$this->init_player_array();
				$players[$row['iNumber']]['ID'] = (int) $row['ID'];
				$players[$row['iNumber']]['nr'] = (int) $row['iNumber'];
				$players[$row['iNumber']]['name'] = (string) $row['strName'];
				$players[$row['iNumber']]['agn1'] = false;
				$players[$row['iNumber']]['EPS'] = array();
				if($row['bStar'] >= 1) {
					$players[$row['iNumber']]['star'] = true;
				} else {
					$players[$row['iNumber']]['star'] = false;
				}
				$players[$row['iNumber']]['merc'] = false;
				//get current level
				$clevel = (int) $row['idPlayer_Levels'];
				$cspp = $row['iExperience'];
				//echo $clevel;
				//get player stats
				$this->sql = "Select * from Home_Statistics_Players where idPlayer_Listing = ".$players[$row['iNumber']]['nr']." Limit 1";
				foreach ($this->_db_read->query($this->sql) as $stats) {
					$tcp = 0;
					$nspp = 0;
					$players[$row['iNumber']]['mvp'] = (int) $stats['iMVP'];
						
					if($players[$row['iNumber']]['mvp'] > 0) {
						$nspp = (int)$nspp + (int)($players[$row['iNumber']]['mvp'] * 5);
					}
						
					$players[$row['iNumber']]['cp']  = (int) $stats['Inflicted_iPasses'];
					$inflicted_pass					 = (int) $stats['Inflicted_iPasses'];
					if($inflicted_pass > 0) {
						$nspp = $nspp + ($inflicted_pass * 1);
					}
					$players[$row['iNumber']]['td']  = (int) $stats['Inflicted_iTouchdowns'];
					if($players[$row['iNumber']]['td'] > 0) {
						$nspp = $nspp + ($players[$row['iNumber']]['td'] * 3);
					}
					$players[$row['iNumber']]['intcpt'] = (int) $stats['Inflicted_iInterceptions'];
					if($players[$row['iNumber']]['intcpt'] > 0) {
						$nspp = $nspp + ($players[$row['iNumber']]['intcpt'] * 2);
					}
					$players[$row['iNumber']]['bh'] = (int) $stats['Inflicted_iCasualties'] - (int)$stats['Inflicted_iDead'];
					//$players[$row['iNumber']]['si'] = (int) $stats['Inflicted_iInjuries'];
					$players[$row['iNumber']]['si'] = 0;
					$players[$row['iNumber']]['ki'] = (int) $stats['Inflicted_iDead'];
					$tcp = (int)$players[$row['iNumber']]['bh'] + (int)$players[$row['iNumber']]['si'] + (int)$players[$row['iNumber']]['ki'];
					if($tcp >= 1) {
						//Echo "Setting cas spp to";
						$nspp = $nspp + ($tcp * 2);
						//echo $nspp;
					}
				}
				$this->sql = "Select idPlayer_Casualty_Types from Home_Player_Casualties where idPlayer_Listing =".$players[$row['iNumber']]['ID'];
				foreach ($this->_db_read->query($this->sql) as $cas) {
					$players[$row['iNumber']]['inj'] = $cas['idPlayer_Casualty_Types'];

				}
				if(isset($cas['idPlayer_Casualty_Types'])){
					$players[$row['iNumber']]['inj'] = $cas['idPlayer_Casualty_Types'];
					$players[$row['iNumber']]['inj'] = $this->set_inj($players[$row['iNumber']]['inj']);
				} else {
					$players[$row['iNumber']]['inj'] = NULL;
				}
				//echo "<br />".$players[$row['iNumber']]['name'];
				$tspp = $cspp + $nspp;
				//echo "<br /> current spp is :".$cspp;
				//echo "<br /> new spp is :".$nspp;
				//echo "<br />Spp total is ".$tspp."";

				$nlevel = $this->get_p_level($tspp);
				//echo "<br />".$nlevel."<hr />";
				if($nlevel > $clevel) {
					$players[$row['iNumber']]['ir1_d1'] = rand(1,6);
					$players[$row['iNumber']]['ir1_d2'] = rand(1,6);
					$players[$row['iNumber']]['ir2_d1'] = rand(1,6);
					$players[$row['iNumber']]['ir2_d2'] = rand(1,6);
					$players[$row['iNumber']]['ir3_d1'] = rand(1,6);
					$players[$row['iNumber']]['ir3_d2'] = rand(1,6);
				} else {
					$players[$row['iNumber']]['ir1_d1'] = 0;
					$players[$row['iNumber']]['ir1_d2'] = 0;
					$players[$row['iNumber']]['ir2_d1'] = 0;
					$players[$row['iNumber']]['ir2_d2'] = 0;
					$players[$row['iNumber']]['ir3_d1'] = 0;
					$players[$row['iNumber']]['ir3_d2'] = 0;
				}
			}
				
			$this->homeplayers = $players;
			//echo "<pre>";
			//print_r($this->homeplayers);
			//echo "</pre><hr />";
		} elseif ($t == 'Away') {
				
			$this->set_sql(10);
			foreach ($this->_db_read->query($this->sql) as $row) {
				$stats   = array();
				$cas	 = array();
				$inflicted_pass = (int) 0;
				
				$players[$row['iNumber']]['ID'] = (int) $row['ID'];
				$players[$row['iNumber']]['nr'] = (int) $row['iNumber'];
				$players[$row['iNumber']]['name'] = (string) $row['strName'];
				$players[$row['iNumber']]['agn1'] = false;
				$players[$row['iNumber']]['EPS'] = array();
				if($row['bStar'] >= 1) {
					$players[$row['iNumber']]['star'] = true;
				} else {
					$players[$row['iNumber']]['star'] = false;
				}
				$players[$row['iNumber']]['merc'] = false;
				//get current level
				$clevel = (int) $row['idPlayer_Levels'];
				$cspp = $row['iExperience'];
				//echo $clevel;
				//get player stats
				$this->sql = "Select * from Away_Statistics_Players where idPlayer_Listing = ".$players[$row['iNumber']]['nr']." Limit 1";
				foreach ($this->_db_read->query($this->sql) as $stats) {
					$tcp = 0;
					$nspp = 0;
					$players[$row['iNumber']]['mvp'] = (int) $stats['iMVP'];
						
					if($players[$row['iNumber']]['mvp'] > 0) {
						$nspp = (int)$nspp + (int)($players[$row['iNumber']]['mvp'] * 5);
					}
						
					$players[$row['iNumber']]['cp']  = (int) $stats['Inflicted_iPasses'];
					$inflicted_pass 				 = (int) $stats['Inflicted_iPasses'];
					
					if($inflicted_pass > 0) {
						$nspp = $nspp + ($inflicted_pass * 1);
					}
					$players[$row['iNumber']]['td']  = (int) $stats['Inflicted_iTouchdowns'];
					if($players[$row['iNumber']]['td'] > 0) {
						$nspp = $nspp + ($players[$row['iNumber']]['td'] * 3);
					}
					$players[$row['iNumber']]['intcpt'] = (int) $stats['Inflicted_iInterceptions'];
					if($players[$row['iNumber']]['intcpt'] > 0) {
						$nspp = $nspp + ($players[$row['iNumber']]['intcpt'] * 2);
					}
					$players[$row['iNumber']]['bh'] = (int) $stats['Inflicted_iCasualties'] - (int)$stats['Inflicted_iDead'];
					//$players[$row['iNumber']]['si'] = (int) $stats['Inflicted_iInjuries'];
					$players[$row['iNumber']]['si'] = 0;
					$players[$row['iNumber']]['ki'] = (int) $stats['Inflicted_iDead'];
					$tcp = (int)$players[$row['iNumber']]['bh'] + (int)$players[$row['iNumber']]['si'] + (int)$players[$row['iNumber']]['ki'];
					if($tcp >= 1) {
						//Echo "Setting cas spp to";
						$nspp = $nspp + ($tcp * 2);
						//echo $nspp;
					}
				}
				$this->sql = "Select idPlayer_Casualty_Types from Away_Player_Casualties where idPlayer_Listing =".$players[$row['iNumber']]['ID'];
				foreach ($this->_db_read->query($this->sql) as $cas) {
					$players[$row['iNumber']]['inj'] = $cas['idPlayer_Casualty_Types'];

				}
				if(isset($cas['idPlayer_Casualty_Types'])){
					$players[$row['iNumber']]['inj'] = $cas['idPlayer_Casualty_Types'];
					$players[$row['iNumber']]['inj'] = $this->set_inj($players[$row['iNumber']]['inj']);
				} else {
					$players[$row['iNumber']]['inj'] = NULL;
				}
				//echo "<br />".$players[$row['iNumber']]['name'];
				$tspp = $cspp + $nspp;
				//echo "<br /> current spp is :".$cspp;
				//echo "<br /> new spp is :".$nspp;
				//echo "<br />Spp total is ".$tspp."";

				$nlevel = $this->get_p_level($tspp);
				//	echo "<br />".$nlevel."<hr />";
				if($nlevel > $clevel) {
					$players[$row['iNumber']]['ir1_d1'] = rand(1,6);
					$players[$row['iNumber']]['ir1_d2'] = rand(1,6);
					$players[$row['iNumber']]['ir2_d1'] = rand(1,6);
					$players[$row['iNumber']]['ir2_d2'] = rand(1,6);
					$players[$row['iNumber']]['ir3_d1'] = rand(1,6);
					$players[$row['iNumber']]['ir3_d2'] = rand(1,6);
				} else {
					$players[$row['iNumber']]['ir1_d1'] = 0;
					$players[$row['iNumber']]['ir1_d2'] = 0;
					$players[$row['iNumber']]['ir2_d1'] = 0;
					$players[$row['iNumber']]['ir2_d2'] = 0;
					$players[$row['iNumber']]['ir3_d1'] = 0;
					$players[$row['iNumber']]['ir3_d2'] = 0;
				}
			}
			$this->awayplayers = $players;
			//echo "<pre>";
			//print_r($this->awayplayers);
			//echo "</pre><hr />";
		}

	}
	private function set_inj($id) {
		switch ( $id ) {
			case 1:
				$out = NULL;
				break;
			case 2:
				$out = "Miss Next Game";
				break;
			case 3:
				$out = "Miss Next Game";
				break;
			case 4:
				$out = "Miss Next Game";
				break;
			case 5:
				$out = "Miss Next Game";
				break;
			case 6:
				$out = "Miss Next Game";
				break;
			case 7:
				$out = "Miss Next Game";
				break;
			case 8:
				$out = "Miss Next Game";
				break;
			case 9:
				$out = "Miss Next Game";
				break;
			case 10:
				$out = "Niggling Injury";
				break;
			case 11:
				$out = "Niggling Injury";
				break;
			case 12:
				$out = "-1 MA";
				break;
			case 13:
				$out = "-1 MA";
				break;
			case 14:
				$out = "-1 AV";
				break;
			case 15:
				$out = "-1 AV";
				break;
			case 16:
				$out = "-1 AG";
				break;
			case 17:
				$out = "-1 ST";
				break;
			case 18:
				$out = "Dead";
				break;
			default:
				$out = NULL;
				break;
				 
				 
		}
		return $out;

	}
	public function get_p_level($int){

		switch($int) {
			case 0:
				return 1;
				break;
			case ($int > 175):
				return 7;
				break;
			case ($int > 75):
				return 6;
				break;
			case ($int >50):
				return 5;
				break;
			case ($int > 30):
				return 4;
				break;
			case ($int > 15):
				return 3;
				break;
			case ($int > 5):
				return 2;
				break;
			default:
				return 1;
				break;
		}

	}
	private function set_sql($id){
		switch($id) {
			case 1:
				$this->sql = "Select Home_iScore from Calendar where ID = 1 Limit 1"; //get homescore
				break;
			case 2:
				$this->sql = "Select Away_iScore from Calendar where ID = 1 Limit 1"; //get awayscore
				break;
			case 3:
				$this->sql = "Select strName from Home_Team_Listing Where ID = 1 Limit 1"; // get home team name
				break;
			case 4:
				$this->sql = "Select strName from Away_Team_Listing Where ID = 1 Limit 1"; // get home team name
				break;
			case 5:
				$this->sql = "SELECT count(iMVP) as 'count' from Home_Statistics_Players where iMVP > 0"; // concession check
				break;
			case 6:
				$this->sql = "SELECT iSpectators from Calendar where ID = 1 Limit 1"; // get gate
				break;
			case 7:
				$this->sql = "SELECT iValue, iPopularity from Home_Team_Listing where ID = 1 Limit 1"; // get gate
				break;
			case 8:
				$this->sql = "SELECT iValue, iPopularity from Away_Team_Listing where ID = 1 Limit 1"; // get gate
				break;
			case 9;
			$this->sql = "SELECT * from Home_Player_Listing where idTeam_Listing = ".$this->homeid."";
			break;
			case 10;
			$this->sql = "SELECT * from Away_Player_Listing where idTeam_Listing = ".$this->awayid."";
			break;
			case 11;
			$this->sql ="SELECT idTeam_Listing_Home,idTeam_Listing_Away From Calendar where ID = 1 Limit 1";
			break;
		}
	}

}

?>
