<?php

/*
 *  Copyright (c) Daniel Straalman <email is protected> 2008-2009. All Rights Reserved.
 *
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/*****************************************************************************************
* Author: Daniel Straalman, 2009
* Most of the below functions were found in the Scripts section at www.fpdf.org
* Lars Scharrenberg wrote the first version of PDF support in OBBLM and used PDFlib
* which was used heavily as a reference. rgb2cmyk and hex2rgb are left from Lars' version
******************************************************************************************/

require_once('fpdf.php');

class BB_PDF extends FPDF
{

var $tmpFiles = array(); // Private for PNG Alpha Channel support

// For rounded rectangles
// Allows to draw rounded rectangles. Parameters are:
//
// x, y: top left corner of the rectangle.
// w, h: width and height.
// r: radius of the rounded corners.
// style: same as Rect(): F, D (default), FD or DF. 

function RoundedRect($x, $y, $w, $h, $r, $style = '')
{
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' or $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2F %.2F m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2F %.2F l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2F %.2F l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
}

// Belongs to RoundedRect()
function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
{
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
}

function SetDrawColorBB($cmyk) {
    //Set color for all stroking operations
    // $cmyk is array 0 => c, 1 => m, 2 => y, 3 => k
    if (is_array($cmyk)) 
        $this->DrawColor = sprintf('%.3f %.3f %.3f %.3f K', $cmyk[0] / 100, $cmyk[1] / 100, $cmyk[2] / 100, $cmyk[3] / 100);
    else
        $this->DrawColor = '0 G';
    if($this->page > 0)
        $this->_out($this->DrawColor);
}

function SetFillColorBB($cmyk) {
    // Set fill color
    // $cmyk is array 0 => c, 1 => m, 2 => y, 3 => k
    if (is_array($cmyk)) 
        $this->FillColor = sprintf('%.3f %.3f %.3f %.3f k', $cmyk[0] / 100, $cmyk[1] / 100, $cmyk[2] / 100, $cmyk[3] / 100);
    else 
        $this->FillColor = '0 g';
    $this->ColorFlag = ($this->FillColor != $this->TextColor);
    if($this->page > 0)
        $this->_out($this->FillColor);
}

function SetTextColorBB($cmyk) {
    //Set color for text
    // $cmyk is array 0 => c, 1 => m, 2 => y, 3 => k
    if (is_array($cmyk)) 
        $this->TextColor = sprintf('%.3f %.3f %.3f %.3f k',$cmyk[0] / 100, $cmyk[1] / 100, $cmyk[2] / 100, $cmyk[3] / 100);
    else
        $this->TextColor = '0 g';
    $this->ColorFlag = ($this->FillColor != $this->TextColor);
}

// Print Player row
function print_prow($p, $x, $y, $h, $bgcolor='#FFFFFF', $bordercolor='#000000', $linewidth=1, $fontsize, $ma_color, $st_color, $ag_color, $av_color) {
  $this->SetFillColorBB($this->hex2cmyk($bgcolor));
  $this->SetDrawColorBB($this->hex2cmyk($bordercolor));
  $this->SetFontSize($fontsize);
  $this->SetLineWidth($linewidth);
  $this->SetXY($x,$y);
  $newheight = $h;
  $newfontsize=$fontsize;
  
  // Needs to correct fontsize and height for skills, if text doesn't fit
  list($newheight, $newfontsize) = $this->FitTextInCell($h, 329, $fontsize, $p['skills']);
  if ($newheight<$h)
    $h=$newheight*2;
  $this->SetFontSize($fontsize);
  
  //Print cells in order
  $this->Cell(23, $h, $p['nr'], 1, 0, 'C', true, '');
  $this->Cell(97, $h, $p['name'], 1, 0, 'L', true, '');
  $this->Cell(75, $h, $p['pos'], 1, 0, 'L', true, '');
  $this->SetFillColorBB($this->hex2cmyk($ma_color));
  $this->Cell(18, $h, $p['ma'], 1, 0, 'C', true, '');
  $this->SetFillColorBB($this->hex2cmyk($st_color));
  $this->Cell(18, $h, $p['st'], 1, 0, 'C', true, '');
  $this->SetFillColorBB($this->hex2cmyk($ag_color));
  $this->Cell(18, $h, $p['ag'], 1, 0, 'C', true, '');
  $this->SetFillColorBB($this->hex2cmyk($av_color));
  $this->Cell(18, $h, $p['av'], 1, 0, 'C', true, '');
  $this->SetFillColorBB($this->hex2cmyk($bgcolor));
  $this->SetXY(($x+23+97+75+18+18+18+18),$y);
  // Need to change to MultiCell to fit Skills and Injuries text if too long
  if ($newfontsize<$fontsize) {
    $this->SetFontSize($newfontsize);
    $this->MultiCell(329, $newheight, $p['skills'], 1, 'L', true);
    $this->SetFontSize($fontsize);
  }
  else 
    $this->MultiCell(329, $h, $p['skills'], 1, 'L', true);
  $this->SetXY($x+23+97+75+18+18+18+18+329,$y);
  $this->Cell(23, $h, $p['inj'], 1, 0, 'C', true, '');
  $this->Cell(21, $h, $p['cp'], 1, 0, 'C', true, '');
  $this->Cell(21, $h, $p['td'], 1, 0, 'C', true, '');
  $this->Cell(21, $h, $p['int'], 1, 0, 'C', true, '');
  $this->Cell(21, $h, $p['cas'], 1, 0, 'C', true, '');
  $this->Cell(23, $h, $p['mvp'], 1, 0, 'C', true, '');
  $this->Cell(25, $h, $p['spp'], 1, 0, 'C', true, '');
  $this->Cell(41, $h, $p['value'], 1, 1, 'R', true, '');
  return $h; // To know y pos for next player row
}

// Print stars and mercs row
function print_srow($p, $x, $y, $h, $bgcolor='#FFFFFF', $bordercolor='#000000', $linewidth=1, $fontsize) {
  $this->SetFillColorBB($this->hex2cmyk($bgcolor));
  $this->SetDrawColorBB($this->hex2cmyk($bordercolor));
  $this->SetFontSize($fontsize);
  $this->SetLineWidth($linewidth);
  $this->SetXY($x,$y);
  $newheight = $h;
  $newfontsize=$fontsize;
  
  // Needs to correct fontsize and height for skills, if text doesn't fit
  list($newheight, $newfontsize) = $this->FitTextInCell($h, 329, $fontsize, $p['skills']);
  if ($newheight<$h)
    $h=$newheight*2;
  $this->SetFontSize($fontsize);
  
  //Print cells in order
  $this->Cell(97+75, $h, $p['name'], 0, 0, 'L', true, '');
  $this->Cell(18, $h, $p['ma'], 0, 0, 'C', true, '');
  $this->Cell(18, $h, $p['st'], 0, 0, 'C', true, '');
  $this->Cell(18, $h, $p['ag'], 0, 0, 'C', true, '');
  $this->Cell(18, $h, $p['av'], 0, 0, 'C', true, '');
  $this->SetXY(($x+97+75+18+18+18+18),$y);
  // Need to change to MultiCell to fit Skills and Injuries text if too long
  if ($newfontsize<$fontsize) {
    $this->SetFontSize($newfontsize);
    $this->MultiCell(329, $newheight, $p['skills'], 0, 'L', true);
    $this->SetFontSize($fontsize);
  }
  else 
    $this->MultiCell(329, $h, $p['skills'], 0, 'L', true);
  $this->SetXY($x+97+75+18+18+18+18+329,$y);
  $this->Cell(21, $h, $p['cp'], 0, 0, 'C', true, '');
  $this->Cell(21, $h, $p['td'], 0, 0, 'C', true, '');
  $this->Cell(21, $h, $p['int'], 0, 0, 'C', true, '');
  $this->Cell(21, $h, $p['cas'], 0, 0, 'C', true, '');
  $this->Cell(23, $h, $p['mvp'], 0, 0, 'C', true, '');
  $this->Cell(25, $h, $p['spp'], 0, 0, 'C', true, '');
  $this->Cell(41, $h, $p['value'], 0, 1, 'R', true, '');
  return $h; // To know y pos for next row
}

function print_box($x, $y, $w, $h, $bgcolor='#FFFFFF', $bordercolor='#000000', $linewidth, $borderstyle, $fontsize, $font, $bold=false, $align, $text) {
  $this->SetFillColorBB($this->hex2cmyk($bgcolor));
  $this->SetDrawColorBB($this->hex2cmyk($bordercolor));
  ($bold) ? $this->SetFont($font, 'B', $fontsize) : $this->SetFont($font, '', $fontsize);
  $this->SetLineWidth($linewidth);
  $this->SetXY($x,$y);
  // Print the cell with text
  $this->Cell($w, $h, $text, $borderstyle, 0, $align, true, '');
}

function print_inducements($x, $y, $h, $bgcol, $linecol, $fontsize, $ind_name, $ind_amount, $ind_value) {
  (is_null($ind_amount)) ? $multiplier='' : $multiplier='x';
  $this->print_box(($currentx = $x), $y, 170, $h, $bgcol, $linecol, 0, 0, $fontsize, 'Tahoma', false, 'R', $ind_name);
  $this->print_box(($currentx += 170), $y, 15, $h, $bgcol, $linecol, 0, 0, $fontsize, 'Tahoma', false, 'C', $ind_amount);
  $this->print_box(($currentx += 15), $y, 15, $h, $bgcol, $linecol, 0, 0, $fontsize, 'Tahoma', false, 'C', $multiplier);
  $this->print_box(($currentx += 15), $y, 35, $h, $bgcol, $linecol, 0, 0, $fontsize, 'Tahoma', false, 'R', $ind_value);
}

function print_team_goods($x, $y, $h, $bgcol, $linecol, $perm_name, $perm_nr, $perm_value, $perm_total_value, $bold=false) {
  $this->print_box(($currentx = $x), $y, 40, $h, $bgcol, $linecol, 0, 0, 8, 'Tahoma', $bold, 'R', $perm_name);
  $this->print_box(($currentx += 40), $y, 20, $h, $bgcol, $linecol, 0, 0, 8, 'Tahoma', $bold, 'C', $perm_nr);
  $this->print_box(($currentx += 20), $y, 20, $h, $bgcol, $linecol, 0, 0, 8, 'Tahoma', $bold, 'C', 'x');
  $this->print_box(($currentx += 20), $y, 30, $h, $bgcol, $linecol, 0, 0, 8, 'Tahoma', $bold, 'R', $perm_value);
  $this->print_box(($currentx += 30), $y, 20, $h, $bgcol, $linecol, 0, 0, 8, 'Tahoma', $bold, 'C', '=');
  $this->print_box(($currentx += 20), $y, 45, $h, $bgcol, $linecol, 0, 0, 8, 'Tahoma', $bold, 'R', $perm_total_value);
}

function hex2cmyk($hex) {
  $color = str_replace('#','',$hex);
  $r = hexdec(substr($color,0,2));
  $g = hexdec(substr($color,2,2));
  $b = hexdec(substr($color,4,2));
  $cyan    = 255 - $r;
  $magenta = 255 - $g;
  $yellow  = 255 - $b;
  $black   = min($cyan, $magenta, $yellow);
  $cyan    = @(($cyan    - $black) / (255 - $black)) * 255;
  $magenta = @(($magenta - $black) / (255 - $black)) * 255;
  $yellow  = @(($yellow  - $black) / (255 - $black)) * 255;
  
  return array($cyan / 2.55, $magenta / 2.55, $yellow / 2.55, $black / 2.55);
}

function GetStringRemainder($s, $cellsize) {
	//Get remainder that doesnt fit in cell
	$s=(string)$s;
	$cw=&$this->CurrentFont['cw'];
	$w=0;
	$cellsize*=1000;   // To have same scale as CurrentFont['cw']
	$linebreaked=false;
	$l=strlen($s);
	$sep=-1;
	for($i=0;$i<$l;$i++) {
    if($s[$i]==' ') $sep=$i; 
		$w+=$cw[$s[$i]];
		if (($w*$this->FontSize)>$cellsize) {
		  $linebreaked=true;
		  break;
		}
	}
	$second_line=substr($s,$sep,$l);
	if ($linebreaked)
	  return $second_line;
	else
	  return '0';
}

function FitTextInCell($h, $cellpx, $fontsize, $txt) {
  $fontsizesave = $this->FontSizePt;
  $txt = str_replace("\r",'',$txt); // Removing any \r, like MultiCell
  $cellpx-=5.67; // Reducing with fpdf hardcoded cellmargins, 2*2.835
  $f = $fontsize;
  $this->SetFontSize($f);   // Just to make sure font is set to expected size...
  $r=$this->GetStringRemainder($txt, $cellpx);
  if ($r == '0')    // Meaning $txt fits in 1 line
    return array(0=>$h, 1=>$f);

  while ($r != '0') {
    $this->SetFontSize($f-=0.5); // Downsize font 
    $r=$this->GetStringRemainder($txt, $cellpx);
    if ($r == '0') // txt fits in 1 line
      break;
    else {
      $r2=$this->GetStringRemainder($r, $cellpx);
      if ($r2 == '0') {
        $h=$h/2+0.5;    // Setting cellheight to half because MultiCell() will double that height when it linebreaks. And adding 1px extra space.
        break;
      }
      else
        continue;       // Second line is still too long to fit.
    }
  }

  $this->SetFontSize($fontsizesave); // Resetting fontsize to what it was before calling this function
  return array(0=>$h, 1=>$f); // Returning array with height and fontsize.
}

// Adding space as thousand divider for numbers
function Mf($m) {
  $r=number_format((double) $m,0,'.',' ');
  return $r;
}

// Version 1.2 of PDF_ImageAlpha
// by Valentin Schmidt
// For alpha support in PNG images, which FPDF 1.6 doesnt have
function Image($file,$x=null, $y=null,$w=0,$h=0,$type='',$link='', $isMask=false, $maskImg=0)
{
    //Put an image on the page
    if(!isset($this->images[$file]))
    {
        //First use of image, get info
        if($type=='')
        {
            $pos=strrpos($file,'.');
            if(!$pos)
                $this->Error('Image file has no extension and no type was specified: '.$file);
            $type=substr($file,$pos+1);
        }
        $type=strtolower($type);
        $mqr=get_magic_quotes_runtime();
#        set_magic_quotes_runtime(0);
        if($type=='jpg' || $type=='jpeg')
            $info=$this->_parsejpg($file);
        elseif($type=='png'){
            $info=$this->_parsepng($file);
            if ($info=='alpha') return $this->ImagePngWithAlpha($file,$x,$y,$w,$h,$link);
        }
        else
        {
            //Allow for additional formats
            $mtd='_parse'.$type;
            if(!method_exists($this,$mtd))
                $this->Error('Unsupported image type: '.$type);
            $info=$this->$mtd($file);
        }
#        set_magic_quotes_runtime($mqr);
        
        if ($isMask){
      $info['cs']="DeviceGray"; // try to force grayscale (instead of indexed)
    }
        $info['i']=count($this->images)+1;
        if ($maskImg>0) $info['masked'] = $maskImg;###
        $this->images[$file]=$info;
    }
    else
        $info=$this->images[$file];
    //Automatic width and height calculation if needed
    if($w==0 && $h==0)
    {
        //Put image at 72 dpi
        $w=$info['w']/$this->k;
        $h=$info['h']/$this->k;
    }
    if($w==0)
        $w=$h*$info['w']/$info['h'];
    if($h==0)
        $h=$w*$info['h']/$info['w'];
        
    if ($isMask) $x = $this->CurPageFormat[1] + 10; // embed hidden, ouside the canvas, was: fwPt
    $this->_out(sprintf('q %.2f 0 0 %.2f %.2f %.2f cm /I%d Do Q',$w*$this->k,$h*$this->k,$x*$this->k,($this->h-($y+$h))*$this->k,$info['i']));
    if($link)
        $this->Link($x,$y,$w,$h,$link);
        
    return $info['i'];
}

// Belongs to Image() but can be called if you know it is a PNG you're working with
// needs GD 2.x extension
// pixel-wise operation, not very fast
function ImagePngWithAlpha($file,$x,$y,$w=0,$h=0,$link='')
{
    $tmp_alpha = tempnam('.', 'mska');
    $this->tmpFiles[] = $tmp_alpha;
    $tmp_plain = tempnam('.', 'mskp');
    $this->tmpFiles[] = $tmp_plain;
    
    list($wpx, $hpx) = getimagesize($file);
    $img = imagecreatefrompng($file);
    $alpha_img = imagecreate( $wpx, $hpx );
    
    // generate gray scale pallete
    for($c=0;$c<256;$c++) ImageColorAllocate($alpha_img, $c, $c, $c);
    
    // extract alpha channel
    $xpx=0;
    while ($xpx<$wpx){
        $ypx = 0;
        while ($ypx<$hpx){
            $color_index = imagecolorat($img, $xpx, $ypx);
            $col = imagecolorsforindex($img, $color_index);
            imagesetpixel($alpha_img, $xpx, $ypx, $this->_gamma( (127-$col['alpha'])*255/127)  );
        ++$ypx;
        }
        ++$xpx;
    }

    imagepng($alpha_img, $tmp_alpha);
    imagedestroy($alpha_img);
    
    // extract image without alpha channel
    $plain_img = imagecreatetruecolor ( $wpx, $hpx );
    imagecopy ($plain_img, $img, 0, 0, 0, 0, $wpx, $hpx );
    imagepng($plain_img, $tmp_plain);
    imagedestroy($plain_img);
    
    //first embed mask image (w, h, x, will be ignored)
    $maskImg = $this->Image($tmp_alpha, 0,0,0,0, 'PNG', '', true);
    
    //embed image, masked with previously embedded mask
    $this->Image($tmp_plain,$x,$y,$w,$h,'PNG',$link, false, $maskImg);
}

// Belongs to Image()
function Close()
{
    parent::Close();
    // clean up tmp files
    foreach($this->tmpFiles as $tmp) @unlink($tmp);
}

/*******************************************************************************
*                                                                              *
*                               Private methods                                *
*                                                                              *
*******************************************************************************/
// Belongs to Image()
function _putimages()
{
    $filter=($this->compress) ? '/Filter /FlateDecode ' : '';
    reset($this->images);
    while(list($file,$info)=each($this->images))
    {
        $this->_newobj();
        $this->images[$file]['n']=$this->n;
        $this->_out('<</Type /XObject');
        $this->_out('/Subtype /Image');
        $this->_out('/Width '.$info['w']);
        $this->_out('/Height '.$info['h']);
        
        if (isset($info["masked"])) $this->_out('/SMask '.($this->n-1).' 0 R'); ###
        
        if($info['cs']=='Indexed')
            $this->_out('/ColorSpace [/Indexed /DeviceRGB '.(strlen($info['pal'])/3-1).' '.($this->n+1).' 0 R]');
        else
        {
            $this->_out('/ColorSpace /'.$info['cs']);
            if($info['cs']=='DeviceCMYK')
                $this->_out('/Decode [1 0 1 0 1 0 1 0]');
        }
        $this->_out('/BitsPerComponent '.$info['bpc']);
        if(isset($info['f']))
            $this->_out('/Filter /'.$info['f']);
        if(isset($info['parms']))
            $this->_out($info['parms']);
        if(isset($info['trns']) && is_array($info['trns']))
        {
            $trns='';
            for($i=0;$i<count($info['trns']);$i++)
                $trns.=$info['trns'][$i].' '.$info['trns'][$i].' ';
            $this->_out('/Mask ['.$trns.']');
        }
        $this->_out('/Length '.strlen($info['data']).'>>');
        $this->_putstream($info['data']);
        unset($this->images[$file]['data']);
        $this->_out('endobj');
        //Palette
        if($info['cs']=='Indexed')
        {
            $this->_newobj();
            $pal=($this->compress) ? gzcompress($info['pal']) : $info['pal'];
            $this->_out('<<'.$filter.'/Length '.strlen($pal).'>>');
            $this->_putstream($pal);
            $this->_out('endobj');
        }
    }
}

// Belongs to Image()
// GD seems to use a different gamma, this method is used to correct it again
function _gamma($v){
    return pow ($v/255, 2.2) * 255;
}

// Belongs to Image()
// this method is overwriting the original version is only needed to make the Image method support PNGs with alpha channels.
// if you only use the ImagePngWithAlpha method for such PNGs, you can remove it from this script.
function _parsepng($file)
{
    //Extract info from a PNG file
    $f=fopen($file,'rb');
    if(!$f)
        $this->Error('Can\'t open image file: '.$file);
    //Check signature
    if(fread($f,8)!=chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10))
        $this->Error('Not a PNG file: '.$file);
    //Read header chunk
    fread($f,4);
    if(fread($f,4)!='IHDR')
        $this->Error('Incorrect PNG file: '.$file);
    $w=$this->_readint($f);
    $h=$this->_readint($f);
    $bpc=ord(fread($f,1));
    if($bpc>8)
        $this->Error('16-bit depth not supported: '.$file);
    $ct=ord(fread($f,1));
    if($ct==0)
        $colspace='DeviceGray';
    elseif($ct==2)
        $colspace='DeviceRGB';
    elseif($ct==3)
        $colspace='Indexed';
    else {
        fclose($f);      // the only changes are
        return 'alpha';  // made in those 2 lines
    }
    if(ord(fread($f,1))!=0)
        $this->Error('Unknown compression method: '.$file);
    if(ord(fread($f,1))!=0)
        $this->Error('Unknown filter method: '.$file);
    if(ord(fread($f,1))!=0)
        $this->Error('Interlacing not supported: '.$file);
    fread($f,4);
    $parms='/DecodeParms <</Predictor 15 /Colors '.($ct==2 ? 3 : 1).' /BitsPerComponent '.$bpc.' /Columns '.$w.'>>';
    //Scan chunks looking for palette, transparency and image data
    $pal='';
    $trns='';
    $data='';
    do
    {
        $n=$this->_readint($f);
        $type=fread($f,4);
        if($type=='PLTE')
        {
            //Read palette
            $pal=fread($f,$n);
            fread($f,4);
        }
        elseif($type=='tRNS')
        {
            //Read transparency info
            $t=fread($f,$n);
            if($ct==0)
                $trns=array(ord(substr($t,1,1)));
            elseif($ct==2)
                $trns=array(ord(substr($t,1,1)),ord(substr($t,3,1)),ord(substr($t,5,1)));
            else
            {
                $pos=strpos($t,chr(0));
                if($pos!==false)
                    $trns=array($pos);
            }
            fread($f,4);
        }
        elseif($type=='IDAT')
        {
            //Read image data block
            $data.=fread($f,$n);
            fread($f,4);
        }
        elseif($type=='IEND')
            break;
        else
            fread($f,$n+4);
    }
    while($n);
    if($colspace=='Indexed' && empty($pal))
        $this->Error('Missing palette in '.$file);
    fclose($f);
    return array('w'=>$w,'h'=>$h,'cs'=>$colspace,'bpc'=>$bpc,'f'=>'FlateDecode','parms'=>$parms,'pal'=>$pal,'trns'=>$trns,'data'=>$data);
}

}

