# ArtFlow 2.0 — Módulo Tags: Documentação Completa

**Data:** 11/02/2026  
**Status Geral:** ⚠️ Melhoria 3 deployada — 3 regressões de UI pendentes no index.php  
**Versão Base:** CRUD estabilizado + Paginação + Ordenação + Descrição/Ícone  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)

---

## 📋 RESUMO EXECUTIVO

O módulo de Tags do ArtFlow 2.0 gerencia etiquetas/categorias para organizar artes do negócio. Tags permitem classificar obras por técnica (Aquarela, Óleo, Digital), tema (Retrato, Paisagem, Abstrato), tipo (Encomenda, Favorito) ou qualquer critério personalizado. O módulo opera com relacionamento N:N com Artes através da tabela pivot `arte_tags`, e oferece endpoints AJAX para integração com formulários de outros módulos.

O módulo passou por uma fase de estabilização (5 bugs corrigidos), duas melhorias funcionais (paginação + ordenação), e uma terceira melhoria de campos (descrição + ícone) que introduziu regressões de UI que precisam ser corrigidas.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 5 bugs corrigidos | ✅ COMPLETA (07/02/2026) |
| Melhoria 1 | Paginação (12 itens/página) | ✅ COMPLETA (08/02/2026) |
| Melhoria 2 | Ordenação dinâmica (nome, data, contagem) | ✅ COMPLETA (08/02/2026) |
| Melhoria 3 | Campo descrição + ativação ícone | ⚠️ DEPLOYADA COM REGRESSÕES (09/02/2026) |

### ⚠️ BUGS PENDENTES — Regressões da Melhoria 3

Após o deploy da Melhoria 3, a view `index.php` perdeu 3 elementos de UI que existiam na versão original. Estes são **regressões** introduzidas quando o arquivo `09_views_tags_index.php` da Melhoria 3 reescreveu a estrutura dos cards:

| # | Bug | Elemento Perdido | Arquivo Afetado | Prioridade |
|---|-----|-----------------|-----------------|------------|
| R1 | Menu dropdown (...) sumiu | Botão `⋯` (three-dots) com dropdown no card de cada tag | views/tags/index.php | 🔴 ALTA |
| R2 | Botão "Ver Tags" sumiu | Link de detalhes que ficava abaixo do badge no card | views/tags/index.php | 🔴 ALTA |
| R3 | Botão "Excluir" sumiu | Opção de exclusão que ficava dentro do dropdown (...) | views/tags/index.php | 🔴 ALTA |

**Causa Raiz:** A Melhoria 3 reescreveu os tag cards no `index.php` com uma estrutura simplificada (apenas ícones de olho e lápis no footer), perdendo o layout original que tinha:
- Header: badge + dropdown three-dots com opções (Ver Artes, Editar, Excluir)
- Body: contagem de artes
- O botão Excluir usava `onclick="confirmarExclusao(id, 'nome')"` com formulário hidden

**O que a versão original do card tinha:**
```php
<div class="d-flex justify-content-between align-items-start mb-3">
    <span class="badge fs-5" style="background-color: ...">Nome</span>
    <div class="dropdown">
        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
            <i class="bi bi-three-dots"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a href="/tags/{id}" class="dropdown-item"><i class="bi bi-eye"></i> Ver Artes</a></li>
            <li><a href="/tags/{id}/editar" class="dropdown-item"><i class="bi bi-pencil"></i> Editar</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><button class="dropdown-item text-danger" onclick="confirmarExclusao(id, 'nome')">
                <i class="bi bi-trash"></i> Excluir
            </button></li>
        </ul>
    </div>
</div>
```

**Solução necessária:** Restaurar o dropdown three-dots nos cards do `index.php`, preservando as adições da Melhoria 3 (ícone no badge + descrição resumida). A versão corrigida deve ter:
1. Badge com ícone (Melhoria 3) ✅
2. Dropdown three-dots com Ver Artes, Editar, Excluir (original) ❌ restaurar
3. Contagem de artes (original) ✅
4. Descrição resumida (Melhoria 3) ✅
5. Formulário hidden + JavaScript `confirmarExclusao()` para o botão Excluir ❌ restaurar

### Melhorias Futuras

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | ✅ COMPLETA |
| 2 | Ordenação dinâmica (nome, data, contagem) | Baixa | ✅ COMPLETA |
| 3 | Campo descrição e ícone customizado | Baixa | ⚠️ DEPLOYADA — regressões UI |
| 4 | Merge de tags duplicadas | Média | 📲 PLANEJADA |
| 5 | Estatísticas por tag (valor médio, técnica popular) | Média | 📲 PLANEJADA |
| 6 | Tag cloud visual / gráfico de distribuição | Média | 📲 PLANEJADA |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Tag.php                       ✅ Melhoria 3 (+ descricao, hasIcone, hasDescricao, getDescricaoResumida)
├── Repositories/
│   └── TagRepository.php             ✅ Melhoria 3 (+ fillable: descricao, icone + allWithCountPaginated, countAll)
├── Services/
│   └── TagService.php                ✅ Melhoria 3 (+ normalizarDados icone/descricao + listarPaginado)
├── Controllers/
│   └── TagController.php             ✅ Melhoria 3 (+ icones para views, only() com 4 campos)
└── Validators/
    └── TagValidator.php              ✅ Melhoria 3 (+ validação descricao/icone + getIconesDisponiveis)

views/
└── tags/
    ├── index.php                     ⚠️ Melhoria 3 — REGRESSÕES (dropdown/excluir perdidos)
    ├── create.php                    ✅ Melhoria 3 (+ textarea descricao + select icone + preview)
    ├── show.php                      ✅ Melhoria 3 (+ card descrição + ícone no badge + info lateral)
    └── edit.php                      ✅ Melhoria 3 (+ textarea descricao + select icone + preview)

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

**Nota sobre acoplamento:** O módulo Tags é o mais independente do sistema. Ele NÃO depende de nenhum outro módulo, mas OUTROS módulos dependem dele (Artes usa Tags para categorização).

### Tabela `tags` (Banco de Dados — após Melhoria 3)

```sql
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,              -- Nome da tag (único)
    cor VARCHAR(7) DEFAULT '#6c757d',       -- Cor hexadecimal (#RRGGBB)
    descricao TEXT NULL,                    -- MELHORIA 3: Descrição opcional (max 500 chars na validação)
    icone VARCHAR(50) NULL,                 -- Classe do ícone (Bootstrap Icons) — ativado na Melhoria 3
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE INDEX idx_tags_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Migration da Melhoria 3:**
```sql
ALTER TABLE tags ADD COLUMN descricao TEXT NULL AFTER cor;
```

### Tabela `arte_tags` (Pivot N:N)

```sql
CREATE TABLE arte_tags (
    arte_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    
    PRIMARY KEY (arte_id, tag_id),
    
    FOREIGN KEY (arte_id) REFERENCES artes(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    
    INDEX idx_arte_tags_arte (arte_id),
    INDEX idx_arte_tags_tag (tag_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Dados Iniciais (Seeds)

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

## ✅ MELHORIA 1 — PAGINAÇÃO (COMPLETA)

**Implementada em:** 08/02/2026  
**Arquivos alterados:** TagRepository, TagService, TagController, views/tags/index.php

### O que foi feito:
- `TagRepository::allWithCountPaginated(int $page, int $perPage)` com LIMIT/OFFSET
- `TagRepository::countAll(?string $termo)` para total de registros
- `TagService::listarPaginado(int $page, int $perPage, array $filtros)` 
- Controller passa `$paginacao` array para a view com: `pagina_atual`, `total_paginas`, `total_registros`, `tem_anterior`, `tem_proxima`
- View exibe controles de paginação Bootstrap com números de página clicáveis
- **12 tags por página** (configurável)
- Preserva parâmetros de busca/ordenação nas URLs de paginação

---

## ✅ MELHORIA 2 — ORDENAÇÃO DINÂMICA (COMPLETA)

**Implementada em:** 08/02/2026  
**Arquivos alterados:** TagRepository, TagController, views/tags/index.php

### O que foi feito:
- Controller lê `?ordenar=nome|data|contagem` e `?direcao=ASC|DESC`
- Repository aplica ORDER BY dinâmico com whitelist de colunas válidas
- View exibe botões de ordenação (Nome ↑↓, Data ↑↓, Artes ↑↓) com estado ativo
- Toggle de direção: clicar no botão ativo inverte ASC↔DESC
- Helper `tagUrl()` na view monta URLs preservando todos os parâmetros

---

## ⚠️ MELHORIA 3 — DESCRIÇÃO + ÍCONE (DEPLOYADA COM REGRESSÕES)

**Implementada em:** 09/02/2026  
**Status:** Backend OK, Views create/edit/show OK, **View index.php com regressões de UI**

### O que foi feito:

**Database:**
- `ALTER TABLE tags ADD COLUMN descricao TEXT NULL AFTER cor`
- Campo `icone` já existia na tabela — ativado no código

**Backend (tudo funcionando):**
- **Tag Model:** `+descricao` property, `getDescricao()`, `setDescricao()`, `hasDescricao()`, `getDescricaoResumida(80)`, `hasIcone()`, `getBadgeHtml()` renderiza `<i>` com ícone
- **TagValidator:** `descricao` max 500 chars, `icone` regex `/^[a-zA-Z0-9\s\-]{1,100}$/` (XSS protection), `getIconesDisponiveis()` com 50+ Bootstrap Icons
- **TagService:** `normalizarDados()` trata descricao/icone (empty→NULL), `getIconesDisponiveis()` delega para Validator
- **TagController:** `store()/update()` extraem `['nome', 'cor', 'descricao', 'icone']`, `create()/edit()` passam `$icones` para views
- **TagRepository:** `$fillable` inclui `'descricao'` e `'icone'` (CRÍTICO para mass assignment)

**Views (funcionando):**
- `create.php` — textarea descrição (500 chars, contador live) + select ícone (50+ opções) + preview em tempo real
- `edit.php` — mesma UI, pré-preenchida com valores atuais
- `show.php` — badge com ícone, card "Descrição" condicional, info de ícone na sidebar

**View com regressões:**
- `index.php` — ícones nos badges ✅ e descrição resumida ✅ funcionam, MAS perdeu dropdown (...), link Ver Tags e botão Excluir

### Arquivos da Melhoria 3 (10 arquivos entregues)

| Arquivo | Deploy para | Linhas | Status |
|---------|------------|--------|--------|
| 00_MIGRATION_SQL.sql | phpMyAdmin/CLI | 31 | ✅ Executada |
| 01_Tag_Model_COMPLETO.php | src/Models/Tag.php | 190 | ✅ OK |
| 02_TagValidator_COMPLETO.php | src/Validators/TagValidator.php | 265 | ✅ OK |
| 03_TagService_COMPLETO.php | src/Services/TagService.php | 421 | ✅ OK |
| 04_TagController_COMPLETO.php | src/Controllers/TagController.php | 300 | ✅ OK |
| 05_TagRepository_COMPLETO.php | src/Repositories/TagRepository.php | 498 | ✅ OK |
| 06_views_tags_create.php | views/tags/create.php | 304 | ✅ OK |
| 07_views_tags_edit.php | views/tags/edit.php | 317 | ✅ OK |
| 08_views_tags_show.php | views/tags/show.php | 216 | ✅ OK |
| 09_views_tags_index.php | views/tags/index.php | 240 | ⚠️ REGRESSÕES |

### Detalhes Técnicos da Melhoria 3

**XSS Protection:** TagValidator rejeita `<>"'&;` no campo icone. Todos os outputs usam `e()` (htmlspecialchars). Icon classes validados com regex.

**NULL vs Empty String:** Service normaliza empty descricao/icone para NULL (database limpo, `hasDescricao()` funciona via `!empty()`).

**Backward Compatibility:** Tags sem descricao/icone exibem exatamente como antes (campos são NULL por default).

**$fillable CRÍTICO:** Sem `'descricao'` e `'icone'` no array `$fillable` do Repository, o `BaseRepository::filterFillable()` descarta silenciosamente esses campos nos INSERT/UPDATE.

---

## 🔧 FASE 1 — ESTABILIZAÇÃO CRUD (5 BUGS CORRIGIDOS)

### Status dos Testes CRUD (Fase 1)

| Operação | Rota | Status |
|----------|------|--------|
| Listar | `GET /tags` | ✅ OK |
| Criar | `POST /tags` | ✅ OK |
| Visualizar | `GET /tags/{id}` | ✅ OK (corrigido) |
| Editar | `PUT /tags/{id}` | ✅ OK |
| Excluir | `DELETE /tags/{id}` | ✅ OK |
| Buscar | `GET /tags?termo=X` | ✅ OK (corrigido) |
| Ver Artes com Tag | `GET /artes?tag_id=X` | ✅ OK (corrigido) |

### Bug 1: TagService::pesquisar() Undefined (Fatal Error)

**Problema:** Buscar tags na listagem (`/tags?termo=X`) causava Fatal Error.  
**Causa:** Método declarado no Controller mas nunca implementado no Service.  
**Correção:** Adicionado `pesquisar()` no TagService + `searchWithCount()` no TagRepository.

### Bug 2: TagService::getArtesComTag() Undefined (Fatal Error)

**Problema:** Acessar detalhes de uma tag (`/tags/{id}`) causava Fatal Error.  
**Causa:** Método declarado no Controller mas nunca implementado no Service.  
**Correção:** Adicionado `getArtesComTag()` no TagService + `getArtesByTag()` no TagRepository.

### Bug 3: show.php — Acesso Objeto em Array (Fatal Error)

**Problema:** View show.php falhava ao tentar chamar `$arte->getStatus()`.  
**Causa:** `getArtesByTag()` retorna `FETCH_ASSOC` (arrays), mas a view usava acesso a objetos.  
**Correção:** Convertidas todas as referências de `$arte->getX()` para `$arte['x']` com proteções null coalescing.

### Bug 4: normalizarDados() — Cor Default Silenciosa

**Problema:** Bloco `else` para cor padrão continha `$dados['cor'] ?? '#6c757d'` mas `$dados['cor']` era undefined.  
**Correção:** Simplificado para `$dados['cor'] = '#6c757d'` direto.

### Bug 5: ArteController — Parâmetros Incompatíveis

**Problema:** Controller lia `$request->get('q')` mas view enviava `name="termo"`. Controller lia `$request->get('tag')` mas links usavam `?tag_id=X`.  
**Correção:** Alterados parâmetros no ArteController para `'termo'` e `'tag_id'`.

---

## 📊 REFERÊNCIA RÁPIDA DE MÉTODOS

### Tag Model (`src/Models/Tag.php`) — Após Melhoria 3

| Método | Retorno | Fase | Descrição |
|--------|---------|------|-----------|
| `getId()` | ?int | Base | ID da tag |
| `getNome()` | string | Base | Nome da tag |
| `getCor()` | string | Base | Cor hexadecimal (#RRGGBB) |
| `getIcone()` | ?string | Base | Classe ícone Bootstrap (nullable) |
| `getDescricao()` | ?string | **M3** | Texto descritivo (nullable) |
| `getArtesCount()` | int | Base | Contagem de artes associadas |
| `getCreatedAt()` | ?string | Base | Data de criação |
| `getUpdatedAt()` | ?string | Base | Data de atualização |
| `setDescricao(?string)` | self | **M3** | Fluent setter |
| `hasDescricao()` | bool | **M3** | Verifica se tem descrição |
| `hasIcone()` | bool | **M3** | Verifica se tem ícone |
| `getDescricaoResumida(int)` | string | **M3** | Trunca texto com "..." |
| `getBadgeHtml()` | string | **M3** | HTML do badge com ícone condicional |
| `getCorTexto()` | string | Base | `#000000` ou `#ffffff` (contraste automático) |
| `getStyleInline()` | string | Base | CSS inline `background-color: X; color: Y;` |
| `toArray()` | array | **M3** | Inclui descricao no array |
| `fromArray(array)` | Tag | **M3** | Hidrata descricao do array |

### TagRepository (`src/Repositories/TagRepository.php`)

| Método | Retorno | Fase | Descrição |
|--------|---------|------|-----------|
| `find(int)` | Tag/null | Herdado | Busca por ID |
| `findAll()` | array | Herdado | Todas as tags |
| `create(array)` | Tag | Herdado | Insere nova tag |
| `update(int, array)` | bool | Herdado | Atualiza campos |
| `delete(int)` | bool | Herdado | Remove por ID |
| `findOrFail(int)` | Tag | Herdado | Busca ou lança NotFoundException |
| `findByNome(string)` | Tag/null | Base | Busca case-insensitive |
| `allOrdered()` | array | Base | Todas ordenadas por nome |
| `allWithCount()` | array\<Tag> | Base | Todas com artes_count (LEFT JOIN) |
| `allWithCountPaginated(int, int, array)` | array\<Tag> | **M1** | Paginado + ordenação + busca |
| `countAll(?string)` | int | **M1** | Total de registros (com filtro opcional) |
| `getMaisUsadas(int)` | array\<Tag> | Base | Top N por contagem (INNER JOIN) |
| `getContagemPorTag()` | array | Base | Dados para gráfico |
| `getTagsPorArte(int)` | array | Base | Tags de uma arte |
| `getTagIdsPorArte(int)` | array\<int> | Base | IDs das tags de uma arte |
| `sincronizarTags(int, array)` | void | Base | Sync pivot (delete + insert) |
| `nomeExists(string, ?int)` | bool | Base | Unicidade com exclusão |
| `findOrCreate(string, string)` | Tag | Base | Cria se não existir |
| `deleteWithRelations(int)` | bool | Base | Transação: pivot + tag |
| `searchWithCount(string, int)` | array | **F1** | LIKE + LEFT JOIN + COUNT |
| `getArtesByTag(int)` | array | **F1** | Artes via INNER JOIN (FETCH_ASSOC) |

**Legenda:** F1=Fase 1, M1=Melhoria 1, M3=Melhoria 3

### TagService (`src/Services/TagService.php`)

| Método | Retorno | Fase | Descrição |
|--------|---------|------|-----------|
| `listar(array)` | array | Base | Lista com filtros |
| `listarPaginado(int, int, array)` | array | **M1** | Paginação + ordenação |
| `listarComContagem()` | array\<Tag> | Base | allWithCount() |
| `buscar(int)` | Tag | Base | Busca por ID |
| `criar(array)` | Tag | Base→**M3** | Agora aceita descricao/icone |
| `atualizar(int, array)` | Tag | Base→**M3** | Agora aceita descricao/icone |
| `remover(int)` | bool | Base | Remove com transação |
| `getMaisUsadas(int)` | array\<Tag> | Base | Top N |
| `getParaSelect()` | array | Base | Para dropdowns |
| `getCoresPredefinidas()` | array | Base | Paleta de cores |
| `getIconesDisponiveis()` | array | **M3** | Ícones Bootstrap disponíveis |
| `criarSeNaoExistir(string, string)` | Tag | Base | findOrCreate |
| `criarDeString(string)` | array\<int> | Base | Múltiplas de CSV |
| `pesquisar(string, int)` | array | **F1** | Busca LIKE + contagem |
| `getArtesComTag(int)` | array | **F1** | Artes da tag |

### TagController (`src/Controllers/TagController.php`)

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET /tags | Lista paginada + busca + ordenação + tags mais usadas |
| `create()` | GET /tags/criar | Formulário com cores + ícones (M3) |
| `store()` | POST /tags | Valida + cria (nome, cor, descricao, icone) |
| `show($id)` | GET /tags/{id} | Detalhes + artes + descrição (M3) |
| `edit($id)` | GET /tags/{id}/editar | Form edição com ícones (M3) |
| `update($id)` | PUT /tags/{id} | Atualiza 4 campos |
| `destroy($id)` | DELETE /tags/{id} | Remove + flash |
| `buscar()` | GET /tags/buscar | AJAX autocomplete |
| `select()` | GET /tags/select | AJAX dropdown |
| `criarRapida()` | POST /tags/rapida | AJAX criação inline |

### TagValidator (`src/Validators/TagValidator.php`) — Após Melhoria 3

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `validate(array)` | bool | Validação completa (4 campos) |
| `validateCreate(array)` | bool | Alias de validate |
| `validateUpdate(array)` | bool | Validação parcial |
| `normalizeCor(string)` | string | `#RGB` → `#RRGGBB` + uppercase |
| `getCoresPredefinidas()` | array | Paleta de 12 cores |
| `getIconesDisponiveis()` | array | **M3** — 50+ Bootstrap Icons |

---

## 🗺️ SISTEMA DE ROTAS

```
TAGS — Rotas AJAX (declaradas ANTES do resource)
  GET  /tags/buscar     → TagController@buscar       (autocomplete)
  GET  /tags/select     → TagController@select        (dropdown JSON)
  POST /tags/rapida     → TagController@criarRapida   (criação inline)

TAGS — Resource (7 rotas automáticas)
  GET    /tags           → TagController@index         (listar paginado)
  GET    /tags/criar     → TagController@create        (formulário)
  POST   /tags           → TagController@store         (salvar)
  GET    /tags/{id}      → TagController@show          (detalhes)
  GET    /tags/{id}/editar → TagController@edit        (form editar)
  PUT    /tags/{id}      → TagController@update        (atualizar)
  DELETE /tags/{id}      → TagController@destroy       (excluir)
```

**REGRA CRÍTICA:** Rotas AJAX ANTES de `$router->resource(...)`. Caso contrário, Router interpreta "buscar" como `{id}`.

---

## 🎨 VALIDAÇÃO E NORMALIZAÇÃO — Após Melhoria 3

### Regras de Validação

| Campo | Regra | Mensagem |
|-------|-------|----------|
| nome | Obrigatório, 2-50 chars, regex letras/números/espaços/hífens, unique | Diversas |
| cor | Opcional (default `#6c757d`), regex `#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})` | "Cor inválida" |
| descricao | **M3** Opcional, max 500 chars | "A descrição deve ter no máximo 500 caracteres" |
| icone | **M3** Opcional, regex `/^[a-zA-Z0-9\s\-]{1,100}$/`, rejeita `<>"'&;` | "Ícone contém caracteres inválidos" |

### Normalização Automática

| Campo | Transformação |
|-------|--------------|
| nome | `trim()` + `ucfirst(mb_strtolower())` |
| cor | `ltrim('#')` + expande `#RGB` → `#RRGGBB` + `strtoupper()` |
| descricao | **M3** `trim()` + empty → `NULL` |
| icone | **M3** `trim()` + empty → `NULL` |

---

## 📝 NOTAS TÉCNICAS IMPORTANTES

### View show.php — Sempre Usar Acesso por Array
As artes em show.php vêm do `TagRepository::getArtesByTag()` que retorna `FETCH_ASSOC`. Usar `$arte['nome']`, NUNCA `$arte->getNome()`.

### Rotas AJAX Antes do Resource
As 3 rotas AJAX DEVEM ser declaradas ANTES de `$router->resource(...)`. Se movidas para depois, Router interpreta "buscar" como `{id}`.

### Transação na Exclusão
`deleteWithRelations()` usa `BEGIN TRANSACTION` + `COMMIT/ROLLBACK` mesmo com CASCADE nas FKs.

### Contagem de Artes — LEFT JOIN vs INNER JOIN
- `allWithCount()` / `allWithCountPaginated()` = LEFT JOIN (todas as tags)
- `getMaisUsadas()` = INNER JOIN (apenas com artes)

### Contraste Automático de Texto
`Tag::getCorTexto()` calcula luminância (ITU-R BT.601) para decidir texto preto/branco.

### Router Bug Fix — Conversão de Tipos
O Router tem fix que converte parâmetros string de URL para int, prevenindo TypeErrors em `findOrFail()`.

### Variável de Anos no Metas
O controller de Metas passa `'anosDisponiveis'` (renomeado de `'anos'`). Se filtro de anos quebrar, reverter nome da variável.

---

## 📮 MELHORIAS FUTURAS — ESPECIFICAÇÕES

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

## 🔄 FLUXO DE OPERAÇÕES

### Criar Tag (POST /tags) — Após Melhoria 3

```
1. TagController::store() recebe Request
2. validateCsrf($request) → protege contra CSRF
3. $request->only(['nome', 'cor', 'descricao', 'icone']) → extrai 4 campos
4. TagService::criar($dados)
   4a. TagValidator::validate($dados) → verifica regras (4 campos)
   4b. TagRepository::nomeExists($nome) → unicidade
   4c. normalizarDados() → ucfirst, normaliza cor, trim descricao, trim icone, empty→NULL
   4d. TagRepository::create($dados) → INSERT SQL (fillable filtra campos)
   4e. Retorna objeto Tag hidratado
5. flashSuccess("Tag criada!")
6. redirectTo('/tags')
```

### Listar Tags (GET /tags) — Após Melhorias 1+2

```
1. TagController::index() recebe Request
2. Extrai: page, ordenar, direcao, termo
3. Se termo → TagService::pesquisar() (busca LIKE)
4. Senão → TagService::listarPaginado(page, 12, filtros)
   → TagRepository::allWithCountPaginated() com LIMIT/OFFSET + ORDER BY dinâmico
5. TagService::getMaisUsadas(5) → top 5 para sidebar
6. View recebe: $tags, $paginacao, $tagsMaisUsadas, $filtros
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

---

## 📌 PRÓXIMAS AÇÕES (para nova conversa)

1. **PRIORIDADE 1:** Corrigir as 3 regressões de UI no `views/tags/index.php`:
   - Restaurar dropdown three-dots (...) nos cards de tag
   - Restaurar link "Ver Artes" / detalhes
   - Restaurar botão "Excluir" com `confirmarExclusao()` + formulário hidden
   - Preservar adições da Melhoria 3 (ícone no badge + descrição resumida)

2. **Testar CRUD completo** após correção do index.php:
   - ✅ GET /tags — index carrega com dropdown funcional
   - ✅ Dropdown (...) → Ver Artes, Editar, Excluir
   - ✅ Excluir via dropdown funciona (confirm + DELETE)
   - ✅ Ícones visíveis nos badges
   - ✅ Descrição resumida visível nos cards
   - ✅ Paginação + Ordenação preservadas

3. **Após estabilizar Melhoria 3:** Avançar para Melhoria 4 (Merge de Tags) ou próximo módulo

---

**Última atualização:** 11/02/2026  
**Status:** ⚠️ Módulo Tags — Melhoria 3 deployada, 3 regressões de UI no index.php pendentes  
**Próxima ação:** Corrigir index.php restaurando dropdown + excluir, mantendo ícone + descrição
