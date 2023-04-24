<?php

use JetBrains\PhpStorm\ArrayShape;

class sheet
{
    private static $client;
    private static $service;

    private static $sheet_id;
    private static $page;

    private static $possible_cols;
    private static $mandatory_cols;
    private $jump_cols;

    public $sheet_data;
    public $header;

    private $cols_init = "A";
    private $cols_end;

    public function __construct($sheet_id, $page)
    {
        //INSTANCIA E CONFIGURA DOCUMENTO GOOGLE SHEETS
        $this::$client = new \Google_Client();
        $this::$client->setApplicationName('Google Sheets and PHP');
        $this::$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this::$client->setAccessType('offline');
        $this::$client->setAuthConfig(credentials);
        $this::$service = new Google_Service_Sheets($this::$client);

        // GRAVA DADOS DO SHEET
        $this::$sheet_id = $sheet_id;
        $this::$page = $page;

        // PEGA DADOS DA PLANILHA
        $this::$sheet_data = $this->getSheet($this::$sheet_id, $this::$page);
        // SEPARA CABEÇALHO (COLUNAS)
        $this::$header = $this->getHeader();
        // PEGA ULTIMA LETRA DAS COLUNAS
        $this::$cols_end = $this->getAlphabetRange($this::$header);
    }

    private static function throwError($type, ...$args)
    {
        switch($type)
        {
            case "code_exception":

                die($args[0]);

            break;

            case "lead_exception":

                

            break;

            case "request_exception":

                

            break;
        }
    }

    private function getSheet($sheet_id, $page)
    {
        $response = $this::$service->spreadsheets_values->get($sheet_id, $page);
        return $response->getValues();
    }

    private function getHeader()
    {
        return is_array($this::$sheet_data) && count($this::$sheet_data) > 0 ? array_shift($this::$sheet_data) : Array();
    }

    private function getAlphabetRange($header)
    {
        // OBTÉM RANGE DE COLUNAS
        $alphabet_range = range('A', 'Z');
        return $alphabet_range[(count($header) - 1)];
    }

    /**
     * Valida se as colunas do header estão nas possíveis, se não, pula coluna
     * Grava se coluna está nas colunas obrigatórias
     */
    public function checkMandatoryCols($header)
    {
        foreach($header as $i => $col)
        {
            $col = strtolower($col);
        
            if(!in_array($col, $this::$possible_cols))
                $this::$jump_cols[] = $i;
            else if(isset($this::$mandatory_cols[$col]))
                $this::$mandatory_cols[$col] = true;
        }

        // CASO UMA COLUNA OBRIGATÓRIA NÃO FOI ENVIADA, CÓDIGO PARA
        if(in_array(false, $this::$mandatory_cols))
            $this::throwError("code_exception", "COLUNA OBRIGATORIA NAO ENVIADA");
    }
}