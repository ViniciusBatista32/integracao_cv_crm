<?php

class sheet
{
    //INSTANCIA E CONFIGURA DOCUMENTO GOOGLE SHEETS
    private static $client;
    private static $service;

    public function __construct($sheet_id, $page)
    {
        $this::$client = new \Google_Client();
        $this::$client->setApplicationName('Google Sheets and PHP');
        $this::$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this::$client->setAccessType('offline');
        $this::$client->setAuthConfig(credentials);
        $this::$service = new Google_Service_Sheets($this::$client);

        $this::$sheet_id = $sheet_id;
        $this::$page = $page;
    }

    public function throwError($type, ...$args)
    {
        switch($type)
        {
            case "exception":



            break;

            case "row_record_request":

                

            break;
        }
    }

    public function getSheet($sheet_id, $page)
    {
        $response = $this::$service->spreadsheets_values->get($sheet_id, $page);
        return $response->getValues();
    }

    public function checkMandatoryCols($header)
    {
        foreach($header as $idx => $col)
        {
            $col = strtolower($col);
        
            if(!in_array($col, $possible_cols))
                $jump_cols[] = $idx;
            else if(isset($mandatory_cols[$col]))
                $mandatory_cols[$col] = true;
        }
    }
}