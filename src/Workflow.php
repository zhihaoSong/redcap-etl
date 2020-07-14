<?php
#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\REDCapETL;

use IU\PHPCap\RedCap;
use IU\PHPCap\PhpCapException;

use IU\REDCapETL\Database\DbConnection;
use IU\REDCapETL\Database\DbConnectionFactory;

use IU\REDCapETL\Schema\FieldTypeSpecifier;

/**
 * Class used to store ETL workflow information from
 * a configuration file.
 *
 * Workflows contain one or more configurations that are
 * executed sequentially by REDCap-ETL with optional global properties
 * that override properties defined for individual configurations.
 */
class Workflow
{
    private $logger;

    /** @var array array of global properties (as map from property name to property value) */
    private $globalProperties;

    /** @var array array of Configurations */
    private $configurations;

    private $configurationFile;

    /**
     * Creates a Workflow object from a workflow file.
     *
     * @param Logger $logger logger for information and errors
     * @param mixed $properties if this is a string, it is assumed to
     *     be the name of the properties file to use, if it is an array,
     *     it is assumed to be a map from property names to values.
     *     If a properties file name string is used, then it is assumed
     *     to be a JSON file if the file name ends with .json, and a
     *     .ini file otherwise.
     */
    public function __construct(& $logger, $properties)
    {
        $this->logger = $logger;

        $this->globalProperties = array();
        $this->configurations = array();

        if (empty($properties)) {
            $message = 'No configuration was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        } elseif (is_array($properties)) {
            # Configuration specified as an array of properties
            $configuration = new Configuration($logger, $properties);
            $this->configurations[] = $configuration;
        } elseif (is_string($properties)) {
            # Configuration is in a file (properties is the name/path of the file)
            $this->configurationFile = trim($properties);

            $baseDir = realpath(dirname($this->configurationFile));

            if (preg_match('/\.ini$/i', $this->configurationFile) === 1) {
                #---------------------------
                # .ini configuration file
                #---------------------------
                $this->parseIniWorkflowFile($this->configurationFile);
            } elseif (preg_match('/\.json$/i', $this->configurationFile) === 1) {
                #-----------------------------------------------------------------
                # JSON configuration file
                #-----------------------------------------------------------------
                $this->parseJsonWorkflowFile($this->configurationFile);
            } else {
                $message = 'Non-JSON workflow file specified.';
                $code    = EtlException::INPUT_ERROR;
                throw new EtlException($message, $code);
            }
        } else {
            $message = 'Unrecognized configuration type "'.gettype($properties).'" was specified.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        // Need to get projects for this workflow:
        // ...
    }

    public function parseJsonWorkflowFile($configurationFile)
    {
        $baseDir = realpath(dirname($configurationFile));

        print "CONFIGURATION FILE: {$configurationFile}\n\n";
        $configurationFileContents = file_get_contents($configurationFile);
        if ($configurationFileContents === false) {
            $message = 'The workflow file "'.$this->configurationFile.'" could not be read.';
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }

        print "\n\n{$configurationFileContents}\n\n";

        $config = json_decode($configurationFileContents, true);
        if ($config == null && json_last_error() !== JSON_ERROR_NONE) {
            $message = 'Error parsing JSON configuration file "'.$this->configurationFile.'": '.json_last_error();
            $code    = EtlException::INPUT_ERROR;
            throw new EtlException($message, $code);
        }
        print "\n\n******************************* CONFIG:\n";
        print_r($config);
        print "******************************* END CONFIG\n";
    }

    public function parseIniWorkflowFile($configurationFile)
    {
        $baseDir = realpath(dirname($configurationFile));

        $processSections = true;
        $config = parse_ini_file($configurationFile, $processSections);

        $isWorkflow = $this->isWorkflow($config);

        if ($isWorkflow) {
            foreach ($config as $propertyName => $propertyValue) {
                if (is_array($propertyValue)) {
                    # Section (that defines a configuration)
                    $section = $propertyName;
                    $sectionProperties = $propertyValue;
                    $sectionProperties = Configuration::makeFilePropertiesAbsolute($sectionProperties, $baseDir);
                    $configFile = null;

                    $properties = $this->globalProperties;
                    $properties = Configuration::makeFilePropertiesAbsolute($properties, $baseDir);

                    if (array_key_exists(ConfigProperties::CONFIG_FILE, $properties)) {
                        # Config file property
                        $configFile = $properties[ConfigProperties::CONFIG_FILE];
                        unset($properties[ConfigProperties::CONFIG_FILE]);
                        $fileProperties = Configuration::getPropertiesFromFile($configFile);
                        $fileProperties = Configuration::makeFilePropertiesAbsolute($fileProperties, $baseDir);
                        $properties = Configuration::overrideProperties($properties, $fileProperties);
                    }

                    $properties = Configuration::overrideProperties($properties, $sectionProperties);

                    # Properties used from lowest to highest precedence:
                    # global properties, config file properties, properties defined in section
                    $this->configurations[$section] = new Configuration($this->logger, $properties);
                } else {
                    # Global property
                    $this->globalProperties[$propertyName] =  $propertyValue;
                }
            }
        } else {
            $configuration = new Configuration($this->logger, $configurationFile);
            array_push($this->configurations, $configuration);
        }

        # Parse file into properties and sections
        $properties = array();
        $sections = array();
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $sections[$key] = $value;
            } else {
                $properties[$key] = $value;
            }
        }
    }


    /**
     * @return true if this is a workflow configuration, false otherwise
     */
    public function isWorkflow($config)
    {
        $isWorkflow = false;
        $numArrays = 0;
        $numScalars = 0;
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $isWorkflow = true;
                break;
            }
        }

        return $isWorkflow;
    }

    public function generateJson()
    {
        $data = [$this->globalProperties, $this->configurations];
        $json = json_encode($data);
        #$json = json_encode($this, JSON_FORCE_OBJECT); // | JSON_PRETTY_PRINT);
        return $json;
    }

    /*
    public function toString()
    {
        $string = '';
        $string .= "Global Properties [\n";
        foreach ($this->globalProperties as $name => $value) {
            $string .= "    {$name}: {$value}\n";
        }
        $string .= "]\n";
        foreach ($this->configurations as $name => $configuration) {
            $string .= "Configuration \"{$name}\": [\n";
            $string .= print_r($configuration, true);
            $string .= "]\n";
        }

        return $string;
    }
    */

    public function getConfigurations()
    {
        return $this->configurations;
    }
}
