<?php
namespace powerorm\cli;

class ColorCLi
{

    private static $escape_char = "\33";
    private static $error = "[0;31m";
    private static $success = "[0;32m";
    private static $info = "[1;34m";
    private static $warning = "[1;36m";
    private static $reset = "[0m";
    

    public static function error($message){
        echo sprintf('%1$s%3$s %2$s %1$s%4$s'.PHP_EOL.PHP_EOL, ColorCLi::$escape_char, $message, ColorCLi::$error,ColorCLi::$reset);
    }
    public static function warning($message){
        echo sprintf('%1$s%3$s %2$s %1$s%4$s'.PHP_EOL.PHP_EOL, ColorCLi::$escape_char, $message, ColorCLi::$warning,ColorCLi::$reset);
    }
    public static function success($message){
        echo sprintf('%1$s%3$s %2$s %1$s%4$s'.PHP_EOL.PHP_EOL, ColorCLi::$escape_char, $message, ColorCLi::$success,ColorCLi::$reset);
    }
    public static function info($message){
        echo sprintf('%1$s%3$s %2$s %1$s%4$s'.PHP_EOL.PHP_EOL, ColorCLi::$escape_char, $message, ColorCLi::$info,ColorCLi::$reset);
    }

    public static function normal($message){
        echo sprintf('%1$s%3$s %2$s %1$s%4$s'.PHP_EOL.PHP_EOL, ColorCLi::$escape_char, $message, ColorCLi::$reset,ColorCLi::$reset);
    }
}