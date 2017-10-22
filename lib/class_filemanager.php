<?php
/*
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

class FileManager {
    static function getCssDirectoryName() {
        return realpath('./css');
    }

    static function getSettingsDirectoryName() {
        return realpath('./localsettings');
    }
    
    static function getAllCoreCssSheetFileNames() {
        $cssDirectory = FileManager::getCssDirectoryName();
        return glob($cssDirectory . '/stylesheet*.css');
    }
    
    static function writeFile($fileName, $fileContents) {
        $file = fopen($fileName, 'w');
        fwrite($file, $fileContents);
        fclose($file);
    }
    
    static function readFile($fileName) {
        $fileContents = '';
        $file = fopen($fileName, 'r');
        while($file && !feof($file))
             $fileContents .= fgets($file);
        fclose($file);
        return $fileContents;
    }
    
    static function copyFile($fileName, $newName) {
        return copy($fileName, $newName);
    }
}