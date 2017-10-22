<?php

$_t_savegameinfo = 
'
DROP TABLE IF EXISTS "SavedGameInfo";
CREATE TABLE SavedGameInfo (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,strName VARCHAR(255) ,strSaveDate VARCHAR(255) ,strVersion VARCHAR(255) ,Championship_idTeam_Listing INTEGER ,Championship_strTeamName VARCHAR(255) NOT NULL,Championship_idTeamLogo INTEGER ,Championship_idRule_Types_Current INTEGER ,Championship_iCurrentDay INTEGER ,Championship_iCurrentSeason INTEGER ,Championship_iTeamCash INTEGER ,Championship_iTeamValue INTEGER ,Championship_iTeamPopularity INTEGER ,Campaign_idCampaign_Listing INTEGER ,Campaign_bRealTime INTEGER ,Campaign_iTeamPrestige INTEGER ,Campaign_iCurrentPeriod INTEGER ,Match_strSave TEXT ,Match_idDifficultyLevels INTEGER ,Match_iStadium INTEGER ,Match_bDeathZoneEnabled INTEGER ,iSlot INTEGER ,strTeamLogo VARCHAR(255) NOT NULL,iLogoRace INTEGER ,iLogoIndex INTEGER ,strAvailableRaces VARCHAR(8) NOT NULL,iInMatch INTEGER ,iRace INTEGER );
';

?>