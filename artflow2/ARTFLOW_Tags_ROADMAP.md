# ArtFlow 2.0 — Módulo Tags: Documentação Completa

**Data:** 12/02/2026  
**Status Geral:** ✅ Melhoria 6 (Gráfico de Distribuição) completa — Módulo COMPLETO  
**Versão Base:** CRUD estabilizado + Paginação + Ordenação + Descrição/Ícone + Merge + Estatísticas + Gráfico  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)

---

## 📋 RESUMO EXECUTIVO

O módulo de Tags do ArtFlow 2.0 gerencia etiquetas/categorias para organizar artes do negócio. Tags permitem classificar obras por técnica (Aquarela, Óleo, Digital), tema (Retrato, Paisagem, Abstrato), tipo (Encomenda, Favorito) ou qualquer critério personalizado. O módulo opera com relacionamento N:N com Artes através da tabela pivot `arte_tags`, e oferece endpoints AJAX para integração com formulários de outros módulos.

O módulo passou por uma fase de estabilização (5 bugs corrigidos), seis melhorias funcionais (paginação, ordenação, descrição/ícone, merge de tags, estatísticas por tag, gráfico de distribuição), e está **COMPLETO** com todas as regressões de UI corrigidas.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 5 bugs corrigidos | ✅ COMPLETA (07/02/2026) |
| Melhoria 1 | Paginação (12 itens/página) | ✅ COMPLETA (08/02/2026) |
| Melhoria 2 | Ordenação dinâmica (nome, data, contagem) | ✅ COMPLETA (08/02/2026) |
| Melhoria 3 | Campo descrição + ativação ícone | ✅ COMPLETA (09/02/2026 — regressões corrigidas 11/02/2026) |
| Melhoria 4 | Merge de tags (mesclar/absorver tags) | ✅ COMPLETA (12/02/2026) |
| Melhoria 5 | Estatísticas por tag (métricas financeiras e produção) | ✅ COMPLETA (12/02/2026) |
| Melhoria 6 | Gráfico de distribuição (Doughnut/Barras Chart.js) | ✅ COMPLETA (12/02/2026) |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | ✅ COMPLETA |
| 2 | Ordenação dinâmica (nome, data, contagem) | Baixa | ✅ COMPLETA |
| 3 | Campo descrição e ícone customizado | Baixa | ✅ COMPLETA |
| 4 | Merge de tags duplicadas | Média | ✅ COMPLETA |
| 5 | Estatísticas por tag (produção + vendas) | Média | ✅ COMPLETA |
| 6 | Gráfico de distribuição (Doughnut ↔ Barras) | Média | ✅ COMPLETA |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Tag.php                       ✅ Melhoria 3 (+ descricao, hasIcone, hasDescricao, getDescricaoResumida)
├── Repositories/
│   └── TagRepository.php             ✅ Melhoria 5 (+ getEstatisticasByTag — 2 queries + complexidade)
├── Services/
│   └── TagService.php                ✅ Melhoria 6 (+ getContagemPorTag — dados para gráfico)
├── Controllers/
│   └── TagController.php             ✅ Melhoria 6 (+ index() passa $contagemPorTag)
└── Validators/
    └── TagValidator.php              ✅ Melhoria 3 (+ validação descricao/icone + getIconesDisponiveis)

views/
└── tags/
    ├── index.php                     ✅ Melhoria 6 (+ gráfico Chart.js Doughnut/Barras)
    ├── create.php                    ✅ Melhoria 3 (+ textarea descricao + select icone + preview)
    ├── show.php                      ✅ Melhoria 5 (+ mini-cards + card estatísticas detalhadas)
    └── edit.php                      ✅ Melhoria 3 (+ textarea descricao + select icone + preview)

database/
├── migrations/
│   ├── 005_create_tags_table.php     ✅ Executada
│   └── 006_create_arte_tags_table.php ✅ Executada
└── seeds/
    └── TagSeeder.php                 ✅ Executado (8 tags iniciais)

config/
└── routes.php                        ✅ Melhoria 4 (+ POST /tags/{id}/merge)
```

### Dependências entre Classes

```
TagController → TagService
TagService    → TagRepository + TagValidator

ArteController → TagService (seletor de tags no form de Artes)
ArteService    → TagRepository (associação N:N via arte_tags)

ArteController::index() usa tag_id para filtrar artes por tag
TagController::show() usa getArtesByTag() para listar artes da tag
TagController::show() usa listarComContagem() para dropdown de merge (M4)
TagController::show() usa getEstatisticasTag() para cards de métricas (M5)
TagController::merge() usa TagService::mergeTags() para mesclar tags (M4)
TagController::index() usa TagService::getContagemPorTag() para gráfico (M6)
```

**Nota sobre acoplamento:** O módulo Tags é o mais independente do sistema. Ele NÃO depende de nenhum outro módulo, mas OUTROS módulos dependem dele (Artes usa Tags para categorização).

**Nota sobre Melhoria 5:** As queries de estatísticas fazem JOIN com as tabelas `artes` e `vendas`, mas isso é acesso SOMENTE LEITURA via SQL — não há dependência de código PHP (não importa Models/Services de outros módulos).

**Nota sobre Melhoria 6:** O gráfico usa Chart.js 4.4.7 via CDN (mesmo padrão do módulo Metas). Dados vêm de `getContagemPorTag()` que já existia no Repository — só faltava o wrapper no Service.

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
- View exibe botões de ordenação (Nome ↕, Data ↕, Artes ↕) com estado ativo
- Toggle de direção: clicar no botão ativo inverte ASC↔DESC
- Helper `tagUrl()` na view monta URLs preservando todos os parâmetros

---

## ✅ MELHORIA 3 — DESCRIÇÃO + ÍCONE (COMPLETA)

**Implementada em:** 09/02/2026  
**Regressões corrigidas em:** 11/02/2026  
**Status:** ✅ Totalmente funcional — backend + todas as views

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

**Views (todas funcionando):**
- `create.php` — textarea descrição (500 chars, contador live) + select ícone (50+ opções) + preview em tempo real
- `edit.php` — mesma UI, pré-preenchida com valores atuais
- `show.php` — badge com ícone, card "Descrição" condicional, info de ícone na sidebar
- `index.php` — ícones nos badges ✅, descrição resumida ✅, dropdown three-dots ✅, botão Excluir ✅ (regressões corrigidas)

### Regressões da Melhoria 3 (RESOLVIDAS)

Três elementos de UI foram perdidos no index.php durante o deploy da Melhoria 3 e restaurados em 11/02/2026:
1. ✅ Menu dropdown three-dots (...) restaurado nos cards
2. ✅ Botão "Ver Tags" restaurado
3. ✅ Botão "Excluir" com `confirmarExclusao()` + formulário hidden restaurado

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
| 09_views_tags_index.php | views/tags/index.php | 240 | ✅ OK (regressões corrigidas) |

### Detalhes Técnicos da Melhoria 3

**XSS Protection:** TagValidator rejeita `<>"'&;` no campo icone. Todos os outputs usam `e()` (htmlspecialchars). Icon classes validados com regex.

**NULL vs Empty String:** Service normaliza empty descricao/icone para NULL (database limpo, `hasDescricao()` funciona via `!empty()`).

**Backward Compatibility:** Tags sem descricao/icone exibem exatamente como antes (campos são NULL por default).

**$fillable CRÍTICO:** Sem `'descricao'` e `'icone'` no array `$fillable` do Repository, o `BaseRepository::filterFillable()` descarta silenciosamente esses campos nos INSERT/UPDATE.

---

## ✅ MELHORIA 4 — MERGE DE TAGS (COMPLETA)

**Implementada em:** 12/02/2026  
**Arquivos alterados:** TagRepository, TagService, TagController, views/tags/show.php, config/routes.php  
**Correções visuais:** Botão cinza/amarelo toggle + badges com inline style (v2 — 12/02/2026)

### Objetivo

Permitir mesclar (absorver) uma tag em outra, transferindo todas as associações de `arte_tags` da tag origem para a tag destino, tratando duplicatas (artes que já possuem ambas as tags) sem violar a constraint de chave primária composta, e deletando a tag origem ao final.

### Lógica de Merge — Transação com Tratamento de Duplicatas

**Problema resolvido:** Se arte #1 tem tags [A, B] e fazemos merge de A → B, um UPDATE direto causaria `Duplicate entry (1, B)` na chave primária de `arte_tags`.

**Solução em 3 passos (dentro de transação):**

```
BEGIN TRANSACTION
  1. COUNT transferíveis  → artes que têm APENAS a tag origem
  2. COUNT duplicatas      → artes que têm AMBAS as tags
  3. UPDATE arte_tags SET tag_id = destino 
     WHERE tag_id = origem 
     AND arte_id NOT IN (SELECT arte_id WHERE tag_id = destino)  ← transfere só não-conflitantes
  4. DELETE FROM arte_tags WHERE tag_id = origem                  ← remove duplicatas restantes
  5. DELETE FROM tags WHERE id = origem                           ← deleta a tag origem
COMMIT
```

**Retorno:** `['transferidas' => int, 'duplicatas' => int]`

### Arquivos Alterados (5 arquivos)

| # | Arquivo | O que foi alterado |
|---|---------|-------------------|
| 1 | `config/routes.php` | + `POST /tags/{id}/merge` (ANTES do resource) |
| 2 | `src/Repositories/TagRepository.php` | + `mergeTags($origemId, $destinoId)` — transação SQL |
| 3 | `src/Services/TagService.php` | + `mergeTags($origemId, $destinoId)` — validações |
| 4 | `src/Controllers/TagController.php` | + `merge()` method + `show()` passa `$todasTags` |
| 5 | `views/tags/show.php` | + Card "Mesclar Tag" + Modal confirmação + JavaScript |

### Detalhes por Camada

**TagRepository::mergeTags(int $origemId, int $destinoId): array**
- Localização: após `getArtesByTag()`
- Transação completa com try/catch + rollback
- Contagem prévia de transferíveis vs duplicatas via subqueries
- UPDATE seletivo (só não-conflitantes) + DELETE residual + DELETE tag
- Retorna `['transferidas' => int, 'duplicatas' => int]`

**TagService::mergeTags(int $origemId, int $destinoId): array**
- Localização: após `remover()`
- Validações:
  - `$origemId === $destinoId` → ValidationException ("Não pode mesclar consigo mesma")
  - `findOrFail($origemId)` → NotFoundException se origem não existe
  - `findOrFail($destinoId)` → NotFoundException se destino não existe
- Retorna: `['tag_origem' => Tag, 'tag_destino' => Tag, 'transferidas' => int, 'duplicatas' => int]`

**TagController::merge(Request $request, int $id)**
- Localização: após `destroy()`, antes dos métodos AJAX
- Valida CSRF + extrai `tag_destino_id` do POST
- Chama `TagService::mergeTags()`
- Flash message detalhada: "X arte(s) transferida(s). Y duplicata(s) ignorada(s)."
- Redireciona para show da tag destino (a origem foi deletada)
- Catches: ValidationException → flash error + redirect show, NotFoundException → flash error + redirect /tags

**TagController::show() — Modificação**
- Adicionado: `$todasTags = $this->tagService->listarComContagem();`
- Passa `$todasTags` para a view (dropdown de merge precisa de todas as tags)

**views/tags/show.php — UI do Merge**
- Card "Mesclar Tag" (borda amarela) na sidebar, APÓS o card "Ações" (estrutura HTML correta)
- Select dropdown: todas as tags exceto a atual, com contagem de artes
- Botão: inicia `btn-secondary` (cinza) disabled, toggle para `btn-warning` (amarelo) ao selecionar
- Modal "Confirmar Mesclagem":
  - Badge origem com `$styleOrigem` (fallback se `getStyleInline()` vazio)
  - Seta → no meio
  - Badge destino com inline style (sem `bg-secondary` que usa `!important`)
  - Contagem de artes de cada tag
  - Alerta amarelo com 3 pontos sobre a irreversibilidade
  - Form POST com hidden `tag_destino_id` + CSRF
- JavaScript:
  - `addEventListener('change')`: toggle `btn-secondary` ↔ `btn-warning` + disabled
  - `abrirModalMerge()`: lê data-attributes, preenche badge, calcula contraste (luminância ITU-R BT.601)

### Correções Visuais (v2)

| Bug | Causa | Correção |
|-----|-------|---------|
| Botão amarelo-claro quando desabilitado | Bootstrap `btn-warning` + `disabled` só reduz opacidade | Classe inicial `btn-secondary`, JS alterna para `btn-warning` |
| Badges cinzas no modal | `bg-secondary` do BS5 usa `!important`, JS não sobrescreve | Inline style em vez de classe `bg-*` |
| Badge origem sem cor | `getStyleInline()` retornava vazio | Fallback com `getCor()` + `getCorTexto()` |
| Card merge dentro do card Ações | HTML aninhado incorretamente | Card merge como irmão (após) o card Ações |

### Testes Realizados

| Fase | Cenário | Resultado |
|------|---------|-----------|
| 1 | UI — view carrega, select, botão, modal, cancelar | ✅ PASSOU |
| 2 | Merge simples (sem duplicatas) | ✅ PASSOU |
| 3 | Merge com duplicatas (arte com ambas as tags) | ✅ PASSOU |
| 3.2 | Verificação SQL pós-merge (integridade banco) | ✅ PASSOU |
| — | Limpeza de dados de teste | ✅ EXECUTADA |

**Teste 3 (Cenário Crítico — Duplicatas):**
- Setup: Arte 1 com tags [Dup-Origem, Dup-Destino], Arte 2 só com Dup-Origem, Arte 5 só com Dup-Destino
- Merge Dup-Origem → Dup-Destino
- Resultado: Arte 2 transferida (1), Arte 1 duplicata ignorada (1)
- Verificação SQL: 3 registros em arte_tags com tag Dup-Destino (Artes 1, 2, 5) ✅

---

## ✅ MELHORIA 5 — ESTATÍSTICAS POR TAG (COMPLETA)

**Implementada em:** 12/02/2026  
**Arquivos alterados:** TagRepository, TagService, TagController, views/tags/show.php  
**Nenhuma migration necessária** — usa dados existentes via JOINs SQL

### Objetivo

Exibir métricas financeiras e de produção na página de detalhes de cada tag (`show.php`), cruzando dados das tabelas `artes` e `vendas` com a tabela pivot `arte_tags`. Permite ao usuário avaliar a performance de cada categoria/tag em termos de produção, custos, vendas, lucro e rentabilidade.

### Arquivos Alterados (4 arquivos)

| # | Arquivo | O que foi alterado |
|---|---------|-------------------|
| 1 | `src/Repositories/TagRepository.php` | + `getEstatisticasByTag(int $tagId): array` — 2 queries SQL + complexidade |
| 2 | `src/Services/TagService.php` | + `getEstatisticasTag(int $tagId): array` — métricas derivadas |
| 3 | `src/Controllers/TagController.php` | `show()` agora passa `$estatisticas` à view |
| 4 | `views/tags/show.php` | + seção de mini-cards (4 colunas) + card estatísticas detalhadas |

### Detalhes por Camada

#### TagRepository::getEstatisticasByTag(int $tagId): array

**Localização:** após `mergeTags()`, nova seção "ESTATÍSTICAS POR TAG (Melhoria 5)"

**Estratégia de 2 queries separadas** (não um único JOIN):
- **Query 1 — Artes:** `arte_tags` INNER JOIN `artes` → contagens por status, AVG/SUM custos, SUM horas
- **Query Complexidade:** GROUP BY `complexidade` ORDER BY COUNT DESC LIMIT 1 (separada por causa do GROUP BY)
- **Query 2 — Vendas:** `arte_tags` INNER JOIN `artes` INNER JOIN `vendas` → SUM valor/lucro, AVG ticket, MIN/MAX data

**Por que 2 queries separadas?** Se fizéssemos um único JOIN de artes + vendas, artes com múltiplas vendas seriam contadas múltiplas vezes no AVG/SUM de artes, distorcendo os resultados.

**Dados retornados (array associativo):**

| Chave | Tipo | Query | Descrição |
|-------|------|-------|-----------|
| `total_artes` | int | Q1 | Total de artes com esta tag |
| `artes_vendidas` | int | Q1 | Artes com status='vendida' |
| `artes_disponiveis` | int | Q1 | Artes com status='disponivel' |
| `artes_producao` | int | Q1 | Artes com status='em_producao' |
| `custo_medio` | float | Q1 | AVG(preco_custo) |
| `custo_total` | float | Q1 | SUM(preco_custo) |
| `horas_totais` | float | Q1 | SUM(horas_trabalhadas) |
| `complexidade_mais_comum` | ?string | QC | ENUM com maior contagem |
| `total_vendas` | int | Q2 | Quantidade de registros em vendas |
| `faturamento_total` | float | Q2 | SUM(vendas.valor) |
| `lucro_total` | float | Q2 | SUM(vendas.lucro_calculado) |
| `ticket_medio` | float | Q2 | AVG(vendas.valor) |
| `lucro_medio` | float | Q2 | AVG(vendas.lucro_calculado) |
| `rentabilidade_media` | float | Q2 | AVG(vendas.rentabilidade_hora) |
| `primeira_venda` | ?string | Q2 | MIN(vendas.data_venda) |
| `ultima_venda` | ?string | Q2 | MAX(vendas.data_venda) |

**Detalhes SQL importantes:**
- COALESCE garante valores 0 em vez de NULL quando não há dados
- CASE WHEN conta artes por status sem queries separadas
- Tipos explicitamente convertidos: `(int)`, `(float)`, `round()` para evitar problemas na view

#### TagService::getEstatisticasTag(int $tagId): array

**Localização:** após `mergeTags()`, nova seção "ESTATÍSTICAS POR TAG (Melhoria 5)"

**Responsabilidades do Service (não do Repository):**
1. Valida existência da tag via `findOrFail()` (lança NotFoundException)
2. Busca dados brutos no Repository
3. Calcula **métricas derivadas**:

| Chave adicionada | Fórmula | Descrição |
|-----------------|---------|-----------|
| `percentual_vendidas` | `(artes_vendidas / total_artes) * 100` | % de artes vendidas |
| `margem_lucro` | `(lucro_total / faturamento_total) * 100` | Eficiência financeira |
| `custo_por_hora` | `custo_total / horas_totais` | R$/hora investido |
| `complexidade_label` | match expression | "Baixa", "Média", "Alta" ou "—" |
| `tem_dados` | `total_artes > 0` | Flag para exibição na view |
| `tem_vendas` | `total_vendas > 0` | Flag para seção de vendas |

#### TagController::show() — Modificação

```php
// MELHORIA 5: Busca estatísticas da tag
$estatisticas = $this->tagService->getEstatisticasTag($id);

return $this->view('tags/show', [
    'titulo'       => 'Tag: ' . $tag->getNome(),
    'tag'          => $tag,
    'artes'        => $artes,
    'todasTags'    => $todasTags,      // MELHORIA 4
    'estatisticas' => $estatisticas,    // MELHORIA 5
]);
```

#### views/tags/show.php — UI das Estatísticas

**Posição no layout:** ANTES da tabela de artes, dentro da `col-lg-8` (coluna principal).

**Estrutura visual (2 componentes):**

**1. Mini-cards de Resumo (row com 4 colunas `col-6 col-md-3`):**
- **Total Artes** — ícone `bi-palette`, valor em azul (text-primary)
- **Vendidas** — ícone `bi-bag-check`, valor em verde (text-success) + "X% do total"
- **Faturamento** — ícone `bi-currency-dollar`, valor em cyan (text-info) com `number_format`
- **Lucro** — ícone `bi-graph-up-arrow`, cor dinâmica (verde positivo / vermelho negativo) + margem %

**2. Card Estatísticas Detalhadas (2 colunas internas):**

**Coluna Produção (`col-md-6`):**
- Badges de distribuição por status (disponível/produção/vendida) — condicionais
- Custo médio por arte
- Custo total investido
- Horas trabalhadas
- Custo médio por hora (R$/h) — condicional
- Complexidade predominante com ícone colorido (match expression)

**Coluna Vendas (`col-md-6`):**
- Se `tem_vendas`:
  - Vendas realizadas
  - Ticket médio
  - Lucro médio por venda (cor verde/vermelha)
  - Rentabilidade média (R$/h) — condicional
  - Barra visual de margem de lucro (progress bar: verde >30%, amarelo 10-30%, vermelho <10%)
  - Período de vendas (primeira — última, formatado "jan/2026 — fev/2026")
- Se NÃO `tem_vendas`:
  - Estado vazio: ícone `bi-cart-x` + mensagem informativa

**Estado vazio global:** Se `!$estatisticas['tem_dados']`, exibe alert cinza com ícone e mensagem "Estatísticas serão exibidas quando artes forem associadas a esta tag."

### Testes Realizados

| Cenário | Resultado |
|---------|-----------|
| Tag com artes e vendas — cards completos | ✅ PASSOU |
| Tag com artes sem vendas — seção vendas mostra estado vazio | ✅ PASSOU |
| Tag sem artes — alert informativo, sem cards | ✅ PASSOU |
| Valores financeiros (R$, %, margem) formatados corretamente | ✅ PASSOU |
| Mini-cards responsivos (2 colunas mobile, 4 desktop) | ✅ PASSOU |

---

## ✅ MELHORIA 6 — GRÁFICO DE DISTRIBUIÇÃO (COMPLETA)

**Implementada em:** 12/02/2026  
**Arquivos alterados:** TagService, TagController, views/tags/index.php  
**Dependências externas:** Chart.js 4.4.7 via CDN

### Objetivo

Visualização gráfica da distribuição de tags por quantidade de artes associadas, com gráfico interativo (Doughnut ↔ Barras horizontais) usando Chart.js.

### O que foi feito

**Backend (alterações mínimas):**
- **TagService:** +1 método `getContagemPorTag()` — wrapper que delega para `TagRepository::getContagemPorTag()` (já existia)
- **TagController::index():** +2 linhas — chama `getContagemPorTag()` e passa `$contagemPorTag` para a view

**Frontend (view index.php):**
- Card "Distribuição de Tags" posicionado entre "Tags Mais Usadas" e "Busca/Ordenação"
- Gráfico Doughnut (rosca) como default, com toggle para Barras horizontais
- Legenda lateral: nome + quantidade + porcentagem de cada tag
- Cores reais do banco de dados (campo `cor` de cada tag)
- Botão collapse para recolher/expandir o gráfico
- Container altura fixa 300px (evita loop de redimensionamento do Chart.js)
- Proteção: só exibe se existirem tags com ≥1 arte (sem gráfico vazio)
- Chart.js 4.4.7 via CDN (mesmo padrão do módulo Metas)

### Arquivos Alterados (3 arquivos)

| # | Arquivo | O que foi alterado | Linhas |
|---|---------|-------------------|--------|
| 1 | `src/Services/TagService.php` | + `getContagemPorTag()` wrapper | +15 |
| 2 | `src/Controllers/TagController.php` | + chamada + passagem para view no `index()` | +3 |
| 3 | `views/tags/index.php` | + seção completa do gráfico Chart.js | +130 |

### Detalhes Técnicos

**Dados do gráfico:**
- Fonte: `TagRepository::getContagemPorTag()` retorna `[{nome, cor, quantidade}]` ordenado DESC
- Filtro na view: `array_filter()` exclui tags com 0 artes (evita gráfico poluído)
- Total calculado com `array_sum()` para porcentagens na legenda

**Chart.js — Configuração:**
- Tipo default: `doughnut` com `cutout: '55%'`
- Tipo alternativo: `bar` horizontal (`indexAxis: 'y'`)
- Toggle: destrói instância atual e recria (previne memory leak)
- Tooltip customizado: `nome: X arte(s) (Y%)`
- Responsivo: `maintainAspectRatio: false` + container fixo

**Bug encontrado e corrigido durante implementação:**
- `TagService::getContagemPorTag()` não existia — mesmo padrão dos Bugs 1 e 2 da Fase 1 (método no Repository sem wrapper no Service)
- Correção: adicionado método simples que delega para o Repository

### Testes Realizados

| Teste | Resultado |
|-------|-----------|
| Página /tags carrega sem erro | ✅ OK |
| Gráfico Doughnut aparece com cores reais | ✅ OK |
| Toggle Doughnut ↔ Barras funciona | ✅ OK |
| Collapse recolhe/expande corretamente | ✅ OK |
| Legenda mostra quantidade + porcentagem | ✅ OK |
| Sem tags com artes → gráfico não aparece | ✅ OK |
| Melhorias 1-5 preservadas (paginação, ordenação, dropdown, etc.) | ✅ OK |

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

### TagRepository (`src/Repositories/TagRepository.php`) — Após Melhoria 5

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
| `mergeTags(int, int)` | array | **M4** | Transação: transfere artes + trata duplicatas + deleta origem |
| `getEstatisticasByTag(int)` | array | **M5** | 2 queries: artes (status/custo/horas) + vendas (valor/lucro/ticket) |

**Legenda:** F1=Fase 1, M1=Melhoria 1, M3=Melhoria 3, M4=Melhoria 4, M5=Melhoria 5

### TagService (`src/Services/TagService.php`) — Após Melhoria 6

| Método | Retorno | Fase | Descrição |
|--------|---------|------|-----------|
| `listar(array)` | array | Base | Lista com filtros |
| `listarPaginado(int, int, array)` | array | **M1** | Paginação + ordenação |
| `listarComContagem()` | array\<Tag> | Base | allWithCount() |
| `buscar(int)` | Tag | Base | Busca por ID |
| `criar(array)` | Tag | Base→**M3** | Agora aceita descricao/icone |
| `atualizar(int, array)` | Tag | Base→**M3** | Agora aceita descricao/icone |
| `remover(int)` | bool | Base | Remove com transação |
| `mergeTags(int, int)` | array | **M4** | Valida + delega merge ao Repository |
| `getEstatisticasTag(int)` | array | **M5** | Busca dados + calcula métricas derivadas (%, margem, R$/h) |
| `getMaisUsadas(int)` | array\<Tag> | Base | Top N |
| `getContagemPorTag()` | array | **M6** | Dados para gráfico Chart.js [{nome, cor, quantidade}] |
| `getParaSelect()` | array | Base | Para dropdowns |
| `getCoresPredefinidas()` | array | Base | Paleta de cores |
| `getIconesDisponiveis()` | array | **M3** | Ícones Bootstrap disponíveis |
| `criarSeNaoExistir(string, string)` | Tag | Base | findOrCreate |
| `criarDeString(string)` | array\<int> | Base | Múltiplas de CSV |
| `pesquisar(string, int)` | array | **F1** | Busca LIKE + contagem |
| `getArtesComTag(int)` | array | **F1** | Artes da tag |

### TagController (`src/Controllers/TagController.php`) — Após Melhoria 6

| Método | Rota | Descrição |
|--------|------|-----------|
| `index()` | GET /tags | Lista paginada + busca + ordenação + tags mais usadas + gráfico (M6) |
| `create()` | GET /tags/criar | Formulário com cores + ícones (M3) |
| `store()` | POST /tags | Valida + cria (nome, cor, descricao, icone) |
| `show($id)` | GET /tags/{id} | Detalhes + artes + descrição (M3) + merge (M4) + estatísticas (M5) |
| `edit($id)` | GET /tags/{id}/editar | Form edição com ícones (M3) |
| `update($id)` | PUT /tags/{id} | Atualiza 4 campos |
| `destroy($id)` | DELETE /tags/{id} | Remove + flash |
| `merge($request, $id)` | POST /tags/{id}/merge | **M4** — Mescla tag origem na destino |
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

TAGS — Rota de Merge (declarada ANTES do resource) — MELHORIA 4
  POST /tags/{id}/merge → TagController@merge         (mesclar tags)

TAGS — Resource (7 rotas automáticas)
  GET    /tags           → TagController@index         (listar paginado)
  GET    /tags/criar     → TagController@create        (formulário)
  POST   /tags           → TagController@store         (salvar)
  GET    /tags/{id}      → TagController@show          (detalhes + merge + estatísticas)
  GET    /tags/{id}/editar → TagController@edit        (form editar)
  PUT    /tags/{id}      → TagController@update        (atualizar)
  DELETE /tags/{id}      → TagController@destroy       (excluir)
```

**REGRA CRÍTICA:** Rotas AJAX e Merge ANTES de `$router->resource(...)`. Caso contrário, Router interpreta "buscar" ou "merge" como `{id}`.

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

### Listar Tags (GET /tags) — Após Melhorias 1+2+6

```
1. TagController::index() recebe Request
2. Extrai: page, ordenar, direcao, termo
3. Se termo → TagService::pesquisar() (busca LIKE)
4. Senão → TagService::listarPaginado(page, 12, filtros)
   → TagRepository::allWithCountPaginated() com LIMIT/OFFSET + ORDER BY dinâmico
5. TagService::getMaisUsadas(5) → top 5 para sidebar
6. TagService::getContagemPorTag() → dados para gráfico Chart.js (M6)
7. View recebe: $tags, $paginacao, $tagsMaisUsadas, $contagemPorTag, $filtros
```

### Ver Detalhes (GET /tags/{id}) — Após Melhorias 4+5

```
1. TagController::show() recebe Request + id
2. TagService::buscar($id) → findOrFail() → Tag
3. TagService::getArtesComTag($id) → arrays FETCH_ASSOC
4. TagService::listarComContagem() → todas as tags para dropdown merge (M4)
5. TagService::getEstatisticasTag($id) → dados brutos + métricas derivadas (M5)
   5a. findOrFail($tagId) → valida existência
   5b. TagRepository::getEstatisticasByTag($tagId) → 2 queries SQL
   5c. Calcula: percentual_vendidas, margem_lucro, custo_por_hora, complexidade_label
   5d. Flags: tem_dados, tem_vendas
6. View recebe: $tag, $artes, $todasTags, $estatisticas
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

### Mesclar Tags (POST /tags/{id}/merge) — MELHORIA 4

```
1. TagController::merge() recebe Request + id (origem)
2. validateCsrf($request) → protege contra CSRF
3. Extrai tag_destino_id do POST
4. Validação: destino_id vazio → flash error + redirect show
5. TagService::mergeTags($id, $destinoId)
   5a. Valida: $origemId === $destinoId → ValidationException
   5b. findOrFail($origemId) → NotFoundException se não existe
   5c. findOrFail($destinoId) → NotFoundException se não existe
   5d. TagRepository::mergeTags($origemId, $destinoId)
       → BEGIN TRANSACTION
       → COUNT transferíveis (artes SÓ na origem)
       → COUNT duplicatas (artes em AMBAS)
       → UPDATE arte_tags: transfere não-conflitantes (origem → destino)
       → DELETE arte_tags: remove duplicatas restantes da origem
       → DELETE tags: remove tag origem
       → COMMIT
   5e. Retorna ['tag_origem', 'tag_destino', 'transferidas', 'duplicatas']
6. flashSuccess("Tag mesclada! X transferida(s), Y duplicata(s) ignorada(s)")
7. redirectTo('/tags/' . $destinoId) → abre show da tag destino
```

---

## 🔍 NOTAS TÉCNICAS IMPORTANTES

### View show.php — Sempre Usar Acesso por Array
As artes em show.php vêm do `TagRepository::getArtesByTag()` que retorna `FETCH_ASSOC`. Usar `$arte['nome']`, NUNCA `$arte->getNome()`.

### Rotas AJAX e Merge Antes do Resource
As 3 rotas AJAX + rota de merge DEVEM ser declaradas ANTES de `$router->resource(...)`. Se movidas para depois, Router interpreta "buscar" ou parâmetros como `{id}`.

### Transação na Exclusão
`deleteWithRelations()` usa `BEGIN TRANSACTION` + `COMMIT/ROLLBACK` mesmo com CASCADE nas FKs.

### Transação no Merge (M4)
`mergeTags()` usa transação completa com try/catch. Se qualquer passo falhar, faz ROLLBACK. A ordem das operações é crítica: UPDATE antes de DELETE para evitar perda de dados.

### Contagem de Artes — LEFT JOIN vs INNER JOIN
- `allWithCount()` / `allWithCountPaginated()` = LEFT JOIN (todas as tags)
- `getMaisUsadas()` = INNER JOIN (apenas com artes)

### Contraste Automático de Texto
`Tag::getCorTexto()` calcula luminância (ITU-R BT.601) para decidir texto preto/branco. Mesmo algoritmo replicado no JavaScript do modal de merge.

### Bootstrap 5 — bg-* Classes Usam !important
Classes como `bg-secondary` aplicam `background-color: ... !important;`. Para badges que precisam de cor dinâmica via JavaScript, usar inline style em vez de classes `bg-*`.

### Router Bug Fix — Conversão de Tipos
O Router tem fix que converte parâmetros string de URL para int, prevenindo TypeErrors em `findOrFail()`.

### Variável de Anos no Metas
O controller de Metas passa `'anosDisponiveis'` (renomeado de `'anos'`). Se filtro de anos quebrar, reverter nome da variável.

### Estatísticas: 2 Queries Separadas (M5)
`getEstatisticasByTag()` usa 2 queries em vez de 1 JOIN grande. Motivo: artes com múltiplas vendas distorceriam AVG/SUM das artes se feitas em uma única query. Separar garante precisão.

### Estatísticas: Proteção Contra Divisão por Zero (M5)
O Service calcula métricas derivadas com verificação prévia: `total_artes > 0`, `faturamento_total > 0`, `horas_totais > 0`. A view usa flags `tem_dados` e `tem_vendas` para decidir o que exibir.

### Gráfico: Wrapper Ausente no Service (M6)
`TagRepository::getContagemPorTag()` existia desde a base mas nunca teve wrapper no Service. Mesmo padrão dos Bugs 1 e 2 (Fase 1). Lição: sempre validar que métodos do Repository têm correspondente no Service antes de chamar no Controller.

### Gráfico: Chart.js via CDN (M6)
Chart.js 4.4.7 carregado via `cdn.jsdelivr.net` — mesmo padrão do módulo Metas. O script só é incluído se `$temDadosGrafico` for true (evita carregamento desnecessário). Container com altura fixa de 300px previne loop de redimensionamento.

---

## 📌 PRÓXIMAS AÇÕES (para nova conversa)

1. **Limpeza opcional:** Existem tags de teste no banco (Teste2, Teste5, Teste6, Teste7, Teste8) com 0 artes que podem ser removidas:
   ```sql
   DELETE FROM tags WHERE nome LIKE 'Teste%' AND id NOT IN (
       SELECT DISTINCT tag_id FROM arte_tags
   );
   ```

2. **Módulo Tags COMPLETO:** Todas as 6 melhorias planejadas foram implementadas. O módulo está estável e funcional.

3. **Próximo módulo:** Iniciar ciclo de melhorias em outro módulo (Artes, Clientes ou Vendas) seguindo o mesmo padrão: estabilização → melhorias incrementais → documentação.

---

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

**Última atualização:** 12/02/2026  
**Status:** ✅ Módulo Tags — 6 melhorias completas, MÓDULO COMPLETO  
**Próxima ação:** Próximo módulo (Artes, Clientes ou Vendas)
