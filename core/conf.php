<?php
namespace core; 
use Symfony\Component\Yaml\Yaml;

class conf {
    public static function file(): string
    {
        return __FWDIR__."/config.yaml";
    }
    public static function get(): mixed {
        return Yaml::parse(file_get_contents(self::file()));
    }
}

// mathmark was here


// no he wasnt


// im real mature


// im so tired

// its 12:48 am

// top 10 revivals of 2023
// 1. goonerlox

// real
