<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): never {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function environment(): array {
    $path = __DIR__ . '/.env';
    if (!is_readable($path)) {
        throw new RuntimeException('Arquivo .env não encontrado. Copie .env.example e informe as credenciais do MySQL.');
    }
    $values = parse_ini_file($path, false, INI_SCANNER_RAW);
    if ($values === false) throw new RuntimeException('Não foi possível ler o arquivo .env.');
    return $values;
}

try {
    $env = environment();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['MYSQL_HOST'] ?? 'localhost', $env['MYSQL_PORT'] ?? '3306', $env['MYSQL_DATABASE'] ?? 'crud_de_cruds');
    $db = new PDO($dsn, $env['MYSQL_USER'] ?? '', $env['MYSQL_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    if (!str_ends_with($path, '/cruds')) respond(404, ['error' => 'Rota não encontrada.']);

    if ($method === 'GET') {
        $statement = $db->query('SELECT c.id, c.nome_do_crud AS name, c.orientacao_colunas AS orientation, COUNT(DISTINCT cc.id_coluna) AS columns, COUNT(DISTINCT r.id) AS records FROM cruds c LEFT JOIN cruds_colunas cc ON cc.id_crud = c.id LEFT JOIN registros_do_crud r ON r.id_crud = c.id GROUP BY c.id, c.nome_do_crud, c.orientacao_colunas ORDER BY c.id DESC');
        respond(200, ['cruds' => $statement->fetchAll(PDO::FETCH_ASSOC)]);
    }
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim((string) ($input['name'] ?? ''));
        $orientation = $input['orientation'] ?? null;
        if ($name === '' || mb_strlen($name) > 150 || !in_array($orientation, [0, 1], true)) respond(422, ['error' => 'Nome ou orientação inválidos.']);
        $statement = $db->prepare('INSERT INTO cruds (nome_do_crud, orientacao_colunas) VALUES (?, ?)');
        $statement->execute([$name, $orientation]);
        respond(201, ['crud' => ['id' => (int) $db->lastInsertId(), 'name' => $name, 'orientation' => $orientation, 'columns' => 0, 'records' => 0]]);
    }
    respond(405, ['error' => 'Método não permitido.']);
} catch (Throwable $error) {
    respond(503, ['error' => 'Não foi possível conectar ao MySQL. Verifique o .env e se o schema foi importado.']);
}
