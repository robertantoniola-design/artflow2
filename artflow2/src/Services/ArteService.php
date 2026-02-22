<?php

namespace App\Services;

use App\Models\Arte;
use App\Repositories\ArteRepository;
use App\Repositories\TagRepository;
use App\Validators\ArteValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * ARTE SERVICE — MELHORIA 4 (Upload de Imagem)
 * ============================================
 * 
 * Camada de lógica de negócio para Artes.
 * Orquestra validação, repository e regras de negócio.
 * 
 * HISTÓRICO:
 * ─────────────────────
 * Fase 1:   [Bug T1] Normalização de filtros com ?: null
 *           [Bug T11] Transições de status com 'reservada'
 * Melhoria 1: listarPaginado() + POR_PAGINA=12
 * Melhoria 4: processarUploadImagem(), removerImagemFisica()
 *             Fluxo criar() e atualizar() agora suportam upload
 *             Fluxo remover() agora limpa arquivo físico
 * 
 * CORREÇÃO M4-BUG1 (20/02/2026):
 *   getPublicDir() retornava raiz do projeto (artflow2/) em vez de
 *   artflow2/public/ quando SCRIPT_FILENAME apontava para index.php
 *   na raiz. Arquivos iam para artflow2/uploads/artes/ (inacessível).
 *   Solução: Detectar a pasta public/ de forma confiável usando
 *   dirname(__DIR__, 2) que sobe de src/Services/ até a raiz do projeto,
 *   e então concatena /public.
 */
class ArteService
{
    private ArteRepository $arteRepository;
    private TagRepository $tagRepository;
    private ArteValidator $validator;
    
    // ==========================================
    // CONSTANTES
    // ==========================================
    
    /** [MELHORIA 1] Itens por página na listagem */
    const POR_PAGINA = 12;
    
    /**
     * [MELHORIA 4] Diretório de upload relativo à pasta public/
     * 
     * O caminho COMPLETO no disco será: {PROJECT_ROOT}/public/uploads/artes/
     * O caminho salvo no BANCO será:    uploads/artes/arte_1_1708123456.jpg
     * A URL para o NAVEGADOR usará:     url('/uploads/artes/arte_1_1708123456.jpg')
     * 
     * Nota: Armazenado dentro de public/ para servir direto via Apache.
     * O .htaccess no diretório impede execução de scripts PHP.
     */
    const UPLOAD_DIR = 'uploads/artes';
    
    public function __construct(
        ArteRepository $arteRepository,
        TagRepository $tagRepository,
        ArteValidator $validator
    ) {
        $this->arteRepository = $arteRepository;
        $this->tagRepository = $tagRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
    /**
     * Lista artes com filtros opcionais
     * 
     * [Bug T1 CORRIGIDO] — Normalização de filtros com ?: null
     * Problema: URL ?status= gera "" (string vazia) → Repository adicionava AND status = '' → 0 resultados
     * Solução: Encadeamento ?? null ?: null converte "" para null
     */
    public function listar(array $filtros = []): array
    {
        $status = $filtros['status'] ?? null ?: null;
        $termo  = $filtros['termo']  ?? null ?: null;
        $tagId  = $filtros['tag_id'] ?? null ?: null;
        
        if ($status && !$termo) {
            return $this->arteRepository->findByStatus($status);
        }
        
        if ($termo) {
            return $this->arteRepository->pesquisar($termo, $status);
        }
        
        if ($tagId) {
            return $this->arteRepository->findByTag((int)$tagId);
        }
        
        return $this->arteRepository->all('created_at', 'DESC');
    }
    
    /**
     * [MELHORIA 1] Lista artes com paginação e filtros combinados
     * 
     * @param array $filtros ['status', 'termo', 'tag_id', 'pagina', 'ordenar', 'direcao']
     * @return array ['artes' => [...], 'paginacao' => [...]]
     */
    public function listarPaginado(array $filtros): array
    {
        $pagina  = max(1, (int)($filtros['pagina'] ?? 1));
        $status  = $filtros['status'] ?? null ?: null;
        $termo   = $filtros['termo']  ?? null ?: null;
        $tagId   = isset($filtros['tag_id']) && $filtros['tag_id'] !== '' 
                   ? (int)$filtros['tag_id'] : null;
        $ordenar = $filtros['ordenar'] ?? 'created_at';
        $direcao = $filtros['direcao'] ?? 'DESC';
        
        // Busca paginada com filtros combinados
        $artes = $this->arteRepository->allPaginated(
            $pagina, self::POR_PAGINA,
            $termo, $status, $tagId,
            $ordenar, $direcao
        );
        
        // Contagem total para cálculo de paginação
        $total = $this->arteRepository->countAll($termo, $status, $tagId);
        $totalPaginas = max(1, ceil($total / self::POR_PAGINA));
        
        return [
            'artes' => $artes,
            'paginacao' => [
                'total'        => $total,
                'porPagina'    => self::POR_PAGINA,
                'paginaAtual'  => $pagina,
                'totalPaginas' => $totalPaginas,
                'temAnterior'  => $pagina > 1,
                'temProxima'   => $pagina < $totalPaginas,
            ]
        ];
    }
    
    /**
     * Busca arte por ID
     */
    public function buscar(int $id): Arte
    {
        return $this->arteRepository->findOrFail($id);
    }
    
    /**
     * Cria nova arte
     * 
     * [MELHORIA 4] — Agora aceita $arquivo para upload de imagem
     * 
     * Fluxo:
     * 1. Validar dados do formulário
     * 2. Aplicar defaults
     * 3. INSERT no banco (sem imagem — ID ainda não existe)
     * 4. Se tem arquivo, processar upload usando o ID recém-criado
     * 5. UPDATE com o caminho da imagem
     * 6. Sincronizar tags
     * 7. Retornar arte completa
     * 
     * @param array $dados Dados do formulário
     * @param array|null $arquivo Dados de $_FILES['imagem'] (ou null se sem upload)
     * @return Arte
     * @throws ValidationException
     */
    public function criar(array $dados, ?array $arquivo = null): Arte
    {
        // 1. Validação dos dados do formulário
        if (!$this->validator->validateCreate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // [MELHORIA 4] 1b. Validação da imagem (se enviada)
        if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!$this->validator->validateImagem($arquivo)) {
                throw new ValidationException($this->validator->getErrors());
            }
        }
        
        // 2. Defaults
        $dados['status'] = $dados['status'] ?? 'disponivel';
        $dados['horas_trabalhadas'] = $dados['horas_trabalhadas'] ?? 0;
        $dados['preco_custo'] = $dados['preco_custo'] ?? 0;
        
        // 3. Cria a arte (sem imagem — precisamos do ID primeiro)
        $arte = $this->arteRepository->create($dados);
        
        // [MELHORIA 4] 4. Processa upload se há arquivo válido
        if ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
            $caminhoImagem = $this->processarUploadImagem($arquivo, $arte->getId());
            
            // 5. Atualiza registro com o caminho da imagem
            $this->arteRepository->update($arte->getId(), ['imagem' => $caminhoImagem]);
            $arte = $this->arteRepository->find($arte->getId()); // Recarrega com imagem
        }
        
        // 6. Associa tags se fornecidas
        if (!empty($dados['tags'])) {
            $this->tagRepository->syncArte($arte->getId(), (array) $dados['tags']);
        }
        
        return $arte;
    }
    
    /**
     * Atualiza arte existente
     * 
     * [MELHORIA 4] — Agora aceita $arquivo e $removerImagem
     * 
     * Fluxo de imagem na edição:
     * - Se $removerImagem = true  → Remove arquivo físico + limpa campo no banco
     * - Se $arquivo enviado       → Remove imagem anterior + salva nova
     * - Se nenhum dos dois        → Mantém imagem atual (não altera)
     * 
     * @param int $id
     * @param array $dados Dados do formulário
     * @param array|null $arquivo Dados de $_FILES['imagem']
     * @param bool $removerImagem Se true, remove imagem sem substituir
     * @return Arte
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados, ?array $arquivo = null, bool $removerImagem = false): Arte
    {
        // Verifica se existe
        $arte = $this->arteRepository->findOrFail($id);
        
        // Validação dos dados
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // [MELHORIA 4] Validação da imagem (se enviada)
        if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {
            if (!$this->validator->validateImagem($arquivo)) {
                throw new ValidationException($this->validator->getErrors());
            }
        }
        
        // [MELHORIA 4] Processa lógica de imagem ANTES do update
        if ($removerImagem) {
            // ── Remoção explícita: checkbox "Remover imagem" marcado ──
            $this->removerImagemFisica($arte);
            $dados['imagem'] = null; // Limpa campo no banco
            
        } elseif ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
            // ── Nova imagem enviada: substitui a anterior ──
            $this->removerImagemFisica($arte); // Remove arquivo anterior (se existir)
            $caminhoImagem = $this->processarUploadImagem($arquivo, $id);
            $dados['imagem'] = $caminhoImagem;
        }
        // Se nenhum dos dois: $dados não inclui 'imagem' → campo NÃO é alterado no UPDATE
        
        // Atualiza registro
        $this->arteRepository->update($id, $dados);
        
        // Atualiza tags se fornecidas
        if (isset($dados['tags'])) {
            $this->tagRepository->syncArte($id, (array) $dados['tags']);
        }
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Remove arte
     * 
     * [MELHORIA 4] — Agora remove arquivo de imagem antes de deletar
     */
    public function remover(int $id): bool
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        // Verifica se pode ser removida (não vendida)
        if ($arte->getStatus() === 'vendida') {
            throw new ValidationException([
                'arte' => 'Artes vendidas não podem ser removidas'
            ]);
        }
        
        // [MELHORIA 4] Remove arquivo de imagem do disco
        $this->removerImagemFisica($arte);
        
        // Remove associações com tags
        $this->tagRepository->syncArte($id, []);
        
        // Remove a arte do banco
        return $this->arteRepository->delete($id);
    }
    
    // ==========================================
    // OPERAÇÕES DE STATUS
    // ==========================================
    
    /**
     * Altera status da arte
     */
    public function alterarStatus(int $id, string $novoStatus): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        $this->validarTransicaoStatus($arte->getStatus(), $novoStatus);
        $this->arteRepository->update($id, ['status' => $novoStatus]);
        return $this->arteRepository->find($id);
    }
    
    /**
     * Valida se transição de status é permitida
     * 
     * [Bug T11 CORRIGIDO] — 'reservada' adicionada como origem E destino
     */
    private function validarTransicaoStatus(string $statusAtual, string $novoStatus): void
    {
        $transicoesPermitidas = [
            'disponivel'  => ['em_producao', 'vendida', 'reservada'],
            'em_producao' => ['disponivel', 'vendida', 'reservada'],
            'reservada'   => ['disponivel', 'em_producao', 'vendida'],
            'vendida'     => [] // Estado final — não pode sair
        ];
        
        if (!isset($transicoesPermitidas[$statusAtual])) {
            throw new ValidationException([
                'status' => "Status atual '{$statusAtual}' é inválido"
            ]);
        }
        
        if (!in_array($novoStatus, $transicoesPermitidas[$statusAtual])) {
            throw new ValidationException([
                'status' => "Não é permitido mudar de '{$statusAtual}' para '{$novoStatus}'"
            ]);
        }
    }
    
    /**
     * Adiciona horas trabalhadas
     */
    public function adicionarHoras(int $id, float $horas): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        if ($horas <= 0) {
            throw new ValidationException([
                'horas' => 'O valor de horas deve ser maior que zero'
            ]);
        }
        
        $novasHoras = $arte->getHorasTrabalhadas() + $horas;
        $this->arteRepository->update($id, ['horas_trabalhadas' => $novasHoras]);
        
        return $this->arteRepository->find($id);
    }
    
    // ==========================================
    // ESTATÍSTICAS E CONSULTAS
    // ==========================================
    
    /**
     * Estatísticas de artes (contagem por status)
     */
    public function getEstatisticas(): array
    {
        return $this->arteRepository->countByStatus();
    }
    
    /**
     * Artes disponíveis para venda (usado pelo módulo Vendas)
     */
    public function getDisponiveisParaVenda(): array
    {
        return $this->arteRepository->findByStatus('disponivel');
    }
    
    /**
     * Tags associadas a uma arte
     */
    public function getTags(int $arteId): array
    {
        return $this->tagRepository->getByArte($arteId);
    }
    
    // ==========================================
    // CÁLCULOS DE MÉTRICAS
    // ==========================================
    
    /**
     * Calcula custo por hora trabalhada
     */
    public function calcularCustoPorHora(Arte $arte): ?float
    {
        if ($arte->getHorasTrabalhadas() <= 0) {
            return null;
        }
        return round($arte->getPrecoCusto() / $arte->getHorasTrabalhadas(), 2);
    }
    
    /**
     * Calcula preço sugerido (multiplicador de 2.5x sobre o custo)
     */
    public function calcularPrecoSugerido(Arte $arte): float
    {
        return round($arte->getPrecoCusto() * 2.5, 2);
    }
    
    // ==========================================
    // [MELHORIA 5] MÉTRICAS INDIVIDUAIS (show.php)
    // ==========================================

    /**
     * [M5] Calcula o progresso da arte em relação ao tempo estimado
     * 
     * Fórmula: (horas_trabalhadas / tempo_medio_horas) × 100
     * Limitado a 100% para não ultrapassar a barra de progresso,
     * mas o valor real é preservado em 'valor_real' para exibição.
     * 
     * Retorna null se não há tempo estimado (campo não preenchido).
     * 
     * @param Arte $arte Objeto Arte com dados de horas
     * @return array|null [
     *     'percentual'  => int,   // 0-100 (limitado para barra visual)
     *     'valor_real'  => float, // Percentual real (pode ser >100%)
     *     'horas_faltam' => float // Horas restantes (0 se já completou)
     * ] ou null se sem estimativa
     */
    public function calcularProgresso(Arte $arte): ?array
    {
        $tempoEstimado = $arte->getTempoMedioHoras();
        
        // Sem estimativa de tempo → não é possível calcular progresso
        if ($tempoEstimado === null || $tempoEstimado <= 0) {
            return null;
        }
        
        $horasTrabalhadas = $arte->getHorasTrabalhadas();
        $valorReal = ($horasTrabalhadas / $tempoEstimado) * 100;
        
        return [
            'percentual'   => min(100, (int) round($valorReal)), // Barra visual: máx 100%
            'valor_real'    => round($valorReal, 1),              // Exibição: pode ser >100%
            'horas_faltam'  => max(0, round($tempoEstimado - $horasTrabalhadas, 2)),
        ];
    }

    /**
     * [M5] Monta array completo de métricas para a view show.php
     * 
     * Centraliza TODOS os cálculos de métricas em um único lugar,
     * evitando que o Controller precise chamar múltiplos métodos.
     * 
     * NOTA: Cards de Lucro e Rentabilidade serão adicionados após
     * o módulo Vendas estar estabilizado (dependem da tabela vendas).
     * 
     * @param Arte $arte Objeto Arte
     * @return array Métricas calculadas para exibição
     */
    public function getMetricasArte(Arte $arte): array
    {
        return [
            'custo_por_hora'   => $this->calcularCustoPorHora($arte),
            'preco_sugerido'   => $this->calcularPrecoSugerido($arte),
            'progresso'        => $this->calcularProgresso($arte),
            
            // ┌─────────────────────────────────────────────────┐
            // │ TODO: Adicionar após módulo Vendas estável       │
            // │                                                   │
            // │ 'lucro' => $this->calcularLucro($arte),          │
            // │ 'rentabilidade' => $this->calcularRentab($arte), │
            // │                                                   │
            // │ Dependem de: query na tabela vendas               │
            // │ Condição: só quando status = 'vendida'            │
            // └─────────────────────────────────────────────────┘
        ];
    }

    // ==========================================
    // [MELHORIA 6] DADOS PARA GRÁFICOS (index.php)
    // ==========================================

    /**
     * [M6] Retorna distribuição de artes por complexidade
     * 
     * Wrapper para ArteRepository::countByComplexidade().
     * Padrão: Service delega para Repository (mesma abordagem de Tags M6).
     * 
     * @return array ['baixa' => N, 'media' => N, 'alta' => N]
     */
    public function getDistribuicaoComplexidade(): array
    {
        return $this->arteRepository->countByComplexidade();
    }

    /**
     * [M6] Retorna dados para os 4 cards de resumo na index.php
     * 
     * Indicadores:
     * - Total de artes
     * - Valor em estoque (soma custo das NÃO vendidas)
     * - Horas totais investidas
     * - Artes disponíveis para venda
     * 
     * @return array Associativo com os indicadores
     */
    public function getResumoCards(): array
    {
        return $this->arteRepository->getResumoFinanceiro();
    }


    // ==========================================
    // [MELHORIA 4] UPLOAD DE IMAGEM
    // ==========================================
    
    /**
     * Processa upload de imagem e move para diretório final
     * 
     * Fluxo:
     * 1. Garantir que o diretório de uploads existe
     * 2. Gerar nome seguro: arte_{id}_{timestamp}.{ext}
     * 3. Mover arquivo do tmp para destino final
     * 4. Retornar caminho relativo (para salvar no banco)
     * 
     * @param array $arquivo Dados de $_FILES['imagem']
     * @param int $arteId ID da arte (para compor nome do arquivo)
     * @return string Caminho relativo salvo no banco (ex: "uploads/artes/arte_1_1708123456.jpg")
     * @throws ValidationException Se falhar ao mover o arquivo
     */
    private function processarUploadImagem(array $arquivo, int $arteId): string
    {
        // 1. Garante que o diretório existe
        $diretorioAbsoluto = $this->getUploadDirAbsoluto();
        if (!is_dir($diretorioAbsoluto)) {
            // Cria recursivamente com permissões 0755 (seguro para XAMPP)
            if (!mkdir($diretorioAbsoluto, 0755, true)) {
                throw new ValidationException([
                    'imagem' => 'Erro ao criar diretório de uploads. Verifique permissões do servidor.'
                ]);
            }
        }
        
        // 2. Gera nome seguro e único
        // Formato: arte_{id}_{timestamp}.{extensão}
        // O timestamp evita colisões ao re-enviar imagem para a mesma arte
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $nomeArquivo = sprintf('arte_%d_%d.%s', $arteId, time(), $extensao);
        
        // 3. Caminho completo no disco
        //    CORREÇÃO M4-BUG1: Usa separadores consistentes (/)
        //    PHP no Windows aceita ambos, mas misturar causa confusão
        $caminhoAbsoluto = $diretorioAbsoluto . '/' . $nomeArquivo;
        
        // 4. Move arquivo do tmp para destino final
        // move_uploaded_file() verifica se o arquivo veio via HTTP POST (segurança)
        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoAbsoluto)) {
            throw new ValidationException([
                'imagem' => 'Falha ao salvar imagem. Verifique permissões do diretório.'
            ]);
        }
        
        // 5. Retorna caminho RELATIVO (armazenado no banco)
        // Formato: "uploads/artes/arte_1_1708123456.jpg"
        return self::UPLOAD_DIR . '/' . $nomeArquivo;
    }
    
    /**
     * Remove arquivo de imagem do disco (se existir)
     * 
     * Chamado em:
     * - atualizar() quando checkbox "Remover imagem" está marcado
     * - atualizar() quando nova imagem substitui a anterior
     * - remover() quando arte é deletada
     * 
     * CORREÇÃO M4-BUG1: Também tenta remover do local antigo (raiz/uploads)
     * caso existam arquivos salvos antes do fix.
     * 
     * @param Arte $arte Objeto arte com o caminho da imagem
     * @return void
     */
    private function removerImagemFisica(Arte $arte): void
    {
        $caminhoRelativo = $arte->getImagem();
        
        // Se não tem imagem, nada a fazer
        if (empty($caminhoRelativo)) {
            return;
        }
        
        // Monta caminho absoluto CORRETO: {PROJECT_ROOT}/public/{caminho_relativo}
        $caminhoCorreto = $this->getPublicDir() . '/' . $caminhoRelativo;
        
        // Remove o arquivo se existir no local correto (public/uploads/artes/)
        if (file_exists($caminhoCorreto)) {
            unlink($caminhoCorreto);
            return;
        }
        
        // CORREÇÃO M4-BUG1: Também tenta o local antigo (raiz/uploads/artes/)
        // Arquivos salvos antes do fix podem estar em artflow2/uploads/artes/
        $projectRoot = dirname($this->getPublicDir());
        $caminhoAntigo = $projectRoot . '/' . $caminhoRelativo;
        
        if (file_exists($caminhoAntigo)) {
            unlink($caminhoAntigo);
        }
    }
    
    /**
     * Retorna caminho absoluto do diretório de uploads
     * 
     * Exemplo em XAMPP: C:/xampp/htdocs/artflow2/public/uploads/artes
     * 
     * @return string
     */
    private function getUploadDirAbsoluto(): string
    {
        return $this->getPublicDir() . '/' . self::UPLOAD_DIR;
    }
    
    /**
     * Retorna caminho absoluto da pasta public/
     * 
     * CORREÇÃO M4-BUG1 (20/02/2026):
     * ────────────────────────────────
     * PROBLEMA:
     *   Método usava dirname(SCRIPT_FILENAME) como primeira opção.
     *   No XAMPP com artflow2/, o entry point pode ser:
     *     - artflow2/index.php (raiz) → dirname() = artflow2/ (SEM /public!) ❌
     *     - artflow2/public/index.php → dirname() = artflow2/public/ ✅
     *   Quando o entry point era na raiz, os uploads iam para
     *   artflow2/uploads/artes/ em vez de artflow2/public/uploads/artes/.
     * 
     * SOLUÇÃO:
     *   Usa dirname(__DIR__, 2) como método primário.
     *   __DIR__ neste arquivo é sempre src/Services/, então:
     *     dirname('src/Services', 2) = artflow2/  (raiz do projeto)
     *     + '/public' = artflow2/public/  ✅ SEMPRE CORRETO
     *   Isso funciona independente de qual index.php é o entry point.
     * 
     * JUSTIFICATIVA:
     *   dirname(__DIR__, 2) é determinístico: baseado na posição FIXA
     *   deste arquivo no filesystem, não depende de variáveis de ambiente.
     *   SCRIPT_FILENAME depende de como o Apache foi configurado.
     * 
     * @return string Caminho absoluto da pasta public/
     */
    private function getPublicDir(): string
    {
        // ── Método primário: baseado na posição deste arquivo ──
        // Este arquivo está em: {PROJECT_ROOT}/src/Services/ArteService.php
        // dirname(__DIR__, 2) sobe 2 níveis: Services → src → {PROJECT_ROOT}
        // Resultado: {PROJECT_ROOT}/public (SEMPRE correto)
        $projectRoot = dirname(__DIR__, 2);
        $publicDir = $projectRoot . '/public';
        
        if (is_dir($publicDir)) {
            return $publicDir;
        }
        
        // ── Fallback 1: SCRIPT_FILENAME (se public/ não for encontrado acima) ──
        // Funciona quando o entry point É o public/index.php
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
            // Verifica se estamos dentro de public/ (contém index.php + assets)
            if (basename($scriptDir) === 'public' || file_exists($scriptDir . '/index.php')) {
                return $scriptDir;
            }
        }
        
        // ── Fallback 2: BASE_PATH (se definida no bootstrap) ──
        if (defined('BASE_PATH')) {
            return rtrim(BASE_PATH, '/\\') . '/public';
        }
        
        // ── Último recurso: retorna o que temos ──
        return $projectRoot . '/public';
    }
}