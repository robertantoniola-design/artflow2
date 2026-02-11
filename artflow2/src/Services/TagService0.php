<?php

namespace App\Services;

use App\Models\Tag;
use App\Repositories\TagRepository;
use App\Validators\TagValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * TAG SERVICE
 * ============================================
 * 
 * Camada de lógica de negócio para Tags.
 * 
 * Responsabilidades:
 * - Validar dados de entrada
 * - Garantir unicidade de nomes
 * - Normalizar cores
 * - Gerenciar relacionamentos com artes
 * - Paginação e ordenação (Melhoria 1+2)
 * 
 * CORREÇÕES APLICADAS:
 * - [07/02/2026] Adicionado pesquisar() — chamado pelo TagController::index() e buscar()
 * - [07/02/2026] Adicionado getArtesComTag() — chamado pelo TagController::show()
 * - [07/02/2026] Fix normalizarDados() — lógica de cor padrão corrigida
 * 
 * MELHORIAS APLICADAS:
 * - [07/02/2026] Melhoria 1+2: listarPaginado() — paginação + ordenação dinâmica
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
    
    // ==========================================
    // PAGINAÇÃO E ORDENAÇÃO (Melhoria 1+2)
    // ==========================================

    /**
     * ============================================
     * MELHORIA 1+2: Lista tags paginadas com ordenação
     * ============================================
     * 
     * Método principal para a listagem paginada de tags.
     * Combina dados + metadados de paginação num único retorno.
     * 
     * O Controller chama este método e recebe tudo pronto:
     * - 'tags': array de objetos Tag (para a view iterar)
     * - 'paginacao': array com dados para renderizar controles
     * 
     * FLUXO:
     * 1. Busca tags paginadas no Repository (com contagem + ordenação)
     * 2. Conta total de registros (com mesmo filtro) para calcular páginas
     * 3. Monta array de metadados de paginação
     * 4. Retorna tudo empacotado
     * 
     * @param int $page Página atual (1-based, mínimo 1)
     * @param int $perPage Itens por página (default 12)
     * @param string $ordenar Coluna de ordenação: 'nome'|'data'|'contagem'
     * @param string $direcao Direção: 'ASC'|'DESC'
     * @param string|null $termo Filtro de busca por nome (LIKE)
     * @return array ['tags' => Tag[], 'paginacao' => [...]]
     */
    public function listarPaginado(
        int $page = 1,
        int $perPage = 12,
        string $ordenar = 'nome',
        string $direcao = 'ASC',
        ?string $termo = null
    ): array {
        // 1. Busca tags da página atual (já hidratadas como objetos Tag)
        $tags = $this->tagRepository->allWithCountPaginated(
            $page, $perPage, $ordenar, $direcao, $termo
        );

        // 2. Conta total de registros (aplicando mesmo filtro de busca)
        $total = $this->tagRepository->countAll($termo);

        // 3. Calcula total de páginas (ceil garante arredondamento para cima)
        // Ex: 25 registros / 12 por página = ceil(2.08) = 3 páginas
        $totalPaginas = $total > 0 ? (int) ceil($total / $perPage) : 1;

        // 4. Monta metadados de paginação para a view
        return [
            'tags' => $tags,
            'paginacao' => [
                'pagina_atual'    => $page,
                'por_pagina'      => $perPage,
                'total_registros' => $total,
                'total_paginas'   => $totalPaginas,
                'tem_anterior'    => $page > 1,
                'tem_proxima'     => $page < $totalPaginas,
            ]
        ];
    }
    
    // ==========================================
    // CRUD (continuação)
    // ==========================================
    
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
     * @param array $dados
     * @return Tag
     * @throws ValidationException
     */
    public function criar(array $dados): Tag
    {
        // Validação
        $this->validator->validate($dados);
        
        // Verifica unicidade do nome
        if ($this->tagRepository->nomeExists($dados['nome'])) {
            throw new ValidationException([
                'nome' => 'Já existe uma tag com este nome'
            ]);
        }
        
        // Normaliza dados
        $dados = $this->normalizarDados($dados);
        
        return $this->tagRepository->create($dados);
    }
    
    /**
     * Atualiza tag existente
     * 
     * @param int $id
     * @param array $dados
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
    // BUSCA E PESQUISA
    // ==========================================
    
    /**
     * ============================================
     * FASE 1: Pesquisa tags por termo
     * ============================================
     * 
     * Método chamado pelo TagController::index() quando há filtro de busca
     * e pelo TagController::buscar() no endpoint AJAX de autocomplete.
     * 
     * Retorna arrays associativos (não objetos Tag) para compatibilidade
     * com o endpoint AJAX que espera arrays com chaves string.
     * 
     * @param string $termo Texto para buscar (LIKE %termo%)
     * @param int $limite Máximo de resultados (default 50)
     * @return array Array de arrays associativos com dados das tags
     */
    public function pesquisar(string $termo, int $limite = 50): array
    {
        $tags = $this->tagRepository->searchWithCount($termo, $limite);
        
        // Converte objetos Tag para arrays (compatibilidade com AJAX)
        return array_map(function(Tag $tag) {
            return [
                'id'          => $tag->getId(),
                'nome'        => $tag->getNome(),
                'cor'         => $tag->getCor(),
                'total_artes' => $tag->getArtesCount(),
            ];
        }, $tags);
    }
    
    /**
     * ============================================
     * FASE 1: Retorna artes associadas a uma tag
     * ============================================
     * 
     * Chamado pelo TagController::show() para exibir artes na página
     * de detalhes da tag. Delega para o Repository que retorna
     * FETCH_ASSOC (arrays, não objetos Arte).
     * 
     * IMPORTANTE: A view show.php DEVE usar acesso por array ($arte['campo']),
     * não por métodos ($arte->getCampo()).
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
     * FIX [07/02/2026]: Corrigida lógica da cor padrão.
     * ANTES: O else+?? nunca executava (se !isset, ?? também não resolve)
     * AGORA: Verifica isset separadamente e atribui default quando ausente
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
            // Cor fornecida: normaliza formato hex
            $dados['cor'] = TagValidator::normalizeCor($dados['cor']);
        } else {
            // Cor não fornecida ou vazia: aplica cor padrão cinza
            $dados['cor'] = '#6c757d';
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
     */
    public function syncArte(int $arteId, array $tagIds): void
    {
        // Filtra IDs válidos (numéricos e positivos)
        $tagIds = array_filter($tagIds, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        $this->tagRepository->syncArte($arteId, $tagIds);
    }
    
    /**
     * Adiciona tag a uma arte
     * 
     * @param int $arteId
     * @param int $tagId
     * @return bool
     */
    public function attachArte(int $arteId, int $tagId): bool
    {
        return $this->tagRepository->attachArte($arteId, $tagId);
    }
    
    /**
     * Remove tag de uma arte
     * 
     * @param int $arteId
     * @param int $tagId
     * @return bool
     */
    public function detachArte(int $arteId, int $tagId): bool
    {
        return $this->tagRepository->detachArte($arteId, $tagId);
    }
    
    // ==========================================
    // ESTATÍSTICAS E UTILITÁRIOS
    // ==========================================
    
    /**
     * Retorna tags mais usadas
     * 
     * @param int $limit
     * @return array
     */
    public function getMaisUsadas(int $limit = 10): array
    {
        return $this->tagRepository->getMaisUsadas($limit);
    }
    
    /**
     * Retorna contagem de artes por tag (para gráfico)
     * 
     * @return array
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