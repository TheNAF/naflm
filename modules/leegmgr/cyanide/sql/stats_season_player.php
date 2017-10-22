<?php

$_t_statistics_season_players = 
'
DROP TABLE IF EXISTS "Statistics_Season_Players";
CREATE TABLE Statistics_Season_Players (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,idPlayer_Listing INTEGER ,iSeason INTEGER ,iMatchPlayed INTEGER ,iMVP INTEGER ,Inflicted_iPasses INTEGER ,Inflicted_iCatches INTEGER ,Inflicted_iInterceptions INTEGER ,Inflicted_iTouchdowns INTEGER ,Inflicted_iCasualties INTEGER ,Inflicted_iTackles INTEGER ,Inflicted_iKO INTEGER ,Inflicted_iStuns INTEGER ,Inflicted_iInjuries INTEGER ,Inflicted_iDead INTEGER ,Inflicted_iMetersRunning INTEGER ,Inflicted_iMetersPassing INTEGER ,Sustained_iInterceptions INTEGER ,Sustained_iCasualties INTEGER ,Sustained_iTackles INTEGER ,Sustained_iKO INTEGER ,Sustained_iStuns INTEGER ,Sustained_iInjuries INTEGER ,Sustained_iDead INTEGER );
';

?>