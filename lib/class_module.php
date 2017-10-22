<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009. All Rights Reserved.
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
    The contents for this file is definitions used by modules.
*/

// Modules MUST implement this interface.
interface ModuleInterface
{
    public static function main($argv);
    public static function getModuleAttributes();
    public static function getModuleTables();
    public static function getModuleUpgradeSQL();
    public static function triggerHandler($type, $argv);
}

// Module handler
class Module
{

    private static $modules = array(
    /*
        'modname' => array(
                'class'         => Name of module class in which implements ModuleInterface.
                'filesLoadTime' => array('file1', 'file2', 'file3'),
                'filesRunTime'  => array('file1', 'file2', 'file3'),
        ),
    */
    );
    const MOD_RPATH = 'modules/'; # Relative path from base path to the modules directory in which modules (dirs) are placed.
    
    public static function register(array $struct)
    {
        // Disabled? Don't load, return. If no entry exists then enable! Don't change this behaviour!
        global $settings;
        if (isset($settings['modules_enabled'][$struct['class']]) && !$settings['modules_enabled'][$struct['class']]) { 
            return;
        }
        
        self::$modules[$struct['class']] = $struct;
        /*
            From manual/en/function.include.php
            
            When a file is included, the code it contains inherits the variable scope of the line on which the include occurs. 
            Any variables available at that line in the calling file will be available within the called file, from that point forward. 
            However, all functions and classes defined in the included file have the global scope. 
        */
        foreach ($struct['filesLoadTime'] as $file) {require_once(self::MOD_RPATH . $file);} # Load module files.
        // Load translation file, if exists. Exploit that the translation.xml MUST lie in same directory as $struct['filesLoadTime'][0].
        if (file_exists($file = self::MOD_RPATH.dirname($struct['filesLoadTime'][0]).'/translations.xml')) {
            global $lng;
            if (is_object($lng)) {
                $lng->registerTranslationFile($struct['class'], $file);
            }
        }
    }
    
    public static function unregister($class)
    {
        unset(self::$modules[$class]);
    }
    
    public static function run($class, array $argv)
    {
        if (!array_key_exists($class, self::$modules)) return null;
        foreach (self::$modules[$class]['filesRunTime'] as $file) {require_once(self::MOD_RPATH . $file);} # Load module files.
        $module = array_merge(self::$modules[$class], call_user_func(array($class, 'getModuleAttributes'))); # Shortcut.
        global $coach; # Used for fetching stylesheet.
        if ($module['setCanvas']) {HTMLOUT::frame_begin();}
        // Test if module implements the required interface.
        $reflection = new ReflectionClass($class);
        if (!$reflection->implementsInterface($modIntf = 'ModuleInterface')) {fatal("Module registered by class name '$class' does not implement the interface '$modIntf'");}
        $return = call_user_func(array($class, 'main'), $argv);
        if ($module['setCanvas']) {HTMLOUT::frame_end();}
        
        return $return;
    }

    public static function isRegistered($class)
    {
        return in_array($class, array_keys(self::$modules));
    }
    
    public static function getInfo($class)
    {
        $module = array_merge(self::$modules[$class], call_user_func(array($class, 'getModuleAttributes'))); # Shortcut.
        return array($module['author'], $module['date'], $module['moduleName']);
    }
    
    public static function getRegistered()
    {
        return array_keys(self::$modules);
    }
    
    /*
        SQL
    */
    
    public static function createAllRequiredTables()
    {
        $tables = array();
        foreach (array_keys(self::$modules) as $class) {
            foreach (call_user_func(array($class, 'getModuleTables')) as $name => $tblStruct) {
                $tables[$class][$name] = Table::createTable($name, $tblStruct);
            }
        }
        return $tables;
    }
    
    public static function getAllUpgradeSQLs($version)
    {
        $SQLs = array();
        foreach (array_keys(self::$modules) as $class) {
            $mod_SQLs = call_user_func(array($class, 'getModuleUpgradeSQL'));
            $SQLs[$class] = isset($mod_SQLs[$version]) ? $mod_SQLs[$version] : array();
        }
        return $SQLs;
    }
    
    /*
        Triggers
    */
    
    private static $TRIGGER_CNT = 1;
    const TRIGGER_TYPE_PREFIX = 'T_TRIGGER_'; # Ex T_TRIGGER_MATCH_SAVE
    
    public static function runTriggers($type, array $argv = null)
    {
        foreach (array_keys(self::$modules) as $class) {
            call_user_func(array($class, 'triggerHandler'), $type, $argv);
        }
        return true;
    }
    
    public static function registerTrigger($name)
    {
        define($const = self::TRIGGER_TYPE_PREFIX.$name, self::$TRIGGER_CNT++);
        return $const; # Return name of trigger constant, not its value.
    }
    
    public static function isTriggerRegistered($name)
    {
        return defined(self::TRIGGER_TYPE_PREFIX.$name);
    }
}

// Register core triggers.
foreach (array('MATCH_CREATE', 'MATCH_SAVE', 'MATCH_DELETE', 'MATCH_RESET', 'BEFORE_PAGE_RENDER') as $name) {
    Module::registerTrigger($name);
}

