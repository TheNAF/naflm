<?php

$_t_races = 
'
DROP TABLE IF EXISTS "Races";
CREATE TABLE Races (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,DATA_CONSTANT VARCHAR(255) ,idStrings_Localized INTEGER ,idStrings_Localized_Info INTEGER ,strName VARCHAR(255) NOT NULL,iRerollPrice INTEGER );
';

?>