<?php
/**
 *  Copyright (c) Juergen Unfried <juergen.unfried@gmail.com> and Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009. All Rights Reserved.
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

/*
 * Usage example:
 * --------------
 * require_once 'class.OBBLMRssWriter.php';
 * $rss = new RSSfeed('OBBLM League xyz', 'http://www.xyz.com', 'The rss feed for the league....'EN', '1,2');
 * $rss->generateNewsRssFeed();
 *
 * NOTE: This class reads the version from the translations file, so it is required
 *       to include the translation class.
 * IMPORTANT: either place the class in the same folder as the translation xml file
 *            or change the translationFile path in the translation class to point to 
 *            the correct file (eg. ../lang/translations.xml instead of translations.xml
 * 
 * NOTE: don't forget to include the rss.xml file into the html header
 */ 

define('RSS_SIZE', 20); // Number of entries in feed.
define('RSS_FEEDS', implode(',', array(T_TEXT_MSG, 'HOF', 'Wanted', T_TEXT_MATCH_SUMMARY, T_TEXT_TNEWS))); // Create feeds from the text types.

class RSSfeed implements ModuleInterface
{
	/**
	 * The name of the channel. It's how people refer to your service.
	 */
	private $title = '';
	
	/**
	 * The URL to the HTML website corresponding to the channel.
	 */
	private $link = '';
	
	/**
	 *  Phrase or sentence describing the channel.
	 */
	private $desc = '';
	
	/*
	 * A URL that points to the documentation for the format used in the RSS file.
	 */
	private $docs = 'http://www.rssboard.org/rss-specification';
	
	/**
	 * The language the channel is written in.
	 * A list of allowable values for this element, as provided by Netscape, is
	 * at http://www.rssboard.org/rss-language-codes
	 */
	private $lang = '';
	
	/**
	 * Types of texts in table texts that shall be included in the newsfeed.
	 * Either an array of numeric values or a comma seperated string.
	 * Default: 1;
	 */
	private $type = '1';
	
	/**
	 * Constructor for the RSSfeed class
	 *
	 * Required elements
	 * @param String the name of the channel
	 * @param String the url to the website
	 * @param String the description of the channel
	 * 
	 * Optional elements
	 * @param String The language of the channel
	 * @param Types of texts in tabe texts that shall be included in the newsfeed
	 */
	public function __construct($title, $link, $desc, $lang = '', $type = 1) {
   		$this->title = $title;
   		$this->link = $link;
   		$this->desc = $desc;
   		$this->lang = $lang;
   		$this->type = $type;
  	}
  	
  	/**
  	 * Generates the newsfeed and writes it to disc.
  	 *
  	 */
	public function generateNewsRssFeed() {
        $dom = new DOMDocument();
        $dom->formatOutput = true;

        $el_root = $dom->appendChild($dom->createElement('rss'));
        $el_root->setAttribute('version', '2.0');
        
        $el_channel = $el_root->appendChild($dom->createElement('channel'));
        
        $el_channel->appendChild($dom->createElement('title', $this->title));
        $el_channel->appendChild($dom->createElement('link', $this->link));
        $el_channel->appendChild($dom->createElement('description', $this->desc));
        
        if ($this->lang != '') {
        	$el_channel->appendChild($dom->createElement('language', $this->lang));
        }
        
        $el_channel->appendChild($dom->createElement('docs', $this->docs));
        $el_channel->appendChild($dom->createElement('lastBuildDate', date(DATE_RSS)));
        $el_channel->appendChild($dom->createElement('generator', 'OBBLM ' . OBBLM_VERSION));
        
        $entries = array();
        foreach ($this->type as $t) {
            $obj = (object) null;
            switch ($t)
            {
                case T_TEXT_MSG:
                    foreach (Message::getMessages(RSS_SIZE) as $item) {
                        $entries[] = (object) array('title' => "Announcement by ".get_alt_col('coaches', 'coach_id', $item->f_coach_id, 'name').": $item->title", 'desc' => $item->message, 'date' => $item->date);
                    }
                    break;
                    
                case 'HOF':
                    if (Module::isRegistered('HOF')) {
                        foreach (Module::run('HOF', array('getHOF',false,RSS_SIZE)) as $item) {
                            $item = $item['hof'];
                            $entries[] = (object) array('title' => "HOF entry for ".get_alt_col('players', 'player_id', $item->pid, 'name').": $item->title", 'desc' => $item->about, 'date' => $item->date);
                        }
                    }
                    break;
                
                case 'Wanted':
                    if (Module::isRegistered('Wanted')) {
                        foreach (Module::run('Wanted', array('getWanted', false, RSS_SIZE)) as $item) {
                            $item = $item['wanted'];
                            $entries[] = (object) array('title' => "Wanted entry for ".get_alt_col('players', 'player_id', $item->pid, 'name').": $item->bounty", 'desc' => $item->why, 'date' => $item->date);
                        }
                    }
                    break;
                
                case T_TEXT_MATCH_SUMMARY:
                    foreach (MatchSummary::getSummaries(RSS_SIZE) as $item) {
                        $m = new Match($item->match_id);
                        $entries[] = (object) array(
                            'title' => "Match: $m->team1_name ($m->team1_score) vs. $m->team2_name ($m->team2_score)", 
                            'desc' => $m->getText(), 
                            'date' => $m->date_played
                        );
                    }
                    break;
                
                case T_TEXT_TNEWS:
                    foreach (TeamNews::getNews(false, RSS_SIZE) as $item) {
                        $entries[] = (object) array('title' => "Team news by ".get_alt_col('teams', 'team_id', $item->f_id, 'name'), 'desc' => $item->txt, 'date' => $item->date);
                    }
                    break;
            }
        }
        objsort($entries, array('-date'));
        foreach (array_slice($entries, 0, RSS_SIZE) as $item) {
            $el_item = $dom->createElement('item');
            $el_item->appendChild($dom->createElement('title', htmlspecialchars($item->title, ENT_NOQUOTES, "UTF-8")));
            $el_item->appendChild($dom->createElement('description', htmlspecialchars($item->desc, ENT_NOQUOTES, "UTF-8")));
            $el_item->appendChild($dom->createElement('link', $this->link));
#            $el_item->appendChild($dom->createElement('pubDate', $item->date));
            $el_item->appendChild($dom->createElement('pubDate', date('r', strtotime($item->date))));
            $el_item->appendChild($dom->createElement('guid', $this->link . 'index.php?' . date('U', strtotime($item->date)))); # RSS 2.0 dirty workaround.

            $el_channel->appendChild($el_item);
        }
        
        // Write the file
        $handle = fopen ("rss.xml", "w");
        fwrite($handle, $dom->saveXML());
        fclose($handle);
        
        return $dom->saveXML();
    }
    
    public static function main($argv) {
    
        global $settings;
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; 
        $matches = array();
        preg_match('/(\w*)/', strtolower($_SERVER["SERVER_PROTOCOL"]), $matches); 
        $protocol = $matches[0].$s;
        $rss = new RSSfeed(
            $settings['league_name'].' feed', 
            $protocol."://".$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']), 
            'Blood bowl league RSS feed',
            'en-EN', 
            explode(',', RSS_FEEDS)
        );
        echo $rss->generateNewsRssFeed();
    }
    
    public static function getModuleAttributes()
    {
        return array(
            'author'     => 'Juergen Unfried',
            'moduleName' => 'RSS feed',
            'date'       => '2008',
            'setCanvas'  => false,
        );
    }

    public static function getModuleTables()
    {
        return array();
    }
    
    public static function getModuleUpgradeSQL()
    {
        return array();
    }
    
    public static function triggerHandler($type, $argv){}
}

