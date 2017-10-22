<?php

$_t_statistics_season_teams = 
'
DROP TABLE IF EXISTS "Statistics_Season_Teams";
CREATE TABLE Statistics_Season_Teams (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,idTeam_Listing INTEGER ,iSeason INTEGER ,iMatchPlayed INTEGER ,iMVP INTEGER ,Inflicted_iPasses INTEGER ,Inflicted_iCatches INTEGER ,Inflicted_iInterceptions INTEGER ,Inflicted_iTouchdowns INTEGER ,Inflicted_iCasualties INTEGER ,Inflicted_iTackles INTEGER ,Inflicted_iKO INTEGER ,Inflicted_iInjuries INTEGER ,Inflicted_iDead INTEGER ,Inflicted_iMetersRunning INTEGER ,Inflicted_iMetersPassing INTEGER ,Sustained_iPasses INTEGER ,Sustained_iCatches INTEGER ,Sustained_iInterceptions INTEGER ,Sustained_iTouchdowns INTEGER ,Sustained_iCasualties INTEGER ,Sustained_iTackles INTEGER ,Sustained_iKO INTEGER ,Sustained_iInjuries INTEGER ,Sustained_iDead INTEGER ,Sustained_iMetersRunning INTEGER ,Sustained_iMetersPassing INTEGER ,iPoints INTEGER ,iWins INTEGER ,iDraws INTEGER ,iLoss INTEGER ,iBestMatchRating INTEGER ,Average_iMatchRating INTEGER ,Average_iSpectators INTEGER ,Average_iCashEarned INTEGER ,iSpectators INTEGER ,iCashEarned INTEGER ,iPossessionBall INTEGER ,Occupation_iOwn INTEGER ,Occupation_iTheir INTEGER );
';

?>