<?php

$_t_player_types = 
'
DROP TABLE IF EXISTS "Player_Types";
CREATE TABLE Player_Types (ID INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,DATA_CONSTANT VARCHAR(255) ,idRaces INTEGER ,idPlayer_BaseTypes INTEGER ,idPlayer_Name_Types INTEGER ,idStrings_Localized INTEGER ,strName VARCHAR(255) ,Characteristics_fMovementAllowance real ,Characteristics_fStrength real ,Characteristics_fAgility real ,Characteristics_fArmourValue real ,iPrice INTEGER ,iMaxQuantity INTEGER );
';

$_dat_player_types =
"
INSERT INTO player_types VALUES (1, 'Team_Human_Lineman', 1, 1, 2, 31, '', 50, 50, 50, 72.222, 50000, 16);
INSERT INTO player_types VALUES (2, 'Team_Human_Catcher', 1, 3, 3, 32, '', 66.667, 40, 50, 58.333, 70000, 4);
INSERT INTO player_types VALUES (3, 'Team_Human_Thrower', 1, 2, 3, 33, '', 50, 50, 50, 72.222, 70000, 2);
INSERT INTO player_types VALUES (4, 'Team_Human_Blitzer', 1, 4, 1, 1143, '', 58.333, 50, 50, 72.222, 90000, 4);
INSERT INTO player_types VALUES (5, 'BigGuy_Human_Ogre', 1, 5, 4, 35, '', 41.665, 70, 33.333, 83.333, 140000, 1);
INSERT INTO player_types VALUES (6, 'Team_Dwarf_Blocker', 2, 1, 13, 558, '', 33.333, 50, 33.333, 83.333, 70000, 16);
INSERT INTO player_types VALUES (7, 'Team_Dwarf_Runner', 2, 2, 13, 34, '', 50, 50, 50, 72.222, 80000, 2);
INSERT INTO player_types VALUES (8, 'Team_Dwarf_Blitzer', 2, 4, 13, 1143, '', 41.665, 50, 50, 83.333, 80000, 2);
INSERT INTO player_types VALUES (9, 'Team_Dwarf_TrollSlayer', 2, 1, 14, 118, '', 41.665, 50, 33.333, 72.222, 90000, 2);
INSERT INTO player_types VALUES (10, 'Team_Dwarf_DeathRoller', 2, 5, 13, 102337, '', 33.333, 90, 16.666, 91.666, 160000, 1);
INSERT INTO player_types VALUES (11, 'Team_WoodElf_Lineman', 7, 1, 8, 31, '', 58.333, 50, 66.666, 58.333, 70000, 16);
INSERT INTO player_types VALUES (12, 'Team_WoodElf_Catcher', 7, 3, 8, 32, '', 74.999, 40, 66.666, 58.333, 90000, 4);
INSERT INTO player_types VALUES (13, 'Team_WoodElf_Thrower', 7, 2, 8, 33, '', 58.333, 50, 66.666, 58.333, 90000, 2);
INSERT INTO player_types VALUES (14, 'Team_WoodElf_WarDancer', 7, 4, 9, 122, '', 66.667, 50, 66.666, 58.333, 120000, 2);
INSERT INTO player_types VALUES (15, 'BigGuy_WoodElf_Treeman', 7, 5, 10, 123, '', 16.667, 80, 16.666, 91.666, 120000, 1);
INSERT INTO player_types VALUES (16, 'Team_Skaven_Lineman', 3, 1, 19, 31, '', 58.333, 50, 50, 58.333, 50000, 16);
INSERT INTO player_types VALUES (17, 'Team_Skaven_Thrower', 3, 2, 19, 33, '', 58.333, 50, 50, 58.333, 70000, 2);
INSERT INTO player_types VALUES (18, 'Team_Skaven_GutterRunner', 3, 3, 19, 124, '', 74.999, 40, 66.666, 58.333, 80000, 4);
INSERT INTO player_types VALUES (19, 'Team_Skaven_Blitzer', 3, 4, 19, 557, '', 58.333, 50, 50, 72.222, 90000, 2);
INSERT INTO player_types VALUES (20, 'BigGuy_Skaven_RatOgre', 3, 5, 20, 125, '', 50, 70, 33.333, 72.222, 160000, 1);
INSERT INTO player_types VALUES (21, 'Team_Orc_Lineman', 4, 1, 15, 31, '', 41.665, 50, 50, 83.333, 50000, 16);
INSERT INTO player_types VALUES (22, 'Team_Orc_Goblin', 4, 3, 17, 119, '', 50, 40, 50, 58.333, 40000, 4);
INSERT INTO player_types VALUES (23, 'Team_Orc_Thrower', 4, 2, 15, 33, '', 41.665, 50, 50, 72.222, 70000, 2);
INSERT INTO player_types VALUES (24, 'Team_Orc_BlackBlocker', 4, 5, 16, 126, '', 33.333, 60, 33.333, 83.333, 80000, 4);
INSERT INTO player_types VALUES (25, 'Team_Orc_Blitzer', 4, 4, 15, 1143, '', 50, 50, 50, 83.333, 80000, 4);
INSERT INTO player_types VALUES (26, 'BigGuy_Orc_Troll', 4, 5, 18, 121, '', 33.333, 70, 16.666, 83.333, 110000, 1);
INSERT INTO player_types VALUES (27, 'Team_Lizardman_Skink', 5, 2, 11, 127, '', 66.667, 40, 50, 58.333, 60000, 16);
INSERT INTO player_types VALUES (28, 'Team_Lizardman_Saurus', 5, 1, 11, 128, '', 50, 60, 16.666, 83.333, 80000, 6);
INSERT INTO player_types VALUES (29, 'BigGuy_Lizardman_Kroxigor', 5, 5, 12, 129, '', 50, 70, 16.666, 83.333, 140000, 1);
INSERT INTO player_types VALUES (30, 'Team_Goblin_Gob', 6, 1, 17, 119, '', 50, 40, 50, 58.333, 40000, 16);
INSERT INTO player_types VALUES (31, 'Team_Goblin_Looney', 6, 5, 17, 120, '', 50, 40, 50, 58.333, 40000, 1);
INSERT INTO player_types VALUES (32, 'Team_Chaos_Beastman', 8, 2, 5, 130, '', 50, 50, 50, 72.222, 60000, 16);
INSERT INTO player_types VALUES (33, 'Team_Chaos_Warrior', 8, 1, 6, 131, '', 41.665, 60, 50, 83.333, 100000, 4);
INSERT INTO player_types VALUES (34, 'BigGuy_Chaos_Minotaur', 8, 5, 7, 132, '', 41.665, 70, 33.333, 72.222, 150000, 1);
INSERT INTO player_types VALUES (36, 'AllStar_Chaos_GrashnakBlackhoof', 8, 5, 0, 913, 'Grashnak Blackhoof', 50, 80, 33.333, 72.222, 310000, 1);
INSERT INTO player_types VALUES (37, 'AllStar_Human_GriffOberwald', 1, 5, 0, 906, 'Griff Oberwald', 58.333, 60, 66.666, 72.222, 320000, 1);
INSERT INTO player_types VALUES (38, 'AllStar_Dwarf_GrimIronjaw', 2, 5, 0, 907, 'Grim Ironjaw', 41.665, 60, 50, 72.222, 220000, 1);
INSERT INTO player_types VALUES (39, 'AllStar_Skaven_Headsplitter', 3, 5, 0, 908, 'Headsplitter', 50, 80, 50, 72.222, 340000, 1);
INSERT INTO player_types VALUES (40, 'AllStar_WoodElf_JordellFreshbreeze', 7, 5, 0, 912, 'Jordell Freshbreeze', 66.667, 50, 83.333, 58.333, 230000, 1);
INSERT INTO player_types VALUES (41, 'AllStar_Goblin_Ripper', 6, 5, 0, 911, 'Ripper', 33.333, 80, 16.666, 83.333, 270000, 1);
INSERT INTO player_types VALUES (42, 'AllStar_Lizardman_Silibili', 5, 5, 0, 910, 'Slibli', 58.333, 60, 16.666, 83.333, 250000, 1);
INSERT INTO player_types VALUES (43, 'AllStar_Orc_VaragGhoulChewer', 4, 5, 0, 909, 'Varag Ghoul-Chewer', 50, 60, 50, 83.333, 260000, 1);
INSERT INTO player_types VALUES (44, 'BigGuy_Goblin_Troll', 6, 5, 18, 121, '', 33.333, 70, 16.666, 83.333, 110000, 2);
INSERT INTO player_types VALUES (45, 'Team_Goblin_Pogoer', 6, 3, 17, 101509, '', 58.333, 40, 50, 58.333, 40000, 1);
INSERT INTO player_types VALUES (46, 'Team_Goblin_Fanatic', 6, 5, 17, 101910, '', 25, 90, 50, 58.333, 70000, 1);
INSERT INTO player_types VALUES (47, 'Team_DarkElf_Lineman', 9, 1, 21, 31, '', 50, 50, 66.666, 72.222, 70000, 16);
INSERT INTO player_types VALUES (48, 'Team_DarkElf_Runner', 9, 2, 21, 34, '', 58.333, 50, 66.666, 58.333, 80000, 2);
INSERT INTO player_types VALUES (49, 'Team_DarkElf_Assassin', 9, 3, 22, 102100, '', 50, 50, 66.666, 58.333, 90000, 2);
INSERT INTO player_types VALUES (50, 'Team_DarkElf_Blitzer', 9, 4, 21, 1143, '', 58.333, 50, 66.666, 72.222, 100000, 4);
INSERT INTO player_types VALUES (51, 'Team_DarkElf_WitchElf', 9, 1, 23, 102102, '', 58.333, 50, 66.666, 58.333, 110000, 2);
INSERT INTO player_types VALUES (52, 'AllStar_DarkElf_HorkonHeartripper', 9, 5, 0, 102103, 'Horkon Heartripper', 58.333, 50, 66.666, 58.333, 210000, 1);
INSERT INTO player_types VALUES (53, 'AllStar_Orc_MorgNThorg', 0, 5, 0, 102474, 'Morg ''n'' Thorg', 50, 80, 50, 91.666, 430000, 1);";

global $settings;
if ($settings['leegmgr_cyanide_edition'] == 2) {
    $_dat_player_types .= "
INSERT INTO player_types VALUES (54, 'Team_Undead_Skeleton', 10, 1, 24, 102540, '', 41.665, 50, 33.333, 58.333, 40000, 16);
INSERT INTO player_types VALUES (55, 'Team_Undead_Zombie', 10, 1, 24, 102544, '', 33.333, 50, 33.333, 72.222, 40000, 16);
INSERT INTO player_types VALUES (56, 'Team_Undead_Ghoul', 10, 3, 25, 102541, '', 58.333, 50, 50, 58.333, 70000, 4);
INSERT INTO player_types VALUES (57, 'Team_Undead_Wight', 10, 4, 26, 102542, '', 50, 50, 50, 72.222, 90000, 2);
INSERT INTO player_types VALUES (58, 'BigGuy_Undead_Mummy', 10, 1, 27, 102543, '', 16.667, 70, 16.666, 83.333, 120000, 2);
INSERT INTO player_types VALUES (59, 'AllStar_Undead_CountLuthorvonDrakenborg', 0, 4, 0, 102547, '', 50, 70, 66.666, 83.333, 390000, 1);
INSERT INTO player_types VALUES (60, 'Team_Halfling_Halfling', 11, 3, 50, 102590, '', 41.666, 40, 50, 41.657, 30000, 16);
INSERT INTO player_types VALUES (61, 'BigGuy_Halfling_Treeman', 11, 1, 51, 123, '', 16.667, 80, 16.666, 91.666, 120000, 2);
INSERT INTO player_types VALUES (62, 'Team_Norse_Lineman', 12, 1, 38, 31, '', 50, 50, 50, 58.333, 50000, 16);
INSERT INTO player_types VALUES (63, 'Team_Norse_Thrower', 12, 2, 38, 33, '', 50, 50, 50, 58.333, 70000, 2);
INSERT INTO player_types VALUES (64, 'Team_Norse_Runner', 12, 3, 38, 34, '', 58.333, 50, 50, 58.333, 90000, 2);
INSERT INTO player_types VALUES (65, 'Team_Norse_Berserker', 12, 4, 39, 102612, '', 50, 50, 50, 58.333, 90000, 2);
INSERT INTO player_types VALUES (66, 'Team_Norse_Ulfwerener', 12, 4, 40, 102613, '', 50, 60, 33.333, 72.222, 110000, 2);
INSERT INTO player_types VALUES (67, 'BigGuy_Norse_Yhetee', 12, 1, 41, 102632, '', 41.665, 70, 16.666, 72.222, 140000, 1);
INSERT INTO player_types VALUES (68, 'Team_Amazon_Linewoman', 13, 1, 28, 102609, '', 50, 50, 50, 58.333, 50000, 16);
INSERT INTO player_types VALUES (69, 'Team_Amazon_Thrower', 13, 2, 28, 33, '', 50, 50, 50, 58.333, 70000, 2);
INSERT INTO player_types VALUES (70, 'Team_Amazon_Catcher', 13, 3, 28, 32, '', 50, 50, 50, 58.333, 70000, 2);
INSERT INTO player_types VALUES (71, 'Team_Amazon_Blitzer', 13, 4, 28, 1143, '', 50, 50, 50, 58.333, 90000, 4);
INSERT INTO player_types VALUES (72, 'Team_Elf_Lineman', 14, 1, 29, 31, '', 50, 50, 66.666, 58.333, 60000, 16);
INSERT INTO player_types VALUES (73, 'Team_Elf_Thrower', 14, 2, 29, 33, '', 50, 50, 66.666, 58.333, 70000, 2);
INSERT INTO player_types VALUES (74, 'Team_Elf_Catcher', 14, 3, 29, 32, '', 66.667, 50, 66.666, 58.333, 100000, 4);
INSERT INTO player_types VALUES (75, 'Team_Elf_Blitzer', 14, 4, 29, 1143, '', 58.333, 50, 66.666, 72.222, 110000, 2);
INSERT INTO player_types VALUES (77, 'Team_HighElf_Lineman', 15, 1, 30, 31, '', 50, 50, 66.666, 72.222, 70000, 16);
INSERT INTO player_types VALUES (78, 'Team_HighElf_Thrower', 15, 2, 30, 33, '', 50, 50, 66.666, 72.222, 90000, 2);
INSERT INTO player_types VALUES (79, 'Team_HighElf_Catcher', 15, 3, 30, 32, '', 66.667, 50, 66.666, 58.333, 90000, 4);
INSERT INTO player_types VALUES (80, 'Team_HighElf_Blitzer', 15, 4, 30, 1143, '', 58.333, 50, 66.666, 72.222, 100000, 2);
INSERT INTO player_types VALUES (81, 'Team_Khemri_Skeleton', 16, 1, 31, 102540, '', 41.665, 50, 33.333, 58.333, 40000, 16);
INSERT INTO player_types VALUES (82, 'Team_Khemri_ThroRa', 16, 2, 31, 102611, '', 50, 50, 33.333, 58.333, 70000, 2);
INSERT INTO player_types VALUES (83, 'Team_Khemri_BlitzRa', 16, 4, 31, 102610, '', 50, 50, 33.333, 72.222, 90000, 2);
INSERT INTO player_types VALUES (84, 'Team_Khemri_TombGuardian', 16, 1, 32, 102634, '', 33.333, 70, 16.666, 83.333, 100000, 4);
INSERT INTO player_types VALUES (86, 'Team_Necromantic_Zombie', 17, 1, 33, 102544, '', 33.333, 50, 33.333, 72.222, 40000, 16);
INSERT INTO player_types VALUES (87, 'Team_Necromantic_Ghoul', 17, 3, 34, 102541, '', 58.333, 50, 50, 58.333, 70000, 2);
INSERT INTO player_types VALUES (88, 'Team_Necromantic_Wight', 17, 4, 35, 102542, '', 50, 50, 50, 72.222, 90000, 2);
INSERT INTO player_types VALUES (89, 'BigGuy_Necromantic_FleshGolem', 17, 1, 36, 102635, '', 33.333, 60, 33.333, 83.333, 110000, 2);
INSERT INTO player_types VALUES (90, 'Team_Necromantic_Werewolf', 17, 4, 37, 102636, '', 66.667, 50, 50, 72.222, 120000, 2);
INSERT INTO player_types VALUES (91, 'Team_Nurgle_Rotter', 18, 1, 42, 102616, '', 41.666, 50, 50, 72.222, 40000, 16);
INSERT INTO player_types VALUES (92, 'Team_Nurgle_Pestigor', 18, 4, 43, 102615, '', 50, 50, 50, 72.222, 80000, 4);
INSERT INTO player_types VALUES (93, 'Team_Nurgle_NurgleWarrior', 18, 1, 44, 102614, '', 33.333, 60, 33.333, 83.333, 110000, 4);
INSERT INTO player_types VALUES (94, 'BigGuy_Nurgle_BeastOfNurgle', 18, 1, 45, 102620, '', 33.333, 70, 16.666, 83.333, 140000, 1);
INSERT INTO player_types VALUES (95, 'Team_Ogre_Snotling', 19, 3, 46, 102617, '', 41.666, 30, 50, 27.768, 20000, 16);
INSERT INTO player_types VALUES (96, 'Team_Ogre_Ogre', 19, 1, 47, 35, '', 41.665, 70, 33.333, 83.333, 140000, 6);
INSERT INTO player_types VALUES (97, 'Team_Vampire_Thrall', 20, 1, 48, 102618, '', 50, 50, 50, 58.333, 40000, 16);
INSERT INTO player_types VALUES (98, 'Team_Vampire_Vampire', 20, 2, 49, 102619, '', 50, 60, 66.666, 72.222, 110000, 6);
INSERT INTO player_types VALUES (99, 'AllStar_Human_ZaraTheSlayer', 0, 4, 0, 102647, '', 50, 60, 50, 72.222, 270000, 1);
INSERT INTO player_types VALUES (100, 'AllStar_Goblin_ScrappaSorehead', 0, 3, 0, 102649, '', 58.333, 40, 50, 58.333, 150000, 1);
INSERT INTO player_types VALUES (101, 'AllStar_Elf_EldrilSidewinder', 0, 3, 0, 102648, '', 66.667, 50, 66.667, 58.333, 200000, 1);
INSERT INTO player_types VALUES (102, 'AllStar_Chaos_LordBorakTheDespoiler', 0, 1, 0, 102650, '', 41.667, 70, 50, 83.333, 300000, 1);
INSERT INTO player_types VALUES (103, 'AllStar_Halfling_DeeprootStrongbranch', 0, 1, 0, 102650, '', 16.667, 90, 16.667, 91.666, 300000, 1);
INSERT INTO player_types VALUES (104, 'AllStar_Khemri_Nekbrekerekh', 0, 4, 0, 102652, '', 50, 60, 33.333, 72.222, 220000, 1);
INSERT INTO player_types VALUES (105, 'AllStar_Khemri_RamtutIII', 0, 1, 0, 102651, '', 41.667, 80, 16.667, 83.333, 380000, 1);
INSERT INTO player_types VALUES (106, 'AllStar_Norse_IcepeltHammerblow', 0, 1, 0, 102654, '', 41.667, 80, 16.667, 72.222, 330000, 1);
INSERT INTO player_types VALUES (107, 'Team_Goblin_Bombardier', 6, 4, 17, 102874, '', 50, 40, 50, 58.333, 40000, 1);
";
}
?>
