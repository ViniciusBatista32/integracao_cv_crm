<?php 
require 'vendor/autoload.php';
require 'config.php';
require 'sheet.php';
require 'production.php';
require 'backup.php';

// INSTANCIA PLANILHA DE PRODUÇÃO
$instance_production_sheet = new production(production_sheet, "COPAÍBA");
$instance_backup_sheet = new backup(backup_sheet, "COPAÍBA");
var_dump($instance_production_sheet::$sheet_id);
die();
$leads = [];

// MONTA AS LINHAS E SUBSTITUI NOME DAS COLUNAS PELOS CAMPOS DA API
foreach($instance_production_sheet::$sheet_data as $lead_index => $lead)
{
    $swaped_lead = $instance_production_sheet->swapLeadData($lead, $lead_index);
    
    if($swaped_lead !== false)
        $leads[$lead_index] = $swaped_lead;
}

// CONFIGURA CONEXÃO COM O CV

$ch   = curl_init();
$url  = CV_URL;

$headers = [
    "token: " . CV_AUTH_TOKEN,
    "email: " . CV_AUTH_EMAIL,
    "Content-Type: application/json"
];

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


foreach($leads as $lead)
{
    // OBTÉM JSON DO LEAD
    $lead_json = json_encode($lead["json"]);
    
    // PASSA JSON PARA REQUISIÇÃO AO CV
    curl_setopt($ch, CURLOPT_POSTFIELDS, $lead_json);

    // MANDA REQUISIÇÃO PARA O CV GRAVANDO A RESPOSTA
    $response = curl_exec($ch);
    $response = json_decode($response, true);
    
    // SE RESULTADO FOR == TRUE -> GRAVA LEAD NA PLANILHA DE BACKUP E REMOVE DA DE PRODUÇÃO
    // SE RESULTADO FOR == FALSE -> GRAVA ERRO NA PLANILHA DE PRODUÇÃO E NÃO REMOVE LEAD
    
    if(isset($response['sucesso']) && $response['sucesso'] == true)
    {
        // SEMEIA PLANILHA DE BACKUP COM LEAD QUE FOI INSERIDO
        $instance_backup_sheet->writeBackupLead([$lead["raw_data"]]);

        // LIMPA LEAD NA PLANILHA DE PRODUÇÃO
        $instance_production_sheet->clearRow($lead["lead_init"], $lead["lead_end"]);
    }
    else
    {
        // GRAVAR ERRO NA PLANILHA DE PRODUÇÃO
        $instance_production_sheet->throwError("request_exception", $lead, $response);
    }
}

// FECHA CONEXÃO COM O CV
curl_close($ch);