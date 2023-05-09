<?php

class production extends sheet
{
    public $possible_cols = ["nome", "email", "telefone", "moradia", "cpf", "investimento", "atendimento", "midia"];
    public $mandatory_cols = ["nome" => false, "email" => false, "telefone" => false];

    public function __construct($sheet_id, $page)
    {
        parent::__construct($sheet_id, $page);

        // PEGA DADOS DA PLANILHA
        $this->sheet_data = $this->getSheet($this->sheet_id, $this->page);
        // SEPARA CABEÇALHO (COLUNAS)
        $this->header = $this->getHeader();
        // VALIDA AS COLUNAS OBRIGATÓRIAS
        $this->checkMandatoryCols($this->header);
        // PEGA ULTIMA LETRA DAS COLUNAS
        $this->cols_end = $this->getAlphabetRange($this->header);
    }

    function swapLeadData($lead, $lead_index)
    {
        $empty_lead = true;
        $missing_mandatory_cols = [];

        $lead_data = Array(
            "lead_init" => $this->cols_init . ($lead_index + 2), //LISTA COLUNAS, INIT = A, FIN = COUNT($COL) -> LIN = IDX + 1
            "lead_end" => $this->cols_end . ($lead_index + 2),
            "raw_data" => $lead,
            "json" => Array()
        );

        $lead_desc = "Lead inserido via integração com planilha do Google Sheets\n";

        foreach($this->header as $col_idx => $col)
        {
            $col = strtolower($col);
            $val = isset($lead[$col_idx]) ? trim($lead[$col_idx]) : "";

            if(!in_array($col_idx, $this->jump_cols))
            {
                if(empty($val) && array_key_exists($col, $this->mandatory_cols))
                    $missing_mandatory_cols[] = $col;
                else if(empty($val))
                    continue;
                else if(!empty($val))
                    $empty_lead = false;
                
                // ADICIONA O INTERESSE E ATENDIMENTO À DESCRIÇÃO DO LEAD
                switch($col)
                {
                    case "investimento":
                        $lead_desc .= "Interesse: Investimento\n";
                    break;

                    case "moradia":
                        $lead_desc .= "Interesse: Moradia\n";
                    break;

                    case "atendimento":
                        $lead_desc .= "Atendimento:" . $val . "\n";
                    break;
                    
                    // INSERE MIDIA COMO ENDEREÇO
                    case "midia":
                        $col = "endereco";
                    break;
                }
                
                $lead_data["json"][$col] = $val;
            }
            else
                continue;
        }

        // SE TODAS AS COLUNAS DO LEAD FOREM VAZIAS, LEAD NÃO SERÁ CADASTRADO
        if($empty_lead)
            return false;
        
        foreach($missing_mandatory_cols as $col)
        {
            if($col == "email" && !in_array("telefone", $missing_mandatory_cols))
                continue;
            else if($col == "telefone" && !in_array("email", $missing_mandatory_cols))
                continue;
            else
            {
                // CASO COLUNA OBRIGATÓRIA NÃO TENHA SIDO PREENCHIDA NO LEAD -> NÃO SERÁ CADASTRADO
                $this->throwError("lead_exception", $lead_data, "Coluna obrigatória '" . $col . "' vazia");
                return false;
            }
        }

        // POR ENQUANTO EMPREENDIMENTO PADRÃO SERÁ COPAÍBA = 3
        $lead_data["json"]["idempreendimento"] = 3;

        // INSERE INTERAÇÃO DE DESCRIÇÃO DE INSERÇÃO DO LEAD
        $lead_data["json"]["interacoes"] = Array(
            Array(
                "tipo" => "A",
                "descricao" => $lead_desc,
            )
        );

        return $lead_data;
    }
}