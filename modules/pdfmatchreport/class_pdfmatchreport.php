<?php
/*
 * Code for generating Match reports in PDF format, for use in table-top settings
 * Author Scott Bartel (thefloppy1), 2015
 * based on work by  Daniel Henriksson, 2010
 * and Daniel Straalman
 * Prints both Teams with skills and stats and postions to one A4 Page in landscape.
 *
 */

require_once('modules/pdf/bb_pdf_class.php');


class PDFMatchReport implements ModuleInterface
{

    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'Daniel Henriksson & Nicholas Rathmann',
            'moduleName' => 'PDF match report',
            'date'       => '2010-2012',
            'setCanvas'  => false,
        );
    }

  public static function getModuleTables(){return array();}

    public static function getModuleUpgradeSQL(){return array();}

    public static function triggerHandler($type, $argv){}

    public static function main($argv)
    {

        global $pdf;
        global $DEA;
        global $skillarray;
        global $rules;
        global $lng;
        global $inducements;

        $FILLED = false;

        if (!empty($argv)) {
            $team1 = new Team($argv[0]);
            $team2 = new Team($argv[1]);
            $match = new Match($argv[2]);
            if (!is_null($team1) && !is_null($team2)) {
                $FILLED = true;
            }
        }
        else {
            $team1 = null;
            $team2 = null;
            $match = null;
        }
        define("MARGINX", 20);
	define("MARGINY", 20);
	define("DEFLINECOLOR", '#000000');
	define("HEADLINEBGCOLOR", '#c3c3c3');

	// Custom settings for inducements.

	define('MAX_STARS', 2);
	define('MERC_EXTRA_COST', 30000);
	define('MERC_EXTRA_SKILL_COST', 50000);

	// Color codes.
	define('COLOR_ROSTER_NORMAL',   COLOR_HTML_NORMAL);
	define('COLOR_ROSTER_READY',    COLOR_HTML_READY);
	define('COLOR_ROSTER_MNG',      COLOR_HTML_MNG);
	define('COLOR_ROSTER_DEAD',     COLOR_HTML_DEAD);
	define('COLOR_ROSTER_SOLD',     COLOR_HTML_SOLD);
	define('COLOR_ROSTER_STARMERC', COLOR_HTML_STARMERC);
	define('COLOR_ROSTER_JOURNEY',  COLOR_HTML_JOURNEY);
	define('COLOR_ROSTER_JOURNEY_USED',  COLOR_HTML_JOURNEY_USED);
	define('COLOR_ROSTER_NEWSKILL', COLOR_HTML_NEWSKILL);
	//-----
	define('COLOR_ROSTER_CHR_EQP1', COLOR_HTML_CHR_EQP1); // Characteristic equal plus one.
	define('COLOR_ROSTER_CHR_GTP1', COLOR_HTML_CHR_GTP1); // Characteristic greater than plus one.
	define('COLOR_ROSTER_CHR_EQM1', COLOR_HTML_CHR_EQM1); // Characteristic equal minus one.
	define('COLOR_ROSTER_CHR_LTM1', COLOR_HTML_CHR_LTM1); // Characteristic less than minus one.

	define('T_PDF_ROSTER_SET_EMPTY_ON_ZERO', true); # Prints cp, td etc. as '' (empty string) when field = 0.

	$ind_cost=0;

        $pdf=new BB_PDF('L','pt','A4'); // Creating a new PDF doc. landscape, scale=pixels, size A4
        $pdf->SetAutoPageBreak(false, 20); // No auto page break to mess up layout

        $pdf->SetAuthor('Daniel Straalman, Daniel Henriksson, Nicholas Rathmann');
        $pdf->SetCreator('OBBLM');

        $pdf->SetTitle($lng->getTrn('name', 'PDFMatchReport'));
        $pdf->SetSubject($lng->getTrn('name', 'PDFMatchReport'));

        $pdf->AddFont('Tahoma','','tahoma.php');  // Adding regular font Tahoma which is in font dir
        $pdf->AddFont('Tahoma','B','tahomabd.php');  // Adding Tahoma Bold

        // Initial settings
        $pdf->SetFont('Tahoma','B',8);
        $pdf->AddPage();
        $pdf->SetLineWidth(1.5);
        $currentx = MARGINX - 10;
        $currenty = MARGINY + 6;


        //Real PDF fill starts here
        $pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
#        $pdf->RoundedRect($currentx, $currenty, 542, 20, 6, 'DF'); // Filled rectangle around Team headline
        $pdf->SetDrawColorBB($pdf->hex2cmyk(DEFLINECOLOR));

        // Text in headline
        $pdf->SetXY($currentx+130,$currenty);
        $pdf->SetFont('Tahoma','B',18);
        $pdf->Cell(360, 20, 'OBBLM Match Report', 0, 0, 'R', false, '');  // make a call to get the league Name instead of hardcoded


        //Printing game info rounded box
        $currenty+=5;

        $pdf->SetFillColorBB($pdf->hex2cmyk('#c6c6c6'));
        $pdf->SetDrawColorBB($pdf->hex2cmyk('#000000'));
        $pdf->SetFontSize(1);
        $pdf->SetLineWidth(0.6);
        #$pdf->RoundedRect($currentx, $currenty, 542, 80, 5, 'D');

        //Score boxes
        $pdf->SetLineWidth(0.4);
        $scoreboxOffset = 20;
#        $pdf->RoundedRect($currentx + 15, $currenty + $scoreboxOffset, 50, 50, 5, 'D');
#        $pdf->RoundedRect($currentx + 475, $currenty + $scoreboxOffset, 50, 50, 5, 'D');
        if ($FILLED) {
            $img1 = new ImageSubSys(IMGTYPE_TEAMLOGO,$team1->team_id);
            $img2 = new ImageSubSys(IMGTYPE_TEAMLOGO,$team2->team_id);
            $pdf->Image($img1->getPath(),$currentx +10,  $currenty + $scoreboxOffset, 60,60,'','',false,0);
            $pdf->Image($img2->getPath(),$currentx + 720, $currenty + $scoreboxOffset, 60,60,'','',false,0);

        }


		$currentx += 190;
		$currenty += 15;
		$pdf->SetXY($currentx,$currenty);
		$pdf->SetFont('Tahoma','B',12);
		$noname = '__________________';
		$norace = '__________________';
		$pdf->Cell(390, 50, (!$FILLED ? $noname : $team1->name).' - '.(!$FILLED ? $noname : $team2->name), 0, 0, 'C', false, '');
		$currenty += 15;
		$pdf->SetXY($currentx,$currenty);
		$pdf->SetFont('Tahoma','',10);
		$pdf->Cell(390, 50, (!$FILLED ? $norace : $team1->f_rname).' - '.(!$FILLED ? $norace : $team2->f_rname), 0, 0, 'C', false, '');


	// Gate + score text
		$pdf->SetFont('Tahoma','',10);
		$space = '        ';
        $currenty += 26;
        $pdf->SetXY($currentx,$currenty);
        $pdf->Cell(210, 50, "Score:       -", 0, 0, 'R', false, '');
        $currenty += 10;
        $pdf->SetXY($currentx,$currenty);
        $pdf->Cell(210, 50, "Gate:${space}k", 0, 0, 'R', false, '');

       //Printing headers for player rows. Do all this twice
        $smallFieldSize = 25;
        $mediumFieldSize = 90;
        $nameFieldSize = 100;
        $skillFieldSize = 350;


        //Print two team boxes

        $currenty += 5;
        $i = 0;
        while ($i < 2) {
            $i++;

            $currenty += 15;
            $currentx = MARGINX + 6;
            $pdf->SetXY($currentx,$currenty);

            $pdf->SetFillColorBB($pdf->hex2cmyk('#c6c6c6'));
            $pdf->SetDrawColorBB($pdf->hex2cmyk('#000000'));
            $pdf->SetFontSize(1);
            $pdf->SetLineWidth(0.6);
            #$pdf->RoundedRect($currentx, $currenty, 542, 315, 5, 'D');

            $currenty += $scoreboxOffset;
            $currentx += 15;
            $pdf->SetXY($currentx,$currenty);


            $currentx += 25;
            $pdf->SetXY($currentx,$currenty);

            $h = 5;
            $pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
            $pdf->SetDrawColorBB($pdf->hex2cmyk(DEFLINECOLOR));
            $pdf->SetFont('Tahoma','B',6);
            $pdf->setLineWidth(1.5);

            // Printing headline for team table
            $pdf->Cell(50, $h, 'Winnings', 1, 0, 'L', true, '');
            $pdf->Cell(60, $h, 'Fan Factor', 1, 0, 'C', true, '');
            $pdf->Cell(70, $h, 'Total team CAS', 1, 0, 'C', true, '');
            $pdf->Cell(35, $h, 'FAME', 1, 0, 'C', true, '');

            if (1 && $FILLED) {
                    $pdf->SetFont('Tahoma','B',7);
                    $pdf->Cell(150, $h, '   '.${"team$i"}->name, 0, 0, 'L', false, '');

            }

            $currenty += 5;
            $pdf->SetXY($currentx,$currenty);

            $h=15;  // Row/cell height for team table row
            $pdf->SetFillColorBB($pdf->hex2cmyk('#FFFFFF'));
            $pdf->SetDrawColorBB($pdf->hex2cmyk('#000000'));
            $pdf->SetFontSize(1);
            $pdf->SetLineWidth(0.6);

            //Team table row
            $pdf->Cell(50, $h, '', 1, 0, 'L', true, '');

             //Fan factor box
            $pdf->SetFont('Tahoma', '', 6);
            $boxx = $currentx +50;
            $boxy = $currenty;
            $pdf->SetXY($boxx += 4, $boxy += 5);
            $pdf->Rect($boxx, $boxy, 5, 5, 'DF');
            $pdf->SetXY($boxx += 5, $boxy -=1);
            $pdf->Cell(20, 8, '+1', 0, 0, 'L', false);

            $pdf->SetXY($boxx += 16, $boxy +=1);
            $pdf->Rect($boxx, $boxy, 5, 5, 'DF');
            $pdf->SetXY($boxx += 5, $boxy -=1);
            $pdf->Cell(20, 8, '0', 0, 0, 'L', false);

            $pdf->SetXY($boxx += 12, $boxy += 1);
            $pdf->Rect($boxx, $boxy, 5, 5, 'DF');
            $pdf->SetXY($boxx += 5, $boxy -=1);
            $pdf->Cell(20, 8, '-1', 0, 0, 'L', false);

            //Total team cas, FAME
            $pdf->SetXY($currentx + 110, $currenty);
            $pdf->Cell(70, $h, '', 1, 0, 'C', true, '');
            $pdf->Cell(35, $h, '', 1, 0, 'C', true, '');


          if (1 && $FILLED) {
                    $t = ${"team$i"};
                    $pdf->SetFont('Tahoma','',7);
                    $statsstr = sprintf('TV: %uk - ReRolls: %u - Apocthecary: %u - Fan Factor: %u - Assistant Coaches: %u - Cheerleaders: %u - Played: %u - Win pct.: %1.0f - ELO: %1.0f - CAS inflicted: %u', $t->tv/1000, $t->rerolls,$t->apothecary,$t->rg_ff,$t->ass_coaches,$t->cheerleaders,$t->mv_played,$t->rg_win_pct, $t->rg_elo, $t->mv_cas);
                    $pdf->Cell(250, $h, '    '.$statsstr, 0, 0, 'L', false, '');
            }

            $currenty += $h + 5;
            $pdf->SetXY($currentx - 20,$currenty);

            //Headers for player table
            $h = 10;
            $pdf->SetFillColorBB($pdf->hex2cmyk(HEADLINEBGCOLOR));
            $pdf->SetDrawColorBB($pdf->hex2cmyk(DEFLINECOLOR));
            $pdf->SetFont('Tahoma','B',8);
            $pdf->setLineWidth(1.5);

            // Printing headline for player table
            $pdf->Cell($smallFieldSize, $h, 'Nr', 1, 0, 'C', true, '');
            $pdf->Cell($mediumFieldSize, $h, 'Position', 1, 0, 'C', true, '');
	        $pdf->Cell($smallFieldSize, $h, 'MA', 1, 0, 'C', true, '');
	        $pdf->Cell($smallFieldSize, $h, 'ST', 1, 0, 'C', true, '');
	        $pdf->Cell($smallFieldSize, $h, 'AG', 1, 0, 'C', true, '');
	        $pdf->Cell($smallFieldSize, $h, 'AV', 1, 0, 'C', true, '');
            $pdf->Cell($skillFieldSize, $h, 'Skills', 1, 0, 'L', true, '');
            $pdf->Cell($smallFieldSize, $h, 'SPP', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'MVP', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'Cp', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'Td', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'Int', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'BH', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'SI', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'Ki', 1, 0, 'C', true, '');
            $pdf->Cell($smallFieldSize, $h, 'Inj', 1, 0, 'C', true, '');

            $currenty+=13;

            $pdf->SetXY($currentx -20,$currenty);
            $pdf->SetFont('Tahoma', '', 6);
            $h=10;  // Row/cell height for player table
            $pdf->SetFillColorBB($pdf->hex2cmyk('#FFFFFF'));
            $pdf->SetDrawColorBB($pdf->hex2cmyk('#000000'));
            $pdf->SetFontSize(8);
            $pdf->SetLineWidth(0.6);


            // Printing player rows
            $tmp_players = array();
            if ($FILLED) {
                $players = ${"team$i"}->getPlayers();
                foreach ($players as $p) {
                    if (!Match::player_validation($p, $match))
                        continue;
                    array_push($tmp_players, $p);
                }
            }
            $players = $tmp_players;

            $j=0;
            while ($j < 16) {
                $j++;

                $nr = '';  //reset all strings to clear
                $pos = '';
                $ma = '';
                $st = '';
                $ag = '';
                $av = '';
                $skills_injuries = '';
                $skillfn = '';
                $spp = '';
                $bgc = COLOR_ROSTER_NORMAL;
                if (count($players) >= $j) {
                  $p = $players[$j-1];

                  $name = $p->name;
                  $pos = $p->position;  //Get position
                  $bgc = COLOR_ROSTER_NORMAL;
                  if ($p->is_journeyman) {
                       $name = "$p->name [J]";
                       $bgc = COLOR_ROSTER_JOURNEY;
                  }
                  if ($p->is_journeyman_used) {
                       $name = "$p->name [J]";
                       $bgc = COLOR_ROSTER_JOURNEY_USED;
                  }
                  if ($p->is_mng) {
                       $name = "$p->name [MNG]";
                       $bgc = COLOR_ROSTER_MNG;
                  }
                  if ($p->mayHaveNewSkill()) {
                       $name = "$p->name";
                       $bgc = COLOR_ROSTER_NEWSKILL;
                  }


                  $nr = $p->nr;
                  $ma = $p->ma;
                  $st = $p->st;
                  $ag = $p->ag;
                  $av = $p->av;

                  //  Get skills and injuries
                  $skillstr = $p->getSkillsStr(false);
                  $injstr = $p->getInjsStr(false);
                  if ($skillstr == '') {  // No skills
                       if ($injstr != '') $skills_injuries=$injstr;  // Only injuries
                      else $skills_injuries=''; // No skills nor injuries
                    }
                  else {
                 if ($injstr != '') $skills_injuries=$skillstr . ', ' . $injstr;   // Skills and injuries separated with ', '
                 else $skills_injuries=$skillstr;  // Only skills, no injuries
                  }

                  $spp = $p->mv_spp;  //Current Star Player Points
                }



               $pdf->SetFillColorBB($pdf->hex2cmyk($bgc));


                $pdf->Cell($smallFieldSize, $h, $nr, 1, 0, 'C', true, '');
                $pdf->Cell($mediumFieldSize, $h, $pos, 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, $ma, 1, 0, 'C', true, '');
	            $pdf->Cell($smallFieldSize, $h, $st, 1, 0, 'C', true, '');
	            $pdf->Cell($smallFieldSize, $h, $ag, 1, 0, 'C', true, '');
	            $pdf->Cell($smallFieldSize, $h, $av, 1, 0, 'C', true, '');
                $pdf->Cell($skillFieldSize, $h, $skills_injuries, 1, 0, 'L', true, '');
                $pdf->Cell($smallFieldSize, $h, $spp, 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');
                $pdf->Cell($smallFieldSize, $h, '', 1, 0, 'C', true, '');

                $currenty+=$h;
                $pdf->SetXY($currentx - 20,$currenty);

            }


            // Color legends
            $pdf->SetFont('Tahoma', '', 7);
            $currentx = 335;
            $currenty += 5;
            $dd = 2;
            $pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_MNG));
            $pdf->SetXY($currentx, $currenty);
            $pdf->Rect($currentx, $currenty, 5, 5, 'DF');
            $pdf->SetXY($currentx+=5, $currenty-=1);
            $pdf->Cell(20, 8, 'MNG', 0, 0, 'L', false);
            $pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_JOURNEY));
            $pdf->Rect($currentx+=22+$dd, $currenty+=1, 5, 5, 'DF');
            $pdf->SetXY($currentx+=5, $currenty-=1);

            $pdf->Cell(45, 8, 'Journeyman', 0, 0, 'L', false);

            $pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_JOURNEY_USED));
            $pdf->Rect($currentx+=47+$dd, $currenty+=1, 5, 5, 'DF');
            $pdf->SetXY($currentx+=5, $currenty-=1);
            $pdf->Cell(45, 8, 'Used journeyman', 0, 0, 'L', false);
            $pdf->SetFillColorBB($pdf->hex2cmyk(COLOR_ROSTER_NEWSKILL));
            $pdf->Rect($currentx+=64+$dd, $currenty+=1, 5, 5, 'DF');
            $pdf->SetXY($currentx+=5, $currenty-=1);
            $pdf->Cell(70, 8, 'New skill available', 0, 0, 'L', false);


        }

        // Output the PDF document
        $pdf->Output("Match Report.pdf", 'I');
    }

}