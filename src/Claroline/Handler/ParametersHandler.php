<?php

namespace Claroline\Handler;

class ParametersHandler
{    
    public static function getParamFile()
    {
        return  __DIR__ . '/../../config/parameters.json';
    }
    
    public static function getPackageFile()
    {
        return  __DIR__ . '/../../config/packages.ini';
    }

    public static function getParameter($name)
    {
        $json = file_get_contents(self::getParamFile());
        $data = json_decode($json);

        return $data->$name;
    }

    public static function getHandledPackages()
    {
        return array_keys(parse_ini_file(self::getPackageFile()));
    }

    public static function getRepositorySecret($repository)
    {
        $ini = parse_ini_file(self::getPackageFile());

        return $ini[$repository];
    }
}
