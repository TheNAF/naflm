<?php

/*
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

class Email {
    private static function checkEmail($email) {
        #'/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' : 
        #'/^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i' 

        $status = true;
        $domain = '';
        if (strpos($email, '@')) 
            list($emailuser,$domain) = split("@",$email);

        $emailexp = "/^([.0-9a-z_-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,4})$/i";

        if (!preg_match($emailexp, $email)) 
            $status = false;

        if (!getmxrr($domain, $mxrecords) || !$status)
            $status = false;

        return $status;
    }
    
    public static function getAdministratorEmails() {
        $email = '';
        $sep_comma = "";
        $coaches = Coach::getCoaches();
        foreach ($coaches as $c) {
            if ($c->ring === Coach::T_RING_GLOBAL_ADMIN && Email::checkEmail($c->mail)) {
                $email = $email.$sep_comma.$c->mail;
                if ( $email ) 
                    $sep_comma = ", ";
            }
        }
        return $email;
    }
    
    public static function getLeagueCommissionerEmails($leagueID) {
        $email = '';
        $sep_comma = "";
        $coaches = Coach::getCoaches();
        foreach ($coaches as $c) {
            if($c->isNodeCommish(T_NODE_LEAGUE, $leagueID) && Email::checkEmail($c->mail)) {
                $email = $email.$sep_comma.$c->mail;
                
                if ( $email ) 
                    $sep_comma = ", ";
            }
        }
        return $email;
    }
}