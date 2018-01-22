<?php

namespace IU\REDCapETL\Database;

use IU\REDCapETL\RedCapEtl;
use IU\REDCapETL\Schema\FieldType;

/**
 * Database connection class for CSV (Comma-Separated Values) files.
 * When this class is used, CSV files are generated, instead of
 * database tables.
 */
class DBConnectCSV extends DBConnect
{
    const FILE_EXTENSION = '.csv';

    private $directory;
    private $lookup;

    public function __construct($db_str, $tablePrefix, $labelViewSuffix)
    {
        parent::__construct($db_str, $tablePrefix, $labelViewSuffix);

        $this->directory = $db_str;

        # If the directory doesn't end with a separator, add one
        if (substr($db_str, -strlen(DIRECTORY_SEPARATOR)) !== DIRECTORY_SEPARATOR) {
            $this->directory .= DIRECTORY_SEPARATOR;
        }
    }

    private function getTableFile($table)
    {
        $file = $this->directory  . $table->name . DBConnectCSV::FILE_EXTENSION;
        return $file;
    }

    private function getLookupTableFile($table)
    {
        $file = $this->directory .  $table->name . $this->labelViewSuffix . DBConnectCSV::FILE_EXTENSION;
        return $file;
    }

    private function getLookupFile()
    {
        $file = $this->directory . $this->tablePrefix . RedCapEtl::LOOKUP_TABLE_NAME
            . DBConnectCSV::FILE_EXTENSION;
        return $file;
    }

    /**
     * Gets an array representation of the Lookup "table" (CSV file).
     * Since this method caches the result, it is critical that
     * it not be called until after the Lookup table has actually
     * been created.
     */
    private function getLookup()
    {
        if (!isset($this->lookup)) {
            $this->lookup = array();
            $file = $this->getLookupFile();
            $fh = fopen($file, 'r');
            while (($row = fgetcsv($fh)) !== false) {
                array_push($this->lookup, $row);
            }
        }
        return $this->lookup;
    }

    private function getLookupLabel($fieldName, $value)
    {
        $label = null;
        $lookup = $this->getLookup();

        $rootName = null;
        $category = null;
        if (preg_match('/'.RedCapEtl::CHECKBOX_SEPARATOR.'/', $fieldName)) {
            list($rootName, $category) = explode(RedCapEtl::CHECKBOX_SEPARATOR, $fieldName);
        }

        # @TODO FIX numeric subscripts!!!!!!!!!!!!!!!!!!!
        # (Also want to ignore the header row for label lookup.)
      
        foreach ($lookup as $row) {
            if (isset($category)) {
                if (($value === 1 || $value === "1")
                        && $row[1] === $rootName && $row[3] === $category) {
                    $label = $row[4];
                    break;
                }
            } elseif ($row[1] === $fieldName && $row[3] === $value) {
                $label = $row[4];
                break;
            }
        }
        return $label;
    }

    protected function existsTable($table)
    {
        return file_exists($this->getTableFile($table));
    }

    protected function dropTable($table)
    {
        $file = $this->getTableFile($table);
        unlink($file);
        return(1);
    }

    protected function createTable($table)
    {
        $file = $this->getTableFile($table);
        $fh = fopen($file, 'w');

        $this->createTableHeader($fh, $table);

        fclose($fh);
        return(1);
    }

    private function createTableHeader($fh, $table)
    {
        $fields = $table->getAllFields();
        $isFirst = true;
        foreach ($fields as $field) {
            if ($isFirst) {
                $isFirst = false;
            } else {
                fwrite($fh, ',');
            }
            fwrite($fh, '"'.$field->name.'"');
        }
        fwrite($fh, PHP_EOL);
    }

    /**
     * Creates the spreadsheet that is used to store
     * the version of the table that had labels (instead
     * of numeric codes) for multiple choice fields.
     *
     * Note: no way to create a view with CSV, so just
     *       need to create another file.
     *
     * @param Table table the table for which the
     *     "view" with labels is being created.
     */
    public function replaceLookupView($table, $lookup)
    {
        $lookupFile = $this->getLookupTableFile($table);

        $fileHandle = fopen($lookupFile, 'w');
        $this->createTableHeader($fileHandle, $table);
        fclose($fileHandle);
    }

    protected function existsRow($row)
    {
        return(false);
    }

    protected function updateRow($row)
    {
        return(1);
    }

    /**
     * Insert the specified row into its table.
     */
    protected function insertRow($row)
    {
        $table = $row->table;
        $usesLookup = $table->uses_lookup;

        $file  = $this->getTableFile($table);

        #----------------------------------
        # Lookup processing
        #----------------------------------
        $lfh = null;
        $lookup = null;
        if ($usesLookup) {
            $lookupFile = $this->getLookupTableFile($table);
            $lfh = fopen($lookupFile, 'a');
        }

        $fh  = fopen($file, 'a');

        $this->insertRowIntoFile($fh, $lfh, $row);

        fclose($fh);
        if (isset($lfh)) {
            fclose($lfh);
        }
        return(1);
    }

    /**
     * Inserts the rows from the specified table
     * into the database.
     */
    protected function insertRows($table)
    {
        $rows = $table->getRows();
        foreach ($rows as $row) {
            $this->insertRow($row);
        }
    }

    private function insertRowIntoFile($fh, $lfh, $row)
    {
        $table = $row->table;
        $isFirst = true;
        $position = 0;
        $rowData = $row->getData();
        foreach ($table->getAllFields() as $field) {
            $fieldType = $field->type;
            $value = $rowData[$field->name];

            if ($isFirst) {
                $isFirst = false;
            } else {
                $this->fileWrite($fh, $lfh, ',');
            }

            switch ($fieldType) {
                case FieldType::CHECKBOX:
                    $label = null;
                    if ($field->uses_lookup) {
                        $label = $this->getLookupLabel($field->name, $value);
                    }
                        $this->fileWrite($fh, $lfh, $value, $label);
                    break;
                case FieldType::DATE:   // ???
                        $this->fileWrite($fh, $lfh, $value);
                    break;
                case FieldType::FLOAT:
                        $this->fileWrite($fh, $lfh, $value);
                    break;
                case FieldType::INT:
                    $label = null;
                    if ($field->uses_lookup) {
                        $label = $this->getLookupLabel($field->name, $value);
                    }
                        $this->fileWrite($fh, $lfh, $value, $label);
                    break;
                case FieldType::STRING:
                        $this->fileWrite($fh, $lfh, '"'.$value.'"');
                    break;
                default:
                        $this->fileWrite($fh, $lfh, $value);
            }
            $position++;
        }
        $this->fileWrite($fh, $lfh, PHP_EOL);
    }


    /**
     * Writes the specified value (or it's label) to the
     * specified file or files.
     */
    private function fileWrite($fh, $lfh, $value, $label = null)
    {
        fwrite($fh, $value);
        if (isset($lfh)) {
            if (isset($label)) {
                fwrite($lfh, $label);
            } else {
                fwrite($lfh, $value);
            }
        }
    }
}