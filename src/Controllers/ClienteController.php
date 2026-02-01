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
 * Controller responsável pelas operações de Clientes.
 */
class ClienteController extends BaseController
{
    private ClienteService $clienteService;
    
    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }
    
    /**
     * Lista todos os clientes
     * GET /clientes
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'termo' => $request->get('q')
        ];
        
        $clientes = $this->clienteService->listar($filtros);
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => array_map(fn($c) => $c->toArray(), $clientes)
            ]);
        }
        
        return $this->view('clientes/index', [
            'titulo' => 'Clientes',
            'clientes' => $clientes,
            'filtros' => $filtros,
            'total' => count($clientes)
        ]);
    }
    
    /**
     * Formulário de criação
     * GET /clientes/criar
     */
    public function create(Request $request): Response
    {
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
            $dados = $request->only(['nome', 'email', 'telefone', 'empresa']);
            $cliente = $this->clienteService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Cliente cadastrado!', ['id' => $cliente->getId()]);
            }
            
            $this->flashSuccess('Cliente "' . $cliente->getNome() . '" cadastrado!');
            return $this->redirectTo('/clientes');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
        }
    }
    
    /**
     * Exibe detalhes do cliente
     * GET /clientes/{id}
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $cliente = $this->clienteService->buscar($id);
            
            if ($request->wantsJson()) {
                return $this->json(['success' => true, 'data' => $cliente->toArray()]);
            }
            
            return $this->view('clientes/show', [
                'titulo' => $cliente->getNome(),
                'cliente' => $cliente
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
            $dados = $request->only(['nome', 'email', 'telefone', 'empresa']);
            $cliente = $this->clienteService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Cliente atualizado!');
            }
            
            $this->flashSuccess('Cliente atualizado com sucesso!');
            return $this->redirectTo('/clientes/' . $id);
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
            
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
