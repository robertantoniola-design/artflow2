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
 * ARTE SERVICE
 * ============================================
 * 
 * Camada de lógica de negócio para Artes.
 * Orquestra validação, repository e regras de negócio.
 * 
 * Responsabilidades:
 * - Validar dados de entrada
 * - Aplicar regras de negócio
 * - Coordenar operações entre repositories
 * - Calcular métricas
 */
class ArteService
{
    private ArteRepository $arteRepository;
    private TagRepository $tagRepository;
    private ArteValidator $validator;
    
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
     * Lista todas as artes
     * 
     * @param array $filtros Filtros opcionais (status, termo, etc)
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        // Busca com filtro de status
        if (!empty($filtros['status'])) {
            return $this->arteRepository->findByStatus($filtros['status']);
        }
        
        // Busca com termo de pesquisa
        if (!empty($filtros['termo'])) {
            return $this->arteRepository->search(
                $filtros['termo'],
                $filtros['status'] ?? null
            );
        }
        
        // Busca por tag
        if (!empty($filtros['tag_id'])) {
            return $this->arteRepository->findByTag((int) $filtros['tag_id']);
        }
        
        // Lista todas
        return $this->arteRepository->all();
    }
    
    /**
     * Busca arte por ID
     * 
     * @param int $id
     * @return Arte
     * @throws NotFoundException
     */
    public function buscar(int $id): Arte
    {
        return $this->arteRepository->findOrFail($id);
    }
    
    /**
     * Cria nova arte
     * 
     * @param array $dados
     * @return Arte
     * @throws ValidationException
     */
    public function criar(array $dados): Arte
    {
        // Validação
        $this->validator->validate($dados);
        
        // Dados padrão
        $dados['status'] = $dados['status'] ?? 'disponivel';
        $dados['horas_trabalhadas'] = $dados['horas_trabalhadas'] ?? 0;
        $dados['preco_custo'] = $dados['preco_custo'] ?? 0;
        
        // Cria a arte
        $arte = $this->arteRepository->create($dados);
        
        // Associa tags se fornecidas
        if (!empty($dados['tags'])) {
            $this->tagRepository->syncArte($arte->getId(), (array) $dados['tags']);
        }
        
        return $arte;
    }
    
    /**
     * Atualiza arte existente
     * 
     * @param int $id
     * @param array $dados
     * @return Arte
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Arte
    {
        // Verifica se existe
        $arte = $this->arteRepository->findOrFail($id);
        
        // Validação
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Atualiza
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
     * @param int $id
     * @return bool
     * @throws NotFoundException
     */
    public function remover(int $id): bool
    {
        // Verifica se existe
        $arte = $this->arteRepository->findOrFail($id);
        
        // Verifica se pode ser removida (não vendida)
        if ($arte->getStatus() === 'vendida') {
            throw new ValidationException([
                'arte' => 'Artes vendidas não podem ser removidas'
            ]);
        }
        
        // Remove associações com tags
        $this->tagRepository->syncArte($id, []);
        
        // Remove a arte
        return $this->arteRepository->delete($id);
    }
    
    // ==========================================
    // OPERAÇÕES DE STATUS
    // ==========================================
    
    /**
     * Altera status da arte
     * 
     * @param int $id
     * @param string $novoStatus
     * @return Arte
     */
    public function alterarStatus(int $id, string $novoStatus): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        // Valida transição de status
        $this->validarTransicaoStatus($arte->getStatus(), $novoStatus);
        
        $this->arteRepository->update($id, ['status' => $novoStatus]);
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Valida se transição de status é permitida
     * 
     * @param string $atual
     * @param string $novo
     * @throws ValidationException
     */
    private function validarTransicaoStatus(string $atual, string $novo): void
    {
        // Regras de transição
        $transicoesPermitidas = [
            'disponivel' => ['em_producao', 'vendida'],
            'em_producao' => ['disponivel', 'vendida'],
            'vendida' => [] // Vendida é estado final
        ];
        
        if (!in_array($novo, $transicoesPermitidas[$atual] ?? [])) {
            throw new ValidationException([
                'status' => "Não é possível mudar de '{$atual}' para '{$novo}'"
            ]);
        }
    }
    
    // ==========================================
    // OPERAÇÕES DE TEMPO/HORAS
    // ==========================================
    
    /**
     * Adiciona horas trabalhadas à arte
     * 
     * @param int $id
     * @param float $horas
     * @return Arte
     */
    public function adicionarHoras(int $id, float $horas): Arte
    {
        $arte = $this->arteRepository->findOrFail($id);
        
        if ($horas <= 0) {
            throw new ValidationException([
                'horas' => 'As horas devem ser maiores que zero'
            ]);
        }
        
        $novasHoras = $arte->getHorasTrabalhadas() + $horas;
        $this->arteRepository->update($id, ['horas_trabalhadas' => $novasHoras]);
        
        return $this->arteRepository->find($id);
    }
    
    /**
     * Define horas trabalhadas da arte
     * 
     * @param int $id
     * @param float $horas
     * @return Arte
     */
    public function definirHoras(int $id, float $horas): Arte
    {
        $this->arteRepository->findOrFail($id);
        
        if ($horas < 0) {
            throw new ValidationException([
                'horas' => 'As horas não podem ser negativas'
            ]);
        }
        
        $this->arteRepository->update($id, ['horas_trabalhadas' => $horas]);
        
        return $this->arteRepository->find($id);
    }
    
    // ==========================================
    // CÁLCULOS E MÉTRICAS
    // ==========================================
    
    /**
     * Calcula custo por hora da arte
     * 
     * @param Arte $arte
     * @return float
     */
    public function calcularCustoPorHora(Arte $arte): float
    {
        $horas = $arte->getHorasTrabalhadas();
        
        if ($horas <= 0) {
            return 0;
        }
        
        return $arte->getPrecoCusto() / $horas;
    }
    
    /**
     * Calcula preço sugerido de venda (baseado em margem desejada)
     * 
     * @param Arte $arte
     * @param float $margemDesejada Percentual (ex: 50 para 50%)
     * @param float $valorHoraMinimo Valor mínimo da hora de trabalho
     * @return float
     */
    public function calcularPrecoSugerido(Arte $arte, float $margemDesejada = 50, float $valorHoraMinimo = 50): float
    {
        $custo = $arte->getPrecoCusto();
        $horas = $arte->getHorasTrabalhadas();
        
        // Custo de mão de obra
        $custoMaoObra = $horas * $valorHoraMinimo;
        
        // Custo total
        $custoTotal = $custo + $custoMaoObra;
        
        // Aplica margem
        $precoSugerido = $custoTotal * (1 + ($margemDesejada / 100));
        
        return round($precoSugerido, 2);
    }
    
    /**
     * Retorna estatísticas gerais das artes
     * 
     * @return array
     */
    public function getEstatisticas(): array
    {
        return $this->arteRepository->getEstatisticas();
    }
    
    /**
     * Retorna artes disponíveis para venda
     * 
     * @return array
     */
    public function getDisponiveisParaVenda(): array
    {
        // Retorna artes disponíveis ou em produção
        $disponiveis = $this->arteRepository->findByStatus('disponivel');
        $emProducao = $this->arteRepository->findByStatus('em_producao');
        
        return array_merge($disponiveis, $emProducao);
    }
    
    // ==========================================
    // TAGS
    // ==========================================
    
    /**
     * Retorna tags de uma arte
     * 
     * @param int $arteId
     * @return array
     */
    public function getTags(int $arteId): array
    {
        return $this->tagRepository->getByArte($arteId);
    }
    
    /**
     * Atualiza tags de uma arte
     * 
     * @param int $arteId
     * @param array $tagIds
     * @return void
     */
    public function atualizarTags(int $arteId, array $tagIds): void
    {
        $this->arteRepository->findOrFail($arteId);
        $this->tagRepository->syncArte($arteId, $tagIds);
    }
    
    /**
     * Pesquisa artes por termo (alias para listar com filtros)
     * 
     * @param array $filtros
     * @param int $limit
     * @return array
     */
    public function pesquisar(array $filtros = [], int $limit = 10): array
    {
        $artes = $this->listar($filtros);
        return array_slice($artes, 0, $limit);
    }
}
