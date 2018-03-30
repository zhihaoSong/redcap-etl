<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\EtlErrorHandler;
use IU\REDCapETL\RedCapEtl;

/**
 * DBConnectFactory - Creates storage-specific objects.
 * DBConnectFactory creates a DBConnect* object
 */
class DBConnectFactory
{
    // Database types
    const DBTYPE_CSV    = 'CSV';
    const DBTYPE_MYSQL  = 'MySQL';
    
    #const DBTYPE_SQLSRV = 'SQLServer';

    private $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new EtlErrorHandler();
    }

    public function createDbcon($connectionString, $tablePrefix, $labelViewSuffix)
    {
        list($dbType, $dbString) = $this->parseConnectionString($connectionString);

        switch ($dbType) {
            case DBConnectFactory::DBTYPE_MYSQL:
                $dbcon = new DBConnectMySQL($dbString, $tablePrefix, $labelViewSuffix);
                break;

            #case DBConnectFactory::DBTYPE_SQLSRV:
            #    $dbcon = new DBConnectSQLSRV($dbString, $tablePrefix, $labelViewSuffix);
            #    break;

            case DBConnectFactory::DBTYPE_CSV:
                $dbcon = new DBConnectCSV($dbString, $tablePrefix, $labelViewSuffix);
                break;

            default:
                $message = 'Invalid database type: "'.$dbType.'". Valid types are: CSV and MySQL.';
                $this->errorHandler->throwException($mesage);
        }

        return($dbcon);
    }

    /**
     * Parses a connection string
     *
     * @param string $connectionString a connection string
     *     that has the format: <databaseType>:<databaseString>
     *
     * @return array an array with 2 string elements. The first
     *     is the database type, and the second is the database
     *     string (with database-specific connection information).
     */
    public static function parseConnectionString($connectionString)
    {
        list($dbType, $dbString) = explode(':', $connectionString, 2);

        return array($dbType, $dbString);
    }

    
    /**
     * Creates  connection string from the specified database
     *     type and string.
     */
    public static function createConnectionString($dbType, $dbString)
    {
        return $dbType . ':' . $dbString;
    }
}
