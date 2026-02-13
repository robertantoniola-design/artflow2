<?php

namespace App\Services;

use App\Models\Cliente;
use App\Repositories\ClienteRepository;
use App\Validators\ClienteValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * CLIENTE SERVICE
 * ============================================
 * 
 * Camada de lógica de negócio para Clientes.
 * 
 * Responsabilidades:
 * - Validar dados de entrada
 * - Garantir unicidade de email
 * - Coordenar operações CRUD
 */
class ClienteService
{
    private ClienteRepository $clienteRepository;
    private ClienteValidator $validator;
    
    public function __construct(
        ClienteRepository $clienteRepository,
        ClienteValidator $validator
    ) {
        $this->clienteRepository = $clienteRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
    /**
     * Lista todos os clientes
     * 
     * @param array $filtros
     * @return array
     */
    public function listar(array $filtros = []): array
    {
        // Busca com termo de pesquisa
        if (!empty($filtros['termo'])) {
            return $this->clienteRepository->search($filtros['termo']);
        }
        
        // Lista todos ordenados por nome
        return $this->clienteRepository->allOrdered();
    }
    
    /**
     * Busca cliente por ID
     * 
     * @param int $id
     * @return Cliente
     * @throws NotFoundException
     */
    public function buscar(int $id): Cliente
    {
        return $this->clienteRepository->findOrFail($id);
    }
    
    /**
     * Cria novo cliente
     * 
     * @param array $dados
     * @return Cliente
     * @throws ValidationException
     */
    public function criar(array $dados): Cliente
    {
        // Validação básica
        $this->validator->validate($dados);
        
        // Verifica unicidade do email
        if (!empty($dados['email'])) {
            $this->validarEmailUnico($dados['email']);
        }
        
        // Normaliza dados
        $dados = $this->normalizarDados($dados);
        
        return $this->clienteRepository->create($dados);
    }
    
    /**
     * Atualiza cliente existente
     * 
     * @param int $id
     * @param array $dados
     * @return Cliente
     * @throws ValidationException|NotFoundException
     */
    public function atualizar(int $id, array $dados): Cliente
    {
        // Verifica se existe
        $cliente = $this->clienteRepository->findOrFail($id);
        
        // Validação
        if (!$this->validator->validateUpdate($dados)) {
            throw new ValidationException($this->validator->getErrors());
        }
        
        // Verifica unicidade do email (excluindo cliente atual)
        if (!empty($dados['email']) && $dados['email'] !== $cliente->getEmail()) {
            $this->validarEmailUnico($dados['email'], $id);
        }
        
        // Normaliza dados
        $dados = $this->normalizarDados($dados);
        
        $this->clienteRepository->update($id, $dados);
        
        return $this->clienteRepository->find($id);
    }
    
    /**
     * Remove cliente
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException|ValidationException
     */
    public function remover(int $id): bool
    {
        // Verifica se existe
        $this->clienteRepository->findOrFail($id);
        
        // Verifica se tem vendas associadas
        if ($this->clienteRepository->hasVendas($id)) {
            throw new ValidationException([
                'cliente' => 'Este cliente possui vendas registradas e não pode ser removido'
            ]);
        }
        
        return $this->clienteRepository->delete($id);
    }
    
    // ==========================================
    // VALIDAÇÕES
    // ==========================================
    
    /**
     * Valida se email é único
     * 
     * @param string $email
     * @param int|null $exceptId
     * @throws ValidationException
     */
    private function validarEmailUnico(string $email, ?int $exceptId = null): void
    {
        if ($this->clienteRepository->emailExists($email, $exceptId)) {
            throw new ValidationException([
                'email' => 'Este e-mail já está cadastrado'
            ]);
        }
    }
    
    // ==========================================
    // NORMALIZAÇÃO
    // ==========================================
    
    /**
     * Normaliza dados do cliente
     * 
     * @param array $dados
     * @return array
     */
    private function normalizarDados(array $dados): array
    {
        // Capitaliza nome
        if (isset($dados['nome'])) {
            $dados['nome'] = mb_convert_case(trim($dados['nome']), MB_CASE_TITLE, 'UTF-8');
        }
        
        // Email em minúsculas
        if (isset($dados['email'])) {
            $dados['email'] = strtolower(trim($dados['email']));
        }
        
        // Remove formatação do telefone para armazenamento
        if (isset($dados['telefone'])) {
            // Mantém apenas números
            $dados['telefone'] = preg_replace('/[^0-9]/', '', $dados['telefone']);
        }
        
        // Trim em campos texto
        if (isset($dados['empresa'])) {
            $dados['empresa'] = trim($dados['empresa']);
        }
        
        return $dados;
    }
    
    // ==========================================
    // BUSCAS ESPECÍFICAS
    // ==========================================
    
    /**
     * Busca cliente por email
     * 
     * @param string $email
     * @return Cliente|null
     */
    public function buscarPorEmail(string $email): ?Cliente
    {
        return $this->clienteRepository->findByEmail($email);
    }
    
    /**
     * Pesquisa clientes
     * 
     * @param string $termo
     * @return array
     */
    public function pesquisar(string $termo): array
    {
        return $this->clienteRepository->search($termo);
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna total de clientes
     * 
     * @return int
     */
    public function getTotal(): int
    {
        return $this->clienteRepository->count();
    }
    
    /**
     * Retorna clientes com mais compras
     * 
     * @param int $limit
     * @return array
     */
    public function getTopClientes(int $limit = 10): array
    {
        return $this->clienteRepository->getTopCompradores($limit);
    }
    
    /**
     * Retorna clientes para select (ID e nome)
     * 
     * @return array
     */
    public function getParaSelect(): array
    {
        $clientes = $this->clienteRepository->allOrdered();
        
        $resultado = [];
        foreach ($clientes as $cliente) {
            $resultado[$cliente->getId()] = $cliente->getNome();
        }
        
        return $resultado;
    }
}
