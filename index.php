<?php 
require 'vendor/autoload.php';
require 'config.php';
require 'sheet.php';
require 'production.php';
require 'backup.php';

//TODO PASSAR ISSO PARA CONSTANTE E AVALIAR NECESSIDADE
$possible_cols = ["nome", "email", "telefone", "moradia", "cpf", "investimento", "atendimento", "midia"];
$mandatory_cols = ["nome" => false, "email" => false, "telefone" => false];
$jump_cols = [];

// INSTANCIA PLANILHA DE PRODUÇÃO
$instance_production_sheet = new sheet(production_sheet);
// PEGA OS DADOS DA PLANILHA DE PRODUÇÃO
$instance_production_sheet = $instance_production_sheet->

$header = [];
$rows = [];

// VALIDA SE COLUNAS ATENDEM AS COLUNAS OBRIGATÓRIAS E SE ESTÃO NAS POSSÍVEIS, SE NÃO, PULA COLUNA


// CASO UMA COLUNA OBRIGATÓRIA NÃO FOI ENVIADA, CÓDIGO PARA
if(in_array(false, $mandatory_cols))
    die("COLUNA OBRIGATORIA NAO ENVIADA");



// OBTÉM CABEÇALHO (COM COLUNAS) DA PLANILHA
$header = array_shift($sheet_data);

// OBTÉM RANGE DE COLUNAS
$alphabet_range = range('A', 'Z');
$cols_init = "A";
$cols_end = $alphabet_range[(count($header) - 1)];


// MONTA AS LINHAS E SUBSTITUI NOME DAS COLUNAS PELOS CAMPOS DA API
foreach($sheet_data as $row_idx => $row)
{
    $row_data = Array(
        "sheet_init" => $cols_init . ($row_idx + 2), //LISTA COLUNAS, INIT = A, FIN = COUNT($COL) -> LIN = IDX + 1
        "sheet_end" => $cols_end . ($row_idx + 2),
        "raw_data" => $row,
        "json" => Array()
    );

    $lead_desc = "Lead inserido via integração com planilha do Google Sheets\n";

    foreach($header as $col_idx => $col)
    {
        $col = strtolower($col);

        if(!in_array($col_idx, $jump_cols))
        {
            $val = isset($row[$col_idx]) ? $row[$col_idx] : "";

            if(empty($val) && array_key_exists($col, $mandatory_cols))
                continue 2;
            else if(empty($val))
                continue;
            
            // ADICIONA O INTERESSE E ATENDIMENTO À DESCRIÇÃO DO LEAD
            switch($col)
            {
                case "investimento":
                    $lead_desc .= "Interesse: Investimento\n";
                    continue 2;
                break;

                case "moradia":
                    $lead_desc .= "Interesse: Moradia\n";
                    continue 2;
                break;

                case "atendimento":
                    $lead_desc .= "Atendimento:" . $val . "\n";
                    continue 2;
                break;

                case "midia":
                    $col = "endereco";
                break;
            }
            
            $row_data["json"][$col] = $val;
        }
        else
            continue;
    }

    // POR ENQUANTO EMPREENDIMENTO PADRÃO SERÁ COPAÍBA = 3
    $row_data["json"]["idempreendimento"] = 3;

    // INSERE INTERAÇÃO DE DESCRIÇÃO DE INSERÇÃO DO LEAD
    $row_data["json"]["interacoes"] = Array(
        Array(
            "tipo" => "A",
            "descricao" => $lead_desc,
        )
    );

    $rows[$row_idx] = $row_data;
}

if(HOMOLOG)
{
    $rows = Array(
        Array(
            "nome" => "teste lead integracao",
            "email" => "teste@integracao.com",
            "telefone" => "11888888888",
            "moradia" => "morador"
        )
    );
}

foreach($rows as $row_idx => $row)
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