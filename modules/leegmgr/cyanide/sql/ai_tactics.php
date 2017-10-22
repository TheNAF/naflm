<?php

$_t_ai_tactics = 
'
DROP TABLE IF EXISTS "AI_Tactics";
CREATE TABLE AI_Tactics (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,CONSTANT VARCHAR(255) ,idTeam_Listing INTEGER ,iList_AI_Character_Role_Types VARCHAR(255) NOT NULL,idStrings_Localized INTEGER ,strName VARCHAR(255) );
';

$_dat_ai_tactics = 
"
INSERT INTO 'AI_Tactics' VALUES(1,'ATTRACT_CENTRE','','(4,5,2)',541,'');
INSERT INTO 'AI_Tactics' VALUES(2,'STRONG_LINE','','(3,5,3)',542,'');
INSERT INTO 'AI_Tactics' VALUES(3,'STRONG_BLITZ','','(2,5,4)',593,'');
INSERT INTO 'AI_Tactics' VALUES(4,'WEAK_SIDE','','(3,6,2)',594,'');
INSERT INTO 'AI_Tactics' VALUES(5,'THE_WALL',0,'(1,8,2)',595,'');
INSERT INTO 'AI_Tactics' VALUES(6,'DEEP_SIDE_WEAK','','(4,4,3)',596,'');
INSERT INTO 'AI_Tactics' VALUES(7,'MORE_FIGHT','','(4,5,2)',597,'');
INSERT INTO 'AI_Tactics' VALUES(8,'DEFENSIF_TACLE','','(4,4,3)',598,'');
INSERT INTO 'AI_Tactics' VALUES(9,'ZOULOU','','(2,4,5)',599,'');
INSERT INTO 'AI_Tactics' VALUES(10,'NO_SAFETY','','(3,4,4)',600,'');
";
?>