<?php
/**
 * ============================================
 * ARTFLOW 2.0 - SEEDS DE TESTE
 * ============================================
 * 
 * Acesse: http://localhost/artflow2/seeds.php
 * 
 * CRIA:
 * - 5 Tags (Chibi, Sketch, Full Body, YCH, PWYW)
 * - 20 Artes classificadas
 * - 10 Clientes
 * - 15 Vendas
 * - 3 Metas mensais
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Carrega .env
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($k, $v) = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
        }
    }
}

// Conexão
try {
    $pdo = new PDO(
        "mysql:host=" . ($_ENV['DB_HOST'] ?? 'localhost') . 
        ";dbname=" . ($_ENV['DB_DATABASE'] ?? 'artflow2_db') . 
        ";charset=utf8mb4",
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("<h1>Erro de conexão:</h1><p>{$e->getMessage()}</p>");
}

$acao = $_GET['acao'] ?? 'preview';
$logs = [];

// ============================================
// DADOS
// ============================================

$tags = [
    ['nome' => 'Chibi', 'cor' => '#FF6B9D'],
    ['nome' => 'Sketch', 'cor' => '#6B7280'],
    ['nome' => 'Full Body', 'cor' => '#10B981'],
    ['nome' => 'YCH', 'cor' => '#8B5CF6'],
    ['nome' => 'PWYW', 'cor' => '#F59E0B'],
];

$artes = [
    // CHIBI (4)
    ['nome' => 'Chibi Mago Arcano', 'desc' => 'Chibi de mago com cajado mágico', 'status' => 'disponivel', 'custo' => 15, 'horas' => 2, 'tag' => 'Chibi'],
    ['nome' => 'Chibi Guerreira Élfica', 'desc' => 'Chibi de elfa com armadura dourada', 'status' => 'vendida', 'custo' => 18, 'horas' => 2.5, 'tag' => 'Chibi'],
    ['nome' => 'Chibi Casal Anime', 'desc' => 'Chibi romântico de casal', 'status' => 'disponivel', 'custo' => 25, 'horas' => 3.5, 'tag' => 'Chibi'],
    ['nome' => 'Chibi Gatinho Kawaii', 'desc' => 'Chibi de gatinho antropomórfico', 'status' => 'vendida', 'custo' => 12, 'horas' => 1.5, 'tag' => 'Chibi'],
    
    // SKETCH (4)
    ['nome' => 'Sketch Retrato', 'desc' => 'Esboço detalhado de retrato', 'status' => 'disponivel', 'custo' => 20, 'horas' => 3, 'tag' => 'Sketch'],
    ['nome' => 'Sketch Pose Dinâmica', 'desc' => 'Esboço de personagem em ação', 'status' => 'vendida', 'custo' => 15, 'horas' => 2, 'tag' => 'Sketch'],
    ['nome' => 'Sketch Criatura Fantasy', 'desc' => 'Concept de criatura fantástica', 'status' => 'disponivel', 'custo' => 30, 'horas' => 4, 'tag' => 'Sketch'],
    ['nome' => 'Sketch Expressões', 'desc' => 'Sheet de expressões faciais', 'status' => 'em_producao', 'custo' => 25, 'horas' => 3.5, 'tag' => 'Sketch'],
    
    // FULL BODY (4)
    ['nome' => 'Full Body Samurai', 'desc' => 'Ilustração completa de samurai', 'status' => 'disponivel', 'custo' => 80, 'horas' => 12, 'tag' => 'Full Body'],
    ['nome' => 'Full Body Feiticeira', 'desc' => 'Feiticeira com efeitos mágicos', 'status' => 'vendida', 'custo' => 90, 'horas' => 14, 'tag' => 'Full Body'],
    ['nome' => 'Full Body Cyberpunk', 'desc' => 'Personagem futurista com implantes', 'status' => 'disponivel', 'custo' => 100, 'horas' => 16, 'tag' => 'Full Body'],
    ['nome' => 'Full Body Dragão', 'desc' => 'Personagem draconiano completo', 'status' => 'vendida', 'custo' => 120, 'horas' => 18, 'tag' => 'Full Body'],
    
    // YCH (4)
    ['nome' => 'YCH Pose Sentada', 'desc' => 'Base YCH relaxado', 'status' => 'disponivel', 'custo' => 35, 'horas' => 5, 'tag' => 'YCH'],
    ['nome' => 'YCH Casal Abraço', 'desc' => 'Base YCH para dois personagens', 'status' => 'disponivel', 'custo' => 50, 'horas' => 7, 'tag' => 'YCH'],
    ['nome' => 'YCH Halloween', 'desc' => 'Base YCH temática de Halloween', 'status' => 'vendida', 'custo' => 40, 'horas' => 6, 'tag' => 'YCH'],
    ['nome' => 'YCH Praia Verão', 'desc' => 'Base YCH de praia', 'status' => 'disponivel', 'custo' => 45, 'horas' => 6.5, 'tag' => 'YCH'],
    
    // PWYW (4)
    ['nome' => 'PWYW Icon Simples', 'desc' => 'Ícone básico PWYW', 'status' => 'vendida', 'custo' => 5, 'horas' => 0.5, 'tag' => 'PWYW'],
    ['nome' => 'PWYW Headshot', 'desc' => 'Headshot colorido PWYW', 'status' => 'vendida', 'custo' => 8, 'horas' => 1, 'tag' => 'PWYW'],
    ['nome' => 'PWYW Bust Simples', 'desc' => 'Busto simples PWYW', 'status' => 'disponivel', 'custo' => 10, 'horas' => 1.5, 'tag' => 'PWYW'],
    ['nome' => 'PWYW Chibi Mini', 'desc' => 'Mini chibi PWYW', 'status' => 'vendida', 'custo' => 6, 'horas' => 0.75, 'tag' => 'PWYW'],
];

$clientes = [
    ['nome' => 'Lucas Mendes', 'email' => 'lucas.mendes@email.com', 'telefone' => '(11) 99876-5432', 'cidade' => 'São Paulo', 'estado' => 'SP'],
    ['nome' => 'Amanda Silva', 'email' => 'amanda.silva@email.com', 'telefone' => '(21) 98765-4321', 'cidade' => 'Rio de Janeiro', 'estado' => 'RJ'],
    ['nome' => 'Rafael Costa', 'email' => 'rafa.costa@email.com', 'telefone' => '(31) 97654-3210', 'cidade' => 'Belo Horizonte', 'estado' => 'MG'],
    ['nome' => 'Juliana Oliveira', 'email' => 'ju.oliveira@email.com', 'telefone' => '(41) 96543-2109', 'cidade' => 'Curitiba', 'estado' => 'PR'],
    ['nome' => 'Pedro Henrique', 'email' => 'pedroh@email.com', 'telefone' => '(51) 95432-1098', 'cidade' => 'Porto Alegre', 'estado' => 'RS'],
    ['nome' => 'Carla Fernandes', 'email' => 'carla.f@email.com', 'telefone' => '(61) 94321-0987', 'cidade' => 'Brasília', 'estado' => 'DF'],
    ['nome' => 'Thiago Santos', 'email' => 'thiago.s@email.com', 'telefone' => '(71) 93210-9876', 'cidade' => 'Salvador', 'estado' => 'BA'],
    ['nome' => 'Marina Rodrigues', 'email' => 'marina.r@email.com', 'telefone' => '(81) 92109-8765', 'cidade' => 'Recife', 'estado' => 'PE'],
    ['nome' => 'Bruno Almeida', 'email' => 'bruno.almeida@email.com', 'telefone' => '(85) 91098-7654', 'cidade' => 'Fortaleza', 'estado' => 'CE'],
    ['nome' => 'Fernanda Lima', 'email' => 'fe.lima@email.com', 'telefone' => '(91) 90987-6543', 'cidade' => 'Belém', 'estado' => 'PA'],
];

// Vendas: [arte_index, cliente_index, valor, dias_atras]
$vendas = [
    [1, 0, 45.00, 28],   // Chibi Guerreira -> Lucas
    [3, 1, 35.00, 25],   // Chibi Gatinho -> Amanda
    [5, 2, 50.00, 22],   // Sketch Pose -> Rafael
    [9, 3, 280.00, 20],  // Full Body Feiticeira -> Juliana
    [11, 4, 380.00, 18], // Full Body Dragão -> Pedro
    [14, 5, 150.00, 15], // YCH Halloween -> Carla
    [16, 6, 20.00, 12],  // PWYW Icon -> Thiago
    [17, 7, 35.00, 10],  // PWYW Headshot -> Marina
    [19, 8, 25.00, 8],   // PWYW Chibi Mini -> Bruno
    [0, 9, 55.00, 6],    // Chibi Mago (extra) -> Fernanda
    [4, 0, 60.00, 5],    // Sketch Retrato -> Lucas
    [8, 1, 250.00, 4],   // Full Body Samurai -> Amanda
    [12, 2, 120.00, 3],  // YCH Sentada -> Rafael
    [18, 3, 40.00, 2],   // PWYW Bust -> Juliana
    [6, 4, 90.00, 1],    // Sketch Criatura -> Pedro
];

// ============================================
// EXECUÇÃO
// ============================================

if ($acao === 'executar') {
    try {
        // Desabilita verificação de FK
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        // Limpa tabelas com DELETE (mais seguro que TRUNCATE)
        $tabelasLimpar = ['vendas', 'arte_tags', 'artes', 'clientes', 'tags', 'metas'];
        foreach ($tabelasLimpar as $t) {
            $pdo->exec("DELETE FROM {$t}");
            $pdo->exec("ALTER TABLE {$t} AUTO_INCREMENT = 1");
            $logs[] = "🗑️ Tabela {$t} limpa";
        }
        
        // Reabilita verificação de FK
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Tags
        $stmt = $pdo->prepare("INSERT INTO tags (nome, cor, created_at) VALUES (?, ?, NOW())");
        foreach ($tags as $tag) {
            $stmt->execute([$tag['nome'], $tag['cor']]);
            $logs[] = "🏷️ Tag: {$tag['nome']}";
        }
        
        // Mapa de tags
        $tagMap = [];
        foreach ($pdo->query("SELECT id, nome FROM tags") as $r) {
            $tagMap[$r['nome']] = $r['id'];
        }
        
        // Clientes
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, telefone, cidade, estado, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        foreach ($clientes as $c) {
            $stmt->execute([$c['nome'], $c['email'], $c['telefone'], $c['cidade'], $c['estado']]);
            $logs[] = "👤 Cliente: {$c['nome']}";
        }
        
        // Artes
        $stmtArte = $pdo->prepare("INSERT INTO artes (nome, descricao, status, preco_custo, horas_trabalhadas, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmtTag = $pdo->prepare("INSERT INTO arte_tags (arte_id, tag_id) VALUES (?, ?)");
        
        foreach ($artes as $a) {
            $stmtArte->execute([$a['nome'], $a['desc'], $a['status'], $a['custo'], $a['horas']]);
            $arteId = $pdo->lastInsertId();
            
            if (isset($tagMap[$a['tag']])) {
                $stmtTag->execute([$arteId, $tagMap[$a['tag']]]);
            }
            $logs[] = "🎨 Arte: {$a['nome']} [{$a['tag']}]";
        }
        
        // IDs
        $artesIds = $pdo->query("SELECT id FROM artes ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $clientesIds = $pdo->query("SELECT id FROM clientes ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        
        // Vendas
        $stmtVenda = $pdo->prepare("INSERT INTO vendas (arte_id, cliente_id, valor, data_venda, lucro_calculado, rentabilidade_hora, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($vendas as $v) {
            [$arteIdx, $clienteIdx, $valor, $dias] = $v;
            
            if (!isset($artesIds[$arteIdx]) || !isset($clientesIds[$clienteIdx])) continue;
            
            $arteId = $artesIds[$arteIdx];
            $clienteId = $clientesIds[$clienteIdx];
            $dataVenda = date('Y-m-d', strtotime("-{$dias} days"));
            
            // Busca dados da arte
            $arte = $pdo->query("SELECT nome, preco_custo, horas_trabalhadas FROM artes WHERE id = {$arteId}")->fetch();
            $lucro = $valor - $arte['preco_custo'];
            $rentabilidade = $arte['horas_trabalhadas'] > 0 ? $lucro / $arte['horas_trabalhadas'] : 0;
            
            $stmtVenda->execute([$arteId, $clienteId, $valor, $dataVenda, round($lucro, 2), round($rentabilidade, 2)]);
            
            $cliente = $pdo->query("SELECT nome FROM clientes WHERE id = {$clienteId}")->fetchColumn();
            $logs[] = "💰 Venda: {$arte['nome']} → {$cliente} (R$ " . number_format($valor, 2, ',', '.') . ")";
        }
        
        // Metas - Usando INSERT ... ON DUPLICATE KEY UPDATE para evitar erro de duplicidade
        $mesesMetas = [
            [date('Y-m-01', strtotime('-2 months')), 1500],
            [date('Y-m-01', strtotime('-1 month')), 2000],
            [date('Y-m-01'), 2500],
        ];
        
        $stmtMeta = $pdo->prepare("
            INSERT INTO metas (mes_ano, valor_meta, valor_realizado, porcentagem_atingida, horas_diarias_ideal, dias_trabalho_semana, created_at) 
            VALUES (?, ?, 0, 0, 8, 5, NOW())
            ON DUPLICATE KEY UPDATE valor_meta = VALUES(valor_meta), valor_realizado = 0, porcentagem_atingida = 0
        ");
        
        foreach ($mesesMetas as [$mes, $valorMeta]) {
            $stmtMeta->execute([$mes, $valorMeta]);
            $logs[] = "🎯 Meta: " . date('m/Y', strtotime($mes)) . " → R$ " . number_format($valorMeta, 2, ',', '.');
        }
        
        // Atualiza metas com vendas
        $metas = $pdo->query("SELECT id, mes_ano, valor_meta FROM metas")->fetchAll();
        foreach ($metas as $meta) {
            $mesAno = substr($meta['mes_ano'], 0, 7);
            $total = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM vendas WHERE DATE_FORMAT(data_venda, '%Y-%m') = '{$mesAno}'")->fetchColumn();
            $pct = $meta['valor_meta'] > 0 ? ($total / $meta['valor_meta']) * 100 : 0;
            $pdo->exec("UPDATE metas SET valor_realizado = {$total}, porcentagem_atingida = " . round($pct, 2) . " WHERE id = {$meta['id']}");
        }
        $logs[] = "📊 Metas atualizadas com vendas!";
        
    } catch (PDOException $e) {
        $logs[] = "❌ ERRO: " . $e->getMessage();
    }
}

// Contagem atual
$contagem = [];
foreach (['tags', 'artes', 'clientes', 'vendas', 'metas'] as $t) {
    $contagem[$t] = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
}
$totalVendas = $pdo->query("SELECT COALESCE(SUM(valor), 0) FROM vendas")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ArtFlow 2.0 - Seeds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #1e1b4b, #312e81); min-height: 100vh; color: #e5e7eb; }
        .card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); }
        .card-header { background: rgba(255,255,255,0.05); }
        .stat { background: rgba(255,255,255,0.1); border-radius: 12px; padding: 15px; text-align: center; }
        .stat h2 { margin: 0; font-size: 2rem; }
        .log { padding: 4px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        .table { color: #e5e7eb; }
        .badge-tag { font-size: 11px; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-0">🌱 Seeds de Teste</h1>
            <small class="text-muted">Popula o banco com dados de exemplo</small>
        </div>
        <div>
            <a href="<?= $_ENV['APP_URL'] ?? '/artflow2' ?>" class="btn btn-outline-light btn-sm">🏠 Sistema</a>
            <a href="tests.php" class="btn btn-outline-info btn-sm">🧪 Testes</a>
        </div>
    </div>

    <!-- Status -->
    <div class="row g-3 mb-4">
        <div class="col"><div class="stat"><h2><?= $contagem['tags'] ?></h2><small>Tags</small></div></div>
        <div class="col"><div class="stat"><h2><?= $contagem['artes'] ?></h2><small>Artes</small></div></div>
        <div class="col"><div class="stat"><h2><?= $contagem['clientes'] ?></h2><small>Clientes</small></div></div>
        <div class="col"><div class="stat"><h2><?= $contagem['vendas'] ?></h2><small>Vendas</small></div></div>
        <div class="col"><div class="stat"><h2>R$ <?= number_format($totalVendas, 0, ',', '.') ?></h2><small>Total</small></div></div>
    </div>

    <!-- Ação -->
    <div class="card mb-4">
        <div class="card-body">
            <?php if ($acao === 'preview'): ?>
                <div class="alert alert-warning">
                    ⚠️ <strong>Atenção:</strong> Isso irá APAGAR todos os dados e substituir pelos seeds!
                </div>
                <h6>Será criado:</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li><strong>5 Tags:</strong> Chibi, Sketch, Full Body, YCH, PWYW</li>
                            <li><strong>20 Artes:</strong> 4 de cada categoria</li>
                            <li><strong>10 Clientes:</strong> Com dados completos</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li><strong>15 Vendas:</strong> Distribuídas no mês</li>
                            <li><strong>3 Metas:</strong> Últimos 3 meses</li>
                            <li><strong>~R$ 1.635</strong> em vendas</li>
                        </ul>
                    </div>
                </div>
                <a href="?acao=executar" class="btn btn-danger" onclick="return confirm('APAGAR tudo e criar seeds?')">
                    ⚡ Executar Seeds
                </a>
            <?php else: ?>
                <div class="alert alert-success">✅ Seeds executados com sucesso!</div>
                <a href="<?= $_ENV['APP_URL'] ?? '/artflow2' ?>" class="btn btn-success">🏠 Ver Sistema</a>
                <a href="?acao=preview" class="btn btn-outline-light">🔄 Executar Novamente</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($logs)): ?>
    <div class="card mb-4">
        <div class="card-header"><strong>📋 Log de Execução</strong></div>
        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
            <?php foreach ($logs as $log): ?>
                <div class="log"><?= $log ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($acao === 'preview'): ?>
    <div class="card">
        <div class="card-header"><strong>🎨 Preview das 20 Artes</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>#</th><th>Nome</th><th>Tag</th><th>Status</th><th>Custo</th><th>Horas</th></tr></thead>
                <tbody>
                <?php foreach ($artes as $i => $a): ?>
                    <?php
                    $corTag = '#6B7280';
                    foreach ($tags as $t) if ($t['nome'] === $a['tag']) $corTag = $t['cor'];
                    $corStatus = match($a['status']) { 'disponivel' => 'success', 'vendida' => 'primary', default => 'warning' };
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= $a['nome'] ?></td>
                        <td><span class="badge badge-tag" style="background:<?= $corTag ?>"><?= $a['tag'] ?></span></td>
                        <td><span class="badge bg-<?= $corStatus ?>"><?= ucfirst(str_replace('_', ' ', $a['status'])) ?></span></td>
                        <td>R$ <?= number_format($a['custo'], 2, ',', '.') ?></td>
                        <td><?= $a['horas'] ?>h</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <div class="text-center text-muted py-4">
        <small>ArtFlow 2.0 Seeds | <?= date('d/m/Y H:i') ?></small>
    </div>
</div>
</body>
</html>