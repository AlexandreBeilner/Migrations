<?php
/*
  Biblioteca de Funções.
    Você pode separar funções muito utilizadas nesta biblioteca, evitando replicação de código.
*/

function dateNow(){
  date_default_timezone_set('America/Sao_Paulo');
  return date('d-m-Y \à\s H:i:s');
}

function transformInArray(mysqli_result $data): array{
    $allData = [];
    if ($data->num_rows > 0) {
        while ($row = $data->fetch_assoc()) {
            $allData[] = $row;
        }
    } else {
        die("Ocorreu algum erro na busca de dados em 0temp.temp_pacientes");
    }
    return $allData;
}
