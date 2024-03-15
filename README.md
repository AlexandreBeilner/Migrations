## Descrição geral

Para realizar a migração eu primeiro busquei entender qual seria a relação entre as tabelas para assim fazer a inserção em ordem no banco de dados. Percebi que a tabela Convênios, Procedimentos e Profissionais não dependiam de nenhuma outra, então fiz a inserção delas primeiro, após isso fiz a migração para a tabela de pacientes que tinha relação de uma informação da tabela de convênios, e por último a tabela de agendamentos que tinha relação com todas as outras.


## Detalhes da Implementação
O script estabelece conexões com os bancos de dados MedicalChallenge e 0temp, preparando-se para o processo de importação dos dados do sistema legado.
A próxima fase envolve a leitura dos arquivos CSV do sistema legado e a importação desses dados para o banco de dados temporário 0temp, onde serão manipulados e preparados para a migração final.
Durante a migração dos dados para o banco de dados MedicalChallenge, o script realiza uma verificação cuidadosa para evitar a duplicação de dados, garantindo a integridade do banco de dados final. Ele verifica se os dados já existem no MedicalChallenge antes de inseri-los, evitando assim redundâncias e mantendo a consistência dos registros.
Por fim, o script conclui a migração gerando um dump dos dados já migrados para o banco de dados MedicalChallenge, fornecendo salvamento adicional e confirmando o término bem-sucedido do processo de migração. 

## Funções e Métodos Principais usados na Migração
- mysqli_connect(): Esta função é usada para estabelecer uma conexão com um banco de dados MySQL.
- mysqli_query(): Este método é usado para executar uma consulta SQL no banco de dados.
- mysqli_prepare(): Este método é usado para preparar uma consulta SQL para execução.
- ysqli_bind_param(): Este método é usado para vincular variáveis a uma consulta SQL preparada.
- mysqli_execute(): Este método é usado para executar uma consulta SQL preparada.
- mysqli_rollback(): Este método é usado para reverter uma transação se ocorrer um erro durante a execução de uma consulta SQL.
- mysqli_close(): Este método é usado para fechar uma conexão com um banco de dados MySQL.
- implode():  Une elementos do array com uma string
- explode(): Divide uma string transformando-a em um array
- fetch_assoc(): Transforma uma linha de uma busca em um array associativo
- array_walk():Aplica uma função fornecida pelo usuário a cada membro de um array
