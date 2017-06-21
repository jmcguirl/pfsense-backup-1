<?php

/**
 * Some additional functions
 */


/**
 * Replaces all placeholders with pattern {{placeholder}} in given string
 *
 * @param $str string String with placeholders
 * @param $placeholders array List of replacements
 *
 * @return string
 */
function replacePlaceholders($str, $placeholders = array()) {
    if(!is_array($placeholders)) {
        return $str;
    }

    foreach($placeholders as $placeholder => $replacement) {
        $str = str_ireplace("{{" . $placeholder . "}}",$replacement,$str);
    }

    return $str;
}

/**
 * This functions strips whitespaces but not zeros
 *
 * @param $string string Input string
 * @return bool|mixed|string Output string
 */
function realTrim($string) {
    if(!empty($string)) {
        $string = preg_replace("/\r|\n|\t/", "", $string);
        while(substr($string, 0, 1) == " ") {
            $string = substr($string, 1);
        }
        while(substr($string, -1, 1) == " ") {
            $string = substr($string, 0, -1);
        }
    }
    return $string;
}