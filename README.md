# CRUD de CRUDs

É um **crud de cruds**: uma interface para criar estruturas de cadastro, definir as suas colunas e registrar dados sem precisar criar uma tela nova para cada caso.

## Configuração

1. Copie o arquivo de exemplo: `cp .env.example .env`.
2. Preencha no arquivo `.env` as credenciais de acesso ao banco de dados MySQL.
3. Execute o script [`database/schema.sql`](database/schema.sql) no MySQL.

O arquivo `.env` está incluído no `.gitignore` e **não deve ser versionado**. As credenciais de acesso ao banco de dados MySQL devem ficar exclusivamente nele.

```dotenv
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DATABASE=crud_de_cruds
MYSQL_USER=seu_usuario
MYSQL_PASSWORD=sua_senha
```

## Modelo de dados

- `cruds`: `id`, `nome_do_crud` e `orientacao_colunas` (`INT`, padrão `0`). A orientação `0` mantém as colunas na horizontal e os registros na vertical; a orientação `1` deixa as colunas na vertical e os registros na horizontal.
- `registros_do_crud`: `id` e `id_crud`.
- `colunas`: `id`, `nome_da_coluna`, `tipo` (`INT`, padrão `0`) e `ordem` (`INT`).
- `cruds_colunas`: `id`, `id_crud` e `id_coluna`; permite que uma mesma coluna pertença a mais de um CRUD.
- `c_zero_valores`: `id`, `id_registro` e `valor_da_coluna` (`TEXT`).
- `c_um_valores`: `id`, `id_registro` e `valor_da_coluna` (`INT`).
- `c_dois_valores`: `id`, `id_registro` e `valor_da_coluna` (`INT`), com chave estrangeira para `opcoes_colunas`.
- `opcoes_colunas`: `id`, `id_coluna`, `valor_da_opcao`, `tipo` (`INT`, padrão `0`) e `ordem` (`INT`).

Os valores das colunas do tipo `0` são texto, das colunas do tipo `1` são numéricos e das colunas do tipo `2` são apresentados em uma caixa *select* para que o usuário escolha uma opção.

> O schema também inclui `id_coluna` nas tabelas de valores. Isso identifica de forma inequívoca a coluna a que cada valor pertence — essencial quando um registro possui mais de uma coluna — sem alterar os campos solicitados.

## Interface

Abra `index.html` em um navegador. A interface demonstrativa usa Tailwind CSS em modo escuro e inclui busca, filtros, visualização de orientação, criação de CRUD e definição de colunas.
