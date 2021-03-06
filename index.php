<?php
/*
 * This file is a part of "FromMySqlToPostgreSql" - the database migration tool.
 * 
 * Copyright 2015 Anatoly Khaytovich <anatolyuss@gmail.com>
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program (please see the "LICENSE.md" file).  
 * If not, see <http://www.gnu.org/licenses/gpl.txt>.
 */

/**
 * Retrieves a congiguration data, and converts it to an array.
 *
 * @param string $strPath
 * @return array
 */
function getConfig($strPath)
{
    $arrRetVal = [];

    if(empty($strPath)) {
        $strPath = __DIR__ . '/config.json';
    }

    if (is_file($strPath)) {
        $strExtension = pathinfo($strPath, PATHINFO_EXTENSION);
    }

    switch ($strExtension) {


        case 'xml':
            $config = simplexml_load_file($strPath);
            $arrRetVal = empty($config) ? [] : get_object_vars($config);
            break;
        case 'json':
        default:
            $strContents = file_get_contents($strPath);
            $config = json_decode($strContents, true);
            $strError = '';

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    // No code should be put here.
                    break;

                case JSON_ERROR_DEPTH:
                    $strError = 'Maximum stack depth exceeded';
                    break;

                case JSON_ERROR_STATE_MISMATCH:
                    $strError = 'Underflow or the modes mismatch';
                    break;

                case JSON_ERROR_CTRL_CHAR:
                    $strError = 'Unexpected control character found';
                    break;

                case JSON_ERROR_SYNTAX:
                    $strError = 'Syntax error, malformed JSON';
                    break;

                case JSON_ERROR_UTF8:
                    $strError = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;

                default:
                    $strError = 'Unknown error';
                    break;
            }

            $arrRetVal = is_null($config) || !empty($strError) ? [] : $config;
            break;
    }


    return $arrRetVal;
}

ini_set('display_errors', 'on');

// Verify the extensions are loaded before running.
if (!extension_loaded('pgsql')) {
    echo "Postgresql not enabled: you need the 'pgsql' module.\n";
    //exit(1);
}
if (!extension_loaded('pdo_mysql')) {
    echo "Postgresql not enabled: you need the 'pdo_mysql' module.\n";
    exit(1);
}
if (!extension_loaded('pdo_pgsql')) {
    echo "Postgresql not enabled: you need the 'pdo_pgsql' module.\n";
    exit(1);
}
if (!extension_loaded('mbstring')) {
    echo "Multibyte extension not loaded: you need the 'mbstring' module.\n";
    exit(1);
}
if (ini_get('register_argc_argv') == 0) {
    echo "register_argc_argv is not turned on, we can't process command line arguments.\n";
    //exit(1);
}

$strParam = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : $_SERVER['argv'][1];
$arrConfig = getConfig($strParam);
unset($strParam);

if (empty($arrConfig)) {
    echo PHP_EOL, '-- Cannot perform a migration due to missing "config[.xml | .json]" file.', PHP_EOL;
} else {
    spl_autoload_register(function ($class) {
        require_once 'migration/FromMySqlToPostgreSql/' . $class . '.php';
    });

    $arrConfig['temp_dir_path'] = __DIR__ . '/temporary_directory';
    $arrConfig['logs_dir_path'] = __DIR__ . '/logs_directory';

    $migration = new FromMySqlToPostgreSql($arrConfig);
    $migration->migrate();
}

exit;

