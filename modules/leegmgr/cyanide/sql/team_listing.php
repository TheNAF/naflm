<?php

$_t_team_listing = 
'
DROP TABLE IF EXISTS "Team_Listing";
CREATE TABLE Team_Listing (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,strName VARCHAR(255) NOT NULL ,idRaces INTEGER ,strLogo VARCHAR(255) NOT NULL,iTeamColor INTEGER ,strLeitmotiv TEXT NOT NULL ,strBackground TEXT NOT NULL ,iValue INTEGER ,iPopularity INTEGER ,iCash INTEGER ,iCheerleaders INTEGER ,iBalms INTEGER ,bApothecary INTEGER ,iRerolls INTEGER ,bEdited INTEGER ,idTeam_Listing_Filters INTEGER ,idStrings_Formatted_Background INTEGER ,idStrings_Localized_Leitmotiv INTEGER ,iNextPurchase INTEGER ,iAssistantCoaches INTEGER );
';

?>