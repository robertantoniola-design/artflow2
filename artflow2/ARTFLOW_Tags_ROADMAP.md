# ArtFlow 2.0 — Módulo Tags: Documentação Completa

**Data:** 07/02/2026  
**Status Geral:** ✅ Fase 1 (Estabilização CRUD) completa — 5 bugs corrigidos  
**Versão Base:** Sistema funcional com CRUD estabilizado  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)

---

## 📋 RESUMO EXECUTIVO

O módulo de Tags do ArtFlow 2.0 gerencia etiquetas/categorias para organizar artes do negócio. Tags permitem classificar obras por técnica (Aquarela, Óleo, Digital), tema (Retrato, Paisagem, Abstrato), tipo (Encomenda, Favorito) ou qualquer critério personalizado. O módulo opera com relacionamento N:N com Artes através da tabela pivot `arte_tags`, e oferece endpoints AJAX para integração com formulários de outros módulos.

O módulo passou por uma fase de estabilização onde 5 bugs críticos foram identificados e corrigidos durante os testes CRUD no navegador.

### Status da Estabilização (Fase 1)

| # | Correção | Arquivo | Status |
|---|----------|---------|--------|
| 1 | Método `pesquisar()` faltante | TagService.php | ✅ CORRIGIDO |
| 2 | Método `getArtesComTag()` faltante | TagService.php | ✅ CORRIGIDO |
| 3 | Métodos `searchWithCount()` e `getArtesByTag()` faltantes | TagRepository.php | ✅ CORRIGIDO |
| 4 | Acesso a objeto em array na view show | views/tags/show.php | ✅ CORRIGIDO |
| 5 | Parâmetros `'q'`→`'termo'` e `'tag'`→`'tag_id'` no ArteController | ArteController.php | ✅ CORRIGIDO |

### Status dos Testes CRUD

| Operação | Rota | Status |
|----------|------|--------|
| Listar | `GET /tags` | ✅ OK |
| Criar | `POST /tags` | ✅ OK |
| Visualizar | `GET /tags/{id}` | ✅ OK (corrigido) |
| Editar | `PUT /tags/{id}` | ✅ OK |
| Excluir | `DELETE /tags/{id}` | ✅ OK |
| Buscar | `GET /tags?termo=X` | ✅ OK (corrigido) |
| Ver Artes com Tag | `GET /artes?tag_id=X` | ✅ OK (corrigido) |

### Melhorias Futuras Planejadas

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Paginação na listagem | Baixa | 🔲 PLANEJADA |
| 2 | Ordenação dinâmica (nome, data, contagem) | Baixa | 🔲 PLANEJADA |
| 3 | Campo descrição e ícone customizado | Baixa | 🔲 PLANEJADA |
| 4 | Merge de tags duplicadas | Média | 🔲 PLANEJADA |
| 5 | Estatísticas por tag (valor médio, técnica popular) | Média | 🔲 PLANEJADA |
| 6 | Tag cloud visual / gráfico de distribuição | Média | 🔲 PLANEJADA |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Tag.php                       ✅ Original
├── Repositories/
│   └── TagRepository.php             ✅ Atualizado (Fase 1 — 2 métodos adicionados)
├── Services/
│   └── TagService.php                ✅ Atualizado (Fase 1 — 2 métodos adicionados + fix)
├── Controllers/
│   └── TagController.php             ✅ Original
└── Validators/
    └── TagValidator.php              ✅ Original

views/
└── tags/
    ├── index.php                     ✅ Original
    ├── create.php                    ✅ Original
    ├── show.php                      ✅ Atualizado (Fase 1 — array access)
    └── edit.php                      ✅ Original

database/
├── migrations/
│   ├── 005_create_tags_table.php     ✅ Executada
│   └── 006_create_arte_tags_table.php ✅ Executada
└── seeds/
    └── TagSeeder.php                 ✅ Executado (8 tags iniciais)

config/
└── routes.php                        ✅ Rotas de Tags registradas
```

### Dependências entre Classes

```
TagController → TagService
TagService    → TagRepository + TagValidator

ArteController → TagService (seletor de tags no form de Artes)
ArteService    → TagRepository (associação N:N via arte_tags)

ArteController::index() usa tag_id para filtrar artes por tag
TagController::show() usa getArtesByTag() para listar artes da tag
```

**Nota sobre acoplamento:** O módulo Tags é o mais independente do sistema. Ele NÃO depende de nenhum outro módulo, mas OUTROS módulos dependem dele (Artes usa Tags para categorização). Isso justifica testá-lo primeiro na ordem de validação.

### Tabela `tags` (Banco de Dados)

```sql
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,              -- Nome da tag (único)
    cor VARCHAR(7) DEFAULT '#6c757d',       -- Cor hexadecimal (#RRGGBB)
    icone VARCHAR(50) NULL,                 -- Classe do ícone (Bootstrap Icons)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_tags_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Tabela `arte_tags` (Pivot N:N)

```sql
CREATE TABLE arte_tags (
    arte_id INT UNSIGNED NOT NULL,          -- FK para artes
    tag_id INT UNSIGNED NOT NULL,           -- FK para tags
    
    PRIMARY KEY (arte_id, tag_id),          -- Chave composta impede duplicatas
    
    FOREIGN KEY (arte_id) REFERENCES artes(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    
    INDEX idx_arte_tags_arte (arte_id),
    INDEX idx_arte_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Notas sobre as tabelas:**
- `nome` tem UNIQUE INDEX — impede tags com nomes duplicados
- `arte_tags` usa chave primária composta — uma arte não pode ter a mesma tag duas vezes
- CASCADE em ambas FKs — ao deletar arte ou tag, as associações são removidas automaticamente
- `icone` é nullable — campo planejado para uso futuro, não exibido atualmente nas views

### Dados Iniciais (Seeds)

O `TagSeeder.php` popula 8 tags padrão:

| Nome | Cor | Uso Planejado |
|------|-----|---------------|
| Aquarela | `#17a2b8` | Técnica |
| Óleo | `#fd7e14` | Técnica |
| Acrílica | `#28a745` | Técnica |
| Digital | `#6f42c1` | Técnica |
| Retrato | `#e83e8c` | Tema |
| Paisagem | `#20c997` | Tema |
| Abstrato | `#007bff` | Tema |
| Encomenda | `#dc3545` | Tipo |

---

## 🔧 CORREÇÃO 1: MÉTODOS FALTANTES NO SERVICE — CORRIGIDA

### Problema
`TagController::index()` (linha 42) chamava `$this->tagService->pesquisar($filtros['termo'])` e `TagController::show()` (linha 119) chamava `$this->tagService->getArtesComTag($id)`, mas nenhum dos dois métodos existia no `TagService.php`.

**Erro:** `Fatal error: Call to undefined method App\Services\TagService::pesquisar()`  
**Erro:** `Fatal error: Call to undefined method App\Services\TagService::getArtesComTag()`

### Causa Raiz
Os métodos foram referenciados no Controller durante a geração inicial do código, mas nunca implementados nas camadas Service e Repository.

### Correção

**TagService.php** — 2 métodos adicionados:

```php
/**
 * Pesquisa tags por termo (nome) com contagem de artes
 * Usado por: TagController::index() (busca) e TagController::buscar() (AJAX)
 * 
 * @param string $termo Texto parcial para busca LIKE
 * @param int $limite Máximo de resultados (default 50)
 * @return array Array de arrays associativos com dados da tag + total_artes
 */
public function pesquisar(string $termo, int $limite = 50): array
{
    return $this->tagRepository->searchWithCount($termo, $limite);
}

/**
 * Retorna artes associadas a uma tag específica
 * Usado por: TagController::show() para exibir artes na página de detalhes
 * 
 * @param int $tagId ID da tag
 * @return array Array de arrays associativos (NÃO objetos Arte)
 */
public function getArtesComTag(int $tagId): array
{
    return $this->tagRepository->getArtesByTag($tagId);
}
```

### Testes
✅ `GET /tags?termo=Aqua` — retorna tags filtradas com contagem  
✅ `GET /tags/1` — exibe detalhes + lista de artes associadas  
✅ `GET /tags/buscar?termo=Ret` — endpoint AJAX retorna JSON  

---

## 🔧 CORREÇÃO 2: MÉTODOS FALTANTES NO REPOSITORY — CORRIGIDA

### Problema
Os métodos `searchWithCount()` e `getArtesByTag()` não existiam no `TagRepository.php`, sendo necessários para alimentar o Service.

### Correção

**TagRepository.php** — 2 métodos adicionados:

```php
/**
 * Busca tags por nome com contagem de artes associadas
 * 
 * SQL: SELECT t.*, COUNT(at.arte_id) as total_artes
 *      FROM tags t
 *      LEFT JOIN arte_tags at ON t.id = at.tag_id
 *      WHERE t.nome LIKE :termo
 *      GROUP BY t.id
 *      ORDER BY t.nome ASC
 *      LIMIT :limite
 * 
 * @param string $termo Texto parcial (busca LIKE %termo%)
 * @param int $limite Máximo de resultados
 * @return array Array de arrays associativos (id, nome, cor, total_artes)
 */
public function searchWithCount(string $termo, int $limite = 50): array
{
    $sql = "SELECT t.*, COUNT(at.arte_id) as total_artes
            FROM {$this->table} t
            LEFT JOIN arte_tags at ON t.id = at.tag_id
            WHERE t.nome LIKE :termo
            GROUP BY t.id
            ORDER BY t.nome ASC
            LIMIT :limite";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bindValue(':termo', '%' . $termo . '%', PDO::PARAM_STR);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Retorna artes associadas a uma tag (via tabela pivot arte_tags)
 * 
 * IMPORTANTE: Retorna arrays associativos, NÃO objetos Arte.
 * Motivo: evitar dependência circular TagRepository→Arte Model.
 * A view show.php DEVE usar acesso por chave ($arte['nome']),
 * NÃO por método ($arte->getNome()).
 * 
 * SQL: SELECT a.* FROM artes a
 *      INNER JOIN arte_tags at ON a.id = at.arte_id
 *      WHERE at.tag_id = :tag_id
 *      ORDER BY a.nome ASC
 * 
 * @param int $tagId
 * @return array Array de arrays associativos
 */
public function getArtesByTag(int $tagId): array
{
    $sql = "SELECT a.* FROM artes a
            INNER JOIN arte_tags at ON a.id = at.arte_id
            WHERE at.tag_id = :tag_id
            ORDER BY a.nome ASC";
    
    $stmt = $this->getConnection()->prepare($sql);
    $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### Decisão Técnica: Array vs Objeto

O método `getArtesByTag()` retorna `PDO::FETCH_ASSOC` (arrays) ao invés de objetos `Arte`. Razões:

1. **Evitar dependência circular** — TagRepository não deve instanciar Models de outro módulo
2. **Simplicidade** — A view show.php só precisa exibir dados, não precisa de métodos do Model
3. **Performance** — Evita overhead de hydration desnecessário

**Consequência:** A view `show.php` DEVE acessar dados como `$arte['nome']` e NUNCA como `$arte->getNome()`.

### Testes
✅ `searchWithCount('Aqua')` — retorna array com `total_artes` populado  
✅ `getArtesByTag(1)` — retorna artes em formato FETCH_ASSOC  
✅ LEFT JOIN garante que tags sem artes retornam `total_artes = 0`  

---

## 🔧 CORREÇÃO 3: VIEW SHOW.PHP — ACESSO ARRAY vs OBJETO — CORRIGIDA

### Problema
A view `views/tags/show.php` usava acesso a métodos de objeto (`$arte->getNome()`, `$arte->getStatus()`, etc.), mas o Repository retorna arrays associativos (`PDO::FETCH_ASSOC`).

**Erro:** `Fatal error: Call to a member function getStatus() on array`

### Causa Raiz
A view foi escrita assumindo que `getArtesComTag()` retornaria objetos `Arte`, mas a implementação correta no Repository retorna arrays para evitar acoplamento entre módulos.

### Correção

Todas as referências a métodos de objeto foram convertidas para acesso por chave de array:

```php
// ANTES (❌ causava Fatal Error):
$arte->getStatus()
$arte->getNome()
$arte->getPrecoCusto()
$arte->getHorasTrabalhadas()
$arte->getId()
$arte->getDescricao()

// DEPOIS (✅ funciona com FETCH_ASSOC):
$arte['status']
$arte['nome']
(float)($arte['preco_custo'] ?? 0)
(float)($arte['horas_trabalhadas'] ?? 0)
$arte['id']
$arte['descricao'] ?? ''
```

**Proteções adicionadas:**
- Null coalescing `??` em campos que podem ser NULL
- Cast `(float)` em campos numéricos para evitar warnings
- `htmlspecialchars()` via helper `e()` em todos os outputs

### Testes
✅ `GET /tags/1` — página carrega sem erros  
✅ Artes associadas exibidas com nome, status, preço e horas  
✅ Tags sem artes mostram mensagem "Nenhuma arte com esta tag"  

---

## 🔧 CORREÇÃO 4: NORMALIZAÇÃO DE DADOS — FIX SILENCIOSO — CORRIGIDA

### Problema
No `TagService::normalizarDados()`, a lógica de cor padrão tinha um bug silencioso:

```php
// ANTES (❌ bug lógico — else nunca executava porque ?? requer null):
if (isset($dados['cor'])) {
    $dados['cor'] = TagValidator::normalizeCor($dados['cor']);
} else {
    $dados['cor'] = $dados['cor'] ?? '#6c757d'; // ← $dados['cor'] é undefined aqui!
}
```

### Correção
```php
// DEPOIS (✅ lógica correta):
if (isset($dados['cor'])) {
    $dados['cor'] = TagValidator::normalizeCor($dados['cor']);
} else {
    $dados['cor'] = '#6c757d'; // Cor padrão cinza Bootstrap
}
```

### Impacto
Sem este fix, criar uma tag sem selecionar cor poderia resultar em valor imprevisível ao invés do cinza padrão `#6c757d`.

---

## 🔧 CORREÇÃO 5: ARTECONTROLLER — PARÂMETROS INCOMPATÍVEIS — CORRIGIDA

### Problema
No `ArteController::index()`, os nomes dos parâmetros lidos da URL não correspondiam aos nomes enviados pelos formulários e links das views.

```php
// ANTES (❌ parâmetros incorretos):
$filtros = [
    'status' => $request->get('status'),
    'termo' => $request->get('q'),       // ← View envia name="termo"
    'tag_id' => $request->get('tag')     // ← View envia name="tag_id" / ?tag_id=X
];
```

### Causa Raiz
Inconsistência entre o Controller (que lia `q` e `tag`) e as Views (que enviavam `termo` e `tag_id`). O botão "Ver Artes com esta Tag" na view `tags/show.php` gera link `href="/artes?tag_id=X"`, mas o Controller esperava `?tag=X`.

### Correção
```php
// DEPOIS (✅ nomes consistentes com as views):
$filtros = [
    'status' => $request->get('status'),
    'termo' => $request->get('termo'),    // ✓ Matches view name="termo"
    'tag_id' => $request->get('tag_id')   // ✓ Matches view ?tag_id=X
];
```

### Impacto
- **Busca por nome em Artes** — agora funciona corretamente
- **Botão "Ver Artes com esta Tag"** — agora filtra artes pela tag selecionada
- Afeta `ArteController.php` (módulo Artes, não Tags), mas é bug de integração entre os módulos

### Testes
✅ `GET /artes?termo=Paisagem` — filtra artes por nome  
✅ `GET /artes?tag_id=3` — filtra artes pela tag #3  
✅ Botão na página `/tags/{id}` redireciona e filtra corretamente  

---

## 📊 REFERÊNCIA RÁPIDA DE MÉTODOS

### Tag Model (`src/Models/Tag.php`)

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `getId()` | ?int | ID da tag |
| `getNome()` | string | Nome da tag |
| `getCor()` | string | Cor hexadecimal (#RRGGBB) |
| `getIcone()` | ?string | Classe ícone Bootstrap (nullable) |
| `getArtesCount()` | int | Contagem de artes associadas |
| `getCreatedAt()` | ?string | Data de criação |
| `getUpdatedAt()` | ?string | Data de atualização |
| `setId(?int)` | self | Fluent setter |
| `setNome(string)` | self | Fluent setter (aplica trim) |
| `setCor(string)` | self | Fluent setter |
| `setIcone(?string)` | self | Fluent setter |
| `setArtesCount(int)` | self | Fluent setter |
| `getBadgeHtml()` | string | HTML do badge com cor e ícone |
| `getCorTexto()` | string | `#000000` ou `#ffffff` (contraste automático) |
| `getStyleInline()` | string | CSS inline `background-color: X; color: Y;` |
| `toArray()` | array | Conversão para array associativo |
| `fromArray(array)` | Tag | Factory method estático |

### TagRepository (`src/Repositories/TagRepository.php`)

| Método | Retorno | Fase | Descrição |
|--------|---------|------|-----------|
| `find(int)` | Tag/null | Herdado | Busca por ID |
| `findAll()` | array | Herdado | Todas as tags |
| `create(array)` | Tag | Herdado | Insere nova tag |
| `update(int, array)` | bool | Herdado | Atualiza campos |
| `delete(int)` | bool | Herdado | Remove por ID |
| `findOrFail(int)` | Tag | Herdado | Busca ou lança NotFoundException |
| `findByNome(string)` | Tag/null | Base | Busca case-insensitive por nome |
| `allOrdered()` | array | Base | Todas ordenadas por nome ASC |
| `allWithCount()` | array\<Tag> | Base | Todas com `artes_count` via LEFT JOIN |
| `getMaisUsadas(int)` | array\<Tag> | Base | Top N tags por contagem (INNER JOIN) |
| `getContagemPorTag()` | array | Base | Dados para gráfico (nome, cor, quantidade) |
| `getTagsPorArte(int)` | array | Base | Tags associadas a uma arte |
| `getTagIdsPorArte(int)` | array\<int> | Base | Apenas IDs das tags de uma arte |
| `sincronizarTags(int, array)` | void | Base | Sync total (delete + insert) na pivot |
| `nomeExists(string, ?int)` | bool | Base | Verifica unicidade (com exclusão opcional) |
| `findOrCreate(string, string)` | Tag | Base | Cria se não existir |
| `deleteWithRelations(int)` | bool | Base | Transação: delete pivot + delete tag |
| `searchWithCount(string, int)` | array | **Fase 1** | LIKE search + LEFT JOIN + COUNT |
| `getArtesByTag(int)` | array | **Fase 1** | Artes da tag via INNER JOIN (FETCH_ASSOC) |

### TagService (`src/Services/TagService.php`)

| Método | Retorno | Fase | Descrição |
|--------|---------|------|-----------|
| `listar(array)` | array | Base | Lista com filtros opcionais |
| `listarComContagem()` | array\<Tag> | Base | Alias: allWithCount() |
| `buscar(int)` | Tag | Base | Busca por ID (findOrFail) |
| `criar(array)` | Tag | Base | Valida + unicidade + normaliza + cria |
| `atualizar(int, array)` | Tag | Base | Valida + unicidade + normaliza + atualiza |
| `remover(int)` | bool | Base | Remove tag + associações (transação) |
| `getMaisUsadas(int)` | array\<Tag> | Base | Delega para Repository |
| `getParaSelect()` | array | Base | `[id => nome]` para dropdowns |
| `getCoresPredefinidas()` | array | Base | Paleta de cores do TagValidator |
| `criarSeNaoExistir(string, string)` | Tag | Base | findOrCreate com cor padrão |
| `criarDeString(string)` | array\<int> | Base | Cria múltiplas a partir de CSV |
| `pesquisar(string, int)` | array | **Fase 1** | Busca LIKE + contagem |
| `getArtesComTag(int)` | array | **Fase 1** | Artes da tag (FETCH_ASSOC) |

### TagController (`src/Controllers/TagController.php`)

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET /tags | Lista + busca + tags mais usadas |
| `create()` | GET /tags/criar | Formulário com seletor de cores |
| `store()` | POST /tags | Valida + cria + flash message |
| `show($id)` | GET /tags/{id} | Detalhes + artes associadas |
| `edit($id)` | GET /tags/{id}/editar | Formulário de edição com cor atual |
| `update($id)` | PUT /tags/{id} | Atualiza + flash message |
| `destroy($id)` | DELETE /tags/{id} | Remove + flash message |
| `buscar()` | GET /tags/buscar | **AJAX** — autocomplete (JSON) |
| `select()` | GET /tags/select | **AJAX** — dropdown (JSON) |
| `criarRapida()` | POST /tags/rapida | **AJAX** — criação inline (JSON) |

### TagValidator (`src/Validators/TagValidator.php`)

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `validate(array)` | bool | Validação completa para criação |
| `validateCreate(array)` | bool | Alias de validate |
| `validateUpdate(array)` | bool | Validação parcial (campos opcionais) |
| `normalizeCor(string)` | string | Normaliza `#RGB` → `#RRGGBB` + uppercase |
| `getCoresPredefinidas()` | array | Paleta de 12 cores para seleção |

---

## 🗺️ SISTEMA DE ROTAS

### Rotas Registradas (`config/routes.php`)

```
TAGS — Rotas AJAX (declaradas ANTES do resource)
  GET  /tags/buscar     → TagController@buscar       (autocomplete)
  GET  /tags/select     → TagController@select        (dropdown JSON)
  POST /tags/rapida     → TagController@criarRapida   (criação inline)

TAGS — Resource (7 rotas automáticas)
  GET    /tags           → TagController@index         (listar)
  GET    /tags/criar     → TagController@create        (formulário)
  POST   /tags           → TagController@store         (salvar)
  GET    /tags/{id}      → TagController@show          (detalhes)
  GET    /tags/{id}/editar → TagController@edit        (form editar)
  PUT    /tags/{id}      → TagController@update        (atualizar)
  DELETE /tags/{id}      → TagController@destroy       (excluir)
```

**REGRA CRÍTICA:** As rotas `/tags/buscar`, `/tags/select` e `/tags/rapida` são declaradas ANTES de `$router->resource('/tags', ...)`. Caso contrário, o Router interpretaria "buscar" como `{id}` e chamaria `show()` com um parâmetro não-numérico.

### Integração com Módulo Artes

O módulo Artes consome Tags de duas formas:

1. **No formulário de criar/editar arte:** Usa `GET /tags/select` para popular dropdown de tags
2. **Na listagem de artes:** Aceita filtro `?tag_id=X` para exibir artes de uma tag específica
3. **Link "Ver Artes com esta Tag":** Na view `tags/show.php`, botão redireciona para `/artes?tag_id=X`

---

## 🎨 VALIDAÇÃO E NORMALIZAÇÃO

### Regras de Validação

| Campo | Regra | Mensagem |
|-------|-------|----------|
| nome | Obrigatório | "O nome da tag é obrigatório" |
| nome | Mínimo 2 caracteres | "O nome deve ter pelo menos 2 caracteres" |
| nome | Máximo 50 caracteres | "O nome deve ter no máximo 50 caracteres" |
| nome | Regex `[\p{L}\p{N}\s\-]+` | "O nome deve conter apenas letras, números, espaços e hífens" |
| nome | Unique (banco) | "Já existe uma tag com este nome" |
| cor | Opcional (default `#6c757d`) | — |
| cor | Regex `#([A-Fa-f0-9]{6}\|[A-Fa-f0-9]{3})` | "Cor inválida. Use formato hexadecimal (#RRGGBB ou #RGB)" |

### Normalização Automática

| Campo | Transformação | Exemplo |
|-------|---------------|---------|
| nome | `trim()` + `ucfirst(mb_strtolower())` | `"  aQUARELA  "` → `"Aquarela"` |
| cor | `ltrim('#')` + expande `#RGB` → `#RRGGBB` + `strtoupper()` | `"#abc"` → `"#AABBCC"` |

### Cores Predefinidas (Paleta do Seletor)

O `TagValidator::getCoresPredefinidas()` retorna 12 cores para a interface de seleção:

| Cor | Hex | Uso Sugerido |
|-----|-----|-------------|
| Vermelho | `#dc3545` | Urgente/Encomenda |
| Laranja | `#fd7e14` | Destaque |
| Amarelo | `#ffc107` | Atenção |
| Verde | `#28a745` | Concluído |
| Teal | `#20c997` | Natureza |
| Ciano | `#17a2b8` | Aquarela |
| Azul | `#007bff` | Padrão |
| Índigo | `#6610f2` | Premium |
| Roxo | `#6f42c1` | Digital |
| Rosa | `#e83e8c` | Feminino |
| Cinza | `#6c757d` | Neutro (default) |
| Escuro | `#343a40` | Formal |

---

## 🔄 FLUXO DE OPERAÇÕES

### Criar Tag (POST /tags)

```
1. TagController::store() recebe Request
2. validateCsrf($request) → protege contra CSRF
3. $request->only(['nome', 'cor']) → extrai campos
4. TagService::criar($dados)
   4a. TagValidator::validate($dados) → verifica regras
   4b. TagRepository::nomeExists($nome) → unicidade
   4c. normalizarDados() → ucfirst, normaliza cor
   4d. TagRepository::create($dados) → INSERT SQL
   4e. Retorna objeto Tag hidratado
5. flashSuccess("Tag criada!")
6. redirectTo('/tags')
```

### Excluir Tag (DELETE /tags/{id})

```
1. TagController::destroy() recebe Request + id
2. validateCsrf($request)
3. TagService::remover($id)
   3a. TagRepository::findOrFail($id) → verifica existência
   3b. TagRepository::deleteWithRelations($id)
       → BEGIN TRANSACTION
       → DELETE FROM arte_tags WHERE tag_id = :id
       → DELETE FROM tags WHERE id = :id
       → COMMIT
4. flashSuccess("Tag removida!")
5. redirectTo('/tags')
```

**Nota:** A exclusão usa transação para garantir atomicidade. Se o DELETE na tabela `tags` falhar, o DELETE em `arte_tags` é revertido via ROLLBACK.

### Busca AJAX (GET /tags/buscar?termo=X)

```
1. TagController::buscar() recebe Request
2. Lê 'termo' e 'limite' dos parâmetros
3. Se strlen(termo) < 1 → retorna JSON vazio []
4. TagService::pesquisar($termo, $limite)
   → TagRepository::searchWithCount() → LIKE %termo% + COUNT
5. Retorna JSON: [{id, nome, cor, total_artes}, ...]
```

---

## 🐛 BUGS CORRIGIDOS DURANTE ESTABILIZAÇÃO

### Bug 1: TagService::pesquisar() Undefined (Fatal Error)

**Problema:** Buscar tags na listagem (`/tags?termo=X`) causava Fatal Error.  
**Causa:** Método declarado no Controller mas nunca implementado no Service.  
**Correção:** Adicionado `pesquisar()` no TagService + `searchWithCount()` no TagRepository.  
**Impacto:** Bloqueava toda funcionalidade de busca do módulo.

### Bug 2: TagService::getArtesComTag() Undefined (Fatal Error)

**Problema:** Acessar detalhes de uma tag (`/tags/{id}`) causava Fatal Error.  
**Causa:** Método declarado no Controller mas nunca implementado no Service.  
**Correção:** Adicionado `getArtesComTag()` no TagService + `getArtesByTag()` no TagRepository.  
**Impacto:** Bloqueava a página de detalhes de qualquer tag.

### Bug 3: show.php — Acesso Objeto em Array (Fatal Error)

**Problema:** Mesmo após corrigir o Service, a view show.php falhava ao tentar chamar `$arte->getStatus()`.  
**Causa:** `getArtesByTag()` retorna `FETCH_ASSOC` (arrays), mas a view usava acesso a objetos.  
**Correção:** Convertidas todas as referências de `$arte->getX()` para `$arte['x']` com proteções null coalescing.  
**Impacto:** Completava a cadeia de correção Controller→Service→Repository→View.

### Bug 4: normalizarDados() — Cor Default Silenciosa

**Problema:** O bloco `else` para cor padrão continha `$dados['cor'] ?? '#6c757d'` mas `$dados['cor']` não existia nesse contexto, tornando o `??` inútil.  
**Causa:** Bug lógico — operador null coalescing em variável undefined dentro de array.  
**Correção:** Simplificado para `$dados['cor'] = '#6c757d'` direto.  
**Impacto:** Sem a correção, tags criadas sem cor poderiam ter valor imprevisível.

### Bug 5: ArteController — Parâmetros de Filtro Incorretos

**Problema:** Na listagem de artes, busca por nome e filtro por tag não funcionavam.  
**Causa:** Controller lia `$request->get('q')` mas view enviava `name="termo"`. Controller lia `$request->get('tag')` mas links usavam `?tag_id=X`.  
**Correção:** Alterados os nomes dos parâmetros no ArteController para `'termo'` e `'tag_id'`.  
**Impacto:** Afetava integração Tags↔Artes. O botão "Ver Artes com esta Tag" não funcionava.

---

## 📝 NOTAS TÉCNICAS IMPORTANTES

### View show.php — Sempre Usar Acesso por Array

As artes exibidas em `views/tags/show.php` vêm do `TagRepository::getArtesByTag()` que retorna `PDO::FETCH_ASSOC`. Se no futuro alguém alterar o Repository para retornar objetos `Arte`, a view precisará ser atualizada de volta para acesso por métodos (`$arte->getNome()`).

**Regra:** O tipo de retorno do Repository dita o tipo de acesso na View. Sempre verificar consistência ao alterar queries.

### Rotas AJAX Antes do Resource

As 3 rotas AJAX (`/tags/buscar`, `/tags/select`, `/tags/rapida`) DEVEM ser declaradas ANTES de `$router->resource('/tags', ...)` no `config/routes.php`. Se movidas para depois, o Router interpretará "buscar" como `{id}` na rota `GET /tags/{id}` e chamará `show("buscar")`, causando erro.

### Transação na Exclusão

O `deleteWithRelations()` usa `BEGIN TRANSACTION` + `COMMIT/ROLLBACK` mesmo que as FKs com CASCADE já removam associações automaticamente. Isso é intencional — a transação garante atomicidade caso a constraint CASCADE falhe ou seja removida no futuro.

### Criação Rápida (Inline)

O endpoint `POST /tags/rapida` permite criar tags sem sair do formulário de Artes. Ele:
1. Aceita apenas `nome` e `cor` via POST
2. Usa `criarSeNaoExistir()` — se já existe, retorna a existente
3. Retorna JSON com `{success, tag: {id, nome, cor}, message}`
4. O JavaScript do form de Artes adiciona a nova tag ao select dinamicamente

### Contagem de Artes — LEFT JOIN vs INNER JOIN

- `allWithCount()` usa **LEFT JOIN** — mostra TODAS as tags, inclusive sem artes (count=0)
- `getMaisUsadas()` usa **INNER JOIN** — mostra APENAS tags que têm artes associadas
- `searchWithCount()` usa **LEFT JOIN** — busca inclui tags sem artes para não esconder resultados

### Contraste Automático de Texto

O `Tag::getCorTexto()` calcula luminância usando a fórmula ITU-R BT.601:
```
luminância = (0.299 × R + 0.587 × G + 0.114 × B) / 255
```
Se luminância > 0.5, retorna texto preto (`#000000`); senão, texto branco (`#ffffff`). Isso garante que badges de tags tenham texto legível independente da cor de fundo escolhida.

---

## 🔮 MELHORIAS FUTURAS — ESPECIFICAÇÕES

### Melhoria 1: Paginação na Listagem (Complexidade: Baixa)

**Objetivo:** Limitar resultados por página para performance com muitas tags.

**Implementação prevista:**
- TagRepository: método `allWithCountPaginated(int $page, int $perPage)` com `LIMIT/OFFSET`
- TagController: ler `?page=X` da URL, passar para Service
- View index.php: componente de paginação Bootstrap reutilizável

### Melhoria 2: Ordenação Dinâmica (Complexidade: Baixa)

**Objetivo:** Permitir ordenar por nome, data de criação, ou contagem de artes.

**Implementação prevista:**
- TagController: ler `?ordenar=nome|data|contagem` e `?direcao=ASC|DESC`
- TagRepository: `ORDER BY` dinâmico com whitelist de colunas
- View: headers clicáveis na tabela com seta indicando direção

### Melhoria 3: Campo Descrição e Ícone (Complexidade: Baixa)

**Objetivo:** Enriquecer tags com descrição textual e ícone visual.

**Implementação prevista:**
- Migration: `ALTER TABLE tags ADD COLUMN descricao TEXT NULL`
- Campo `icone` já existe na tabela mas não é usado nas views
- Views: exibir ícone no badge e descrição na página de detalhes

### Melhoria 4: Merge de Tags (Complexidade: Média)

**Objetivo:** Unificar tags duplicadas ou similares, transferindo associações.

**Implementação prevista:**
- Nova rota: `POST /tags/{id}/merge`
- Service: transfere todas `arte_tags` da tag origem para a tag destino, depois deleta a origem
- UI: Select na view show.php para escolher tag de destino

### Melhoria 5: Estatísticas por Tag (Complexidade: Média)

**Objetivo:** Exibir métricas como valor médio das artes, técnica mais usada, etc.

**Implementação prevista:**
- TagRepository: queries com AVG, SUM, COUNT agrupados por tag
- View show.php: cards de estatísticas (similar ao módulo Metas)

### Melhoria 6: Tag Cloud / Gráfico (Complexidade: Média)

**Objetivo:** Visualização gráfica da distribuição de tags.

**Implementação prevista:**
- Chart.js doughnut ou bar chart usando `getContagemPorTag()` (já existe no Repository)
- View index.php: seção com gráfico acima ou ao lado da listagem

---

**Última atualização:** 07/02/2026  
**Status:** ✅ Módulo Tags — CRUD estabilizado, 5 bugs corrigidos, pronto para Fase 2  
**Próxima ação:** Implementar melhorias funcionais (paginação, ordenação) ou avançar para próximo módulo
