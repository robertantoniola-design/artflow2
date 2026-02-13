<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ClienteService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * CLIENTE CONTROLLER
 * ============================================
 * 
 * FASE 1 - CORREÇÕES (13/02/2026):
 * - B1: index() lê 'termo' (antes lia 'q')
 * - B2: store()/update() capturam TODOS os campos da migration
 * - B4: show() busca e passa histórico de compras
 * - B8: Erros/old input salvos direto em $_SESSION
 * - B9: limparDadosFormulario() em edit/index/show (NÃO em create!)
 * 
 * MELHORIA 1 (13/02/2026): Paginação no index()
 * 
 * FLUXO DE VALIDAÇÃO:
 *   POST store() → ValidationException → $_SESSION['_errors'] + back()
 *   → GET create() → form lê errors()/old() → exibe erros + dados anteriores
 *   → Navegação para index/edit/show → limpa dados residuais
 */
class ClienteController extends BaseController
{
    private ClienteService $clienteService;
    
    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }
    
    /**
     * Limpa dados residuais de formulários anteriores
     * 
     * IMPORTANTE: Chamado em index(), edit() e show() — NUNCA em create()!
     */
    private function limparDadosFormulario(): void
    {
        unset($_SESSION['_old_input'], $_SESSION['_errors']);
    }
    
    /**
     * Lista todos os clientes (COM PAGINAÇÃO)
     * GET /clientes
     * GET /clientes?termo=X&pagina=2
     * 
     * MELHORIA 1: Agora usa listarPaginado() do Service
     */
    public function index(Request $request): Response
    {
        // Limpa dados residuais ao navegar para a lista
        $this->limparDadosFormulario();
        
        // MELHORIA 1: Filtros com suporte a paginação
        $filtros = [
            'termo'   => $request->get('termo'),
            'pagina'  => (int) ($request->get('pagina') ?? 1),
            'ordenar' => $request->get('ordenar') ?? 'nome',
            'direcao' => $request->get('direcao') ?? 'ASC'
        ];
        
        // MELHORIA 1: Usa listarPaginado() em vez de listar()
        $resultado = $this->clienteService->listarPaginado($filtros);
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($c) => $c->toArray(), $resultado['clientes']),
                'paginacao' => $resultado['paginacao']
            ]);
        }
        
        return $this->view('clientes/index', [
            'titulo'    => 'Clientes',
            'clientes'  => $resultado['clientes'],
            'paginacao' => $resultado['paginacao'],
            'filtros'   => $filtros,
            'total'     => $resultado['paginacao']['total']
        ]);
    }
    
    /**
     * Formulário de criação
     * GET /clientes/criar
     * 
     * NÃO limpa $_SESSION aqui!
     */
    public function create(Request $request): Response
    {
        // ⚠️ NÃO chamar limparDadosFormulario() aqui!
        // Os erros de validação do store() precisam chegar ao form.
        
        return $this->view('clientes/create', [
            'titulo' => 'Novo Cliente'
        ]);
    }
    
    /**
     * Salva novo cliente
     * POST /clientes
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            // CORREÇÃO B2: Captura todos os campos da migration 002
            $dados = $request->only([
                'nome', 'email', 'telefone', 'empresa',
                'endereco', 'cidade', 'estado', 'observacoes'
            ]);
            
            $cliente = $this->clienteService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Cliente cadastrado!', ['id' => $cliente->getId()]);
            }
            
            // Sucesso: limpa qualquer resíduo antes de redirecionar
            $this->limparDadosFormulario();
            
            $this->flashSuccess('Cliente "' . $cliente->getNome() . '" cadastrado!');
            return $this->redirectTo('/clientes');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            // CORREÇÃO B8: Escreve direto na sessão (padrão VendaController)
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            return $this->back();
        }
    }
    
    /**
     * Exibe detalhes do cliente
     * GET /clientes/{id}
     */
    public function show(Request $request, int $id): Response
    {
        // Limpa dados residuais
        $this->limparDadosFormulario();
        
        try {
            $cliente = $this->clienteService->buscar($id);
            
            // CORREÇÃO B4: Busca histórico de compras do cliente
            $historicoCompras = $this->clienteService->getHistoricoCompras($id);
            
            // Calcula estatísticas para os cards
            $estatisticas = [
                'total_compras' => count($historicoCompras),
                'valor_total' => array_sum(array_column($historicoCompras, 'valor')),
                'ticket_medio' => count($historicoCompras) > 0 
                    ? array_sum(array_column($historicoCompras, 'valor')) / count($historicoCompras) 
                    : 0
            ];
            
            // Converte vendas para objetos Venda (para a view)
            // Usa Venda::fromArray() para evitar propriedades dinâmicas (PHP 8.2+)
            $vendas = [];
            foreach ($historicoCompras as $venda) {
                $obj = \App\Models\Venda::fromArray($venda);
                $vendas[] = $obj;
            }
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true, 
                    'data' => $cliente->toArray(),
                    'historico_compras' => $historicoCompras,
                    'estatisticas' => $estatisticas
                ]);
            }
            
            return $this->view('clientes/show', [
                'titulo' => $cliente->getNome(),
                'cliente' => $cliente,
                'vendas' => $vendas,
                'historicoCompras' => $historicoCompras, // Array original para compatibilidade
                'estatisticas' => $estatisticas
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Cliente não encontrado');
        }
    }
    
    /**
     * Formulário de edição
     * GET /clientes/{id}/editar
     */
    public function edit(Request $request, int $id): Response
    {
        // Limpa dados residuais — CRÍTICO aqui!
        $this->limparDadosFormulario();
        
        try {
            $cliente = $this->clienteService->buscar($id);
            
            return $this->view('clientes/edit', [
                'titulo' => 'Editar: ' . $cliente->getNome(),
                'cliente' => $cliente
            ]);
            
        } catch (NotFoundException $e) {
            $this->flashError('Cliente não encontrado');
            return $this->redirectTo('/clientes');
        }
    }
    
    /**
     * Atualiza cliente
     * PUT /clientes/{id}
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            // CORREÇÃO B2: Captura todos os campos da migration 002
            $dados = $request->only([
                'nome', 'email', 'telefone', 'empresa',
                'endereco', 'cidade', 'estado', 'observacoes'
            ]);
            
            $cliente = $this->clienteService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Cliente atualizado!');
            }
            
            // Sucesso: limpa qualquer resíduo
            $this->limparDadosFormulario();
            
            $this->flashSuccess('Cliente atualizado com sucesso!');
            return $this->redirectTo('/clientes/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            // CORREÇÃO B8: Escreve direto na sessão
            $_SESSION['_errors'] = $e->getErrors();
            $_SESSION['_old_input'] = $request->all();
            
            // Re-renderiza a view diretamente (não usa back)
            try {
                $cliente = $this->clienteService->buscar($id);
                return $this->view('clientes/edit', [
                    'titulo' => 'Editar: ' . $cliente->getNome(),
                    'cliente' => $cliente
                ]);
            } catch (NotFoundException $e2) {
                return $this->notFound('Cliente não encontrado');
            }
            
        } catch (NotFoundException $e) {
            return $this->notFound('Cliente não encontrado');
        }
    }
    
    /**
     * Remove cliente
     * DELETE /clientes/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $this->clienteService->remover($id);
            
            if ($request->wantsJson()) {
                return $this->success('Cliente removido!');
            }
            
            $this->flashSuccess('Cliente removido com sucesso!');
            return $this->redirectTo('/clientes');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getFirstError());
            }
            
            $this->flashError($e->getFirstError());
            return $this->back();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Cliente não encontrado');
        }
    }
    
    /**
     * Busca clientes para autocomplete
     * GET /clientes/buscar
     */
    public function buscar(Request $request): Response
    {
        $termo = $request->get('q', '');
        $clientes = $this->clienteService->pesquisar($termo);
        
        return $this->json([
            'results' => array_map(fn($c) => [
                'id' => $c->getId(),
                'text' => $c->getNome(),
                'email' => $c->getEmail()
            ], $clientes)
        ]);
    }
}