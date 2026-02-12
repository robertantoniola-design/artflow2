<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\TagService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * ============================================
 * TAG CONTROLLER (Melhoria 6 — + Gráfico de Distribuição)
 * ============================================
 * 
 * Gerencia rotas do módulo Tags.
 * 
 * ALTERAÇÕES:
 * - Melhoria 2: index() usa listarPaginado() com paginação/ordenação
 * - Melhoria 3: store()/update() agora leem 'descricao' e 'icone'
 * - Melhoria 3: create()/edit() passam lista de ícones para as views
 * - Melhoria 6: index() passa $contagemPorTag para gráfico Chart.js
 */
class TagController extends BaseController
{
    private TagService $tagService;
    
    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }
    
    // ==========================================
    // CRUD
    // ==========================================
    
    /**
     * Lista tags com paginação e ordenação (Melhoria 2)
     * GET /tags
     * 
     * MELHORIA 6: Agora também passa $contagemPorTag para gráfico de distribuição
     */
    public function index(Request $request): Response
    {
        // Parâmetros de URL
        $page = max(1, (int) $request->get('page', 1));
        $ordenar = $request->get('ordenar', 'nome');
        $direcao = strtoupper($request->get('direcao', 'ASC'));
        $termo = $request->get('termo', '');
        
        // Sanitiza direção
        if (!in_array($direcao, ['ASC', 'DESC'])) {
            $direcao = 'ASC';
        }
        
        // Busca paginada via Service
        $resultado = $this->tagService->listarPaginado(
            $page, 12, $ordenar, $direcao, $termo
        );
        
        // Tags mais usadas (sidebar — independente dos filtros)
        $tagsMaisUsadas = $this->tagService->getMaisUsadas(5);
        
        // ── MELHORIA 6: Dados para gráfico de distribuição ──
        // Retorna [{nome, cor, quantidade}] ordenado por quantidade DESC
        // Usa método que já existe no Repository/Service
        $contagemPorTag = $this->tagService->getContagemPorTag();
        
        return $this->view('tags/index', [
            'titulo' => 'Tags',
            'tags' => $resultado['tags'],
            'paginacao' => $resultado['paginacao'],
            'tagsMaisUsadas' => $tagsMaisUsadas,
            'contagemPorTag' => $contagemPorTag,  // MELHORIA 6: para Chart.js
            // Mantém filtros na view para preservar nos links
            'filtros' => [
                'ordenar' => $ordenar,
                'direcao' => $direcao,
                'termo' => $termo,
            ]
        ]);
    }
    
    /**
     * Formulário de criação
     * GET /tags/criar
     * 
     * MELHORIA 3: Passa lista de ícones disponíveis para a view
     */
    public function create(Request $request): Response
    {
        return $this->view('tags/create', [
            'titulo' => 'Nova Tag',
            'cores' => $this->tagService->getCoresPredefinidas(),
            'icones' => $this->tagService->getIconesDisponiveis(),  // MELHORIA 3
        ]);
    }
    
    /**
     * Salva nova tag
     * POST /tags
     * 
     * MELHORIA 3: Agora lê 'descricao' e 'icone' do request
     */
    public function store(Request $request): Response
    {
        $this->validateCsrf($request);
        
        try {
            // MELHORIA 3: Adicionados 'descricao' e 'icone' aos campos extraídos
            $dados = $request->only(['nome', 'cor', 'descricao', 'icone']);
            
            $tag = $this->tagService->criar($dados);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'tag' => $tag->toArray(),
                    'message' => 'Tag criada!'
                ], 201);
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
     * Exibe detalhes de uma tag
     * GET /tags/{id}
     * 
     * MELHORIA 3: Mostra descrição e ícone
     * MELHORIA 4: Passa lista de tags para dropdown de merge
     * MELHORIA 5: Passa estatísticas (métricas financeiras e de produção)
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $tag = $this->tagService->buscar($id);
            $artes = $this->tagService->getArtesComTag($id);
            
            // MELHORIA 4: Busca todas as tags para o dropdown de merge
            $todasTags = $this->tagService->listarComContagem();
            
            // MELHORIA 5: Busca estatísticas da tag (métricas de artes + vendas)
            // Retorna array com total_artes, faturamento_total, margem_lucro, etc.
            // Se a tag não tem artes, retorna zeros — a view trata com mensagem vazia
            $estatisticas = $this->tagService->getEstatisticasTag($id);
            
            return $this->view('tags/show', [
                'titulo'       => 'Tag: ' . $tag->getNome(),
                'tag'          => $tag,
                'artes'        => $artes,
                'todasTags'    => $todasTags,      // MELHORIA 4: dropdown de merge
                'estatisticas' => $estatisticas,    // MELHORIA 5: cards de estatísticas
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Tag não encontrada');
        }
    }







    
    /**
     * Formulário de edição
     * GET /tags/{id}/editar
     * 
     * MELHORIA 3: Passa lista de ícones disponíveis para a view
     */
    public function edit(Request $request, int $id): Response
    {
        try {
            $tag = $this->tagService->buscar($id);
            
            return $this->view('tags/edit', [
                'titulo' => 'Editar Tag',
                'tag' => $tag,
                'cores' => $this->tagService->getCoresPredefinidas(),
                'icones' => $this->tagService->getIconesDisponiveis(),  // MELHORIA 3
            ]);
            
        } catch (NotFoundException $e) {
            return $this->notFound('Tag não encontrada');
        }
    }
    
    /**
     * Atualiza tag
     * PUT /tags/{id}
     * 
     * MELHORIA 3: Agora lê 'descricao' e 'icone' do request
     */
    public function update(Request $request, int $id): Response
    {
        $this->validateCsrf($request);
        
        try {
            // MELHORIA 3: Adicionados 'descricao' e 'icone' aos campos extraídos
            $dados = $request->only(['nome', 'cor', 'descricao', 'icone']);
            
            $tag = $this->tagService->atualizar($id, $dados);
            
            if ($request->wantsJson()) {
                return $this->json([
                    'success' => true,
                    'tag' => $tag->toArray(),
                    'message' => 'Tag atualizada!'
                ]);
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
    // MELHORIA 4: MERGE DE TAGS
    // ==========================================

    /**
     * Mescla tag atual (origem) em outra tag (destino)
     * POST /tags/{id}/merge
     * 
     * Transfere todas as associações de artes da tag {id} para
     * a tag destino informada via POST (tag_destino_id), e deleta
     * a tag {id} ao final.
     * 
     * @param Request $request  Contém tag_destino_id e _token (CSRF)
     * @param int     $id       ID da tag origem (será deletada)
     * @return Response Redireciona para show da tag destino
     */
    public function merge(Request $request, int $id): Response
    {
        // Proteção CSRF
        $this->validateCsrf($request);
        
        try {
            // Extrai ID da tag destino do formulário
            $destinoId = (int) $request->get('tag_destino_id', 0);
            
            // Validação básica: destino deve ser informado
            if ($destinoId <= 0) {
                $this->flashError('Selecione uma tag de destino para a mesclagem.');
                return $this->redirectTo("/tags/{$id}");
            }
            
            // Executa merge via Service (validações + transação)
            $resultado = $this->tagService->mergeTags($id, $destinoId);
            
            // Monta mensagem de feedback detalhada
            $nomeOrigem  = $resultado['tag_origem']->getNome();
            $nomeDestino = $resultado['tag_destino']->getNome();
            $transferidas = $resultado['transferidas'];
            $duplicatas   = $resultado['duplicatas'];
            
            $mensagem = sprintf(
                'Tag "%s" mesclada com "%s" com sucesso! %d arte(s) transferida(s).',
                $nomeOrigem,
                $nomeDestino,
                $transferidas
            );
            
            // Informa sobre duplicatas se houver
            if ($duplicatas > 0) {
                $mensagem .= sprintf(
                    ' %d associação(ões) duplicada(s) foram ignoradas.',
                    $duplicatas
                );
            }
            
            $this->flashSuccess($mensagem);
            
            // Redireciona para a tag destino (a origem foi deletada)
            return $this->redirectTo("/tags/{$destinoId}");
            
        } catch (ValidationException $e) {
            // Erro de validação (ex: mesclar consigo mesma)
            $errors = $e->getErrors();
            $mensagem = is_array($errors) ? implode(' ', $errors) : $errors;
            $this->flashError($mensagem);
            return $this->redirectTo("/tags/{$id}");
            
        } catch (NotFoundException $e) {
            // Tag origem ou destino não encontrada
            $this->flashError('Uma das tags não foi encontrada.');
            return $this->redirectTo('/tags');
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
}