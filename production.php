<?php

class production extends sheet
{
    public static $possible_cols = ["nome", "email", "telefone", "moradia", "cpf", "investimento", "atendimento", "midia"];
    public static $mandatory_cols = ["nome" => false, "email" => false, "telefone" => false];

    public function __construct($sheet_id, $page)
    {
        parent::__construct($sheet_id, $page);

        // PEGA DADOS DA PLANILHA
        $this::$sheet_data = $this->getSheet($this::$sheet_id, $this::$page);
        // SEPARA CABEÇALHO (COLUNAS)
        $this::$header = $this->getHeader();
        // VALIDA AS COLUNAS OBRIGATÓRIAS
        $this->checkMandatoryCols($this::$header);
        // PEGA ULTIMA LETRA DAS COLUNAS
        $this::$cols_end = $this->getAlphabetRange($this::$header);
    }

    function swapLeadData($lead, $lead_index)
    {
        $lead_data = Array(
            "lead_init" => $this::$cols_init . ($lead_index + 2), //LISTA COLUNAS, INIT = A, FIN = COUNT($COL) -> LIN = IDX + 1
            "lead_end" => $this::$cols_end . ($lead_index + 2),
            "raw_data" => $lead,
            "json" => Array()
        );

        $lead_desc = "Lead inserido via integração com planilha do Google Sheets\n";

        foreach($this::$header as $col_idx => $col)
        {
            $col = strtolower($col);

            if(!in_array($col_idx, $this::$jump_cols))
            {
                $val = isset($lead[$col_idx]) ? $lead[$col_idx] : "";
                
                if(empty($val) && array_key_exists($col, $this::$mandatory_cols))
                {
                    // CASO COLUNA OBRIGATÓRIA NÃO TENHA SIDO PREENCHIDA NO LEAD -> NÃO SERÁ CADASTRADO
                    $this->throwError("lead_exception", "Coluna obrigatória '" . $col . "' vazia");
                    return false;
                }
                else if(empty($val))
                    continue;
                
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