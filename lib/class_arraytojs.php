<?php

/*
 *   By Jan Paepke @ http://www.phpclasses.org/browse/package/3365.html. 
 *   Released under GPL.
 */

class array_to_js {
    var $js_arrays;
    function error ($message, $stop = true) {
        echo "<b>array_to_js</b> - FATAL ERROR: ".$message;
        if ($stop) exit;
    }
    function add_array($myarray, $outputvarname, $level = 0) {
        if (isset($this->js_arrays[$outputvarname]))
            $this->error('This Array has been added more than once: "'.$outputvarname.'"');
        for ($i=0; $i<$level; $i++) $pre .= '    ';
        $this->js_arrays[$outputvarname] .= $pre.$outputvarname.' = new Object();'."\n";
        foreach ($myarray as $key => $value) {
            if (!is_int($key))
                $key = '"'.addslashes($key).'"';
            if (is_array($value))
                $this->add_array($value, $outputvarname.'['.$key.']', $level+1);
            else {
                $this->js_arrays[$outputvarname] .= $pre.'    '.$outputvarname.'['.$key.']'.' = ';

                if (is_int($value) or is_float($value))
                    $this->js_arrays[$outputvarname] .= $value;
                elseif (is_bool($value))
                    $this->js_arrays[$outputvarname] .= $value ? "true" : "false";
                elseif (is_string($value))
                    $this->js_arrays[$outputvarname] .= '"'.addslashes($value).'"';
                else
                    $this->error('Unknown Datatype for "'.$outputvarname.'['.$key.']"');
                $this->js_arrays[$outputvarname] .= ";\n";
            }
        }
    }
    function output_all($scripttag = true) {
        if ($scripttag) $outputstring = '<script language="JavaScript" type="text/javascript">'."\n";
        foreach ($this->js_arrays as $array)
            $outputstring .= $array;
        if ($scripttag) $outputstring .= '</script>'."\n";

        return $outputstring;
    }
}

