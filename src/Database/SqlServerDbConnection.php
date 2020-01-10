<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
#use IU\REDCapETL\LookupTable;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\Schema\FieldType;
use IU\REDCapETL\Schema\Table;

/**
 * Database connection class for SQL Server databases.
 */
class SqlServerDbConnection extends PdoDbConnection
{
    const AUTO_INCREMENT_TYPE = 'INT NOT NULL IDENTITY(0,1) PRIMARY KEY';

    private static $autoIncrementType = 'INT NOT NULL IDENTITY(0,1) PRIMARY KEY';

    public function __construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($dbString, $ssl, $sslVerify, $caCertFile, $tablePrefix, $labelViewSuffix);

        // Initialize error string
        $this->errorString = '';

        #--------------------------------------------------------------
        # Get the database connection values
        #--------------------------------------------------------------
        $driver  = 'sqlsrv';

        #strip out the driver if it has been included
        if (strtolower(substr($dbString, 0, 7)) == $driver . ':') {
            $dbString = substr($dbString, -1*(trim(strlen($dbString)) - 7));
        }

        $dbValues = DbConnection::parseConnectionString($dbString);

        $port = null;
        if (count($dbValues) == 4) {
            list($host,$username,$password,$database) = DbConnection::parseConnectionString($dbString);
        } elseif (count($dbValues) == 5) {
            list($host,$username,$password,$database,$port) = DbConnection::parseConnectionString($dbString);
            $port = intval($port);
        } else {
            $message = 'The database connection is not correctly formatted: ';
            if (count($dbValues) < 4) {
                $message .= 'not enough values.';
            } else {
                $message .= 'too many values.';
            }
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
      
        if (empty($port)) {
            $port = null;
            #$port = 1433; not using the default port for SQL Server; allowing it to be null
        } else {
            $host .= ",$port";
            #print "host has been changed to $host" . PHP_EOL;
        }
        
        $dataSourceName = "{$driver}:server={$host};Database={$database}";
        if ($ssl) {
            $dataSourceName .= ";Encrypt=1";
            if ($sslVerify) {
                #set the attribute to verify the certificate, i.e., TrustServerCertificate is false.
                $dataSourceName .= ";TrustServerCertificate=false";
            } else {
                #set the attribute to so that the cert is not verified,
                #i.e., TrustServerCertificate is true.
                $dataSourceName .= ";TrustServerCertificate=true";
            }
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::SQLSRV_ATTR_ENCODING => \PDO::SQLSRV_ENCODING_UTF8
        ];

        try {
            $this->db = new \PDO($dataSourceName, $username, $password, $options);
        } catch (\Exception $exception) {
            $message = 'Database connection error for database "'.$database.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }
    }
 

    protected function getCreateTableIfNotExistsQueryPrefix($tableName)
    {
        $query = 'IF NOT EXISTS (SELECT [name] FROM sys.tables ';
        $query .= "WHERE [name] = " . $this->db->quote($tableName) . ') ';
        $query .= 'CREATE TABLE ' . $this->escapeName($tableName) . ' (';
        return $query;
    }


    /**
     * Inserts a single row into the datatabase.
     *
     * @parm Row $row the row of data to insert into the table (the row
     *     has a reference to the table that it should be inserted into).
     *
     * @return integer if the table has an auto-increment ID, then the value
     *     of the ID for the record that was inserted.
     */
    public function insertRow($row)
    {
        $table = $row->table;

        #--------------------------------------------------
        # Remove auto-increment fields
        #--------------------------------------------------
        $fields = $table->getAllNonAutoIncrementFields();

        $queryValues = array();
        $rowValues = $this->getRowValues($row, $fields);
                
        #if the table is one the log tables, then convert the string date into
        #a datetime value that SQL Server can process.
        if ($table->name === 'etl_event_log') {
            #check to see if the string is a date.
            if (is_string($rowValues[1])
                 && (substr($rowValues[1], 1, 2) === '20')
                 && (substr($rowValues[1], 5, 1) === '-')
                ) {
                    #Since later code implodes on a comma and commas are used in the convert syntax,
                    #use '!!!!' to get past the implode, then replace the '!!!!' with commas
                    $rowValues[1] = 'CONVERT(datetime!!!!' . substr($rowValues[1], 0, 24) . "'!!!!21)";
            }
        } elseif ($table->name === 'etl_log') {
            #check to see if the string is a date.
            if (is_string($rowValues[0])
                 && (substr($rowValues[0], 1, 2) === '20')
                 && (substr($rowValues[0], 5, 1) === '-')
                ) {
                    #Since later code implodes on a comma and commas are used in the convert syntax,
                    #use '!!!!' to get past the implode, then replace the '!!!!' with commas
                    $rowValues[0] = 'CONVERT(datetime!!!!' . substr($rowValues[0], 0, 24) . "'!!!!21)";
            }
        }

        $queryValues[] = '('.implode(",", $rowValues).')';
        
        if ($table->name === 'etl_log' || $table->name === 'etl_event_log') {
            $queryValues[0] = str_replace('!!!!', ',', $queryValues[0]);
        }

        $query = $this->createInsertStatement($table->name, $fields, $queryValues);
        #print "\nin SqlServerDbConnection, insertRow, QUERY: $query\n";

        try {
            $rc = $this->db->exec($query);
            $insertId = $this->db->lastInsertId();
        } catch (\Exception $exception) {
            $message = 'Database error while trying to insert a single row into table "'
                .$table->name.'": '.$exception->getMessage();
            $code = EtlException::DATABASE_ERROR;
            throw new EtlException($message, $code);
        }

        return $insertId;
    }


    protected function escapeName($name)
    {
        $name = str_replace('[', '', $name);
        $name = str_replace(']', '', $name);
        $name = '['.$name.']';
        return $name;
    }
}
