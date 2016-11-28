<?php
/**
 * Csv Import Script
 *
 *
 * @category Youwe Development
 * @package  psg.pimcore
 * @author   Bas Ouwehand <b.ouwehand@youwe.nl>
 * @date     6/1/15
 *
 */
$workingDirectory = getcwd();
chdir(__DIR__);
//include_once("../../../pimcore/cli/startup.php");
include_once("/data/projects/ibood-pimcore/pimcore/cli/startup.php");

//execute in admin mode
define("PIMCORE_ADMIN", true);

///some command line options for my importer
try {
    $opts = new Zend_Console_Getopt(array(
        'profileId|p=i' => 'profile required integer parameter '
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    echo $e->getUsageMessage();
    exit;
}

$profileId = $opts->getOption('profileId');
/*
try {
    CsvDataMapper_Import_Profile::run($profileId);
} catch( Exception $e) {
    die($e);
}*/

$importer = new Importer($profileId);
$importer->run();



class Importer
{
    const FILE_TYPE_CSV = 'csv';
    const FILE_TYPE_EXCEL = 'excel';

    private $file_path = '';
    private $profile = null;
    private $source = null;
    private $runnerClass = null;


    public function __construct(int $profileId)
    {
        $this->receiveProfile($profileId);
        $this->setPath();
        $this->setSource();
        $this->setRunnerClass();
    }

    private function receiveProfile($profileId)
    {
        $table = new CsvImport_DbTable_Profile();
        $rows = $table->find($profileId);
        if(count($rows) == 0)
        {
            throw new Exception('No valid profile selected');
        }
        $this->profile = reset(reset($rows));
    }

    public function setPath($location = PIMCORE_TEMPORARY_DIRECTORY.DIRECTORY_SEPARATOR.'bulk-pump')
    {
        $this->file_path = $location;
    }

    public function getPath()
    {
        return $this->file_path;
    }

    private function setSource()
    {
        switch ($this->detectType($this->profile['load_path']))
        {
            case Importer::FILE_TYPE_CSV:
                $this->source = new CSVReader();
                break;
            case Importer::FILE_TYPE_EXCEL:
                $this->source = new ExcelReader();
                break;
            default:
                throw new Exception('No correct file type found');
                break;
        }
    }


    private function detectType(string $file)
    {
        $location = $this->getPath().DIRECTORY_SEPARATOR.$file;
        $mime_type = null;

        if(file_exists($location)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $location);
        }else{
            throw new Exception("File not found");
        }

        switch($mime_type)
        {
            case 'text/plain':
            case 'text/csv':
            case 'text/comma-separated-values':
            case 'application/csv':
                return Importer::FILE_TYPE_CSV;
                break;
            case 'application/excel':
            case 'application/vnd.ms-excel':
            case 'application/vnd.msexcel':
                return Importer::FILE_TYPE_EXCEL;
                break;
            default:
                throw new Exception("File type not defined correctly: ".$mime_type);
                break;
        }
    }

    private function setRunnerClass()
    {
        if($this->profile['import_type'] == "custom") {
            $class = $this->checkConfigField('customClass');

            if (class_exists($this->profile['custom_class']) === false) {
                throw new Exception('Importer class not found!');
            }

            /** @var \BulkPump\CustomImportInterface $importer */
            $importer = new $class($this->getConfig());
            if (!$importer instanceof \PimcoreBulkpump\CustomImportInterface) {
                throw new Exception('Importer class has to implement "\BulkPump\CustomImportInterface"!');
            }
        }else{

        }
    }

    public function run()
    {
        if($this->source instanceof SourceInterface)
        {
            if($this->runnerClass instanceof RunnerInterface) {
                for ($i = $this->source->getFirstRowNumber(); $i < $this->source->maxRows(); $i++) {
                    $this->runnerClass->import($this->source->getCurrentRow());
                    $this->runnerClass->executeImport();
                    $this->source->next();
                }
            }else{
                throw new Exception("Runner instance is not correct");
            }
        }else{
            throw new Exception("No correct source");
        }
    }

}


abstract class RunnerClass
{
    abstract public function import($rowData);

    private $object = null;


    final public function executeImport()
    {

    }
}

class DefaultRunner extends RunnerClass
{
    public function import($rowData)
    {

    }
}

class CustomRunner extends RunnerClass
{
    public function import($rowData)
    {

    }
}

interface SourceInterface
{
    public function setFileLocation(string $file);
    public function setFirstRowHeader(boolean $isheader);
    public function getFirstRowNumber();
    public function getColumns();
    public function getCurrentRow();
    public function getMaxRows();
    public function next();
    public function setMapper();
}

class ExcelReader implements SourceInterface
{
    public function setFileLocation(string $file)
    {

    }
    public function setFirstRowHeader(boolean $isheader){}
    public function getFirstRowNumber(){}
    public function getColumns(){}
    public function getCurrentRow(){}
    public function getMaxRows(){}
    public function next(){}
    public function setMapper(){}
}
class CSVReader implements SourceInterface
{
    public function setFileLocation(string $file){}
    public function setFirstRowHeader(boolean $isheader){}
    public function getFirstRowNumber(){}
    public function getColumns(){}
    public function getCurrentRow(){}
    public function getMaxRows(){}
    public function next(){}
    public function setMapper(){}
}