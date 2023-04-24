<?php

class production extends sheet
{
    private static $possible_cols = ["nome", "email", "telefone", "moradia", "cpf", "investimento", "atendimento", "midia"];
    private static $mandatory_cols = ["nome" => false, "email" => false, "telefone" => false];

    function swapLeadData($lead, $lead_index)
    {
        $lead_data = Array(
            "sheet_init" => $this->$cols_init . ($lead_index + 2), //LISTA COLUNAS, INIT = A, FIN = COUNT($COL) -> LIN = IDX + 1
            "sheet_end" => $this->$cols_end . ($lead_index + 2),
            "raw_data" => $lead,
            "json" => Array()
        );

        $lead_desc = "Lead inserido via integração com planilha do Google Sheets\n";

        foreach($this->$header as $col_idx => $col)
        {
            $col = strtolower($col);

            if(!in_array($col_idx, $this->$jump_cols))
            {
                $val = isset($lead[$col_idx]) ? $lead[$col_idx] : "";
                //TODO REALIZAR O THROW ERROR DE INFOS DE LEAD
                // $this->throwError();
                if(empty($val) && array_key_exists($col, $this->$mandatory_cols))
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