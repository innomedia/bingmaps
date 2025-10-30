<?php
namespace bingMap;

class HelperMethods
{
    public static function MinifyString($string): string|array
    {
        //DO NOT REPLACE SPACES
        $replaced = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
        return str_replace(["\r", "\n", "\r\n", "\n\r", "  "], "", $replaced);
    }
    
    public static function RemoveEmptyLines($string): string|array|null
    {
        return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string);
    }
    
    //Not redundant in case of changers to MinifyString
    public static function prepareJavascriptString($string): string|array
    {
        return str_replace(["\r", "\n", "\r\n", "\n\r"], "", $string);
    }
}
