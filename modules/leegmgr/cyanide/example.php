<?php
include('lib_cy_team_db.php');
$cy 							= new cyanide;
$cy_team 						= new cy_team_db;

$obblm_team['race']				= "Skaven";
$obblm_team['id']  				= 105;
$obblm_team['name']  			= 'TeamSkaven';
$obblm_team['colorid'] 			= 51; //@FIXME need to map cy colors 51 = brown
$obblm_team['TeamMOTO'] 		= 'Live and Let Die!';
$obblm_team['TeamBackground'] 	= 'This team is new and needs to prove it can cust the mustard';
$obblm_team['TeamValue']		= 1800;
$obblm_team['TeamFanFactor']	= 6;
$obblm_team['gold']				= 130000;
$obblm_team['Cheerleaders']		= 8;
$obblm_team['apothecary']		= 1;
$obblm_team['rerolls']			= 4;


//conversions
$cy->convert_race_id('cyid',$obblm_team['race']);

//BUILD TEAM DATA
$cy->set_team_constants();
$cy->set_team_id($obblm_team['id']);
$cy->set_team_name($obblm_team['name']);
$cy->set_team_race_id($cy->race['id']);
$cy->set_team_logo($cy->race['name'].'_01');
$cy->set_team_color($obblm_team['colorid']);
$cy->set_team_moto($obblm_team['TeamMOTO']);
$cy->set_team_background($obblm_team['TeamBackground']);
$cy->set_team_value($obblm_team['TeamValue']);
$cy->set_team_fanfactor($obblm_team['TeamFanFactor']);
$cy->set_team_gold($obblm_team['gold']);
$cy->set_team_cheerleaders($obblm_team['Cheerleaders']);
$cy->set_team_apothecary($obblm_team['apothecary']);
$cy->set_team_rerolls($obblm_team['rerolls']);
$cy->set_team_rank_constants();
//Build Race Data
$cy->set_reroll_price($obblm_team['race']);

$obblm_team['players'][0]['id']				= 912;
$obblm_team['players'][0]['name'] 			= 'Magrit The Ugly (AC)';
$obblm_team['players'][0]['type'] 			= 'Gutter Runner';
$obblm_team['players'][0]['skin'] 			= 0;//@FIXME need to map race skin number options
$obblm_team['players'][0]['age']  			= '100.';
$obblm_team['players'][0]['Number']			= 25; //int 1-32 only
$obblm_team['players'][0]['MA']				= 10;
$obblm_team['players'][0]['ST']				= 2;
$obblm_team['players'][0]['AG']				= 4;
$obblm_team['players'][0]['AV']				= 7;
$obblm_team['players'][0]['Level']			= 5;
$obblm_team['players'][0]['SPP']			= 54;
$obblm_team['players'][0]['COST']			= 80000;
$obblm_team['players'][0]['VALUE']			= 180;
$obblm_team['players'][0]['Skills'][0]		= 'MA +1';
$obblm_team['players'][0]['Skills'][1]		= 'Catch';
$obblm_team['players'][0]['Skills'][2]		= 'Sprint';
$obblm_team['players'][0]['Skills'][3]		= 'Foul Appearance';
$obblm_team['players'][0]['Casualty'][0]	= False;

$obblm_team['players'][1]['id']				= 913;
$obblm_team['players'][1]['name'] 			= 'Surgit The Quick';
$obblm_team['players'][1]['type'] 			= 'Gutter Runner';
$obblm_team['players'][1]['skin'] 			= 0;//@FIXME need to map race skin number options
$obblm_team['players'][1]['age']  			= '100.';
$obblm_team['players'][1]['Number']			= 19; //int 1-32 only
$obblm_team['players'][1]['MA']				= 9;
$obblm_team['players'][1]['ST']				= 2;
$obblm_team['players'][1]['AG']				= 4;
$obblm_team['players'][1]['AV']				= 7;
$obblm_team['players'][1]['Level']			= 3;
$obblm_team['players'][1]['SPP']			= 29;
$obblm_team['players'][1]['COST']			= 80000;
$obblm_team['players'][1]['VALUE']			= 120;
$obblm_team['players'][1]['Skills'][0]		= 'Block';
$obblm_team['players'][1]['Skills'][1]		= 'Catch';
$obblm_team['players'][1]['Casualty'][0]	= False;

$obblm_team['players'][2]['id']				= 919;
$obblm_team['players'][2]['name'] 			= 'Wyamute Squeek';
$obblm_team['players'][2]['type'] 			= 'Lineman';
$obblm_team['players'][2]['skin'] 			= 0;//@FIXME need to map race skin number options
$obblm_team['players'][2]['age']  			= '100.';
$obblm_team['players'][2]['Number']			= 20; //int 1-32 only
$obblm_team['players'][2]['MA']				= 8;
$obblm_team['players'][2]['ST']				= 2; //notice that even though the casualty is listed I still had to set this to 2
$obblm_team['players'][2]['AG']				= 4;
$obblm_team['players'][2]['AV']				= 7;
$obblm_team['players'][2]['Level']			= 2;
$obblm_team['players'][2]['SPP']			= 13;
$obblm_team['players'][2]['COST']			= 50000;
$obblm_team['players'][2]['VALUE']			= 70;
$obblm_team['players'][2]['Skills'][0]		= 'Block';
$obblm_team['players'][2]['Casualty'][0]	= 'Smashed Collar Bone';


foreach ($obblm_team['players'] as $i => $player) {
	$local_id = $player['id'];
	$cy->add_player_to_array(
		$local_id,//Player uniq id.. obblm player id should be fine
		$player['name'],//Players name String limit 50
		$cy->convert_player_type($player['type']), // blitzer lineman etc
		$cy->team['ID'],
		$cy->race['id'],
		$player['skin'],// skintexture.. 0 Randaom skin; most models only have 3 if your not sure leave 0
		$player['age'], // age 0 - 100% expresed with decimal but no decimal place. 100. or 001. String
		$player['Number'],// Player number Int 1-32, higher values should be converted to 1-32
		$cy->convert_ma($player['MA']),
		$cy->convert_st($player['ST']),
		$cy->convert_ag($player['AG']),
		$cy->convert_av($player['AV']),
		$player['Level'],//level
		$player['SPP'], //spp
		$player['COST'], //player cost
		$player['VALUE']//player value
	);
	//SKILLS
	foreach ($player['Skills'] as $ii => $skill) {
		if($skill == False) {
			
		} else {
			$cy->set_player_skills($local_id,$skill);
		}
	}
	//casulalty and injuries
	foreach ($player['Casualty'] as $ii => $cas) {
		if($cas == False) {
			
		} else {
			$cy->set_player_casualty($local_id,$cas);
		}
	}
}

$cy_team->make_cy_roster('data/teams/','NotUsed',$cy->players,$cy->team,$cy->race,$cy->player_skills,$cy->casualty);
//BUILD PLAYER DATA
	
//Set Injurys of player including longterm ones and death
//$cy->set_player_casualty(919,'Smashed Collar Bone');



/*
$cy->add_player_to_array(915,'Lhycut Speed Demon (C)',18,$cy->team['ID'],$cy->race['id'],3,'100.',17,83.33,40,66.664,58.333,4,38,80000,170);
$cy->set_player_skills(915,'Claw/Claws');
$cy->set_player_skills(915,'Big Hand');
$cy->set_player_skills(915,'MA +1');

$cy->add_player_to_array(916,'Deeych                ',19,$cy->team['ID'],$cy->race['id'],1,'100.',18,58.331,50,49.998,72.222,3,17,90000,130);
$cy->set_player_skills(916,'Tackle');
$cy->set_player_skills(916,'Strip Ball');

$cy->add_player_to_array(917,'leekch                ',19,$cy->team['ID'],$cy->race['id'],2,'100.',22,58.331,50,49.998,72.222,3,28,90000,140);
$cy->set_player_skills(917,'Dauntless');
$cy->set_player_skills(917,'Horns');


$cy->add_player_to_array(920,'Sniable Nibbler       ',16,$cy->team['ID'],$cy->race['id'],0,'100.',9,58.331,50,49.998,58.333,2,11,50000,70);
$cy->set_player_skills(920,'Block');

$cy->add_player_to_array(921,'Modiflk               ',16,$cy->team['ID'],$cy->race['id'],1,'100.',21,58.331,50,49.998,58.333,2,14,50000,80);
$cy->set_player_skills(921,'Tentacles');

$cy->add_player_to_array(924,'Rat Man Du            ',20,$cy->team['ID'],$cy->race['id'],0,'100.',24,49.998,70,33.332,72.222,2,7,160000,180);
$cy->set_player_skills(924,'Juggernaut');
$cy->add_player_to_array(926,'Thrower #3            ',17,$cy->team['ID'],$cy->race['id'],0,'100.',3,58.333,50,50,58.333,1,2,70000,70);

$cy->add_player_to_array(927,'Gutter Runner #1      ',18,$cy->team['ID'],$cy->race['id'],0,'100.',1,74.999,40,66.666,58.333,1,5,80000,80);

$cy->add_player_to_array(928,'Lineman #2            ',16,$cy->team['ID'],$cy->race['id'],0,'100.',2,58.333,50,50,58.333,1,0,50000,50);

$cy->add_player_to_array(929,'Lineman #4            ',16,$cy->team['ID'],$cy->race['id'],0,'100.',4,58.333,50,50,58.333,1,5,50000,50);

/*echo "<pre>";
print_r($cy->player_skills);
*/

//$cy_team->make_cy_roster('data/teams/','TestSkaven',$cy->players,$cy->team,$cy->race,$cy->player_skills,$cy->casualty);
 
?>