<?php 
require 'vendor/autoload.php';
require 'config.php';
require 'sheet.php';
require 'production.php';
require 'backup.php';
require 'functions.php';

// INSTANCIA PLANILHA DE PRODUÇÃO
$instance_production_sheet = new production(production_sheet, "COPAÍBA");

$leads = [];

// MONTA AS LINHAS E SUBSTITUI NOME DAS COLUNAS PELOS CAMPOS DA API
foreach($instance_production_sheet->$sheet_data as $lead_index => $lead)
{
    $leads[$lead_index] = $instance_production_sheet->swapLeadData($lead, $lead_index);
}

foreach($leads as $row_idx => $row)
{
    // OBTÉM JSON
    $row_json = json_encode($row["json"]);

    // REALIZA A REQUISIÇÃO PARA O CV
    $url  = 'https://auten.cvcrm.com.br/api/cvio/lead';
    $ch   = curl_init();

    $headers = [
        "token: " . cv_auth_token,
        "email: " . cv_auth_email,
        "Content-Type: application/json"
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $row_json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    curl_close($ch);

    var_dump($result);
    echo $row_json;
    
    // SE RESULTADO FOR == TRUE -> GRAVA LEAD NA PLANILHA DE BACKUP E REMOVE DA DE PRODUÇÃO
    // SE RESULTADO FOR == FALSE -> GRAVA ERRO NA PLANILHA DE PRODUÇÃO E NÃO REMOVE LEAD
    $result = json_decode($result, true);
    
    if(isset($result['sucesso']) && $result['sucesso'] == true)
    {
        // SEMEIA PLANILHA DE BACKUP COM LEAD QUE FOI INSERIDO
        $rows = [$row["raw_data"]];
        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($rows);
        $range = $page;
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $service->spreadsheets_values->append($backup_sheet_id, $range, $valueRange, $options);



        // LIMPA LINHA NA PLANILHA DE PRODUÇÃO
        $clear_row = semeiaArrayClear($header);
        $rows = [$clear_row];
        $valueRange = new \Google_Service_Sheets_ValueRange();
        $valueRange->setValues($rows);
        $range = 'COPAÍBA!' . $row["sheet_init"] . ":" . $row["sheet_end"];
        $options = ['valueInputOption' => 'USER_ENTERED'];
        $service->spreadsheets_values->update($production_sheet_id, $range, $valueRange, $options);
    }
    else
    {
        // GRAVAR ERRO NA PLANILHA DE PRODUÇÃO
    }
}

function semeiaArrayClear($row)
{
    $ret = [];
    foreach($row as $col)
    {
        $ret[] = " ";
    }
    return $ret;
}