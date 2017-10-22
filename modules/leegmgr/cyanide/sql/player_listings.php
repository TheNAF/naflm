<?php

$_t_player_listings = 
'
DROP TABLE IF EXISTS "Player_Listing";
CREATE TABLE Player_Listing (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,idPlayer_Names INTEGER ,strName VARCHAR(255) NOT NULL,idPlayer_Types INTEGER ,idTeam_Listing INTEGER ,idTeam_Listing_Previous INTEGER ,idRaces INTEGER ,iPlayerColor INTEGER ,iSkinScalePercent INTEGER NOT NULL,iSkinMeshVariant INTEGER NOT NULL,iSkinTextureVariant INTEGER NOT NULL,fAgeing real ,iNumber INTEGER ,Characteristics_fMovementAllowance real ,Characteristics_fStrength real ,Characteristics_fAgility real ,Characteristics_fArmourValue real ,idPlayer_Levels INTEGER ,iExperience INTEGER ,idEquipment_Listing_Helmet INTEGER ,idEquipment_Listing_Pauldron INTEGER ,idEquipment_Listing_Gauntlet INTEGER ,idEquipment_Listing_Boot INTEGER ,Durability_iHelmet INTEGER ,Durability_iPauldron INTEGER ,Durability_iGauntlet INTEGER ,Durability_iBoot INTEGER ,iSalary INTEGER ,Contract_iDuration INTEGER ,Contract_iSeasonRemaining INTEGER ,idNegotiation_Condition_Types INTEGER ,Negotiation_iRemainingTries INTEGER ,Negotiation_iConditionDemand INTEGER ,iValue INTEGER ,iMatchSuspended INTEGER ,iNbLevelsUp INTEGER ,LevelUp_iRollResult INTEGER ,LevelUp_iRollResult2 INTEGER ,LevelUp_bDouble INTEGER ,bGenerated INTEGER ,bStar INTEGER ,bEdited INTEGER ,bDead INTEGER ,strLevelUp VARCHAR(255) );
';

?>