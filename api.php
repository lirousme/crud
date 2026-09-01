<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function respond(int $status, array $payload): never { http_response_code($status); echo json_encode($payload, JSON_UNESCAPED_UNICODE); exit; }
function input(): array { $data = json_decode(file_get_contents('php://input'), true); return is_array($data) ? $data : []; }
function environment(): array {
    $path = __DIR__ . '/.env';
    if (!is_readable($path)) throw new RuntimeException('Arquivo .env não encontrado.');
    $values = parse_ini_file($path, false, INI_SCANNER_RAW);
    if ($values === false) throw new RuntimeException('Não foi possível ler o arquivo .env.');
    return $values;
}
function crud(PDO $db, int $id): array {
    $query = $db->prepare('SELECT id, nome_do_crud AS name, orientacao_colunas AS orientation FROM cruds WHERE id = ?');
    $query->execute([$id]); $crud = $query->fetch(PDO::FETCH_ASSOC);
    if (!$crud) respond(404, ['error' => 'CRUD não encontrado.']);
    return $crud;
}
function columns(PDO $db, int $crudId): array {
    $query = $db->prepare('SELECT c.id, c.nome_da_coluna AS name, c.tipo AS type, c.ordem AS position FROM colunas c JOIN cruds_colunas cc ON cc.id_coluna = c.id WHERE cc.id_crud = ? ORDER BY c.ordem, c.id');
    $query->execute([$crudId]); $columns = $query->fetchAll(PDO::FETCH_ASSOC);
    $options = $db->prepare('SELECT id, valor_da_opcao AS value, tipo, ordem AS position FROM opcoes_colunas WHERE id_coluna = ? ORDER BY ordem, id');
    foreach ($columns as &$column) { $column['type'] = (int) $column['type']; $column['position'] = (int) $column['position']; $options->execute([$column['id']]); $column['options'] = $options->fetchAll(PDO::FETCH_ASSOC); }
    return $columns;
}
function selectionColumn(PDO $db, int $columnId): array {
    $query = $db->prepare('SELECT id, nome_da_coluna AS name, tipo AS type, ordem AS position FROM colunas WHERE id = ?');
    $query->execute([$columnId]); $column = $query->fetch(PDO::FETCH_ASSOC);
    if (!$column) respond(404, ['error' => 'Coluna não encontrada.']);
    if ((int) $column['type'] !== 2) respond(422, ['error' => 'Apenas colunas de seleção possuem opções.']);
    $column['type'] = (int) $column['type']; $column['position'] = (int) $column['position'];
    $options = $db->prepare('SELECT id, valor_da_opcao AS value, ordem AS position FROM opcoes_colunas WHERE id_coluna = ? ORDER BY ordem, id');
    $options->execute([$columnId]); $column['options'] = $options->fetchAll(PDO::FETCH_ASSOC);
    foreach ($column['options'] as &$option) $option['position'] = (int) $option['position'];
    return $column;
}
function records(PDO $db, int $crudId): array {
    $query = $db->prepare('SELECT r.id, c.id AS column_id, c.tipo, COALESCE(z.valor_da_coluna, u.valor_da_coluna, d.valor_da_coluna) AS value FROM registros_do_crud r JOIN cruds_colunas cc ON cc.id_crud = r.id_crud JOIN colunas c ON c.id = cc.id_coluna LEFT JOIN c_zero_valores z ON z.id_registro = r.id AND z.id_coluna = c.id LEFT JOIN c_um_valores u ON u.id_registro = r.id AND u.id_coluna = c.id LEFT JOIN c_dois_valores d ON d.id_registro = r.id AND d.id_coluna = c.id WHERE r.id_crud = ? ORDER BY r.id DESC, c.ordem');
    $query->execute([$crudId]); $output = [];
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) { $id = (int) $row['id']; if (!isset($output[$id])) $output[$id] = ['id' => $id, 'values' => []]; $output[$id]['values'][(string) $row['column_id']] = $row['value']; }
    return array_values($output);
}
function saveValues(PDO $db, int $recordId, array $columns, array $values): void {
    foreach ($columns as $column) {
        $key = (string) $column['id']; $value = $values[$key] ?? $values[(int) $column['id']] ?? null;
        $type = (int) $column['type'];
        if ($type === 0) { $table = 'c_zero_valores'; }
        elseif ($type === 1) { $table = 'c_um_valores'; }
        else { $table = 'c_dois_valores'; }
        if ($value === null || $value === '') {
            $db->prepare("DELETE FROM $table WHERE id_registro = ? AND id_coluna = ?")->execute([$recordId, $column['id']]);
            continue;
        }
        if ($type === 0) { $value = (string) $value; }
        elseif ($type === 1) { if (filter_var($value, FILTER_VALIDATE_INT) === false) respond(422, ['error' => "{$column['name']} deve ser um número inteiro."]); $table = 'c_um_valores'; $value = (int) $value; }
        else { $valid = array_column($column['options'], 'id'); if (!in_array((int) $value, array_map('intval', $valid), true)) respond(422, ['error' => "Selecione uma opção válida para {$column['name']}."]); $table = 'c_dois_valores'; $value = (int) $value; }
        $statement = $db->prepare("INSERT INTO $table (id_registro, id_coluna, valor_da_coluna) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor_da_coluna = VALUES(valor_da_coluna)");
        $statement->execute([$recordId, $column['id'], $value]);
    }
}

try {
    $env = environment();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $env['MYSQL_HOST'] ?? 'localhost', $env['MYSQL_PORT'] ?? '3306', $env['MYSQL_DATABASE'] ?? 'crud_de_cruds');
    $db = new PDO($dsn, $env['MYSQL_USER'] ?? '', $env['MYSQL_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $method = $_SERVER['REQUEST_METHOD']; $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    $route = trim(preg_replace('#^.*api\.php/?#', '', $path), '/'); $parts = $route === '' ? [] : explode('/', $route);
    if (($parts[0] ?? '') === 'health' && $method === 'GET') respond(200, ['mysql' => 'connected']);
    if (($parts[0] ?? '') === 'columns') {
        $columnId = filter_var($parts[1] ?? null, FILTER_VALIDATE_INT); if (!$columnId || ($parts[2] ?? '') !== 'options') respond(404, ['error' => 'Rota não encontrada.']);
        if (count($parts) === 3 && $method === 'GET') respond(200, ['column' => selectionColumn($db, $columnId)]);
        if (count($parts) === 3 && $method === 'POST') {
            selectionColumn($db, $columnId); $data = input(); $value = trim((string) ($data['value'] ?? '')); $position = $data['position'] ?? null;
            if ($value === '' || mb_strlen($value) > 255 || filter_var($position, FILTER_VALIDATE_INT) === false) respond(422, ['error' => 'Dados da opção inválidos.']);
            $statement = $db->prepare('INSERT INTO opcoes_colunas (id_coluna, valor_da_opcao, ordem) VALUES (?, ?, ?)'); $statement->execute([$columnId, $value, $position]); respond(201, ['option' => ['id' => (int) $db->lastInsertId(), 'value' => $value, 'position' => (int) $position]]);
        }
        $optionId = filter_var($parts[3] ?? null, FILTER_VALIDATE_INT);
        if (!$optionId) respond(404, ['error' => 'Opção não encontrada.']);
        selectionColumn($db, $columnId);
        $option = $db->prepare('SELECT id FROM opcoes_colunas WHERE id = ? AND id_coluna = ?'); $option->execute([$optionId, $columnId]); if (!$option->fetch()) respond(404, ['error' => 'Opção não encontrada nesta coluna.']);
        if ($method === 'PATCH') { $data = input(); $value = trim((string) ($data['value'] ?? '')); $position = $data['position'] ?? null; if ($value === '' || mb_strlen($value) > 255 || filter_var($position, FILTER_VALIDATE_INT) === false) respond(422, ['error' => 'Dados da opção inválidos.']); $db->prepare('UPDATE opcoes_colunas SET valor_da_opcao = ?, ordem = ? WHERE id = ?')->execute([$value, $position, $optionId]); respond(200, ['option' => ['id' => $optionId, 'value' => $value, 'position' => (int) $position]]); }
        if ($method === 'DELETE') { $used = $db->prepare('SELECT EXISTS(SELECT 1 FROM c_dois_valores WHERE valor_da_coluna = ?)'); $used->execute([$optionId]); if ((int) $used->fetchColumn()) respond(409, ['error' => 'Não é possível remover uma opção que já possui valores.']); $db->prepare('DELETE FROM opcoes_colunas WHERE id = ?')->execute([$optionId]); respond(204, []); }
        respond(405, ['error' => 'Método não permitido.']);
    }
    if (($parts[0] ?? '') !== 'cruds') respond(404, ['error' => 'Rota não encontrada.']);
    if (count($parts) === 1 && $method === 'GET') { $statement = $db->query('SELECT c.id, c.nome_do_crud AS name, c.orientacao_colunas AS orientation, COUNT(DISTINCT cc.id_coluna) AS columns, COUNT(DISTINCT r.id) AS records FROM cruds c LEFT JOIN cruds_colunas cc ON cc.id_crud = c.id LEFT JOIN registros_do_crud r ON r.id_crud = c.id GROUP BY c.id ORDER BY c.id DESC'); respond(200, ['cruds' => $statement->fetchAll(PDO::FETCH_ASSOC)]); }
    if (count($parts) === 1 && $method === 'POST') { $data = input(); $name = trim((string) ($data['name'] ?? '')); $orientation = $data['orientation'] ?? null; if ($name === '' || mb_strlen($name) > 150 || !in_array($orientation, [0, 1], true)) respond(422, ['error' => 'Nome ou orientação inválidos.']); $statement = $db->prepare('INSERT INTO cruds (nome_do_crud, orientacao_colunas) VALUES (?, ?)'); $statement->execute([$name, $orientation]); $created = crud($db, (int) $db->lastInsertId()); $created['orientation'] = (int) $created['orientation']; $created['columns'] = 0; $created['records'] = 0; respond(201, ['crud' => $created]); }
    $crudId = filter_var($parts[1] ?? null, FILTER_VALIDATE_INT); if (!$crudId) respond(404, ['error' => 'CRUD não encontrado.']); $entity = $parts[2] ?? '';
    if ($entity === '' && $method === 'GET') { $item = crud($db, $crudId); $item['orientation'] = (int) $item['orientation']; $item['columns'] = columns($db, $crudId); $item['records'] = records($db, $crudId); respond(200, ['crud' => $item]); }
    if ($entity === 'columns' && $method === 'GET') { $item = crud($db, $crudId); $item['orientation'] = (int) $item['orientation']; $item['columns'] = columns($db, $crudId); respond(200, ['crud' => $item]); }
    if ($entity === 'records' && $method === 'GET') { $item = crud($db, $crudId); $item['orientation'] = (int) $item['orientation']; $item['columns'] = columns($db, $crudId); $item['records'] = records($db, $crudId); respond(200, ['crud' => $item]); }
    if ($entity === 'columns' && $method === 'POST') { crud($db, $crudId); $data = input(); $name = trim((string) ($data['name'] ?? '')); $type = $data['type'] ?? null; $position = $data['position'] ?? 0; if ($name === '' || !in_array($type, [0, 1, 2], true) || filter_var($position, FILTER_VALIDATE_INT) === false) respond(422, ['error' => 'Dados da coluna inválidos.']); $db->beginTransaction(); $db->prepare('INSERT INTO colunas (nome_da_coluna, tipo, ordem) VALUES (?, ?, ?)')->execute([$name, $type, $position]); $columnId = (int) $db->lastInsertId(); $db->prepare('INSERT INTO cruds_colunas (id_crud, id_coluna) VALUES (?, ?)')->execute([$crudId, $columnId]); $db->commit(); respond(201, ['column' => ['id' => $columnId, 'name' => $name, 'type' => $type, 'position' => (int) $position, 'options' => []]]); }
    if ($entity === 'columns' && isset($parts[3]) && $method === 'PATCH') {
        $columnId = filter_var($parts[3], FILTER_VALIDATE_INT); crud($db, $crudId);
        $belongs = $db->prepare('SELECT c.id, c.tipo FROM colunas c JOIN cruds_colunas cc ON cc.id_coluna = c.id WHERE cc.id_crud = ? AND c.id = ?');
        $belongs->execute([$crudId, $columnId]); $current = $belongs->fetch(PDO::FETCH_ASSOC);
        if (!$current) respond(404, ['error' => 'Coluna não encontrada neste CRUD.']);
        $data = input(); $name = trim((string) ($data['name'] ?? '')); $type = $data['type'] ?? null; $position = $data['position'] ?? null;
        if ($name === '' || !in_array($type, [0, 1, 2], true) || filter_var($position, FILTER_VALIDATE_INT) === false) respond(422, ['error' => 'Dados da coluna inválidos.']);
        $used = $db->prepare('SELECT EXISTS(SELECT 1 FROM c_zero_valores WHERE id_coluna = ?) OR EXISTS(SELECT 1 FROM c_um_valores WHERE id_coluna = ?) OR EXISTS(SELECT 1 FROM c_dois_valores WHERE id_coluna = ?)');
        $used->execute([$columnId, $columnId, $columnId]);
        if ((int) $used->fetchColumn() && (int) $current['tipo'] !== (int) $type) respond(409, ['error' => 'Não é possível alterar o tipo de uma coluna que já possui valores.']);
        $db->beginTransaction();
        $db->prepare('UPDATE colunas SET nome_da_coluna = ?, tipo = ?, ordem = ? WHERE id = ?')->execute([$name, $type, $position, $columnId]);
        $db->commit(); respond(200, ['column' => columns($db, $crudId)]);
    }
    if ($entity === 'columns' && isset($parts[3]) && $method === 'DELETE') { $columnId = (int) $parts[3]; crud($db, $crudId); $db->prepare('DELETE FROM cruds_colunas WHERE id_crud = ? AND id_coluna = ?')->execute([$crudId, $columnId]); respond(204, []); }
    if ($entity === 'records' && $method === 'POST') { crud($db, $crudId); $db->beginTransaction(); $db->prepare('INSERT INTO registros_do_crud (id_crud) VALUES (?)')->execute([$crudId]); $recordId = (int) $db->lastInsertId(); saveValues($db, $recordId, columns($db, $crudId), input()['values'] ?? []); $db->commit(); respond(201, ['id' => $recordId]); }
    if ($entity === 'records' && isset($parts[3]) && $method === 'PATCH') { $recordId = (int) $parts[3]; $check = $db->prepare('SELECT id FROM registros_do_crud WHERE id = ? AND id_crud = ?'); $check->execute([$recordId, $crudId]); if (!$check->fetch()) respond(404, ['error' => 'Registro não encontrado.']); $db->beginTransaction(); saveValues($db, $recordId, columns($db, $crudId), input()['values'] ?? []); $db->commit(); respond(200, ['id' => $recordId]); }
    if ($entity === 'records' && isset($parts[3]) && $method === 'DELETE') { $db->prepare('DELETE FROM registros_do_crud WHERE id = ? AND id_crud = ?')->execute([(int) $parts[3], $crudId]); respond(204, []); }
    respond(405, ['error' => 'Método não permitido.']);
} catch (Throwable $error) { if (isset($db) && $db->inTransaction()) $db->rollBack(); respond(503, ['error' => 'Não foi possível concluir a operação no MySQL. Verifique o .env e se o schema foi importado.']); }
