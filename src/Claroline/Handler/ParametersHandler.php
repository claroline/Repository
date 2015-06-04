<?php

namespace Claroline\Handler;

class ParametersHandler
{
    public static $paramFile =  __DIR__ . '/../../config/parameters.json';
    public static $packageFile =  __DIR__ . '/../../config/packages.ini';

    public static function getParameter($name)
    {
        $json = file_get_contents(self::$paramFile);
        $data = json_decode($json);

        return $data->$name;
    }

    public static function getHandledPackages()
    {
        return array_keys(parse_ini_file(self::$packageFile));
    }

    public static function getRepositorySecret($repository)
    {
        $ini = parse_ini_file(self::$packageFile);

        return $ini[$repository];
    }
}
