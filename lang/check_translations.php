<?php
$refLang='en-GB';
$translatedLanguages = array('es-ES', 'de-DE', 'fr-FR', 'it-IT');
$translations = array();
$ref = array();

$doc = new DOMDocument();
$doc->load("translations.xml");

$xpath = new DOMXpath($doc);
$refElements = $xpath->query("/translations/".$refLang."/*/*");
if (!is_null($refElements)) {
  foreach ($refElements as $element) {
    $ref[] = $element->nodeName.'@'.$element->parentNode->nodeName ;
  }
}
foreach ($translatedLanguages as $lang){
  $langElements = $xpath->query("/translations/".$lang."/*/*");
  $translation = array();
  if (!is_null($langElements)) {
    foreach ($langElements as $element) {
      $translation[] = $element->nodeName.'@'.$element->parentNode->nodeName ;
    }
  }
  $notTranslated = array_diff($ref,$translation);
  if( count($notTranslated) ){
    echo "<h1>Not translated for $lang</h1>\n";
    echo "<ul>\n";
    foreach ($notTranslated as $value) {
      echo "<li>$value</li>\n";
    }
    echo "</ul>\n";
  }
}

?>