<?php

namespace App\Services;

use App\Models\Arte;
use App\Repositories\ArteRepository;
use App\Repositories\TagRepository;
use App\Repositories\VendaRepository;
use App\Validators\ArteValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * ARTE SERVICE — MELHORIA 4 + M5 COMPLETA
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
 * Melhoria 5: calcularProgresso(), getMetricasArte() (3 cards)
 * 
 * M5 CROSS-MODULE (22/02/2026):
 *   Adicionado VendaRepository como dependência para consultar
 *   dados de venda de artes vendidas. Novos métodos:
 *   - getDadosVenda(Arte): busca venda associada à arte
 *   - calcularLucro(Arte): lucro + margem percentual
 *   - calcularRentabilidade(Arte): R$/hora baseado no lucro
 *   getMetricasArte() agora retorna 5 métricas (antes 3).
 *   Cards de Lucro e Rentabilidade só aparecem para status='vendida'.
 * 
 * CORREÇÃO M4-BUG1 (20/02/2026):
 *   getPublicDir() retornava raiz do projeto (artflow2/) em vez de
 *   artflow2/public/ quando SCRIPT_FILENAME apontava para index.php
 *   na raiz. Solução: dirname(__DIR__, 2) + /public.
 */
class ArteService
{
    private ArteRepository $arteRepository;
    private TagRepository $tagRepository;
    private VendaRepository $vendaRepository;
    private ArteValidator $validator;
    
    // ==========================================
    // CONSTANTES
    // ==========================================
    
    /** [MELHORIA 1] Itens por página na listagem */
    const POR_PAGINA = 12;
    
    /**
     * [MELHORIA 4] Diretório de upload relativo à pasta public/
     */
    const UPLOAD_DIR = 'uploads/artes';
    
    /**
     * Construtor — Container resolve via auto-wiring (Reflection)
     * 
     * ALTERAÇÃO M5 CROSS-MODULE (22/02/2026):
     *   Adicionado VendaRepository para consultar dados de vendas
     *   associadas a artes vendidas (cards Lucro + Rentabilidade).
     *   O Container resolve automaticamente via type-hint.
     */
    public function __construct(
        ArteRepository $arteRepository,
        TagRepository $tagRepository,
        VendaRepository $vendaRepository,
        ArteValidator $validator
    ) {
        $this->arteRepository = $arteRepository;
        $this->tagRepository = $tagRepository;
        $this->vendaRepository = $vendaRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
    /**
     * Lista artes com filtros opcionais
     * 
     * [Bug T1 CORRIGIDO] — Normalização de filtros com ?: null
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
        
        $artes = $this->arteRepository->allPaginated(
            $pagina, self::POR_PAGINA,
            $termo, $status, $tagId,
            $ordenar, $direcao
        );
        
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
            $this->arteRepository->update($arte->getId(), ['imagem' => $caminhoImagem]);
            $arte = $this->arteRepository->find($arte->getId());
        }
        
        // 5. Associa tags se fornecidas
        if (!empty($dados['tags'])) {
            $this->tagRepository->syncArte($arte->getId(), (array) $dados['tags']);
        }
        
        return $arte;
    }
    
    /**
     * Atualiza arte existente
     * 
     * [MELHORIA 4] — Agora aceita $arquivo e $removerImagem
     */
    public function atualizar(int $id, array $dados, ?array $arquivo = null, bool $removerImagem = false): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        
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
            $this->removerImagemFisica($arte);
            $dados['imagem'] = null;
        } elseif ($arquivo && $arquivo['error'] === UPLOAD_ERR_OK) {
            $this->removerImagemFisica($arte);
            $caminhoImagem = $this->processarUploadImagem($arquivo, $id);
            $dados['imagem'] = $caminhoImagem;
        }
        
        $this->arteRepository->update($id, $dados);
        
        if (isset($dados['tags'])) {
            $this->tagRepository->syncArte($id, (array) $dados['tags']);
        }
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Remove arte
     * 
     * [MELHORIA 4] — Remove arquivo de imagem antes de deletar
     */
    public function remover(int $id): bool
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        if ($arte->getStatus() === 'vendida') {
            throw new ValidationException([
                'arte' => 'Artes vendidas não podem ser removidas'
            ]);
        }
        
        $this->removerImagemFisica($arte);
        $this->tagRepository->syncArte($id, []);
        
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
            'vendida'     => []
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
     */
    public function calcularProgresso(Arte $arte): ?array
    {
        $tempoEstimado = $arte->getTempoMedioHoras();
        
        if ($tempoEstimado === null || $tempoEstimado <= 0) {
            return null;
        }
        
        $horasTrabalhadas = $arte->getHorasTrabalhadas();
        $valorReal = ($horasTrabalhadas / $tempoEstimado) * 100;
        
        return [
            'percentual'   => min(100, (int) round($valorReal)),
            'valor_real'    => round($valorReal, 1),
            'horas_faltam'  => max(0, round($tempoEstimado - $horasTrabalhadas, 2)),
        ];
    }

    /**
     * [M5 CROSS-MODULE] Busca dados da venda associada a uma arte
     * 
     * Cada arte só pode ser vendida UMA vez (status → 'vendida' é terminal),
     * então findByArte() retorna no máximo 1 registro relevante.
     * 
     * SEGURANÇA: Try/catch silencioso — se VendaRepository falhar
     * (ex: tabela vendas corrompida), os outros 3 cards continuam funcionando.
     * 
     * @param Arte $arte Objeto Arte
     * @return array|null Dados da venda ou null se não vendida/sem registro
     */
    private function getDadosVenda(Arte $arte): ?array
    {
        // Só consulta vendas para artes com status 'vendida'
        // Evita query desnecessária para artes em outros status
        if ($arte->getStatus() !== 'vendida') {
            return null;
        }
        
        try {
            // findFirstBy() é do BaseRepository — retorna 1 objeto Venda ou null
            // Cada arte só é vendida UMA vez, então findFirstBy é suficiente
            $venda = $this->vendaRepository->findFirstBy('arte_id', $arte->getId());
            
            if ($venda === null) {
                // Arte marcada como vendida mas sem registro na tabela vendas
                // (possível inconsistência de dados — não bloqueia a view)
                error_log("[ArteService] Arte #{$arte->getId()} status=vendida mas sem registro em vendas");
                return null;
            }
            
            return [
                'valor_venda'        => (float) $venda->getValor(),
                'lucro'              => (float) ($venda->getLucroCalculado() ?? 0),
                'rentabilidade_hora' => (float) ($venda->getRentabilidadeHora() ?? 0),
                'data_venda'         => $venda->getDataVenda(),
                'forma_pagamento'    => $venda->getFormaPagamento(),
            ];
            
        } catch (\Exception $e) {
            // Se der erro na consulta, loga e retorna null
            // Os outros 3 cards (Custo/Hora, Preço Sugerido, Progresso) continuam OK
            error_log("[ArteService] Erro ao buscar venda para arte #{$arte->getId()}: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * [M5 CROSS-MODULE] Calcula lucro da venda + margem percentual
     * 
     * Lucro: valor_venda - preco_custo (já armazenado como lucro_calculado na venda)
     * Margem: (lucro / preco_custo) × 100 — quanto % acima do custo
     * 
     * Só disponível para artes com status 'vendida'.
     * 
     * @param Arte $arte Objeto Arte
     * @return array|null ['valor_venda', 'lucro', 'margem_percentual'] ou null
     */
    public function calcularLucro(Arte $arte): ?array
    {
        $dadosVenda = $this->getDadosVenda($arte);
        
        if ($dadosVenda === null) {
            return null;
        }
        
        $precoCusto = $arte->getPrecoCusto();
        
        // Margem percentual: quanto % acima do custo foi o lucro
        // Se custo = 0, qualquer venda é 100% de margem
        $margemPercentual = $precoCusto > 0 
            ? round(($dadosVenda['lucro'] / $precoCusto) * 100, 1) 
            : ($dadosVenda['lucro'] > 0 ? 100.0 : 0);
        
        return [
            'valor_venda'       => $dadosVenda['valor_venda'],
            'lucro'             => $dadosVenda['lucro'],
            'margem_percentual' => $margemPercentual,
        ];
    }

    /**
     * [M5 CROSS-MODULE] Calcula rentabilidade por hora
     * 
     * Rentabilidade = lucro / horas_trabalhadas
     * Já armazenado como rentabilidade_hora na venda,
     * mas recalculamos aqui para consistência com horas atuais.
     * 
     * Só disponível para artes vendidas com horas > 0.
     * 
     * @param Arte $arte Objeto Arte
     * @return float|null R$/hora ou null se não aplicável
     */
    public function calcularRentabilidade(Arte $arte): ?float
    {
        $dadosVenda = $this->getDadosVenda($arte);
        
        if ($dadosVenda === null) {
            return null;
        }
        
        // Se não há horas trabalhadas, retorna o valor direto da venda
        // (evita divisão por zero)
        $horasTrabalhadas = $arte->getHorasTrabalhadas();
        
        if ($horasTrabalhadas <= 0) {
            // Usa o valor armazenado na venda como fallback
            return $dadosVenda['rentabilidade_hora'] > 0 
                ? $dadosVenda['rentabilidade_hora'] 
                : null;
        }
        
        // Recalcula: lucro / horas (pode diferir do armazenado se horas foram editadas)
        return round($dadosVenda['lucro'] / $horasTrabalhadas, 2);
    }

    /**
     * [M5] Monta array completo de métricas para a view show.php
     * 
     * Centraliza TODOS os cálculos de métricas em um único lugar,
     * evitando que o Controller precise chamar múltiplos métodos.
     * 
     * ATUALIZAÇÃO M5 CROSS-MODULE (22/02/2026):
     *   Agora retorna 5 métricas (antes 3). Cards de Lucro e
     *   Rentabilidade são condicionais — retornam null para artes
     *   não vendidas. A view usa isso para mostrar/esconder cards.
     * 
     * @param Arte $arte Objeto Arte
     * @return array Métricas calculadas para exibição:
     *   - custo_por_hora  (float|null)     — sempre disponível
     *   - preco_sugerido  (float)          — sempre disponível
     *   - progresso       (array|null)     — se tem tempo estimado
     *   - lucro           (array|null)     — SÓ se status='vendida'
     *   - rentabilidade   (float|null)     — SÓ se status='vendida' + horas>0
     */
    public function getMetricasArte(Arte $arte): array
    {
        return [
            // ── 3 métricas originais (sempre disponíveis) ──
            'custo_por_hora'   => $this->calcularCustoPorHora($arte),
            'preco_sugerido'   => $this->calcularPrecoSugerido($arte),
            'progresso'        => $this->calcularProgresso($arte),
            
            // ── 2 métricas cross-module (só para artes vendidas) ──
            // Retornam null se status != 'vendida' → view não renderiza os cards
            'lucro'            => $this->calcularLucro($arte),
            'rentabilidade'    => $this->calcularRentabilidade($arte),
        ];
    }

    // ==========================================
    // [MELHORIA 6] DADOS PARA GRÁFICOS (index.php)
    // ==========================================

    /**
     * [M6] Retorna distribuição de artes por complexidade
     */
    public function getDistribuicaoComplexidade(): array
    {
        return $this->arteRepository->countByComplexidade();
    }

    /**
     * [M6] Retorna dados para os 4 cards de resumo na index.php
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
     */
    private function processarUploadImagem(array $arquivo, int $arteId): string
    {
        $diretorioAbsoluto = $this->getUploadDirAbsoluto();
        if (!is_dir($diretorioAbsoluto)) {
            if (!mkdir($diretorioAbsoluto, 0755, true)) {
                throw new ValidationException([
                    'imagem' => 'Erro ao criar diretório de uploads. Verifique permissões do servidor.'
                ]);
            }
        }
        
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        $nomeArquivo = sprintf('arte_%d_%d.%s', $arteId, time(), $extensao);
        $caminhoAbsoluto = $diretorioAbsoluto . '/' . $nomeArquivo;
        
        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoAbsoluto)) {
            throw new ValidationException([
                'imagem' => 'Falha ao salvar imagem. Verifique permissões do diretório.'
            ]);
        }
        
        return self::UPLOAD_DIR . '/' . $nomeArquivo;
    }
    
    /**
     * Remove arquivo de imagem do disco (se existir)
     */
    private function removerImagemFisica(Arte $arte): void
    {
        $caminhoRelativo = $arte->getImagem();
        
        if (empty($caminhoRelativo)) {
            return;
        }
        
        $caminhoCorreto = $this->getPublicDir() . '/' . $caminhoRelativo;
        
        if (file_exists($caminhoCorreto)) {
            unlink($caminhoCorreto);
            return;
        }
        
        // CORREÇÃO M4-BUG1: Tenta local antigo (raiz/uploads/artes/)
        $projectRoot = dirname($this->getPublicDir());
        $caminhoAntigo = $projectRoot . '/' . $caminhoRelativo;
        
        if (file_exists($caminhoAntigo)) {
            unlink($caminhoAntigo);
        }
    }
    
    /**
     * Retorna caminho absoluto do diretório de uploads
     */
    private function getUploadDirAbsoluto(): string
    {
        return $this->getPublicDir() . '/' . self::UPLOAD_DIR;
    }
    
    /**
     * Retorna caminho absoluto da pasta public/
     * 
     * CORREÇÃO M4-BUG1 (20/02/2026):
     *   Usa dirname(__DIR__, 2) como método primário (determinístico).
     */
    private function getPublicDir(): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $publicDir = $projectRoot . '/public';
        
        if (is_dir($publicDir)) {
            return $publicDir;
        }
        
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $scriptDir = dirname($_SERVER['SCRIPT_FILENAME']);
            if (basename($scriptDir) === 'public' || file_exists($scriptDir . '/index.php')) {
                return $scriptDir;
            }
        }
        
        if (defined('BASE_PATH')) {
            return rtrim(BASE_PATH, '/\\') . '/public';
        }
        
        return $projectRoot . '/public';
    }
}