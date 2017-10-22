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

define('NO_PIC', IMG.'/nopic.png'); # Used when no picture is uploaded.
define('UPLOAD_DIR', IMG);

define('IMGTYPE_PLAYER',      1);
define('IMGTYPE_TEAMLOGO',    2);
define('IMGTYPE_TEAMSTADIUM', 3);
define('IMGTYPE_COACH',       4);

define('IMGPATH_PLAYERS',      UPLOAD_DIR.'/players');
define('IMGPATH_TEAMLOGOS',    UPLOAD_DIR.'/teams');
define('IMGPATH_TEAMSTADIUMS', UPLOAD_DIR.'/stadiums');
define('IMGPATH_COACHES',      UPLOAD_DIR.'/coaches');

class ImageSubSys
{
    /***************
     * Properties 
     ***************/

    public $obj = null;
    public $obj_id = 0;
    
    public static $defaultHTMLUploadName = 'pic'; # The default value of the file's <input type='file'> name field.
    
    // Supported file types.
    private static $supportedExtensions = array(
        # mime type => extension
        'image/jpeg'    => array('jpg','jpeg','jpe'),
        'image/pjpeg'   => array('jpg'),
        'image/png'     => array('png'),
        'image/x-png'   => array('png'),
        'image/tiff'    => array('tif'),
        'image/x-tiff'  => array('tif'),
        'image/gif'     => array('gif'),        
    );
    
    // Type to path mappings.
    private static $typeToPathMappings = array(
        IMGTYPE_PLAYER      => IMGPATH_PLAYERS,
        IMGTYPE_TEAMLOGO    => IMGPATH_TEAMLOGOS,
        IMGTYPE_TEAMSTADIUM => IMGPATH_TEAMSTADIUMS,
        IMGTYPE_COACH       => IMGPATH_COACHES,
    );
    
    /***************
     * Methods 
     ***************/
    
    public function __construct($obj, $obj_id) {
        $this->obj = $obj;
        $this->obj_id = $obj_id;
    }

    public function getPath($rid = false) 
    {
        foreach (self::$supportedExtensions as $exts) {
            foreach ($exts as $ext) {
                if (file_exists($filePath = self::$typeToPathMappings[$this->obj].'/'.$this->obj_id.'.'.$ext)) {
                    return $filePath;
                }
            }
        }
        
        // Else return default image.
        if ($this->obj == IMGTYPE_TEAMLOGO) {
            global $DEA, $raceididx;
            $race = $raceididx[($rid) ? $rid : get_alt_col('teams', 'team_id', $this->obj_id, 'f_race_id')];
            return file_exists($path=RACE_ICONS.'/'.$DEA[$race]['other']['icon']) ? $path : NO_PIC;
        }
        else {
            return NO_PIC;
        }
    }

    public function save($file_name = false) 
    {
        // $file_name must be a valid key in the $_FILES array.
    
        // Use default file name?
        if (!$file_name) {
            $file_name = self::$defaultHTMLUploadName;
        }
        
        // Errors?        
        if (!isset($_FILES[$file_name]['tmp_name'])) {
            return array(false, 'Internal error: Can\'t find the uploaded file in PHP $_FILES array.');
        }
        $supportedMIMEs = array_keys(self::$supportedExtensions);
        if (!in_array($_FILES[$file_name]['type'], $supportedMIMEs)) {
            return array(false, '"'.$_FILES[$file_name]['type'].'" is an unsupported filetype. The supported filetypes are: '.implode(', ', $supportedMIMEs));
        }
        
        // Create parent dir if non existing.
        if (!is_dir(self::$typeToPathMappings[$this->obj])) {
            mkdir(self::$typeToPathMappings[$this->obj]);
        }
        
        // Delete all other possible existing files.
        foreach (self::$supportedExtensions as $mimeType => $exts) {
            foreach ($exts as $ext) {
                @unlink(self::$typeToPathMappings[$this->obj].'/'.$this->obj_id.'.'.$ext);
            }
        }

        // Move file away from temp location.
        return move_uploaded_file(
                $A = $_FILES[$file_name]['tmp_name'], 
                $B = self::$typeToPathMappings[$this->obj].'/'.$this->obj_id.'.'.self::$supportedExtensions[$_FILES[$file_name]['type']][0]
            ) ? array(true, true) : array(false, "Internal error: Failed to move file from '$A' to '$B'");
    }
    
    public function delete()
    {
        $fpath = $this->getPath();
        if ($fpath == NO_PIC || preg_match('/'.str_replace(IMG.'/', '', RACE_ICONS).'/', $fpath)) { # Don't delete NO_PIC file or race icons.
            return true;
        }
        @unlink($fpath);
        return true;
    }
    
    public static function makeBox($obj, $obj_id, $showUploadForm = false, $suffix = false) 
    {
        global $lng;
        // Prints a nice picture box.    
        $height = $width = 250; # Picture dimensions.
        $img = new self($obj, $obj_id);
        
        ?>
        <img alt="Image" height="<?php echo $height;?>" width="<?php echo $width;?>" src="<?php echo $img->getPath()?>">
        <br><br>
        <?php
        if ($showUploadForm) {
            ?>
            <form method='POST' enctype="multipart/form-data">
                <input type="hidden" name="type" value="pic">
                <input type="hidden" name="add_del" id="_pic_add_del__<?php echo (($suffix) ? $suffix : '');?>" value="add">
                <input type="hidden" name="pic_obj" value="<?php echo $obj;?>">
                <?php echo $lng->getTrn('common/uploadnew');?> (<?php echo "${width}x${height}";?>): <br>
                <input name="<?php echo self::$defaultHTMLUploadName.(($suffix) ? $suffix : '')?>" type="file"><br>
                <input type="submit" name="pic_upload" value="<?php echo $lng->getTrn('common/upload');?>"> 
                | 
                <input type="submit" name="pic_delete" value="<?php echo $lng->getTrn('common/delete');?>" onClick="document.getElementById('_pic_add_del__<?php echo (($suffix) ? $suffix : '');?>').value='del';"> 
            </form>
            <?php
        }
    }

}

