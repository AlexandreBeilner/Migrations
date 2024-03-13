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

//Criação dos bancos de dados
$conn = new mysqli("localhost", "root", "root");
$conn->query("CREATE DATABASE MedicalChallenge");
$conn->query("CREATE DATABASE 0temp");

//Verefica o sistema operacional e executa os camandos
$os = strtolower(php_uname('s'));
if (str_contains($os, "windows")) {
    exec("mysql --user=root --password=root MedicalChallenge < .\medical_challenge_schema.sql");
    exec("mysql --user=root --password=root 0temp < .\\0temp_schema.sql");
} else {
    exec("mysql --user=root --password=root MedicalChallenge < ./medical_challenge_schema.sql");
    exec("mysql --user=root --password=root 0temp < ./0temp_schema.sql");
}


// Conexão com o banco da clínica fictícia:
$connMedical = mysqli_connect("localhost", "root", "root", "MedicalChallenge")
or die("Não foi possível conectar os servidor MySQL: MedicalChallenge\n");

// Conexão com o banco temporário:
$connTemp = mysqli_connect("localhost", "root", "root", "0temp")
or die("Não foi possível conectar os servidor MySQL: 0temp\n");

// Informações de Inicio da Migração:
echo "Início da Migração: " . dateNow() . ".\n\n";

// Define duas variaveis com o caminho até os dados CSV
$fileAgenName = "./dados_sistema_legado/20210512_agendamentos.csv";
$filePacName = "./dados_sistema_legado/20210512_pacientes.csv";

//Abre o arquivo em modo leitura
$fileAgen = fopen($fileAgenName, "r");
if (!$fileAgen) {
    die("Não foi possivel abrir o arquivo");
}

// Pega aprimeira linha do arquivo e trasforma em um array com o nome das colunas
$AgenColumns = explode(";", fgets($fileAgen));
//Prepara uma string com o nome das culunas separados por vírgula para a query
$AgenColumns = implode(", ", $AgenColumns);

// Faz a inserção no banco de dados temporario com os dados do CSV
while (($line = fgets($fileAgen)) !== false) {
    $values = explode(";", $line);
    $values = "'" . implode("','", $values) . "'";
    $sqlQuery = "INSERT INTO temp_agendamentos ($AgenColumns) VALUES ($values)";
    $connTemp->query($sqlQuery);
}
// Fecha o arquivo
fclose($fileAgen);

//Abre o arquivo em modo leitura
$filePac = fopen($filePacName, "r");
if (!$filePac) {
    die("Não foi possivel abrir o arquivo");
}

//Pega a primeira linha do arquivo e trasforma em uma string com o nome das colunas
$PacColumns = explode(";", fgets($filePac));
$PacColumns = implode(", ", $PacColumns);

// Faz a inserção no banco de dados temporario
while (($line = fgets($filePac)) !== false) {
    $values = explode(";", $line);
    $values = "'" . implode("','", $values) . "'";
    $sqlQuery = "INSERT INTO temp_pacientes ($PacColumns) VALUES ($values)";
    $connTemp->query($sqlQuery);
}
// Fecha o arquivo
fclose($filePac);

// Busca os dados necessarios para a tabela convenios nos dois bancos
$allConveniosLegado = transformInArray($connTemp->query("SELECT DISTINCT cod_convenio, convenio FROM temp_agendamentos"));
$allConvenios = transformInArray($connMedical->query("SELECT id FROM convenios"));

// Verefica quais dados ja estão presentes no novo banco e adiciona a um array
$isAlreadyInNewDBConvenios = array_reduce($allConvenios, function ($reducer, $item) {
    $reducer[] = $item["id"];
    return $reducer;
});

//Faz a inserção se necessario na nova tabela convenios
foreach ($allConveniosLegado as $item) {
    // Verefica se o dado a ser inserido já existe no novo banco
    // Caso nao exista faz a inserção
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
        // Adiciona o novo valor ao array que serve para a vereficação, assim evitando a duplicação
        $isAlreadyInNewDBConvenios[] = $item['cod_convenio'];
    }
}

//busca os dados necessarios para a tabela procedimentos nos dois bancos
$allProcedimentosLegado = transformInArray($connTemp->query("SELECT DISTINCT procedimento FROM temp_agendamentos"));
$allProcedimentos = transformInArray($connMedical->query("SELECT nome FROM procedimentos"));

// Cria um array com os dados que ja estão inseridos no novo banco de dados
$isAlreadyInNewDBProcedimentos = array_reduce($allProcedimentos, function ($reducer, $item) {
    $reducer[] = $item["nome"];
    return $reducer;
});

//Faz a inserção se necessario na nova tabela procedimentos
foreach ($allProcedimentosLegado as $item) {
    //verefica se o procedimento ja existe la, caso não exista faz a inserção
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
        // Adiciona o novo valor ao array que serve para a vereficação, assim evitando a duplicação
        $isAlreadyInNewDBProcedimentos[] = $item['procedimento'];
    }
}

//busca os dados necessarios para a tabela de profissionais em ambos os bancos
$allProfissionaisLegado = transformInArray($connTemp->query("SELECT DISTINCT cod_medico, medico FROM temp_agendamentos"));
$allProfissionais = transformInArray($connMedical->query("SELECT nome FROM profissionais"));

//Cria um array com os proficionais que ja foram adicionados no novo banco
$isAlreadyInNewDBProfissinais = array_reduce($allProfissionais, function ($reducer, $item) {
    $reducer[] = $item["nome"];
    return $reducer;
});


//Faz a inserção na nova tabela profissionais
foreach ($allProfissionaisLegado as $item) {
    //Verefica se o profissional ja está no novo banco
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
        // Adiciona o novo valor ao array que serve para a vereficação, assim evitando a duplicação
        $isAlreadyInNewDBProfissinais[] = $item['medico'];
    }
}


//Busca os dados necessarios para adicionar pacietes ao banco de dados
$allPacientesLegado = transformInArray($connTemp->query("SELECT cod_paciente, nome_paciente, sexo_pac, nasc_paciente, cpf_paciente, rg_paciente, id_conv FROM temp_pacientes"));

// Faz a formatação dos dados para ficarem no mesmo formato aos ja inseridos novo banco de dados
array_walk($allPacientesLegado, function (&$item) {
    switch ($item['sexo_pac']) {
        case "M":
            $item['sexo_pac'] = "Masculino";
            break;
        case "F":
            $item['sexo_pac'] = "Feminino";
            break;
    }
    //Muda o valor de dia/mes/ano para ano-mes-dia
    $item['nasc_paciente'] = implode('-', array_reverse(explode('/', $item['nasc_paciente'])));
    //Remove os pontos do rg
    $item['rg_paciente'] = str_replace(".", "", $item['rg_paciente']);
});


//faz a inserção na nova tabela pacientes
foreach ($allPacientesLegado as $item) {
    try {
        $stmt = $connMedical->prepare("INSERT INTO pacientes (nome, sexo, nascimento, cpf, rg, id_convenio, cod_referencia) VALUES (?, ?, ?, ?, ?,?, ?)");
        $stmt->bind_param("sssssii", $item['nome_paciente'], $item['sexo_pac'], $item['nasc_paciente'], $item['cpf_paciente'], $item['rg_paciente'], $item['id_conv'], $item['cod_paciente']);
        $stmt->execute();
        if ($stmt->error) {
            throw new \Exception("Erro de inserção");
        }
    } catch (\Exception $e) {
        $connMedical->rollback();
    }
}


//busca os dados necessarios para a tabela agendamentos
$allAgendamentosLegado = transformInArray($connTemp->query("SELECT  cod_paciente, cod_medico, dia, hora_inicio, hora_fim, cod_convenio, descricao, procedimento FROM temp_agendamentos"));
$procedimentos = transformInArray($connMedical->query("SELECT id, nome FROM procedimentos"));


//formata os dados para serem inseridos no banco
array_walk($allAgendamentosLegado, function (&$item) use ($procedimentos) {
    //format o valor de dia/mes/ano para ano-mes-dia
    $item['dia'] = implode('-', array_reverse(explode('/', $item['dia'])));
    //adiciona .000 ao fim dos horarios
    $item['hora_fim'] = $item['hora_fim'] . ".000";
    $item['hora_inicio'] = $item['hora_inicio'] . ".000";
    //altera o valor de item['procedimento'] para ao seu id, em vez do seu nome
    foreach ($procedimentos as $procedimento) {
        if (trim($item['procedimento']) === trim($procedimento['nome'])) {
            $item['procedimento'] = $procedimento['id'];
            break;
        }
    }
});

//faz a inserção na nova tabela agendamentos
foreach ($allAgendamentosLegado as $item) {
    $codPaciente = $item['cod_paciente'];
    $dh_inicio = $item['dia'] . " " . $item['hora_inicio'];
    $dh_fim = $item['dia'] . " " . $item['hora_fim'];
    $pacienteId = transformInArray($connMedical->query("SELECT id FROM  pacientes WHERE cod_referencia = $codPaciente"));
    try {
        $stmt = $connMedical->prepare("INSERT INTO agendamentos (id_paciente, id_profissional, dh_inicio, dh_fim, id_convenio, id_procedimento, observacoes) VALUES (?, ?, ?, ?, ?,?, ?)");
        $stmt->bind_param("iissiis", $pacienteId[0]['id'], $item['cod_medico'], $dh_inicio, $dh_fim, $item['cod_convenio'], $item['procedimento'], $item['descricao']);
        $stmt->execute();
        if ($stmt->error) {
            throw new \Exception("Erro de inserção");
        }
    } catch (\Exception $e) {
        $connMedical->rollback();
    }
}

//deleta o banco de dados temporario
$conn->query("DROP DATABASE 0temp");

// Fecha as conexões abertas
$conn->close();
$connMedical->close();
$connTemp->close();

//gera o dump do banco de dados
exec("mysqldump --user=root --password=root --host=localhost MedicalChallenge --result-file=dump.sql 2>&1", $output);

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";
