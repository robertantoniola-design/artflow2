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
        
        // Validação
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
        
        // Remove tag e todas associações
        return $this->tagRepository->deleteWithRelations($id);
    }
    
    // ==========================================
    // NORMALIZAÇÃO
    // ==========================================
    
    /**
     * Normaliza dados da tag
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
        
        // Cor: normaliza para formato padrão
        if (isset($dados['cor'])) {
            $dados['cor'] = TagValidator::normalizeCor($dados['cor']);
        } else {
            // Cor padrão se não fornecida
            $dados['cor'] = $dados['cor'] ?? '#6c757d';
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
        // Filtra IDs válidos
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
