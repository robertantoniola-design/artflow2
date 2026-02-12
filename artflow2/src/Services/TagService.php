<?php

namespace App\Services;

use App\Models\Tag;
use App\Repositories\TagRepository;
use App\Validators\TagValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * TAG SERVICE (Melhoria 6 — + Gráfico de Distribuição)
 * ============================================
 * 
 * Camada de lógica de negócio para Tags.
 * 
 * Responsabilidades:
 * - Validar dados de entrada
 * - Garantir unicidade de nomes
 * - Normalizar cores, descrição e ícones
 * - Gerenciar relacionamentos com artes
 * 
 * CORREÇÕES APLICADAS:
 * - [07/02/2026] Adicionado pesquisar() — chamado pelo TagController::index() e buscar()
 * - [07/02/2026] Adicionado getArtesComTag() — chamado pelo TagController::show()
 * - [07/02/2026] Fix normalizarDados() — lógica de cor padrão corrigida
 * 
 * MELHORIA 2 (Fase 2):
 * - [07/02/2026] Adicionado listarPaginado() — paginação + ordenação
 * 
 * MELHORIA 3:
 * - [07/02/2026] normalizarDados() agora trata descricao (trim) e icone (trim/null)
 * - [07/02/2026] criar() e atualizar() aceitam campos descricao e icone
 * 
 * MELHORIA 6:
 * - [12/02/2026] Adicionado getContagemPorTag() — dados para gráfico Chart.js
 */
class TagService
{
    private TagRepository $tagRepository;
    private TagValidator $validator;
    
    public function __construct(
        TagRepository $tagRepository,
        TagValidator $validator
    ) {
        $this->tagRepository = $tagRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
    /**
     * Lista todas as tags
     * 
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        // Com contagem de artes
        if (!empty($filtros['com_contagem'])) {
            return $this->tagRepository->allWithCount();
        }
        
        // Busca por termo
        if (!empty($filtros['termo'])) {
            return $this->tagRepository->search($filtros['termo']);
        }
        
        // Todas ordenadas
        return $this->tagRepository->allOrdered();
    }
    
    /**
     * Lista todas as tags com contagem de artes associadas
     * Alias para listar(['com_contagem' => true])
     * 
     * @return array
     */
    public function listarComContagem(): array
    {
        return $this->tagRepository->allWithCount();
    }
    
    /**
     * MELHORIA 2: Lista tags paginadas com ordenação e busca
     * 
     * @param int $page Página atual (1-based)
     * @param int $perPage Itens por página
     * @param string $ordenar Campo de ordenação (nome|data|contagem)
     * @param string $direcao ASC ou DESC
     * @param string $termo Termo de busca opcional
     * @return array ['tags' => Tag[], 'paginacao' => [...]]
     */
    public function listarPaginado(
        int $page = 1,
        int $perPage = 12,
        string $ordenar = 'nome',
        string $direcao = 'ASC',
        string $termo = ''
    ): array {
        // Garante valores mínimos
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        
        // Busca tags paginadas no Repository
        $tags = $this->tagRepository->allWithCountPaginated(
            $page, $perPage, $ordenar, $direcao, $termo
        );
        
        // Conta total para calcular páginas
        $total = $this->tagRepository->countAll($termo);
        $totalPaginas = (int) ceil($total / $perPage);
        
        return [
            'tags' => $tags,
            'paginacao' => [
                'pagina_atual' => $page,
                'por_pagina' => $perPage,
                'total_registros' => $total,
                'total_paginas' => $totalPaginas,
                'tem_anterior' => $page > 1,
                'tem_proxima' => $page < $totalPaginas,
            ]
        ];
    }
    
    /**
     * Busca tag por ID
     * 
     * @param int $id
     * @return Tag
     * @throws NotFoundException
     */
    public function buscar(int $id): Tag
    {
        return $this->tagRepository->findOrFail($id);
    }
    
    /**
     * Cria nova tag
     * 
     * MELHORIA 3: Agora aceita 'descricao' e 'icone' no array $dados
     * 
     * @param array $dados ['nome', 'cor', 'descricao'?, 'icone'?]
     * @return Tag
     * @throws ValidationException
     */
    public function criar(array $dados): Tag
    {
        // Validação (TagValidator.doValidation() agora valida descricao e icone)
        $this->validator->validate($dados);
        
        // Verifica unicidade do nome
        if ($this->tagRepository->nomeExists($dados['nome'])) {
            throw new ValidationException([
                'nome' => 'Já existe uma tag com este nome'
            ]);
        }
        
        // Normaliza dados (nome, cor, descricao, icone)
        $dados = $this->normalizarDados($dados);
        
        return $this->tagRepository->create($dados);
    }
    
    /**
     * Atualiza tag existente
     * 
     * MELHORIA 3: Agora aceita 'descricao' e 'icone' no array $dados
     * 
     * @param int $id
     * @param array $dados ['nome'?, 'cor'?, 'descricao'?, 'icone'?]
     * @return Tag
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Tag
    {
        // Verifica se existe
        $tag = $this->tagRepository->findOrFail($id);
        
        // Validação (usa validateUpdate para campos opcionais na edição)
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Verifica unicidade do nome (excluindo tag atual)
        if (isset($dados['nome']) && $dados['nome'] !== $tag->getNome()) {
            if ($this->tagRepository->nomeExists($dados['nome'], $id)) {
                throw new ValidationException([
                    'nome' => 'Já existe uma tag com este nome'
                ]);
            }
        }
        
        // Normaliza dados
        $dados = $this->normalizarDados($dados);
        
        $this->tagRepository->update($id, $dados);
        
        return $this->tagRepository->find($id);
    }
    
    /**
     * Remove tag
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function remover(int $id): bool
    {
        $this->tagRepository->findOrFail($id);
        
        // Remove tag e todas associações na arte_tags
        return $this->tagRepository->deleteWithRelations($id);
    }
    


    // ==========================================
    // MELHORIA 4: MERGE DE TAGS
    // ==========================================

    /**
     * Mescla tag origem na tag destino
     * 
     * Valida que ambas as tags existem e são diferentes,
     * depois delega para o Repository que faz a operação
     * atômica em transação.
     * 
     * @param int $origemId  ID da tag que será absorvida (deletada)
     * @param int $destinoId ID da tag que receberá as associações
     * @return array ['tag_origem' => Tag, 'tag_destino' => Tag, 
     *               'transferidas' => int, 'duplicatas' => int]
     * @throws ValidationException Se origem == destino
     * @throws NotFoundException Se alguma tag não existe
     */
    public function mergeTags(int $origemId, int $destinoId): array
    {
        // ── Validação 1: Não pode mesclar consigo mesma ──
        if ($origemId === $destinoId) {
            throw new ValidationException([
                'tag_destino_id' => 'Não é possível mesclar uma tag consigo mesma.'
            ]);
        }
        
        // ── Validação 2: Ambas as tags devem existir ──
        // findOrFail() lança NotFoundException se não encontrar
        $tagOrigem  = $this->tagRepository->findOrFail($origemId);
        $tagDestino = $this->tagRepository->findOrFail($destinoId);
        
        // ── Executa merge no Repository (transação atômica) ──
        $resultado = $this->tagRepository->mergeTags($origemId, $destinoId);
        
        return [
            'tag_origem'   => $tagOrigem,   // Para mensagem de feedback
            'tag_destino'  => $tagDestino,   // Para redirecionamento
            'transferidas' => $resultado['transferidas'],
            'duplicatas'   => $resultado['duplicatas'],
        ];
    }

    // ==========================================
    // ESTATÍSTICAS POR TAG (Melhoria 5)
    // ==========================================

    /**
     * ============================================
     * MELHORIA 5: Retorna estatísticas formatadas de uma tag
     * ============================================
     * 
     * Delega a query ao Repository e adiciona dados calculados
     * que facilitam a exibição na view (percentuais, labels, etc).
     * 
     * RESPONSABILIDADE DO SERVICE:
     * - Validar que a tag existe (findOrFail)
     * - Buscar dados brutos no Repository
     * - Calcular métricas derivadas (% vendidas, margem de lucro, R$/hora)
     * - Formatar labels para exibição
     * 
     * O Controller recebe tudo pronto — a view só exibe.
     * 
     * @param int $tagId ID da tag
     * @return array Estatísticas brutas + calculadas
     * @throws NotFoundException Se tag não existe
     */
    public function getEstatisticasTag(int $tagId): array
    {
        // Valida existência — lança NotFoundException se inválido
        $this->tagRepository->findOrFail($tagId);
        
        // Busca dados brutos do banco
        $stats = $this->tagRepository->getEstatisticasByTag($tagId);
        
        // ── Métricas calculadas (derivadas dos dados brutos) ──
        
        // Percentual de artes vendidas: (vendidas / total) * 100
        // Proteção contra divisão por zero quando tag não tem artes
        $stats['percentual_vendidas'] = $stats['total_artes'] > 0
            ? round(($stats['artes_vendidas'] / $stats['total_artes']) * 100, 1)
            : 0;
        
        // Margem de lucro: (lucro_total / faturamento_total) * 100
        // Indica eficiência financeira das artes com esta tag
        $stats['margem_lucro'] = $stats['faturamento_total'] > 0
            ? round(($stats['lucro_total'] / $stats['faturamento_total']) * 100, 1)
            : 0;
        
        // Valor médio por hora: custo_total / horas_totais
        // Indica quanto custa em média cada hora investida nestas artes
        $stats['custo_por_hora'] = $stats['horas_totais'] > 0
            ? round($stats['custo_total'] / $stats['horas_totais'], 2)
            : 0;
        
        // Label legível para complexidade mais comum
        // Traduz o ENUM do banco para português
        $stats['complexidade_label'] = match($stats['complexidade_mais_comum']) {
            'baixa' => 'Baixa',
            'media' => 'Média',
            'alta'  => 'Alta',
            default => '—',
        };
        
        // Flag: tag tem dados suficientes para exibir estatísticas?
        // Usado na view para decidir se mostra cards ou mensagem vazia
        $stats['tem_dados'] = $stats['total_artes'] > 0;
        $stats['tem_vendas'] = $stats['total_vendas'] > 0;
        
        return $stats;
    }

    // ==========================================
    // BUSCA E PESQUISA
    // ==========================================
    
    /**
     * Pesquisa tags por termo
     * 
     * Chamado pelo TagController::index() quando há filtro de busca
     * e pelo TagController::buscar() no endpoint AJAX de autocomplete.
     * 
     * @param string $termo Texto para buscar (LIKE %termo%)
     * @param int $limite Máximo de resultados
     * @return array Array de arrays associativos com dados das tags
     */
    public function pesquisar(string $termo, int $limite = 10): array
    {
        return $this->tagRepository->searchWithCount($termo, $limite);
    }
    
    /**
     * Retorna artes associadas a uma tag
     * 
     * IMPORTANTE: Retorna arrays associativos (FETCH_ASSOC), NÃO objetos Arte.
     * A view show.php itera sobre estes arrays diretamente.
     * 
     * @param int $tagId ID da tag
     * @return array Array de arrays associativos com dados das artes
     */
    public function getArtesComTag(int $tagId): array
    {
        return $this->tagRepository->getArtesByTag($tagId);
    }
    
    // ==========================================
    // NORMALIZAÇÃO
    // ==========================================
    
    /**
     * Normaliza dados da tag
     * 
     * MELHORIA 3: Agora normaliza descricao (trim) e icone (trim, converte vazio em null)
     * 
     * @param array $dados
     * @return array
     */
    private function normalizarDados(array $dados): array
    {
        // Nome: trim e primeira letra maiúscula
        if (isset($dados['nome'])) {
            $dados['nome'] = ucfirst(mb_strtolower(trim($dados['nome']), 'UTF-8'));
        }
        
        // Cor: normaliza para formato padrão ou aplica default
        if (isset($dados['cor']) && !empty($dados['cor'])) {
            $dados['cor'] = TagValidator::normalizeCor($dados['cor']);
        } else {
            $dados['cor'] = '#6c757d';
        }
        
        // MELHORIA 3: Descrição — trim, converte string vazia em null
        if (isset($dados['descricao'])) {
            $dados['descricao'] = trim($dados['descricao']);
            if ($dados['descricao'] === '') {
                $dados['descricao'] = null; // Banco: NULL ao invés de string vazia
            }
        }
        
        // MELHORIA 3: Ícone — trim, converte string vazia em null
        if (isset($dados['icone'])) {
            $dados['icone'] = trim($dados['icone']);
            if ($dados['icone'] === '') {
                $dados['icone'] = null; // Banco: NULL ao invés de string vazia
            }
        }
        
        return $dados;
    }
    
    // ==========================================
    // RELACIONAMENTOS COM ARTES
    // ==========================================
    
    /**
     * Retorna tags de uma arte
     * 
     * @param int $arteId
     * @return array
     */
    public function getTagsArte(int $arteId): array
    {
        return $this->tagRepository->getByArte($arteId);
    }
    
    /**
     * Retorna IDs das tags de uma arte
     * 
     * @param int $arteId
     * @return array
     */
    public function getTagIdsArte(int $arteId): array
    {
        return $this->tagRepository->getIdsByArte($arteId);
    }
    
    /**
     * Sincroniza tags de uma arte
     * 
     * @param int $arteId
     * @param array $tagIds
     * @return void
     */
    public function sincronizarTagsArte(int $arteId, array $tagIds): void
    {
        $this->tagRepository->sincronizarTags($arteId, $tagIds);
    }
    
    // ==========================================
    // CONSULTAS ESPECIAIS
    // ==========================================
    
    /**
     * Retorna tags mais usadas
     * 
     * @param int $limite
     * @return array
     */
    public function getMaisUsadas(int $limite = 10): array
    {
        return $this->tagRepository->getMaisUsadas($limite);
    }
    
    /**
     * ============================================
     * MELHORIA 6: Retorna contagem de artes por tag para gráfico
     * ============================================
     * 
     * Wrapper do Repository::getContagemPorTag().
     * Retorna dados formatados para Chart.js na view index.php.
     * 
     * Cada item contém: nome da tag, cor hexadecimal, quantidade de artes.
     * Ordenado por quantidade DESC (tags mais populares primeiro).
     * 
     * @return array [{nome, cor, quantidade}]
     */
    public function getContagemPorTag(): array
    {
        return $this->tagRepository->getContagemPorTag();
    }
    
    /**
     * Retorna tags para select (ID => Nome)
     * 
     * @return array
     */
    public function getParaSelect(): array
    {
        $tags = $this->tagRepository->allOrdered();
        
        $resultado = [];
        foreach ($tags as $tag) {
            $resultado[$tag->getId()] = $tag->getNome();
        }
        
        return $resultado;
    }
    
    /**
     * Retorna cores predefinidas para seleção
     * 
     * @return array
     */
    public function getCoresPredefinidas(): array
    {
        return TagValidator::getCoresPredefinidas();
    }
    
    /**
     * MELHORIA 3: Retorna ícones disponíveis para seleção
     * 
     * @return array
     */
    public function getIconesDisponiveis(): array
    {
        return TagValidator::getIconesDisponiveis();
    }
    
    /**
     * Cria tag se não existir
     * 
     * @param string $nome
     * @param string $cor
     * @return Tag
     */
    public function criarSeNaoExistir(string $nome, string $cor = '#6c757d'): Tag
    {
        return $this->tagRepository->findOrCreate($nome, $cor);
    }
    
    /**
     * Cria múltiplas tags a partir de string separada por vírgula
     * 
     * @param string $tagsString Ex: "Paisagem, Retrato, Abstrato"
     * @return array IDs das tags criadas/encontradas
     */
    public function criarDeString(string $tagsString): array
    {
        $nomes = array_map('trim', explode(',', $tagsString));
        $nomes = array_filter($nomes); // Remove vazios
        
        $tagIds = [];
        foreach ($nomes as $nome) {
            if (strlen($nome) >= 2) {
                $tag = $this->criarSeNaoExistir($nome);
                $tagIds[] = $tag->getId();
            }
        }
        
        return $tagIds;
    }
}