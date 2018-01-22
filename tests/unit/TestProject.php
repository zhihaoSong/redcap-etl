<?php

namespace IU\REDCapETL;

class TestProject extends EtlProject
{
    private $records;

    public function importRecords(
        $records,
        $format = 'php',
        $type = 'flat',
        $overwriteBehavior = 'normal',
        $dateFormat = 'YMD',
        $returnContent = 'count'
    ) {
        if (!isset($this->records)) {
            $record = array();
        }

        foreach ($records as $record) {
            $this->records[$record['record_id']] = $record;
        }
    }

    public function getAllRecords()
    {
        $allRecords = $this->records;
        ksort($allRecords);
        $allRecords = array_values($allRecords);
        return $allRecords;
    }
}