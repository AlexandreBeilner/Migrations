CREATE TABLE IF NOT EXISTS `0temp`.`temp_agendamentos` (
    cod_agendamento INT PRIMARY KEY,
    descricao VARCHAR(255),
    dia VARCHAR(255),
    hora_inicio VARCHAR(255),
    hora_fim VARCHAR(255),
    cod_paciente INT,
    paciente VARCHAR(255),
    cod_medico INT,
    medico VARCHAR(255),
    cod_convenio INT,
    convenio VARCHAR(255),
    procedimento VARCHAR(255)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `0temp`.`temp_pacientes` (
    cod_paciente INT PRIMARY KEY,
    nome_paciente VARCHAR(255),
    nasc_paciente VARCHAR(255),
    pai_paciente VARCHAR(255),
    mae_paciente VARCHAR(255),
    cpf_paciente VARCHAR(255),
    rg_paciente VARCHAR(255),
    sexo_pac CHAR(1),
    id_conv INT,
    convenio VARCHAR(255),
    obs_clinicas TEXT
) ENGINE = InnoDB;
