# CRUD de CRUDs

É um **crud de cruds**: uma interface para criar estruturas de cadastro, definir as suas colunas e registrar dados sem precisar criar uma tela nova para cada caso.

## Configuração

1. Copie o arquivo de exemplo: `cp .env.example .env`.
2. Preencha no arquivo `.env` as credenciais de acesso ao banco de dados MySQL.
3. Execute o script [`database/schema.sql`](database/schema.sql) no MySQL.
4. Inicie a aplicação com `php -S localhost:8000` e abra `http://localhost:8000`.

O arquivo `.env` está incluído no `.gitignore` e **não deve ser versionado**. As credenciais de acesso ao banco de dados MySQL devem ficar exclusivamente nele.

```dotenv
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DATABASE=crud_de_cruds
MYSQL_USER=seu_usuario
MYSQL_PASSWORD=sua_senha
```

### Hospedagem compartilhada / cPanel

Em hospedagens compartilhadas, o nome do banco e do usuário frequentemente recebem o prefixo da conta. Por exemplo, se o painel mostrar `cliente_crud` e `cliente_app`, esses são os valores que devem ser usados em `MYSQL_DATABASE` e `MYSQL_USER` — e não apenas `crud` e `app`. Associe o usuário ao banco no painel e conceda todos os privilégios necessários.

Deixe o `.env` no mesmo diretório de `api.php` (por exemplo, `public_html/crud/.env`). Use **uma variável por linha**, sem espaços ao redor do `=`, e mantenha valores com espaços ou caracteres especiais entre aspas:

```dotenv
MYSQL_HOST=localhost
MYSQL_PORT=3306
MYSQL_DATABASE=cliente_crud
MYSQL_USER=cliente_app
MYSQL_PASSWORD="senha com # ou espaço"
```

Depois de atualizar o arquivo, abra `api.php/health` no navegador. A rota agora informa a causa segura da falha, como credenciais recusadas, banco inexistente, host inacessível ou configuração ausente. Ela não retorna a senha.

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

Abra a aplicação pelo servidor PHP. A interface usa Tailwind CSS em modo escuro e consulta o MySQL de verdade: antes de carregar a lista ela verifica a conexão em uma rota própria, sem cache. O status de conexão, os contadores e a lista vêm do banco. Um novo CRUD só é exibido depois de ser inserido na tabela `cruds`; quando o banco estiver indisponível, a interface informa isso e não exibe dados de demonstração.

Cada card de CRUD oferece dois acessos: **Abrir registros**, para adicionar, editar e remover registros, e **Colunas**, para administrar a estrutura de colunas separadamente. Colunas de seleção (tipo `2`) têm um acesso **Opções** próprio: nele é possível adicionar, editar e remover as opções, sem incluí-las no formulário da estrutura. Ao editar um registro, deixar um campo em branco remove o valor correspondente do banco de dados.

### Tabela de registros

A tela de registros utiliza toda a largura disponível da área principal, sem o limite de largura aplicado às demais telas. Quando a tabela for mais larga que a área visível, uma barra de rolagem horizontal permanece fixa na parte inferior da janela do usuário. Essa barra é sincronizada com a rolagem da tabela, portanto pode ser usada a qualquer momento, sem que seja necessário descer até a última linha dos registros.

Se o servidor ou MySQL não responder em até 10 segundos, a interface troca o estado de **Verificando MySQL…** por uma mensagem de indisponibilidade e desabilita a criação de CRUDs. A mensagem agora identifica falhas comuns de configuração e conexão; confira se o `.env` está no mesmo diretório de `api.php`, se o usuário tem acesso ao banco definido em `MYSQL_DATABASE` e se o schema foi importado.

## API de opções de seleção

As opções das colunas tipo `2` são gerenciadas pelas rotas abaixo, sempre vinculadas ao ID da coluna:

- `GET /api.php/columns/{columnId}/options`
- `POST /api.php/columns/{columnId}/options`
- `PATCH /api.php/columns/{columnId}/options/{optionId}`
- `DELETE /api.php/columns/{columnId}/options/{optionId}`

Uma opção que já esteja sendo usada por um registro não pode ser removida.
