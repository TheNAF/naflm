<?php
/*
    This file is a template for modules.
    
    Note: the two terms functions and methods are used loosely in this documentation. They mean the same thing.
    
    How to USE a module once it's written:
    ---------------------------------
        Firstly you will need to register it in the modules/modsheader.php file. 
        The existing entries and comments should be enough to figure out how to do that.
        Now, let's say that your module (as an example) prints some kind of statistics containing box. 
        What should you then write on the respective page in order to print the box?
        
            if (Module::isRegistered('MyModule')) {
                Module::run('MyModule', array());
            }
        
        The second argument passed to Module::run() is the $argv array passed on to main() (see below).
*/

class MyModule implements ModuleInterface
{

/***************
 * ModuleInterface requirements. These functions MUST be defined.
 ***************/

/*
 *  Basically you are free to design your main() function as you wish. 
 *  If you are writing a simple module that merely echoes out some data, you may want to have main() doing all the work (i.e. place all your code here).
 *  If you on the other hand are writing a module which is divided into several routines, you may (and should) use the main() as a wrapper for calling the appropriate code.
 *  
 *  The below main() example illustrates how main() COULD work as a wrapper, when the subdivision of code is done into functions in this SAME class.
 */
public static function main($argv) # argv = argument vector (array).
{
    /* 
        Let $argv[0] be the name of the function we wish main() to call. 
        Let the remaining contents of $argv be the arguments of that function, in the correct order.
        
        Please note only static functions are callable through main(). 
    */

    $func = array_shift($argv);
    return call_user_func_array(array(__CLASS__, $func), $argv);
}

/*
 *  This function returns information about the module and its author.
 */
public static function getModuleAttributes()
{
    return array(
        'author'     => 'Name of author',
        'moduleName' => 'Name of module',
        'date'       => 'Date written', # For example '2009'.
        'setCanvas'  => false, # If true, whenever your main() is run through Module::run() your code's output will be "sandwiched" into the standard HTML frame.
    );
}

/*
 *  This function returns the MySQL table definitions for the tables required by the module. If no tables are used array() should be returned.
 */
public static function getModuleTables()
{
    return array(
        # Table name => column definitions
        'MyTable_1' => array(
            # Column name => column definition
            'col1' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'col1' => 'MEDIUMINT UNSIGNED',
            'col1' => 'MEDIUMINT UNSIGNED',
        ),
        'MyTable_2' => array(
            'col1' => 'MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT',
            'col1' => 'MEDIUMINT UNSIGNED',
            'col1' => 'MEDIUMINT UNSIGNED',
        ),
    );
}    

public static function getModuleUpgradeSQL()
{
    return array(
        '075-080' => array(
            'SQL CODE #1',
            'SQL CODE #2',
            'SQL CODE #N',
        ),
        '070-075' => array(
            'SQL CODE #1',
            'SQL CODE #2',
            'SQL CODE #N',
        ),
    );
}

public static function triggerHandler($type, $argv){

    // Do stuff on trigger events.
    // $type may be any one of the T_TRIGGER_* types.
}

/***************
 * OPTIONAL subdivision of module code into class methods.
 * 
 * These work as in ordinary classes with the exception that you really should (but are strictly required to) only interact with the class through static methods.
 ***************/

private $attribute = 'Default value';

public function __construct($arg1)
{
    $this->attribute = $arg1;
}

public function myMethod()
{
    return $this->attribute;
}

public static function myStaticMethod($arg)
{
    $obj = new self('New value');
    echo $obj->myMethod();
}

}

