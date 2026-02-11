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
 * 
 * MELHORIAS APLICADAS:
 * - [07/02/2026] Melhoria 1+2: index() agora usa listarPaginado()
 *   com paginação (?page=X) e ordenação (?ordenar=&direcao=)
 */
class TagController extends BaseController
{
    private TagService $tagService;
    
    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }
    
    /**
     * Lista todas as tags com paginação e ordenação
     * GET /tags
     * GET /tags?page=2&ordenar=contagem&direcao=DESC&termo=Aqua
     * 
     * MELHORIA 1: Paginação via ?page=X (12 tags por página)
     * MELHORIA 2: Ordenação via ?ordenar=nome|data|contagem&direcao=ASC|DESC
     */
    public function index(Request $request): Response
    {
        // ── Lê TODOS os filtros da URL ──
        $filtros = [
            'termo'   => $request->get('termo'),                         // Busca por nome
            'ordenar' => $request->get('ordenar', 'nome'),               // Coluna: nome|data|contagem
            'direcao' => $request->get('direcao', 'ASC'),                // Direção: ASC|DESC
            'page'    => max(1, (int) $request->get('page', 1)),         // Página: mínimo 1
        ];

        // ── Busca paginada com ordenação (unifica busca + listagem) ──
        // O Service retorna ['tags' => [...], 'paginacao' => [...]]
        $resultado = $this->tagService->listarPaginado(
            $filtros['page'],           // Página atual
            12,                         // 12 tags por página (3 linhas × 4 colunas em XL)
            $filtros['ordenar'],        // Coluna de ordenação
            $filtros['direcao'],        // ASC ou DESC
            !empty($filtros['termo']) ? $filtros['termo'] : null  // Termo de busca (null se vazio)
        );

        // Tags mais usadas para a seção de destaque (independe da paginação)
        $maisUsadas = $this->tagService->getMaisUsadas(5);

        // ── Resposta AJAX (mantém compatibilidade) ──
        if ($request->wantsJson()) {
            return $this->json([
                'success'     => true,
                'data'        => $resultado['tags'],
                'paginacao'   => $resultado['paginacao'],
                'mais_usadas' => $maisUsadas
            ]);
        }

        // ── Resposta HTML ──
        return $this->view('tags/index', [
            'titulo'            => 'Tags',
            'tags'              => $resultado['tags'],           // Array de objetos Tag
            'paginacao'         => $resultado['paginacao'],      // NOVO: dados de paginação
            'maisUsadas'        => $maisUsadas,                  // Tags populares
            'filtros'           => $filtros,                     // Filtros ativos (para preservar na URL)
            'coresPredefinidas' => $this->getCoresPredefinidas() // Paleta de cores
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
                    'artes' => $artes
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
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
    
    // ==========================================
    // UTILITÁRIOS
    // ==========================================
    
    /**
     * Retorna cores predefinidas para views
     * 
     * @return array
     */
    private function getCoresPredefinidas(): array
    {
        return $this->tagService->getCoresPredefinidas();
    }
}