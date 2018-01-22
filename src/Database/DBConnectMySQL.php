<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\EtlException;
use IU\REDCapETL\EtlErrorHandler;
use IU\REDCapETL\Schema\FieldType;

/**
 * DBConnectMySQL - Interacts w/ MySQL database
 *
 * DBConnectMySQL extends DBConnect and knows how to read/write to a
 * MySQL database.
 */
class DBConnectMySQL extends DBConnect
{
    private $mysqli;

    private $insert_row_stmts;
    private $insert_row_bind_types;

    private $errorHandler;

    public function __construct($db_str, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($db_str, $tablePrefix, $labelViewSuffix);

        $this->errorHandler = new EtlErrorHandler();

        // Initialize error string
        $this->err_str = '';

        // Get parameters from db_str
        list($host,$username,$password,$database) = explode(':', $db_str);
        ###list($host,$database,$username,$password) = explode(':',$db_str,4);

        // Get MySQL connection
        // NOTE: Could add error checking
        // mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        // Setting the above causes the program to stop for any uncaugt errors
        $this->mysqli = new \mysqli($host, $username, $password, $database);

        if ($this->mysqli->connect_errno) {
            $message = 'MySQL error ['.$this->mysqli->connect_errno.']: '.$this->mysqli->connect_error;
            $code = EtlException::DATABASE_ERROR;
            $this->errorHandler->throwException($message, $code);
        }

        $this->insert_row_stmts = array();
        $this->insert_row_bind_types = array();
    }

    protected function existsTable($table)
    {

        // Note: exists_table currently assumes that a table always exists,
        //       as there is no practical problem with attempting to drop
        //       a non-existent table

        return(true);
    }


    protected function dropTable($table)
    {
        // Define query
        $query = "DROP TABLE IF EXISTS ". $table->name;

        // Execute query
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            $this->errorHandler->throwException($message, $code);
        }

        return(1);
    }


    protected function createTable($table)
    {

        // Start query
        $query = 'CREATE TABLE '.$table->name.' (';

        // foreach field
        $field_defs = array();
        foreach ($table->getAllFields() as $field) {
            // Begin field_def
            $field_def = $field->name.' ';

            // Add field type to field definition
            switch ($field->type) {
                case FieldType::DATE:
                    $field_def .= 'DATE';
                    break;

                case FieldType::INT:
                    $field_def .= 'INT';
                    break;

                case FieldType::FLOAT:
                    $field_def .= 'FLOAT';
                    break;
    
                case FieldType::STRING:
                default:
                      $field_def .= 'TEXT';
                    break;
            } // switch

            // Add field_def to array of field_defs
            array_push($field_defs, $field_def);
        }

        // Add field definitions to query
        $query .= join(', ', $field_defs);

        // End query
        $query .= ')';

        // Execute query
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            $this->errorHandler->throwException($message, $code);
        }

        return(1);
    }

    public function replaceLookupView($table, $lookup)
    {
        #$rc = $this->replaceLookupViewDynamic($table, $lookup);
        $rc = $this->replaceLookupViewStatic($table, $lookup);
        ##$query = $this->replaceLookupViewStatic($table, $lookup);
        ##print "\nQUERY: $query\n\n";
        return $rc;
    }


    /**
     * Replaces the view for the specified table that replaces
     * multiple choice question codes with their corresponding labels.
     * The labels are generated dynamically from the Lookup table.
     */
    public function replaceLookupViewDynamic($table, $lookup)
    {

        $selects = array();

        // foreach field
        foreach ($table->getAllFields() as $field) {
            // If the field does not use lookup table
            if (false === $field->uses_lookup) {
                array_push($selects, 't.'.$field->name);
            } else {
                // $field->uses_lookup holds name of lookup field, if not false
                $fname = $field->uses_lookup;

                // If the field uses the lookup table and is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->name)) {
                // For checkbox fields, the join needs to be done based on
                // the category embedded in the name of the checkbox field

                // Separate root from category
                    list($root_name, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);

                    $agg = "GROUP_CONCAT(if(l.field_name='".$fname."' ".
                         "and l.category=".$cat.", label, NULL)) ";

                    $select = 'CASE WHEN t.'.$field->name.' = 1 THEN '.$agg.
                        ' ELSE 0'.
                    ' END as '.$field->name;
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = "GROUP_CONCAT(if(l.field_name='".$fname."' ".
                    "and l.category=t.".$field->name.", label, NULL)) ".
                    "as ".$field->name;
                }

                array_push($selects, $select);
            }
        }

        $query = 'CREATE OR REPLACE VIEW '.$table->name.$this->labelViewSuffix.' AS ';

        $select = 'SELECT '. implode(', ', $selects);
        $from = 'FROM '.$this->tablePrefix.RedCapEtl::LOOKUP_TABLE_NAME.' l, '.$table->name.' t';
        $where = "WHERE l.table_name like '".$table->name."'";
        $group_by = 'GROUP BY t.'.$table->primary->name;

        $query .= $select.' '.$from.' '.$where.' '.$group_by;

        // Execute query
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            $this->errorHandler->throwException($message, $code);
        }


        return(1);
    }


    /**
     * Creates (or replaces) the lookup view for the specified table.
     */
    public function replaceLookupViewStatic($table, $lookup)
    {
        $selects = array();

        // foreach field
        foreach ($table->getAllFields() as $field) {
            // If the field does not use lookup table
            if ($field->uses_lookup === false) {
                array_push($selects, $field->name);
            } else {
                // $field->uses_lookup holds name of lookup field, if not false
                // name of lookup field is root of field name for checkbox
                $fname = $field->uses_lookup;

                // If the field uses the lookup table and is a checkbox field
                if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $field->name)) {
                // For checkbox fields, the join needs to be done based on
                // the category embedded in the name of the checkbox field

                // Separate root from category
                    list($root_name, $cat) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $field->name);

                    $label = $this->mysqli->real_escape_string(
                        $lookup->getLabel($table->name, $fname, $cat)
                    );
                    $select = 'CASE '.$field->name.' WHEN 1 THEN '
                        . "'".$label."'"
                        . ' ELSE 0'
                        . ' END as '.$field->name;
                } // The field uses the lookup table and is not a checkbox field
                else {
                    $select = 'CASE '.$field->name;
                    $map = $lookup->getCategoryLabelMap($table->name, $fname);
                    foreach ($map as $category => $label) {
                        $select .= ' WHEN '."'".($this->mysqli->real_escape_string($category))."'"
                            .' THEN '."'".($this->mysqli->real_escape_string($label))."'";
                    }
                    $select .= ' END as '.$field->name;
                }
                array_push($selects, $select);
            }
        }

        $query = 'CREATE OR REPLACE VIEW '.$table->name.$this->labelViewSuffix.' AS ';

        $select = 'SELECT '. implode(', ', $selects);
        $from = 'FROM '.$table->name;

        $query .= $select.' '.$from;

        ###print("QUERY: $query\n");

        // Execute query
        $result = $this->mysqli->query($query);
        if ($result === false) {
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
            $code = EtlException::DATABASE_ERROR;
            $this->errorHandler->throwException($message, $code);
        }


        return(1);
    }

    protected function existsRow($row)
    {

        // NOTE: For now, existsRow will assume that the row does not
        //       exist and always return false. If the code ever needs
        //       to maintain existing rows, this will need to be implemented

        return(false);
    }

    protected function updateRow($row)
    {

        // NOTE: For now, updateRow is just a stub that returns true. It
        //       is not expected to be reached. If the code ever needs to
        //       maintain existing rows, this will need to be implemented.

        return(1);
    }

    /**
     * Insert the specified row into the database.
     */
    protected function insertRow($row)
    {

        // How to handle unknow # of vars in bind_param. See answer by abd.agha
        // http://stackoverflow.com/questions/1913899/mysqli-binding-params-using-call-user-func-array

        // Get parameterized query
        //     If the query doesn't already exist, it will be created
        list($stmt,$bind_types) = $this->getInsertRowStmt($row->table);

        // Start bind parameters list with bind_types and escaped table name
        $params = array($bind_types);

        // Add field values, in order, to bind parameters
        foreach ($row->table->getAllFields() as $field) {
            // Replace empty string with null
            $to_bind = $row->data[$field->name];
            if ($to_bind === '') {
                $to_bind = null;
            }

            array_push($params, $to_bind);
        }

        // Get references to each parameter -- necessary because
        // call_user_func_array wants references
        $param_refs = array();
        foreach ($params as $key => $value) {
            $param_refs[$key] = &$params[$key];
        }

        // Bind references to prepared query
        call_user_func_array(array($stmt, 'bind_param'), $param_refs);

        // Execute query
        $rc = $stmt->execute();   //NOTE add error checking?

        // If there's an error executing the statement
        if ($rc === false) {
            $this->err_str = $stmt->error;
            $message = 'MySQL error in query "'.$query.'"'
                .' ['.$stmt->errno.']: '.$stmt->error;
            $code = EtlException::DATABASE_ERROR;
            $this->errorHandler->throwException($message, $code);
        }

    
        return(1);
    }


    private function getInsertRowStmt($table)
    {

        // If we've already created this query, return it
        if (isset($this->insert_row_stmts[$table->name])) {
            return(array($this->insert_row_stmts[$table->name],
               $this->insert_row_bind_types[$table->name]));
        } // Otherwise, create and save the query and its associated bind types
        else {
            // Create field_names, positions array, and params arrays
            $field_names = array();
            $bind_positions = array();
            $bind_types = '';
            foreach ($table->getAllFields() as $field) {
                array_push($field_names, $field->name);
                array_push($bind_positions, '?');

                switch ($field->type) {
                    case FieldType::STRING:
                    case FieldType::DATE:
                        $bind_types .= 's';
                        break;

                    case FieldType::FLOAT:
                          $bind_types .= 'd';
                        break;

                    case FieldType::INT:
                    default:
                          $bind_types .= 'i';
                        break;
                }
            }

            // Start $sql
            $query = 'INSERT INTO '.$table->name;

            // Add field names to $sql
            $query .= ' ('. implode(",", $field_names) .') ';

            // Add values positions to $sql
            $query .= 'VALUES';

            $query .= ' ('.implode(",", $bind_positions) .')';

            // Prepare query
            $stmt = $this->mysqli->prepare($query);  //NOTE add error checking?

            // ADA DEBUG
            if (!$stmt) {
                 error_log("query :".$query."\n", 0);
                 error_log("Statement failed: ". $this->mysqli->error . "\n", 0);
            }

            $this->insert_row_stmts[$table->name] = $stmt;
            $this->insert_row_bind_types[$table->name] = $bind_types;
    
            return(array($stmt,$bind_types));
        } // else
    }


    private function getBindType($field)
    {
        switch ($field->type) {
            case FieldType::STRING:
            case FieldType::DATE:
                $bindType = 's';
                break;

            case FieldType::FLOAT:
                $bindType = 'd';
                break;

            case FieldType::INT:
            default:
                $bindType = 'i';
                break;
        }

        return $bindType;
    }



    /**
     * WORK IN PROGRESS
     *
     * Inserts the rows of the specified Table object
     * into the database.
     *
     * @param Table table the table containing the rows that are
     *     to be inserted.
     *
     * @return true on success, false otherwise.
     */
    protected function insertRows($table)
    {
        $maxParams = 2**16 - 1;

        $rows = $table->getRows();

        $fields = $table->getAllFields();
        $fieldNames = array_column($fields, 'name');

        $queryStart = 'INSERT INTO '.$table->name.' ('. implode(",", $fieldNames) .') VALUES ';


        $currentFieldCount = 0;
        $bindTypes = '';
        $query = $queryStart;
        $bindTypesArray = array();
        $isFirst = true;
        $params = array($bind_types);

        foreach ($rows as $row) {
            #------------------------------------------------------------------
            # If the bind param limit reached, need to start a new statement
            #------------------------------------------------------------------
            if ($currentFieldCount + count(fields) > $maxParams) {
                array_push($bindTypesArray, $bindType);
                $bindTypes = '';
            }

            $bindPositions = '';
            foreach ($fields as $field) {
                array_push($bindPositions, '?');
                $bindTypes .= getBindType($field);
            }

            if ($isFirst) {
                $isFirst = false;
            } else {
                $query .= ',';
            }

            $query .= ' ('.implode(",", $bindPositions) .')';
            $statement = $this->mysqli->prepare($query);
            if ($statement === false) {
                $message = 'MySQL error in query "'.$query.'"'
                    .' ['.$this->mysqli->errno.']: '.$this->mysqli->error;
                $code = EtlException::DATABASE_ERROR;
                $this->errorHandler->throwException($message, $code);
            }

            foreach ($params as $param) {
                $paramRefs[$key] = &$params[$key];
                $key++;
            }

            call_user_func_array(array($statement, 'bind_param'), $paramRefs);
        }

        return(0);

        // NEED TO FIX - has problem with placeholders limit exceeded
        $result = true;

        $rows = $table->getRows();



        if (count($rows) > 0) {
            #--------------------------------------------------------------
            # Get parameterized query
            #     If the query doesn't already exist, it will be created
            #--------------------------------------------------------------
            list($statement, $bind_types) = $this->getInsertRowStmt($table, count($rows));

            #$param_refs = array();
            #$params = array($bind_types);
            #--------------------------------------------------------
            # Bind the row values to the parameterized query
            #--------------------------------------------------------
            $params = array($bind_types);
            $param_refs = array();
            $key = 0;

            $fields = $table->getAllFields();

            #---------------------------------
            # Set the params
            #---------------------------------
            foreach ($rows as $row) {
                foreach ($fields as $field) {
                    $to_bind = $row->data[$field->name];
                    if ($to_bind === '') {
                        $to_bind = null;
                    }
                    array_push($params, $to_bind);
                }
            }

            foreach ($params as $param) {
                $param_refs[$key] = &$params[$key];
                $key++;
            }

            ###print_r($param_refs);
            call_user_func_array(array($statement, 'bind_param'), $param_refs);

            #foreach ($params as $key => $value) {
            #    $param_refs[$key] = &$params[$key];
            #}
            #call_user_func_array(array($statement, 'bind_param'), $param_refs);

            #-----------------------------
            # Execute query
            #-----------------------------
            $rc = $statement->execute();

            #---------------------------------------------------
            # If there's an error executing the statement
            #---------------------------------------------------
            if ($rc === false) {
                $this->err_str = $statement->error;
                $result = false;
                $this->err_str = $statement->error;
                $message = 'MySQL error: '
                    .' ['.$statement->errno.']: '.$statement->error;
                $code = EtlException::DATABASE_ERROR;
                $this->errorHandler->throwException($message, $code);
            }
        }
    
        return($result);
    }
}