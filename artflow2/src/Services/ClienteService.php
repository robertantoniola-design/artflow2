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
 * FASE 1 (12/02/2026): Correções B4, normalizarDados expandido
 * MELHORIA 1 (13/02/2026): Adicionado listarPaginado()
 * 
 * Camada de lógica de negócio para Clientes.
 */
class ClienteService
{
    private ClienteRepository $clienteRepository;
    private ClienteValidator $validator;
    
    // Configuração de paginação (mesmo padrão do Tags)
    private const POR_PAGINA = 12;
    
    public function __construct(
        ClienteRepository $clienteRepository,
        ClienteValidator $validator
    ) {
        $this->clienteRepository = $clienteRepository;
        $this->validator = $validator;
    }
    
    // ==========================================
    // LISTAGEM COM PAGINAÇÃO (MELHORIA 1)
    // ==========================================
    
    /**
     * Lista clientes com paginação
     * 
     * Retorna array com clientes e dados de paginação.
     * Padrão: 12 clientes por página (mesmo que Tags).
     * 
     * @param array $filtros [
     *     'termo'     => string|null,  // Termo de busca
     *     'pagina'    => int,          // Página atual (default: 1)
     *     'ordenar'   => string,       // Campo: nome, email, cidade, created_at
     *     'direcao'   => string        // ASC ou DESC
     * ]
     * @return array [
     *     'clientes'  => array,        // Objetos Cliente da página atual
     *     'paginacao' => [
     *         'total'       => int,    // Total de registros
     *         'porPagina'   => int,    // Itens por página (12)
     *         'paginaAtual' => int,    // Página atual
     *         'totalPaginas'=> int,    // Total de páginas
     *         'temAnterior' => bool,   // Tem página anterior?
     *         'temProxima'  => bool    // Tem próxima página?
     *     ]
     * ]
     */
    public function listarPaginado(array $filtros = []): array
    {
        // Extrai parâmetros com defaults
        $termo = $filtros['termo'] ?? null;
        $pagina = max(1, (int)($filtros['pagina'] ?? 1));
        $ordenar = $filtros['ordenar'] ?? 'nome';
        $direcao = $filtros['direcao'] ?? 'ASC';
        
        // Busca total de registros (com ou sem filtro)
        $total = $this->clienteRepository->countAll($termo);
        
        // Calcula total de páginas
        $totalPaginas = (int) ceil($total / self::POR_PAGINA);
        
        // Ajusta página se exceder o total
        if ($totalPaginas > 0 && $pagina > $totalPaginas) {
            $pagina = $totalPaginas;
        }
        
        // Busca clientes da página atual
        $clientes = $this->clienteRepository->allPaginated(
            $pagina,
            self::POR_PAGINA,
            $termo,
            $ordenar,
            $direcao
        );
        
        return [
            'clientes' => $clientes,
            'paginacao' => [
                'total'        => $total,
                'porPagina'    => self::POR_PAGINA,
                'paginaAtual'  => $pagina,
                'totalPaginas' => $totalPaginas,
                'temAnterior'  => $pagina > 1,
                'temProxima'   => $pagina < $totalPaginas
            ]
        ];
    }
    
    /**
     * Lista todos os clientes (sem paginação)
     * 
     * Mantido para compatibilidade. Para listagens grandes,
     * prefira listarPaginado().
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
    
    // ==========================================
    // OPERAÇÕES CRUD
    // ==========================================
    
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
        
        // Normaliza dados antes de salvar
        $dados = $this->normalizarDados($dados);
        
        return $this->clienteRepository->create($dados);
    }
    
    /**
     * Atualiza cliente existente
     * 
     * @param int $id
     * @param array $dados
     * @return Cliente
     * @throws ValidationException
     * @throws NotFoundException
     */
    public function atualizar(int $id, array $dados): Cliente
    {
        // Busca cliente existente
        $cliente = $this->clienteRepository->findOrFail($id);
        
        // Validação flexível para update
        $this->validator->validateUpdate($dados);
        
        // Verifica unicidade do email (exceto o próprio)
        if (!empty($dados['email']) && $dados['email'] !== $cliente->getEmail()) {
            $this->validarEmailUnico($dados['email'], $id);
        }
        
        // Normaliza dados
        $dados = $this->normalizarDados($dados);
        
        return $this->clienteRepository->update($id, $dados);
    }
    
    /**
     * Remove cliente
     * 
     * @param int $id
     * @return bool
     * @throws NotFoundException
     * @throws ValidationException Se cliente tem vendas
     */
    public function remover(int $id): bool
    {
        // Verifica existência
        $this->clienteRepository->findOrFail($id);
        
        // CORREÇÃO B6: Verifica se tem vendas antes de excluir
        if ($this->clienteRepository->hasVendas($id)) {
            throw new ValidationException([
                'cliente' => 'Este cliente possui vendas registradas e não pode ser excluído.'
            ]);
        }
        
        return $this->clienteRepository->delete($id);
    }
    
    // ==========================================
    // VALIDAÇÕES
    // ==========================================
    
    /**
     * Valida unicidade de email
     * 
     * CORREÇÃO B6: emailExists() não existia no Repository.
     * 
     * @param string $email
     * @param int|null $exceptId ID a ignorar (para edição)
     * @throws ValidationException
     */
    private function validarEmailUnico(string $email, ?int $exceptId = null): void
    {
        if ($this->clienteRepository->emailExists($email, $exceptId)) {
            throw new ValidationException([
                'email' => 'Este e-mail já está cadastrado para outro cliente.'
            ]);
        }
    }
    
    /**
     * Normaliza dados antes de salvar
     * 
     * FASE 1: Agora trata todos os 8 campos
     */
    private function normalizarDados(array $dados): array
    {
        // Nome: Title Case
        if (isset($dados['nome'])) {
            $dados['nome'] = mb_convert_case(trim($dados['nome']), MB_CASE_TITLE, 'UTF-8');
        }
        
        // Email: minúsculas
        if (isset($dados['email'])) {
            $dados['email'] = strtolower(trim($dados['email']));
        }
        
        // Telefone: apenas dígitos (mantém formatação no banco)
        if (isset($dados['telefone'])) {
            // Remove tudo exceto dígitos para validação, mas mantém original
            $dados['telefone'] = trim($dados['telefone']);
        }
        
        // Empresa: trim
        if (isset($dados['empresa'])) {
            $dados['empresa'] = trim($dados['empresa']);
        }
        
        // Endereço: trim
        if (isset($dados['endereco'])) {
            $dados['endereco'] = trim($dados['endereco']);
        }
        
        // Cidade: Title Case
        if (isset($dados['cidade'])) {
            $dados['cidade'] = mb_convert_case(trim($dados['cidade']), MB_CASE_TITLE, 'UTF-8');
        }
        
        // Estado: MAIÚSCULAS (UF: PR, SP, RJ...)
        if (isset($dados['estado'])) {
            $dados['estado'] = mb_strtoupper(trim($dados['estado']), 'UTF-8');
        }
        
        // Observações: trim
        if (isset($dados['observacoes'])) {
            $dados['observacoes'] = trim($dados['observacoes']);
        }
        
        return $dados;
    }
    
    // ==========================================
    // BUSCAS ESPECÍFICAS
    // ==========================================
    
    /**
     * Busca cliente por email
     */
    public function buscarPorEmail(string $email): ?Cliente
    {
        return $this->clienteRepository->findByEmail($email);
    }
    
    /**
     * Pesquisa clientes por termo
     */
    public function pesquisar(string $termo): array
    {
        return $this->clienteRepository->search($termo);
    }
    
    /**
     * Retorna histórico de compras de um cliente
     * 
     * CORREÇÃO B4: Método para buscar vendas do cliente.
     */
    public function getHistoricoCompras(int $clienteId): array
    {
        return $this->clienteRepository->getHistoricoCompras($clienteId);
    }
    
    // ==========================================
    // ESTATÍSTICAS
    // ==========================================
    
    /**
     * Retorna total de clientes
     */
    public function getTotal(): int
    {
        return $this->clienteRepository->count();
    }
    
    /**
     * Retorna clientes com mais compras
     */
    public function getTopClientes(int $limit = 10): array
    {
        return $this->clienteRepository->getTopCompradores($limit);
    }
    
    /**
     * Retorna clientes para select (ID => nome)
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