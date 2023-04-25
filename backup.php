<?php 

class backup extends sheet
{
    public function writeBackupLead($lead)
    {
        $this->writeData("append", $lead);
    }
}