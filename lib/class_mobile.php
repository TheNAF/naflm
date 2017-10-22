<?php

/*
 *   Released under GPL.
 */

class Mobile {
    public static function setIsMobile($isMobile) {
        if(isset($isMobile))
            $_SESSION['isMobile'] = $isMobile;
    }
    
    public static function isMobile() {
		return $_SESSION['isMobile'];
    }
}

