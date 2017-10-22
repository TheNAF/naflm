<?php

/*
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

class Match_BOTOCS extends Match 
{
    public $hash_botocs = null;
    
    public function __construct($mid) 
    {
        parent::__construct($mid);
        $this->hash_botocs = get_alt_col('leegmgr_matches', 'mid', $mid, 'hash');
    }
    
    public function setBOTOCSHash($hash) 
    {
        #return mysql_query("UPDATE leegmgr_matches SET hash = '".mysql_real_escape_string($hash)."' WHERE mid = $this->match_id");
        return mysql_query("INSERT INTO leegmgr_matches (mid, hash) VALUES ($this->match_id, '".mysql_real_escape_string($hash)."')");
    }
}

?>
