<?php
/*
  Descrição do Desafio:
    Você precise realizar uma migração dos dados fictícios que estão na pasta <dados_sistema_legado> para a base da clínica fictícia MedicalChallenge.
    Para isso, você precisa:
      1. Instalar o MariaDB na sua máquina. Dica: Você pode utilizar Docker para isso;
      2. Restaurar o banco da clínica fictícia Medical Challenge: arquivo <medical_challenge_schema>;
      3. Migrar os dados do sistema legado fictício que estão na pasta <dados_sistema_legado>:
        a) Dica: você pode criar uma função para importar os arquivos do formato CSV para uma tabela em um banco temporário no seu MariaDB.
      4. Gerar um dump dos dados já migrados para o banco da clínica fictícia Medical Challenge.
*/

// Importação de Bibliotecas:
include "./lib.php";

// Conexão com o banco da clínica fictícia:
$connMedical = mysqli_connect("localhost", "root", "root", "MedicalChallenge")
or die("Não foi possível conectar os servidor MySQL: MedicalChallenge\n");

// Conexão com o banco temporário:
$connTemp = mysqli_connect("localhost", "root", "root", "0temp")
or die("Não foi possível conectar os servidor MySQL: 0temp\n");

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";


/*
  Seu código vai aqui!
*/

//$createTemp_agendamentos = "CREATE TABLE IF NOT EXISTS `0temp`.`temp_agendamentos` (
//    cod_agendamento INT PRIMARY KEY,
//    descricao VARCHAR(255),
//    dia VARCHAR(255),
//    hora_inicio VARCHAR(255),
//    hora_fim VARCHAR(255),
//    cod_paciente INT,
//    paciente VARCHAR(255),
//    cod_medico INT,
//    medico VARCHAR(255),
//    cod_convenio INT,
//    convenio VARCHAR(255),
//    procedimento VARCHAR(255)
//) ENGINE = InnoDB;";
//
//$createTemp_pacientes = "CREATE TABLE IF NOT EXISTS `0temp`.`temp_pacientes` (
//    cod_paciente INT PRIMARY KEY,
//    nome_paciente VARCHAR(255),
//    nasc_paciente VARCHAR(255),
//    pai_paciente VARCHAR(255),
//    mae_paciente VARCHAR(255),
//    cpf_paciente VARCHAR(255),
//    rg_paciente VARCHAR(255),
//    sexo_pac CHAR(1),
//    id_conv INT,
//    convenio VARCHAR(255),
//    obs_clinicas TEXT
//) ENGINE = InnoDB;";

//if ($connTemp->query($createTemp_agendamentos) === TRUE) {
//    echo "Tabela criada com sucesso";
//} else {
//    echo "Erro ao criar tabela: " . $connTemp->error;
//}
//if ($connTemp->query($createTemp_pacientes) === TRUE) {
//    echo "Tabela criada com sucesso";
//} else {
//    echo "Erro ao criar tabela: " . $connTemp->error;
//}
//$fileAgenName = "./dados_sistema_legado/20210512_agendamentos.csv";
//$filePacName = "./dados_sistema_legado/20210512_pacientes.csv";
//$fileAgen = fopen($fileAgenName, "r");
//if (!$fileAgen) {
//    die("Não foi possivel abrir o arquivo");
//}
//$AgenColumns = explode(";", fgets($fileAgen));
//$AgenColumns = implode(", ", $AgenColumns);
//while (($line = fgets($fileAgen)) !== false) {
//    $values = explode(";", $line);
//    $values = "'" . implode("','", $values) . "'";
//    $sqlQuery = "INSERT INTO temp_agendamentos ($AgenColumns) VALUES ($values)";
//    $connTemp->query($sqlQuery);
//}
//fclose($fileAgen);
//
//$filePac = fopen($filePacName, "r");
//if (!$filePac) {
//    die("Não foi possivel abrir o arquivo");
//}
//$PacColumns = explode(";", fgets($filePac));
//$PacColumns = implode(", ", $PacColumns);
//while (($line = fgets($filePac)) !== false) {
//    $values = explode(";", $line);
//    $values = "'" . implode("','", $values) . "'";
//    $sqlQuery = "INSERT INTO temp_pacientes ($PacColumns) VALUES ($values)";
//    $connTemp->query($sqlQuery);
//}
//fclose($filePac);
//convenios adicionados;
$allConveniosLegado = $connTemp->query("SELECT cod_convenio, convenio FROM temp_agendamentos");
$allConvenios = $connMedical->query("SELECT id FROM convenios");
$allConveniosLegado = transformInArray($allConveniosLegado);
$allConvenios = transformInArray($allConvenios);

$isAlreadyInNewDBConvenios = array_reduce($allConvenios, function ($reducer, $item) {
    $reducer[] = $item["id"];
    return $reducer;
});

foreach ($allConveniosLegado as $item) {
    if (!(in_array($item['cod_convenio'], $isAlreadyInNewDBConvenios))) {
        try {
            $stmt = $connMedical->prepare("INSERT INTO convenios (id, nome) VALUES (?, ?)");
            $stmt->bind_param("is", $item['cod_convenio'], $item['convenio']);
            $stmt->execute();
            if ($stmt->error) {
                throw new \Exception("Erro de inserção");
            }
        } catch (\Exception $e) {
            $connMedical->rollback();
        }
        $isAlreadyInNewDBConvenios[] = $item['cod_convenio'];
    }
}
//convenios adicionados;

//procedimentos adicionados;
$allProcedimentosLegado = $connTemp->query("SELECT procedimento FROM temp_agendamentos");
$allProcedimentos = $connMedical->query("SELECT nome FROM procedimentos");
$allProcedimentosLegado = transformInArray($allProcedimentosLegado);
$allProcedimentos = transformInArray($allProcedimentos);

$isAlreadyInNewDBProcedimentos = array_reduce($allProcedimentos, function ($reducer, $item) {
    $reducer[] = $item["nome"];
    return $reducer;
});

foreach ($allProcedimentosLegado as $item) {
    if (!(in_array(trim($item['procedimento']), $isAlreadyInNewDBProcedimentos))) {
        try {
            $stmt = $connMedical->prepare("INSERT INTO procedimentos (nome) VALUES (?)");
            $stmt->bind_param("s", $item['procedimento']);
            $stmt->execute();
            if ($stmt->error) {
                throw new \Exception("Erro de inserção");
            }
        } catch (\Exception $e) {
            $connMedical->rollback();
        }
        $isAlreadyInNewDBProcedimentos[] = $item['procedimento'];
    }
}
//procedimentos adicionados;

//adicionando profissionais
$allProfissionaisLegado = $connTemp->query("SELECT cod_medico, medico FROM temp_agendamentos");
$allProfissionais = $connMedical->query("SELECT nome FROM profissionais");
$allProfissionaisLegado = transformInArray($allProfissionaisLegado);
$allProfissionais = transformInArray($allProfissionais);

$isAlreadyInNewDBProfissinais = array_reduce($allProfissionais, function ($reducer, $item) {
    $reducer[] = $item["nome"];
    return $reducer;
});

foreach ($allProfissionaisLegado as $item) {
    if (!(in_array($item['medico'], $isAlreadyInNewDBProfissinais))) {
        try {
            $stmt = $connMedical->prepare("INSERT INTO profissionais (id, nome) VALUES (?, ?)");
            $stmt->bind_param("is", $item['cod_medico'], $item['medico']);
            $stmt->execute();
            if ($stmt->error) {
                throw new \Exception("Erro de inserção");
            }
        } catch (\Exception $e) {
            $connMedical->rollback();
        }
        $isAlreadyInNewDBProfissinais[] = $item['medico'];
    }
}
//profissionais adicionados

//adicionando pacientes
//$allPacientesLegado = $connTemp->query("SELECT cod_paciente, nome_paciente, sexo_pac, nasc_paciente, cpf_paciente, rg_paciente, id_conv FROM temp_pacientes");
//$allPacientesLegado = transformInArray($allPacientesLegado);
//var_dump($allPacientesLegado[0]);
//
//array_walk($allPacientesLegado, function (&$item) {
//    switch ($item['sexo_pac']) {
//        case "M":
//            $item['sexo_pac'] = "Masculino";
//            break;
//        case "F":
//            $item['sexo_pac'] = "Feminino";
//            break;
//    }
//    $item['nasc_paciente'] = implode('-', array_reverse(explode('/', $item['nasc_paciente'])));
//    $item['rg_paciente'] = str_replace(".", "", $item['rg_paciente']);
//});
//
//foreach ($allPacientesLegado as $item) {
//    try {
//        $stmt = $connMedical->prepare("INSERT INTO pacientes (nome, sexo, nascimento, cpf, rg, id_convenio, cod_referencia) VALUES (?, ?, ?, ?, ?,?, ?)");
//        $stmt->bind_param("sssssii", $item['nome_paciente'], $item['sexo_pac'], $item['nasc_paciente'], $item['cpf_paciente'], $item['rg_paciente'], $item['id_conv'], $item['cod_paciente']);
//        $stmt->execute();
//        if ($stmt->error) {
//            throw new \Exception("Erro de inserção");
//        }
//    } catch (\Exception $e) {
//        $connMedical->rollback();
//    }
//}
//pacientes adicionados

//adicionando agendamentos

//$allAgendamentosLegado = $connTemp->query("SELECT  cod_paciente, cod_medico, dia, hora_inicio, hora_fim, cod_convenio, descricao, procedimento FROM temp_agendamentos");
//$allAgendamentosLegado = transformInArray($allAgendamentosLegado);
//$procedimentos = $connMedical->query("SELECT id, nome FROM procedimentos");
//$procedimentos = transformInArray($procedimentos);
//
//array_walk($allAgendamentosLegado, function (&$item) use ($procedimentos) {
//    $item['dia'] = implode('-', array_reverse(explode('/', $item['dia'])));
//    $item['hora_fim'] = $item['hora_fim'] . ".000";
//    $item['hora_inicio'] = $item['hora_inicio'] . ".000";
//    foreach ($procedimentos as $procedimento) {
//        if (trim($item['procedimento']) === trim($procedimento['nome'])) {
//            $item['procedimento'] = $procedimento['id'];
//            break;
//        }
//    }
//});
//
//foreach ($allAgendamentosLegado as $item) {
//    $codPaciente = $item['cod_paciente'];
//    $dh_inicio = $item['dia'] . " " . $item['hora_inicio'];
//    $dh_fim = $item['dia'] . " " . $item['hora_fim'];
//    $pacienteId = transformInArray($connMedical->query("SELECT id FROM  pacientes WHERE cod_referencia = $codPaciente"));
//    try {
//        $stmt = $connMedical->prepare("INSERT INTO agendamentos (id_paciente, id_profissional, dh_inicio, dh_fim, id_convenio, id_procedimento, observacoes) VALUES (?, ?, ?, ?, ?,?, ?)");
//        $stmt->bind_param("iissiis", $pacienteId[0]['id'], $item['cod_medico'], $dh_inicio, $dh_fim, $item['cod_convenio'], $item['procedimento'], $item['descricao']);
//        $stmt->execute();
//        if ($stmt->error) {
//            throw new \Exception("Erro de inserção");
//        }
//    } catch (\Exception $e) {
//        $connMedical->rollback();
//    }
//}

//agendamentos adicionados
$connMedical->close();
$connTemp->close();

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";

