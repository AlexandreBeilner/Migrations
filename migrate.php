<?php
/*
  Descrição do Desafio:
    Você precisa realizar uma migração dos dados fictícios que estão na pasta <dados_sistema_legado> para a base da clínica fictícia MedicalChallenge.
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
//
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
//
//$allAgendamentos = $connTemp->query("SELECT * FROM temp_agendamentos");
//$agendamentos = [];
//if ($allAgendamentos->num_rows > 0) {
//    while ($row = $allAgendamentos->fetch_assoc()) {
//        $agendamentos[] = $row;
//    }
//} else {
//    die("Ocorreu algum erro na busca de dados em 0temp.temp_agendamentos");
//}
//$allPacientes = $connTemp->query("SELECT * FROM temp_pacientes");
//$pacientes = [];
//if ($allPacientes->num_rows > 0) {
//    while ($row = $allPacientes->fetch_assoc()) {
//        $pacientes[] = $row;
//    }
//} else {
//    die("Ocorreu algum erro na busca de dados em 0temp.temp_pacientes");
//}
//
//var_dump($pacientes[0]);
//var_dump($agendamentos[0]);

//convenios adicionados;
$allConveniosLegado = $connTemp->query("SELECT cod_convenio, convenio FROM temp_agendamentos");
$allConvenios = $connMedical->query("SELECT id FROM convenios");
$allConveniosLegado = transformInArray($allConveniosLegado);
$allConvenios = transformInArray($allConvenios);

$isAlreadyInNewDBConvenios = array_reduce($allConvenios, function ($reducer, $item){
    $reducer[] = $item["id"];
    return $reducer;
});

foreach ($allConveniosLegado as $item){
    if(!(in_array($item['cod_convenio'], $isAlreadyInNewDBConvenios))){
        try {
            $stmt = $connMedical->prepare("INSERT INTO convenios (id, nome) VALUES (?, ?)");
            $stmt->bind_param("is",$item['cod_convenio'], $item['convenio']);
            $stmt->execute();
            if($stmt->error){
                throw new \Exception("Erro de inserção");
            }
        }catch (\Exception $e){
            $connMedical->rollback();
        }
        $isAlreadyInNewDBConvenios[] = $item['cod_convenio'];
    }
}
//convenios adicionados;

////procedimentos adicionados;
//$allProcedimentosLegado = $connTemp->query("SELECT procedimento FROM temp_agendamentos");
//$allProcedimentos = $connMedical->query("SELECT nome FROM procedimentos");
//$allProcedimentosLegado = transformInArray($allProcedimentosLegado);
//$allProcedimentos = transformInArray($allProcedimentos);
//
//$isAlreadyInNewDBProcedimentos = array_reduce($allProcedimentos, function ($reducer, $item){
//    $reducer[] = $item["nome"];
//    return $reducer;
//});
//
//foreach ($allProcedimentosLegado as $item){
//    if(!(in_array($item['procedimento'], $isAlreadyInNewDBProcedimentos))){
//        try {
//            $stmt = $connMedical->prepare("INSERT INTO procedimentos (nome) VALUES (?)");
//            $stmt->bind_param("s",$item['procedimento']);
//            $stmt->execute();
//            if($stmt->error){
//                throw new \Exception("Erro de inserção");
//            }
//        }catch (\Exception $e){
//            $connMedical->rollback();
//        }
//        $isAlreadyInNewDBProcedimentos[] = $item['procedimento'];
//    }
//}
//procedimentos adicionados;
//$allProfissionaisLegado = $connTemp->query("SELECT medico FROM temp_agendamentos");
//$allProfissionais = $connMedical->query("SELECT nome FROM profissionais");
//$allProfissionaisLegado = transformInArray($allProfissionaisLegado);
//$allProfissionais = transformInArray($allProfissionais);
//
//$isAlreadyInNewDBProfissinais = array_reduce($allProfissionais, function ($reducer, $item){
//    $reducer[] = $item["nome"];
//    return $reducer;
//});
//
//foreach ($allProfissionaisLegado as $item){
//    if(!(in_array($item['medico'], $isAlreadyInNewDBProfissinais))){
//        try {
//            $stmt = $connMedical->prepare("INSERT INTO profissionais (nome) VALUES (?)");
//            $stmt->bind_param("s",$item['medico']);
//            $stmt->execute();
//            if($stmt->error){
//                throw new \Exception("Erro de inserção");
//            }
//        }catch (\Exception $e){
//            $connMedical->rollback();
//        }
//        $isAlreadyInNewDBProfissinais[] = $item['medico'];
//    }
//}


$connMedical->close();
$connTemp->close();

// Informações de Fim da Migração:
echo "Fim da Migração: " . dateNow() . ".\n";

