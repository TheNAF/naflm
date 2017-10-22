<?php
/**
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009-2010. All Rights Reserved.
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
 
class Translations 
{

public static $registeredLanguages = array('en-GB', 'es-ES', 'de-DE', 'fr-FR', 'it-IT');
const main = 'main'; # $this->docs[] key of main translation file.
const fallback = 'en-GB'; # Default language.

private $lang = self::fallback; # Translation language used.
private $translationFiles = array(); # XML file names.
private $docs = array(); # DOMDocument objects of the XML files.

public function __construct($lang = false) {
	$this->setLanguage($lang);
	$this->registerTranslationFile(self::main, 'lang/translations.xml');
}

public function setLanguage($lang) {
    $this->lang = in_array($lang, self::$registeredLanguages) ? $lang : self::fallback;
}

public function getLanguage() {
    return $this->lang;
}

public function registerTranslationFile($doc, $file) {
	$this->translationFiles[$doc] = $file;
	$this->docs[$doc] = new DOMDocument();
	$this->docs[$doc]->Load($file);
}
	
public function getTrn($key, $doc = false) { 		

    if (!$doc) {
        $doc = self::main;
    }
    if (!in_array($doc, array_keys($this->docs))) {
        fatal("Failed to look up key '$key' in the translation document '$doc'. $doc is not loaded/exists. The available documents are: ".implode(', ', array_keys($this->docs)));
    }

    $xpath = new DOMXpath($this->docs[$doc]);
    $query = $xpath->query("//$this->lang/$key");
    if ($query->length == 0) {
        # Try fallback language
        $query = $xpath->query("//".self::fallback."/$key");
        if ($query->length == 0)
          return (string) "TRANSLATION ERR ! $key";
    }
    return (string) $query->item(0)->nodeValue;
}

// Filter some characters from the players positions ([J]  &nbsp; spaces etc...)
public function FilterPosition($position)
{
  $position = str_replace(array('&nbsp;',' ','-','[J]'),'',$position);
  return $position;
}


/***************************
   Translate skills
***************************/
public function TranslateSkills()
{
  global $skillarray;
  foreach($skillarray as $cat => $val)
  {
    foreach($skillarray[$cat] as &$skl)
    {
      $skl = $this->getTrn('skill/'.strtolower(str_replace(array(' ','-','&',"'",'/'),'',$skl)));
    }
  }
  unset($skl);
    
  global $skillididx;
  foreach($skillididx as &$skill)
  {
    $skill = $this->getTrn('skill/'.strtolower(str_replace(array(' ','-','&',"'",'/'),'',$skill)));
  }
  unset($skill);
}

}	

