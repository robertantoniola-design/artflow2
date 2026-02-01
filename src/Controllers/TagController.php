<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TagService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * TAG CONTROLLER
 * ============================================
 * 
 * Controller responsável pelas operações de Tags.
 * Tags são usadas para categorizar artes.
 */
class TagController extends BaseController
{
    private TagService $tagService;
    
    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }
    
    /**
     * Lista todas as tags
     * GET /tags
     */
    public function index(Request $request): Response
    {
        $filtros = [
            'termo' => $request->get('termo'),
            'ordenar' => $request->get('ordenar', 'nome'),
            'direcao' => $request->get('direcao', 'ASC')
        ];
        
        // Se busca por termo, pesquisa; senão lista todas com contagem
        if (!empty($filtros['termo'])) {
            $tags = $this->tagService->pesquisar($filtros['termo']);
        } else {
            $tags = $this->tagService->listarComContagem();
        }
        
        // Tags mais usadas para destaque
        $maisUsadas = $this->tagService->getMaisUsadas(5);
        
        if ($request->wantsJson()) {
            return $this->json([
                'success' => true,
                'data' => $tags,
                'mais_usadas' => $maisUsadas
            ]);
        }
        
        return $this->view('tags/index', [
            'titulo' => 'Tags',
            'tags' => $tags,
            'maisUsadas' => $maisUsadas,
            'filtros' => $filtros,
            'coresPredefinidas' => $this->getCoresPredefinidas()
        ]);
    }
    
    /**
     * Formulário de nova tag
     * GET /tags/criar
     */
    public function create(Request $request): Response
    {
        return $this->view('tags/create', [
            'titulo' => 'Nova Tag',
            'coresPredefinidas' => $this->getCoresPredefinidas()
        ]);
    }
    
    /**
     * Salva nova tag
     * POST /tags
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only(['nome', 'cor']);
            $tag = $this->tagService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->success('Tag criada!', [
                    'id' => $tag->getId(),
                    'nome' => $tag->getNome(),
                    'cor' => $tag->getCor()
                ]);
            }
            
            $this->flashSuccess("Tag \"{$tag->getNome()}\" criada com sucesso!");
            return $this->redirectTo('/tags');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
        }
    }
    
    /**
     * Exibe detalhes da tag com artes associadas
     * GET /tags/{id}
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $tag = $this->tagService->buscar($id);
            $artes = $this->tagService->getArtesComTag($id);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'data' => $tag->toArray(),
                    'artes' => array_map(fn($a) => $a->toArray(), $artes)
                ]);
            }
            
            return $this->view('tags/show', [
                'titulo' => "Tag: {$tag->getNome()}",
                'tag' => $tag,
                'artes' => $artes
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Tag não encontrada');
        }
    }
    
    /**
     * Formulário de edição
     * GET /tags/{id}/editar
     */
    public function edit(Request $request, int $id): Response
    {
        try {
            $tag = $this->tagService->buscar($id);
            
            return $this->view('tags/edit', [
                'titulo' => 'Editar Tag',
                'tag' => $tag,
                'coresPredefinidas' => $this->getCoresPredefinidas()
            ]);
            
        } catch (NotFoundException $e) {
            $this->flashError('Tag não encontrada');
            return $this->redirectTo('/tags');
        }
    }
    
    /**
     * Atualiza tag
     * PUT /tags/{id}
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $dados = $request->only(['nome', 'cor']);
            $tag = $this->tagService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->success('Tag atualizada!');
            }
            
            $this->flashSuccess('Tag atualizada com sucesso!');
            return $this->redirectTo('/tags');
            
        } catch (ValidationException $e) {
            if ($request->wantsJson()) {
                return $this->error('Erro de validação', $e->getErrors(), 422);
            }
            
            return $this->back()->withErrors($e->getErrors())->withInput();
            
        } catch (NotFoundException $e) {
            return $this->notFound('Tag não encontrada');
        }
    }
    
    /**
     * Remove tag
     * DELETE /tags/{id}
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            $tag = $this->tagService->buscar($id);
            $nome = $tag->getNome();
            
            $this->tagService->remover($id);
            
            if ($request->wantsJson()) {
                return $this->success('Tag removida!');
            }
            
            $this->flashSuccess("Tag \"{$nome}\" removida com sucesso!");
            return $this->redirectTo('/tags');
            
        } catch (NotFoundException $e) {
            return $this->notFound('Tag não encontrada');
        }
    }
    
    // ==========================================
    // ENDPOINTS AJAX
    // ==========================================
    
    /**
     * Busca tags para autocomplete
     * GET /tags/buscar?termo=xxx
     */
    public function buscar(Request $request): Response
    {
        $termo = $request->get('termo', '');
        $limite = (int) $request->get('limite', 10);
        
        if (strlen($termo) < 1) {
            return $this->json([]);
        }
        
        $tags = $this->tagService->pesquisar($termo, $limite);
        
        return $this->json(array_map(function($tag) {
            return [
                'id' => $tag['id'],
                'nome' => $tag['nome'],
                'cor' => $tag['cor'],
                'total_artes' => $tag['total_artes'] ?? 0
            ];
        }, $tags));
    }
    
    /**
     * Lista tags para select (dropdown)
     * GET /tags/select
     */
    public function select(Request $request): Response
    {
        $tags = $this->tagService->getParaSelect();
        return $this->json($tags);
    }
    
    /**
     * Cria tag rapidamente (inline)
     * POST /tags/rapida
     */
    public function criarRapida(Request $request): Response
    {
        try {
            $nome = $request->get('nome');
            $cor = $request->get('cor', '#6c757d');
            
            $tag = $this->tagService->criarSeNaoExistir($nome, $cor);
            
            return $this->json([
                'success' => true,
                'tag' => $tag->toArray(),
                'message' => 'Tag criada!'
            ]);
            
        } catch (ValidationException $e) {
            return $this->error('Erro de validação', $e->getErrors(), 422);
        }
    }
    
    // ==========================================
    // HELPERS
    // ==========================================
    
    /**
     * Cores predefinidas para seleção
     */
    private function getCoresPredefinidas(): array
    {
        return [
            '#dc3545' => 'Vermelho',
            '#fd7e14' => 'Laranja',
            '#ffc107' => 'Amarelo',
            '#28a745' => 'Verde',
            '#20c997' => 'Verde-água',
            '#17a2b8' => 'Ciano',
            '#007bff' => 'Azul',
            '#6610f2' => 'Índigo',
            '#6f42c1' => 'Roxo',
            '#e83e8c' => 'Rosa',
            '#6c757d' => 'Cinza',
            '#343a40' => 'Escuro'
        ];
    }
}
