<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\REDCapETL\TestProject;

/**
 * PHPUnit tests for the Logger class.
 */
class ConfigurationTest extends TestCase
{
    public function setUp()
    {
    }

    public function testConfig()
    {
        $propertiesFile = __DIR__.'/../data/config-testconfiguration.ini';
        $logger = new Logger('test-app');

        $config = new Configuration($logger, $propertiesFile);
        $this->assertNotNull($config, 'config not null check');

        $retrievedLogger = $config->getLogger();
        $this->assertEquals($logger, $retrievedLogger, 'Logger check');

        $expectedAllowedServers = 'foo.com';
        $allowedServers = $config->getAllowedServers();
        $this->assertEquals($expectedAllowedServers, $allowedServers,
                            'AllowedServers check');

        $this->assertEquals($logger->getApp(),
                            $config->getApp(), 'GetApp check');

        $expectedDataSourceApiToken = '1111111122222222333333334444444';
        $dataSourceApiToken = $config->getDataSourceApiToken();
        $this->assertEquals($expectedDataSourceApiToken, $dataSourceApiToken,
                            'DataSourceApiToken check');

        $expectedTransformRulesSource = '3';
        $transformRulesSource = $config->getTransformRulesSource();
        $this->assertEquals($expectedTransformRulesSource,
                            $transformRulesSource,
                            'TransformRulesSource check');

        $expectedTransformationRules = 'TEST RULES';
        $config->setTransformationRules($expectedTransformationRules);
        $transformationRules = $config->getTransformationRules();
        $this->assertEquals($expectedTransformationRules, $transformationRules,
                            'TransformationRules check');

        $expectedBatchSize = '10';
        $config->setBatchSize($expectedBatchSize);
        $batchSize = $config->getBatchSize();
        $this->assertEquals($expectedBatchSize, $batchSize, 'BatchSize check');

        $expectedCaCertFile = 'test_cacert_file_path';
        $caCertFile = $config->getCaCertFile();
        $this->assertEquals($expectedCaCertFile, $caCertFile,
                            'CaCertFile check');

        $expectedCalcFieldIgnorePattern = '/^0$/';
        $calcFieldIgnorePattern = $config->getCalcFieldIgnorePattern();
        $this->assertEquals($expectedCalcFieldIgnorePattern,
                            $calcFieldIgnorePattern,
                            'CalcFieldIgnorePattern check');

        $expectedEmailFromAddress = 'foo@bar.com';
        $emailFromAddress = $config->getEmailFromAddress();
        $this->assertEquals($expectedEmailFromAddress, $emailFromAddress,
                            'EmailFromAddress check');

        $expectedEmailSubject = 'email subject';
        $emailSubject = $config->getEmailSubject();
        $this->assertEquals($expectedEmailSubject, $emailSubject,
                            'EmailSubject check');

        $expectedEmailToList = 'bang@bucks.net,what@my.com';
        $emailToList = $config->getEmailToList();
        $this->assertEquals($expectedEmailToList, $emailToList,
                            'EmailToList check');

        $expectedExtractedRecordCountCheck = false;
        $extractedRecordCountCheck = $config->getExtractedRecordCountCheck();
        $this->assertEquals($expectedExtractedRecordCountCheck,
                            $extractedRecordCountCheck,
                            'ExtractedRecordCountCheck check');

        $expectedFieldType = Schema\FieldType::VARCHAR;
        $expectedFieldSize = 123;

        $generatedInstanceType = $config->getGeneratedInstanceType();
        $this->assertEquals($expectedFieldType,
                            $generatedInstanceType->getType(),
                            'GeneratedInstanceType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedInstanceType->getSize(),
                            'GeneratedInstanceType size check');

        $generatedKeyType = $config->getGeneratedKeyType();
        $this->assertEquals($expectedFieldType,
                            $generatedKeyType->getType(),
                            'GeneratedKeyType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedKeyType->getSize(),
                            'GeneratedKeyType size check');

        $generatedLabelType = $config->getGeneratedLabelType();
        $this->assertEquals($expectedFieldType,
                            $generatedLabelType->getType(),
                            'GeneratedLabelType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedLabelType->getSize(),
                            'GeneratedLabelType size check');

        $generatedNameType = $config->getGeneratedNameType();
        $this->assertEquals($expectedFieldType,
                            $generatedNameType->getType(),
                            'GeneratedNameType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedNameType->getSize(),
                            'GeneratedNameType size check');

        $generatedRecordIdType = $config->getGeneratedRecordIdType();
        $this->assertEquals($expectedFieldType,
                            $generatedRecordIdType->getType(),
                            'GeneratedRecordIdType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedRecordIdType->getSize(),
                            'GeneratedRecordIdType size check');

        $generatedSuffixType = $config->getGeneratedSuffixType();
        $this->assertEquals($expectedFieldType,
                            $generatedSuffixType->getType(),
                            'GeneratedSuffixType type check');
        $this->assertEquals($expectedFieldSize,
                            $generatedSuffixType->getSize(),
                            'GeneratedSuffixType size check');

        $expectedLabelViewSuffix = 'testlabelviewsuffix';
        $labelViewSuffix = $config->getLabelViewSuffix();
        $this->assertEquals($expectedLabelViewSuffix,
                            $labelViewSuffix,
                            'LabelViewSuffix check');

        $expectedLogFile = '/tmp/logfile';
        $logFile = $config->getLogFile();
        $this->assertEquals($expectedLogFile, $logFile, 'LogFile check');

        $expectedLogProjectApiToken = '111222333';
        $logProjectApiToken = $config->getLogProjectApiToken();
        $this->assertEquals($expectedLogProjectApiToken, $logProjectApiToken,
                            'LogProjectApiToken check');

        $expectedCreateLookupTable = true;
        $createLookupTable = $config->getCreateLookupTable();
        $this->assertEquals($expectedCreateLookupTable,
                            $createLookupTable, 'CreateLookupTable check');

        $expectedLookupTableName = 'test_name';
        $lookupTableName = $config->getLookupTableName();
        $this->assertEquals($expectedLookupTableName,
                            $lookupTableName, 'LookupTableName check');

        $expectedPostProcessingSqlFile = '/tmp/postsql';
        $postProcessingSqlFile = $config->getPostProcessingSqlFile();
        $this->assertEquals($expectedPostProcessingSqlFile,
                            $postProcessingSqlFile,
                            'PostProcessingSqlFile check');

        $expectedProjectId = 7;
        $config->setProjectId($expectedProjectId);
        $projectId = $config->getProjectId();
        $this->assertEquals($expectedProjectId,$projectId,'ProjectId check');

        $expectedREDCapApiUrl = 'https://redcap.someplace.edu/api/';
        $redcapApiUrl = $config->getREDCapApiUrl();
        $this->assertEquals($expectedREDCapApiUrl,
                            $redcapApiUrl,
                            'REDCapApiUrl check');

        $expectedSslVerify = true;
        $sslVerify = $config->getSslVerify();
        $this->assertEquals($expectedSslVerify, $sslVerify, 'SslVerify check');

        $expectedTablePrefix = '';
        $tablePrefix = $config->getTablePrefix();
        $this->assertEquals($expectedTablePrefix,
                            $tablePrefix, 'TablePrefix check');

        $expectedTimeLimit= 3600;
        $timeLimit = $config->getTimeLimit();
        $this->assertEquals($expectedTimeLimit, $timeLimit,
                            'Time limit check');

        $expectedTimezone = 'America/Indiana/Indianapolis';
        $timezone = $config->getTimezone();
        $this->assertEquals($expectedTimezone, $timezone, 'Timezone check');

        $expectedTriggerEtl = true;
        $config->setTriggerEtl($expectedTriggerEtl);
        $triggerEtl = $config->getTriggerEtl();
        $this->assertEquals($expectedTriggerEtl, $triggerEtl,
                            'TriggerEtl check');
    }

    public function testNullPropertiesFile()
    {
        $propertiesFile = null;
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }

    public function testNonExistentPropertiesFile()
    {
        $propertiesFile = __DIR__.'/../data/non-existent-config-file.ini';
        $logger = new Logger('test-app');

        $exceptionCaught = false;
        try {
            $config = new Configuration($logger, $propertiesFile);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught, 'Exception caught');

        $expectedCode = EtlException::INPUT_ERROR;
        $this->assertEquals($expectedCode, $exception->getCode(), 'Exception code check');
    }

    public function testProperties()
    {

        // Invalid property
        // Property not defined
        // Property defined in file

        $propertiesFile = __DIR__.'/../data/config-test.ini';
        $logger = new Logger('test-app');
        $config = new Configuration($logger, $propertiesFile);

        $property = 'invalid-property';
        $expectedInfo = 'invalid property';
        $info = $config->getPropertyInfo($property);
        $this->assertEquals($expectedInfo, $info,
                           'GetProperyInfo invalid test');

        $property = 'allowed_servers';
        $expectedInfo = 'undefined';
        $info = $config->getPropertyInfo($property);
        $this->assertEquals($expectedInfo, $info,
                           'GetProperyInfo undefined test');

        $property = 'transform_rules_source';
        $expectedInfo = '3 - defined in file: '.
            __DIR__.'/../data/config-test.ini';
        $info = $config->getPropertyInfo($property);
        $this->assertEquals($expectedInfo, $info,
                           'GetProperyInfo in file test');

        $isFromFile = $config->isFromFile($property);
        $this->assertTrue($isFromFile, 'IsFromFile from file test');

        $expectedGetProperty = '3';
        $getProperty = $config->getProperty($property);
        $this->assertEquals($expectedGetProperty, $getProperty,
                            'GetProperty check');


        // Property defined in array
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $expectedProperties = array(
            'redcap_api_url' => 'https://redcap.someplace.edu/api/',
            'transform_rules_source' => '2');
        $configMock->setProperties($expectedProperties);
        $properties = $configMock->getProperties();
        $this->assertEquals($expectedProperties, $properties,
                            'Get Properties check');

        $property = 'transform_rules_source';
        $expectedInfo = '2 - defined in array argument';
        $info = $configMock->getPropertyInfo($property);
        $this->assertEquals($expectedInfo, $info,
                           'GetProperyInfo in array test');

        // Property defined in configuration project
        $configMock->setConfiguration($expectedProperties);
        $expectedInfo = '2 - defined in configuration project';
        $info = $configMock->getPropertyInfo($property);
        $this->assertEquals($expectedInfo, $info,
                           'GetProperyInfo in config test');

        $isFromFile = $configMock->isFromFile($property);
        $this->assertFalse($isFromFile, 'IsFromFile from elsewhere test');
    }

    public function testConfigWithoutConstructor()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $reflection = new \ReflectionClass('IU\PHPCap\RedCapProject');
        $expectedConfigProject = $reflection->newInstanceWithoutConstructor();
        $configMock->SetConfigProject($expectedConfigProject);
        $configProject = $configMock->GetConfigProject();
        $this->assertEquals($expectedConfigProject, $configProject,
                            'GetConfigProject check');
    }

    public function testProcessDirectory()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        // Null argument
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage =
            'Null path specified as argument to processDirectory';
        try {
            $configMock->processDirectory(null);
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught,
                          'ProcessDirectory null Exception caught');
        $this->assertEquals($expectedCode, $exception->getCode(),
                            'ProcessDirectory null exception code check');
        $this->assertEquals(substr($expectedMessage,15,0),
                            substr($exception->getMessage(),15,0),
                            'ProcessDirectory null exception message check');

        // Non-string argument
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Non-string path specified as argument';
        try {
            $configMock->processDirectory(array('foo'));
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught,
                          'ProcessDirectory non-string Exception caught');
        $this->assertEquals($expectedCode, $exception->getCode(),
                            'ProcessDirectory non-string exception code check');
        $this->assertEquals(substr($expectedMessage,15,0),
                            substr($exception->getMessage(),15,0),
                            'ProcessDirectory non-string exception message check');

        // Absolute path argument
        $expectedRealDir = '/tmp';
        $realDir = $configMock->processDirectory($expectedRealDir);

        $this->assertEquals($expectedRealDir, $realDir,
                            'ProcessDirectory absolute');

        // Relative path argument, no properties file
        // NOTE: Because PHPUnit runs the test from the 'tests/unit'
        //       directory, the __DIR__ variable will include tests/unit
        //       already.
        $path = 'tests/unit/Database';
        $expectedRealDir = __DIR__.'/Database';
        $realDir = $configMock->processDirectory($path);

        $this->assertEquals($expectedRealDir, $realDir,
                            'ProcessDirectory relative, no properties');

        // Relative path argument, no properties file, dir not found
        $exceptionCaught = false;
        $expectedCode = EtlException::INPUT_ERROR;
        $expectedMessage = 'Directory';
        try {
            $configMock->processDirectory('foo');
        } catch (EtlException $exception) {
            $exceptionCaught = true;
        }

        $this->assertTrue($exceptionCaught,
                          'ProcessDirectory relative not found Exception caught');
        $this->assertEquals($expectedCode, $exception->getCode(),
                            'ProcessDirectory relative not found exception code check');
        $this->assertEquals(substr($expectedMessage,9,0),
                            substr($exception->getMessage(),9,0),
                            'ProcessDirectory relative not found exception message check');

        // Relative path argument, using properties file
        // NOTE: Because PHPUnit runs the test from the 'tests/unit'
        //       directory, the __DIR__ variable will include tests/unit
        //       already. Additionally, because we've specified the
        //       properties file using __DIR_, too, then REDCapEtl will check
        //       for relative paths to the same directory as the properties
        //       file, effectively adding 'test/unit' to the relative path.
        $method = $reflection->getMethod('setPropertiesFile');
        $method->setAccessible(true);
        $method->invokeArgs($configMock, array(__DIR__.'/ConfigurationTest.php'));

        $path = 'Database';
        $expectedRealDir = __DIR__.'/Database';
        $realDir = $configMock->processDirectory($path);
        $this->assertEquals($expectedRealDir, $realDir,
                            'ProcessDirectory relative, properties file');

    }

    public function testIsValidEmail()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $validEmail = 'foo@bar.com';
        $invalidEmail = 'foo-bar-bang';

        $isValidEmail = $configMock->isValidEmail($validEmail);
        $this->assertTrue($isValidEmail, 'IsValidEmail true check');

        $isValidEmail = $configMock->isValidEmail($invalidEmail);
        $this->assertFalse($isValidEmail, 'IsValidEmail false check');
    }

    public function testDbConnection()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();

        $expectedMySqlConnectionInfo = array('foo','bar','bang');
        $expectedDbConnection =
            implode(':',
                    array_merge(array('MySQL'),
                                $expectedMySqlConnectionInfo));
        $configMock->SetDbConnection($expectedDbConnection);

        $dbConnection = $configMock->GetDbConnection();
        $this->assertEquals($expectedDbConnection,
                            $dbConnection, 'GetDbConnection check');

        $mySqlConnectionInfo = $configMock->getMySqlConnectionInfo();
        $this->assertEquals($expectedMySqlConnectionInfo,
                            $mySqlConnectionInfo,
                            'GetMySqlConnectionInfo check');
    }

    // NOTE: The following unit test allows for a direct test of a private
    //       function, 'isAbsolutePath'.
    // NOTE: This  unit test is probably unnecessary, as the
    //       private method 'isAbsolutePath' could probably have been tested
    //       as part of the test of some other, public method.
    // NOTE: I don't know how to get full code coverage on code that
    //       can't be reached fully from any one single OS.
    public function testIsAbsolutePath()
    {
        $reflection = new \ReflectionClass('IU\REDCapETL\Configuration');
        $configMock = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('isAbsolutePath');
        $method->setAccessible(true);

        $absolutePath = '/foo/bar/bang';
        $relativePath = 'foo/bar/bang';

        $isAbsolutePath = $method->invokeArgs($configMock,
                                              array($absolutePath));
        $this->assertTrue($isAbsolutePath, 'IsAbsolutePath true check');

        $isAbsolutePath = $method->invokeArgs($configMock,
                                              array($relativePath));
        $this->assertFalse($isAbsolutePath, 'IsAbsolutePath false check');
    }
}
