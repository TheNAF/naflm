<?php

$_t_team_rankings = 
'
DROP TABLE IF EXISTS "Team_Rankings";
CREATE TABLE Team_Rankings (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,idTeam_Listing INTEGER ,idRule_Types INTEGER ,iSeason INTEGER ,iGroup INTEGER ,iPoints INTEGER ,iRanking INTEGER ,iWins INTEGER ,iDraws INTEGER ,iLosses INTEGER ,iTeamValue INTEGER );
';

?>