<?php
    function CleanChars($val){
        $sanitized = '';
        foreach (str_split($val) as $char) {
            switch($char){
                case '&':
                    $sanitized .= "&amp;";
                    break;
                case '<':
                    $sanitized .= "&lt;";
                    break;
                case '>':
                    $sanitized .= "&gt;";
                    break;
                case '"':
                    $sanitized .= "&quot;";
                    break;
                case '\'':
                    $sanitized .= "&#x27;";
                    break;
                case '/':
                    $sanitized .= "&#x2F;";
                    break;
                case '(':
                    $sanitized .= "&#x00028;";
                    break;
                case ')':
                    $sanitized .= "&#x00029;";
                    break;
                case '{':
                    $sanitized .= "&lcub;";
                    break;
                case '}':
                    $sanitized .= "&rcub;";
                    break;
                default:
                    $sanitized .= $char;
                    break;
            }
        }
        //return htmlspecialchars($val);
        return $sanitized;
    }

    function 
?>