<?php

$_t_player_casualties = 
'
DROP TABLE IF EXISTS "Player_Casualties";
CREATE TABLE Player_Casualties (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,idPlayer_Listing INTEGER ,idPlayer_Casualty_Types INTEGER );
';


?>