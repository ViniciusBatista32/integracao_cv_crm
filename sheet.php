<?php

abstract class sheet
{
    protected $client;
    protected $service;

    protected $sheet_id;
    protected $page;

    protected $possible_cols;
    protected $mandatory_cols;
    protected $jump_cols = [];

    public $sheet_data;
    protected $header;

    protected $cols_init = "A";
    protected $cols_end;

    public function __construct($sheet_id, $page)
    {
        //INSTANCIA E CONFIGURA DOCUMENTO GOOGLE SHEETS
        $this->client = new \Google_Client();
        $this->client->setApplicationName('Google Sheets and PHP');
        $this->client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
        $this->client->setAccessType('offline');
        $this->client->setAuthConfig(CREDENTIALS_FILE);
        $this->service = new Google_Service_Sheets($this->client);

        // GRAVA DADOS DO SHEET
        $this->sheet_id = $sheet_id;
        $this->page = $page;
    }

    public function throwError($type, ...$args)
    {
        switch($type)
        {
            case "code_exception":

                die($args[0]);

            break;

            case "lead_exception":

                $lead = $args[0];
                $mensagem = $args[1];

                $lead_end_word = substr($lead["lead_end"], 0, 1);
                $lead_end_number = substr($lead["lead_end"], 1);

                $cell = $this->getAlphabetNext($lead_end_word) . $lead_end_number;
                $this->writeData("update", [[$mensagem]], $cell, $cell);

            break;

            case "request_exception":
                
                $lead = $args[0];
                $response = $args[1];
                $erro = isset($response["mensagem"]) ? $response["mensagem"] : "Erro ao cadastrar Lead no CV. Contate o desenvolvedor da integração.";

                $lead_end_word = substr($lead["lead_end"], 0, 1);
                $lead_end_number = substr($lead["lead_end"], 1);

                $cell = $this->getAlphabetNext($lead_end_word) . $lead_end_number;
                $this->writeData("update", [[$erro]], $cell, $cell);
            break;
        }
    }

    protected function getSheet($sheet_id, $page)
    {
        $response = $this->service->spreadsheets_values->get($sheet_id, $page);
        return $response->getValues();
    }

    protected function getHeader()
    {
        return is_array($this->sheet_data) && count($this->sheet_data) > 0 ? array_shift($this->sheet_data) : Array();
    }

    protected function getAlphabetRange($header)
    {
        // OBTÉM RANGE DE COLUNAS
        $alphabet_range = range('A', 'Z');
        return $alphabet_range[(count($header) - 1)];
    }
    
    protected function getAlphabetNext($col)
    {
        // OBTÉM RANGE DE COLUNAS
        $alphabet_range = range('A', 'Z');
        return $alphabet_range[(array_search($col, $alphabet_range) + 1)];
    }

    /**
     * Valida se as colunas do header estão nas possíveis, se não, pula coluna
     * Grava se coluna está nas colunas obrigatórias
     */
    protected function checkMandatoryCols($header)
    {
        foreach($header as $i => $col)
        {
            $col = strtolower($col);
        
            if(!in_array($col, $this->possible_cols))
                $this->jump_cols[] = $i;
            else if(isset($this->mandatory_cols[$col]))
                $this->mandatory_cols[$col] = true;
        }

        // CASO UMA COLUNA OBRIGATÓRIA NÃO FOI ENVIADA, CÓDIGO PARA
        if(in_array(false, $this->mandatory_cols))
            $this->throwError("code_exception", "COLUNA OBRIGATORIA NAO ESTÁ PRESENTE NA PLANILHA");
    }

    protected function writeData($type, $values, $init = NULL, $end = NULL)
    {
        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($values);
        $range = $this->page . ((!empty($init) && !empty($end)) ? ('!' . $init . ":" . $end) : "");
        $options = ['valueInputOption' => 'USER_ENTERED'];

        switch($type)
        {
            case "update":
                $this->service->spreadsheets_values->update($this->sheet_id, $range, $valueRange, $options);
            break;

            case "append":
                $this->service->spreadsheets_values->append($this->sheet_id, $range, $valueRange, $options);
            break;
        }
    }

    public function clearRow($init, $end)
    {
        $this->writeData("update", $this->getClearRow($this->header), $init, $end);
    }

    protected function getClearRow($cols)
    {
        return [array_fill(0, count($cols), "")];
    }
}