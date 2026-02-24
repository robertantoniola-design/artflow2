# ArtFlow 2.0 — Módulo Vendas: Documentação Completa

**Data:** 24/02/2026  
**Status Geral:** ✅ FASE 1 + M1+M2+M3 COMPLETAS — Paginação, Ordenação e Filtros funcionais  
**Versão Base:** Código corrigido na Fase 1 (22/02/2026) + Melhorias M1+M2+M3 (23/02/2026)  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Vendas do ArtFlow 2.0 é o **módulo mais acoplado** do sistema — registra transações de venda de artes, calcula lucro e rentabilidade automaticamente, atualiza o status da arte para "vendida" e incrementa o progresso das metas mensais. É o único módulo com 3 dependências no Controller e 4 no Service, orquestrando operações que afetam 3 tabelas simultaneamente (`vendas`, `artes`, `metas`).

O módulo é **pré-requisito** para:
1. **Dashboard completo** — 5 dos 8 dados do Dashboard vêm de Vendas
2. ~~**Cards Lucro + Rentabilidade** do módulo Artes (M5 pendência cross-module)~~ ✅ IMPLEMENTADO (22/02/2026)
3. **Metas funcionais** — o `valor_realizado` e `porcentagem_atingida` dependem de vendas registradas

A Fase 1 foi concluída em 22/02/2026 com **6 bugs corrigidos** e **12/12 testes manuais OK**. As melhorias M1+M2+M3 foram implementadas em 23/02/2026 com **14/14 testes OK** (T7 corrigido via fix do bug global scope). Todas as integrações cross-module funcionam nos dois sentidos: registrar venda → arte vendida + meta incrementada, excluir venda → arte disponível + meta decrementada.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 6 bugs corrigidos, 12/12 testes | ✅ COMPLETA (22/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | ✅ COMPLETA (23/02/2026) |
| Melhoria 2 | Ordenação dinâmica (7 colunas clicáveis) | ✅ COMPLETA (23/02/2026) |
| Melhoria 3 | Filtros combinados (termo + cliente + pgto + período) | ✅ COMPLETA (23/02/2026) |
| Melhoria 4 | Relatório aprimorado (resumo financeiro + exportação) | 📋 PLANEJADA |
| Melhoria 5 | Estatísticas por venda (cards métricas no show.php) | 📋 PLANEJADA |
| Melhoria 6 | Gráficos de vendas (Chart.js — faturamento + ranking) | 📋 PLANEJADA |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | Fase 1 ✅ | ✅ COMPLETA (23/02) |
| 2 | Ordenação dinâmica (7 colunas) | Baixa | Melhoria 1 ✅ | ✅ COMPLETA (23/02) |
| 3 | Filtros combinados (termo + cliente + pgto + período) | Média | Melhoria 1 ✅ | ✅ COMPLETA (23/02) |
| 4 | Relatório aprimorado + exportação | Média | Fase 1 ✅ | 📋 PLANEJADA |
| 5 | Estatísticas por venda (cards no show.php) | Média | Fase 1 ✅ | 📋 PLANEJADA |
| 6 | Gráficos de vendas (Chart.js) | Baixa | Fase 1 ✅ | 📋 PLANEJADA |

### ✅ PENDÊNCIA CROSS-MODULE RESOLVIDA (Artes ↔ Vendas)

| Pendência | Origem | Onde implementado | Status |
|-----------|--------|-------------------|--------|
| Card **Lucro** no Artes show.php | Artes M5 | ArteService + views/artes/show.php | ✅ COMPLETO (22/02/2026) |
| Card **Rentabilidade** no Artes show.php | Artes M5 | ArteService + views/artes/show.php | ✅ COMPLETO (22/02/2026) |

**Implementação realizada:**
1. `ArteService::getDadosVenda(Arte)` — método privado, busca venda via `findFirstBy('arte_id', $id)`
2. `ArteService::calcularLucro(Arte)` — retorna `valor_venda`, `lucro`, `margem_percentual`
3. `ArteService::calcularRentabilidade(Arte)` — retorna R$/hora (recalcula com horas atuais)
4. `ArteService::getMetricasArte()` agora retorna 5 métricas (antes 3)
5. `VendaRepository` adicionado como dependência do ArteService (auto-wiring resolve)
6. Cards condicionais na view: só aparecem quando `$arte->getStatus() === 'vendida'`

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Venda.php                      ✅ Implementado (getters/setters + Arte/Cliente relacionados)
├── Repositories/
│   └── VendaRepository.php            🔧 Fase 1 + M1+M2+M3 (+ allPaginated, countAll)
├── Services/
│   └── VendaService.php               🔧 Fase 1 + M1 (+ listarPaginado, POR_PAGINA)
├── Controllers/
│   └── VendaController.php            🔧 Fase 1 + M1+M2+M3 (index reescrito)
└── Validators/
    └── VendaValidator.php             ✅ Implementado (arte_id, valor, data, forma_pgto)

views/
└── vendas/
    ├── index.php                      🔧 M1+M2+M3 (reescrito: paginação + filtros + ordenação + R$/h)
    ├── create.php                     ✅ Funcional (selects arte + cliente + campos)
    ├── show.php                       ✅ Funcional (detalhes com arte + cliente hydrated)
    ├── edit.php                       ✅ Funcional (edição com arte_id fixo)
    └── relatorio.php                  🔧 Fase 1 (fix chave total_vendas vs total)
```

### Dependências entre Classes (MÓDULO MAIS ACOPLADO)

```
VendaController
├── __construct(VendaService, ArteService, ClienteService)  ← 3 dependências!
│
├── index()     usa VendaService::listarPaginado() + getEstatisticas() + ClienteService::getParaSelect()  [M1+M2+M3]
├── create()    usa ArteService::getDisponiveisParaVenda() + ClienteService::getParaSelect()
├── store()     usa VendaService::registrar() [orquestra 3 tabelas]
├── show()      usa VendaService::buscarComRelacionamentos() [V9 fix]
├── edit()      usa VendaService::buscarComRelacionamentos() + ClienteService::getParaSelect()
├── update()    usa VendaService::atualizar()
├── destroy()   usa VendaService::excluir() [V7 fix: reverte arte + recalcula meta]
└── relatorio() usa VendaService::getVendasMensais() + getEstatisticas() + getRankingRentabilidade()

VendaService ← ORQUESTRA 3 REPOSITORIES
├── VendaRepository   — CRUD vendas + allPaginated/countAll [M1+M2+M3]
├── ArteRepository    — buscar arte + atualizar status → 'vendida' / 'disponivel'
├── MetaRepository    — incrementar/recalcular meta do mês
└── VendaValidator    — validação de dados
```

### Fluxo Principal: Registrar Venda

```
POST /vendas → VendaController::store()
  │
  ├─► 1. Sanitiza dados (cliente_id vazio → null, observacoes vazia → null)
  ├─► 2. VendaValidator::validate() — campos obrigatórios + tipos
  ├─► 3. ArteRepository::findOrFail() — busca arte
  ├─► 4. VendaValidator::validateArteDisponivel() — status != 'vendida'
  ├─► 5. Calcula: lucro = valor - arte.preco_custo
  ├─► 6. Calcula: rentabilidade = lucro / arte.horas_trabalhadas
  ├─► 7. VendaRepository::create() — INSERT na tabela vendas
  ├─► 8. ArteRepository::update(arte_id, ['status' => 'vendida'])
  └─► 9. MetaRepository: recalcularMetaMes() via findByMesAno()
```

### Fluxo: Excluir Venda (CORRIGIDO V7)

```
DELETE /vendas/{id} → VendaController::destroy()
  │
  ├─► 1. VendaService::buscar($id) — busca venda
  ├─► 2. ArteRepository::update(arte_id, ['status' => 'disponivel'])  ← V7 FIX
  ├─► 3. VendaRepository::delete($id) — remove registro
  └─► 4. VendaService::recalcularMetaMes() — re-soma vendas do mês
         ├─► VendaRepository::getTotalVendasMes()
         └─► MetaRepository::atualizarProgresso()
```

### Tabela `vendas` (Banco de Dados)

```sql
CREATE TABLE vendas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arte_id INT UNSIGNED NULL,                          -- FK → artes(id) SET NULL
    cliente_id INT UNSIGNED NULL,                       -- FK → clientes(id) SET NULL
    valor DECIMAL(10,2) NOT NULL,                       -- Valor da venda em R$
    data_venda DATE NOT NULL,                           -- Data da transação
    lucro_calculado DECIMAL(10,2) NULL,                 -- valor - preco_custo (calculado no Service)
    rentabilidade_hora DECIMAL(10,2) NULL,              -- lucro / horas_trabalhadas (calculado)
    forma_pagamento ENUM('dinheiro','pix','cartao_credito','cartao_debito','transferencia','outro')
                    DEFAULT 'pix',                      -- Método de pagamento
    observacoes TEXT NULL,                               -- Notas da venda
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (arte_id) REFERENCES artes(id) ON DELETE SET NULL,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL,

    INDEX idx_vendas_data (data_venda),
    INDEX idx_vendas_arte (arte_id),
    INDEX idx_vendas_cliente (cliente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Campos do Formulário (create.php / edit.php)

| Campo | Tipo HTML | Validação | Obrigatório | Notas |
|-------|-----------|-----------|-------------|-------|
| arte_id | select | exists:artes, status != vendida | ✅ | Lista artes disponíveis (não vendidas) |
| cliente_id | select | exists:clientes (se fornecido) | ❌ | Venda pode ser sem cliente identificado |
| valor | number (step 0.01) | min:0.01, max:9999999.99 | ✅ | Em R$ |
| data_venda | date | date_format Y-m-d | ✅ | Default: hoje |
| forma_pagamento | select | in:dinheiro,pix,cartao_credito,cartao_debito,transferencia,outro | ✅ | Default: pix |
| observacoes | textarea | — | ❌ | Notas livres |

**Nota sobre edit:** Na edição, `arte_id` NÃO pode ser alterado (arte já marcada como vendida). Apenas `cliente_id`, `valor`, `data_venda`, `forma_pagamento` e `observacoes` são editáveis.

### Rotas (8 total)

```
VENDAS (7 RESTful + 1 extra)
  GET    /vendas/relatorio   → VendaController@relatorio   (ANTES do resource! Senão "relatorio" = {id})
  GET    /vendas             → VendaController@index        (lista com filtros + resumo)
  GET    /vendas/criar       → VendaController@create       (formulário com selects arte/cliente)
  POST   /vendas             → VendaController@store        (registra + calcula lucro + atualiza meta)
  GET    /vendas/{id}        → VendaController@show         (detalhes com arte + cliente)
  GET    /vendas/{id}/editar → VendaController@edit         (edição — arte_id fixo)
  PUT    /vendas/{id}        → VendaController@update       (atualiza dados editáveis)
  DELETE /vendas/{id}        → VendaController@destroy      (exclui + reverte arte + recalcula meta)
```

**REGRA CRÍTICA:** A rota `/vendas/relatorio` DEVE ser declarada ANTES de `$router->resource('/vendas')`, senão o Router interpreta "relatorio" como `{id}` numérico e chama `show()`.

---

## ✅ FASE 1 — ESTABILIZAÇÃO CRUD (COMPLETA)

**Status:** ✅ COMPLETA — 22/02/2026  
**Bugs corrigidos:** 6 (V1, V2, V3, V7, V9, findByMesAno)  
**Testes:** 12/12 OK  
**Arquivos alterados:** VendaController.php, VendaService.php, views/vendas/relatorio.php

### Bugs Corrigidos

| # | Bug | Correção | Arquivo |
|---|-----|----------|---------|
| V1 | **B8 — Validação Invisível** — `store()`/`update()` usavam `Response::withErrors()` que grava em `$_SESSION['_flash']`, mas views leem `$_SESSION['_errors']` | Gravação direta em `$_SESSION['_errors']` (padrão ClienteController) | VendaController |
| V2 | **B9 — Dados Residuais** — Faltava `limparDadosFormulario()` em métodos de leitura | Método privado `limparDadosFormulario()` + chamadas em `index()`, `show()`, `edit()`, `relatorio()`. NUNCA em `create()` | VendaController |
| V3 | **Conversão string→int** — Router passa `$id` como string, Service espera int | `$id = (int) $id` em `show()`, `edit()`, `update()`, `destroy()` | VendaController |
| V7 | **destroy() não revertia arte** — Arte permanecia 'vendida' após excluir venda | `excluir()` agora reverte arte para 'disponivel' ANTES de recalcular meta | VendaService |
| V9 | **show() sem relacionamentos** — Usava `findOrFail()` perdendo arte_nome/cliente_nome | Novo método `buscarComRelacionamentos()` usando `findWithRelations()` | VendaService |
| — | **findMesAno() inexistente** — `recalcularMetaMes()` chamava método que não existe no MetaRepository | Corrigido para `findByMesAno()` (nome real no MetaRepository) | VendaService |
| — | **Chave view relatório** — Card "Total Vendas" mostrava 0 por chave incorreta | `$estatisticas['total']` → `$estatisticas['total_vendas'] ?? $estatisticas['total']` | relatorio.php |

### Checklist de Testes Fase 1

| # | Operação | Rota | O que verificar | Status |
|---|----------|------|-----------------|--------|
| T1 | Listar | `GET /vendas` | Carrega, exibe 27 vendas com arte/cliente | ✅ OK |
| T2 | Criar (form) | `GET /vendas/criar` | Selects de artes (11) e clientes (13) populados | ✅ OK |
| T3 | Criar (salvar) | `POST /vendas` | Validação funciona, lucro calculado automaticamente | ✅ OK |
| T4 | Verificar arte | — | Após T3: arte muda para status 'vendida' | ✅ OK |
| T5 | Verificar meta | — | Após T3: meta do mês incrementa valor_realizado | ✅ OK |
| T6 | Visualizar | `GET /vendas/{id}` | Exibe dados + arte_nome + cliente_nome + lucro | ✅ OK |
| T7 | Editar (form) | `GET /vendas/{id}/editar` | Campos preenchidos, arte_id fixo (não editável) | ✅ OK |
| T8 | Editar (salvar) | `PUT /vendas/{id}` | Valor alterado, lucro recalculado, meta ajustada | ✅ OK |
| T9 | Excluir | `DELETE /vendas/{id}` | Confirmação funciona, venda removida | ✅ OK |
| T10 | Meta + Arte pós-excluir | — | Meta decrementada, arte volta para 'disponivel' | ✅ OK |
| T11 | Relatório | `GET /vendas/relatorio` | Página carrega, cards e tabela corretos | ✅ OK |
| T12 | Validação | `POST /vendas` vazio | Validação HTML5 nativa bloqueia envio | ✅ OK |

### Correções Adicionais no VendaController

| Melhoria | Detalhe |
|----------|---------|
| **Sanitização** | `cliente_id` vazio → `null`, `observacoes` vazia → `null` |
| **Catch DatabaseException** | Logs detalhados para diagnóstico cross-module |
| **buscarComRelacionamentos()** | Novo método no Service para hydrating Arte+Cliente |

---

## ✅ MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (COMPLETA)

**Status:** ✅ COMPLETA — 23/02/2026  
**Padrão:** Idêntico a Tags, Clientes e Artes (12 itens por página)  
**Pré-requisito:** Fase 1 ✅  
**Testes:** T1–T5 OK (14/14 total com M2+M3)

### Implementação Realizada

| Recurso | Descrição |
|---------|-----------|
| **12 vendas por página** | Controles Bootstrap 5 com janela de 5 páginas centrada |
| **Preservação de filtros** | Helper `vendaUrl()` mantém todos os parâmetros ao paginar |
| **Indicador** | "Mostrando X–Y de Z vendas" + "Página N de M" |
| **Navegação** | Primeira, Anterior, [janela 5 pags], Próxima, Última |

### Arquivos Alterados

| Arquivo | Ação | Detalhes |
|---------|------|---------|
| VendaRepository | **+2 métodos novos** | `allPaginated()` com JOINs + hydrating, `countAll()` com mesmos filtros |
| VendaService | **+1 método + 1 constante** | `listarPaginado($filtros)`, `const POR_PAGINA = 12` |
| VendaController | **index() reescrito** | Usa `listarPaginado()`, passa `$paginacao` para a view |
| views/vendas/index.php | **Reescrito** | Helper `vendaUrl()`, controles Bootstrap 5, "X–Y de Z" |

### Nota sobre Compatibilidade

Os métodos originais `listar()`, `paginate()`, `getRecentes()` permanecem intactos para compatibilidade com Dashboard e relatório. O `index()` agora usa exclusivamente `listarPaginado()`.

---

## ✅ MELHORIA 2 — ORDENAÇÃO DINÂMICA (COMPLETA)

**Status:** ✅ COMPLETA — 23/02/2026  
**Padrão:** Idêntico a Tags, Clientes e Artes (headers clicáveis com toggle)  
**Pré-requisito:** Melhoria 1 ✅  
**Testes:** T6–T8 OK

### Colunas Ordenáveis (7 colunas)

| Coluna | Campo BD | Direção padrão | Tipo ícone |
|--------|----------|----------------|------------|
| Data | `v.data_venda` | DESC (recentes) — **PADRÃO** | bi-sort-down/up |
| Arte | `a.nome` (via JOIN) | ASC (A→Z) | bi-sort-alpha-down/up |
| Cliente | `c.nome` (via JOIN) | ASC (A→Z) | bi-sort-alpha-down/up |
| Valor | `v.valor` | DESC (maior primeiro) | bi-sort-numeric-down/up |
| Lucro | `v.lucro_calculado` | DESC (maior primeiro) | bi-sort-numeric-down/up |
| R$/h | — | — (não ordenável, exibição apenas) | — |
| Forma Pgto | `v.forma_pagamento` | ASC (ordem ENUM) | bi-sort-alpha-down/up |

### Implementação

| Componente | Descrição |
|-----------|-----------|
| **Whitelist** | `$colunasPermitidas` no Repository mapeia nomes da URL → colunas SQL com alias |
| **Segurança** | Apenas colunas na whitelist aceitas; fallback para `v.data_venda` |
| **Toggle** | `vendaSortUrl()` inverte ASC↔DESC se coluna já ativa; reset para pag 1 |
| **Ícones** | `vendaSortIcon()` — ativo: azul com direção, inativo: cinza neutro |

### ⚠️ Bug Corrigido: Global Scope em Helpers (23/02/2026)

**Problema:** Os helpers `vendaUrl()`, `vendaSortUrl()`, `vendaSortIcon()` usavam `global $filtros`, mas o framework renderiza views dentro de `extract()` que cria escopo local. `global` aponta para o escopo global (vazio), não para a variável local `$filtros`.

**Sintoma:** T6 (primeira ordenação) passava por coincidência, T7 (toggle) falhava pois `$ordenarAtual` sempre era o default `'data_venda'`.

**Fix aplicado:**
```php
// Topo do index.php — empurra para escopo global
$GLOBALS['_vendaFiltros'] = $filtros ?? [];

// Dentro dos helpers — lê do escopo global
$filtros = $GLOBALS['_vendaFiltros'] ?? [];
```

**⚠️ VERIFICAR:** Este mesmo bug pode existir nos módulos Tags, Clientes e Artes se usam `global $filtros` em helpers das views index.php. Todos devem usar `$GLOBALS['_key']` em vez de `global`.

---

## ✅ MELHORIA 3 — FILTROS COMBINADOS (COMPLETA)

**Status:** ✅ COMPLETA — 23/02/2026  
**Padrão:** WHERE dinâmico com AND (mesmo de Artes M1/M3)  
**Pré-requisito:** Melhoria 1 ✅  
**Testes:** T9–T14 OK

### Filtros Implementados (5 campos combinados com AND)

| Filtro | Tipo | Campo BD | UI |
|--------|------|----------|-----|
| Busca (termo) | text | `a.nome LIKE` OR `v.observacoes LIKE` | Input text com placeholder |
| Cliente | select | `v.cliente_id = ?` | Dropdown com todos os clientes |
| Forma pagamento | select | `v.forma_pagamento = ?` | Dropdown com 6 opções |
| Data início | date | `v.data_venda >= ?` | Input date |
| Data fim | date | `v.data_venda <= ?` | Input date |

### Implementação

| Componente | Descrição |
|-----------|-----------|
| VendaRepository `allPaginated()` | WHERE dinâmico com AND — 5 filtros combinados simultaneamente |
| VendaRepository `countAll()` | **MESMOS filtros** que `allPaginated()` — crucial para paginação consistente |
| VendaService `listarPaginado()` | Normaliza filtros com `?? null ?: null` (strings vazias → null) |
| VendaController `index()` | Extrai 8 parâmetros da URL (5 filtros + pagina + ordenar + direcao) |
| views/vendas/index.php | Barra de filtros com 5 campos + botões Filtrar/Limpar |

**Superado:** O bug V4 (filtros mutuamente exclusivos no `listar()`) agora é irrelevante para a listagem, pois `index()` usa `listarPaginado()` com WHERE dinâmico. O método `listar()` permanece intacto para compatibilidade com código legado.

---

## 📋 MELHORIA 4 — RELATÓRIO APRIMORADO (PLANEJADA)

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅ (recomendado: após M6 para reaproveitar gráficos)  
**Rota:** `GET /vendas/relatorio` (já existe)

### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Filtro por período** | Data início + data fim |
| **Filtro por ano** | Dropdown com anos disponíveis |
| **Cards de resumo** | Total vendas, Faturamento, Lucro total, Ticket médio, Margem média |
| **Tabela detalhada** | Lista de vendas do período com arte, cliente, valor, lucro |
| **Comparativo mensal** | Tabela mês a mês com evolução |

---

## 📋 MELHORIA 5 — ESTATÍSTICAS POR VENDA (PLANEJADA)

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅

### Cards de Métricas no show.php

| Card | Dado | Cálculo | Condição |
|------|------|---------|----------|
| **Margem de Lucro** | % de lucro sobre valor | `(lucro / valor) × 100` | Sempre |
| **Rentabilidade/Hora** | R$/hora de lucro | `lucro / horas_trabalhadas` (da arte) | Se arte com horas > 0 |
| **Comparativo** | vs média de vendas | valor da venda vs ticket médio geral | Sempre |
| **Posição no Ranking** | X° mais rentável | Ranking entre todas as vendas | Se rentabilidade > 0 |

---

## 📋 MELHORIA 6 — GRÁFICOS DE VENDAS (PLANEJADA)

**Complexidade:** Baixa  
**Pré-requisito:** Fase 1 ✅  
**Biblioteca:** Chart.js 4.4.7 via CDN (mesmo padrão Tags/Metas/Artes)

### Gráficos no index.php

| Gráfico | Tipo Chart.js | Dados | Localização |
|---------|--------------|-------|-------------|
| **Faturamento Mensal** | Barras verticais | SUM(valor) por mês (últimos 6-12 meses) | index.php (topo) |
| **Forma de Pagamento** | Doughnut | COUNT por forma_pagamento | index.php (topo) |

### Cards de Resumo no index.php (já implementados em M1)

| Indicador | Cálculo | Status |
|-----------|---------|--------|
| **Total de Vendas** | COUNT(*) | ✅ Implementado |
| **Faturamento Total** | SUM(valor) | ✅ Implementado |
| **Lucro Total** | SUM(lucro_calculado) | ✅ Implementado |
| **Ticket Médio** | AVG(valor) ou SUM/COUNT | ✅ Implementado |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível

**Status no módulo Vendas:** ✅ Workaround aplicado no VendaController (grava direto em `$_SESSION['_errors']`).

### Bug B9: Dados Residuais no Edit

**Status no módulo Vendas:** ✅ Workaround aplicado — `limparDadosFormulario()` chamado em index(), show(), edit(), relatorio().

### Conversão string→int do Router

**Status no módulo Vendas:** ✅ Corrigido — `$id = (int) $id` em show(), edit(), update(), destroy().

### ⚠️ Bug Global Scope em Helpers de Views (NOVO — 23/02/2026)

**Escopo:** Potencialmente afeta TODOS os módulos que usam `global $variavel` dentro de funções helper definidas em views.

**Causa:** O framework renderiza views dentro de `extract($data)`, criando escopo local. Funções definidas na view (helpers) usam `global $variavel` que aponta para o escopo **global** (vazio), não para a variável local criada por `extract()`.

**Fix:** Usar `$GLOBALS['_chave']` em vez de `global $variavel`:
```php
// No topo da view — empurra variável para escopo global
$GLOBALS['_vendaFiltros'] = $filtros ?? [];

// Dentro dos helpers — lê de $GLOBALS
function vendaUrl(array $override = []): string {
    $filtros = $GLOBALS['_vendaFiltros'] ?? [];
    // ...
}
```

**Status por módulo:**

| Módulo | Usa helpers com global? | Fix aplicado? |
|--------|------------------------|---------------|
| Tags index.php | ⚠️ VERIFICAR | ❓ Pendente |
| Clientes index.php | ⚠️ VERIFICAR | ❓ Pendente |
| Artes index.php | ⚠️ VERIFICAR | ❓ Pendente |
| Vendas index.php | ✅ Usa helpers | ✅ Corrigido (23/02) |

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos do VendaRepository

| Método | Melhoria | Descrição |
|--------|----------|-----------|
| `allPaginated(...)` | **M1+M2+M3** | Query paginada com JOINs + WHERE dinâmico + whitelist ORDER BY |
| `countAll(...)` | **M1+M3** | COUNT com mesmos filtros — crucial para paginação |
| `allWithRelations()` | Base | Lista todas com JOINs (LEGADO — usar allPaginated) |
| `findWithRelations($id)` | Base | Busca por ID com JOINs (usado em show/edit) |
| `findByPeriodo($inicio, $fim)` | Base | Filtro por período |
| `findByMesAno($ano, $mes)` | Base | Filtro por mês/ano |
| `findByMes($mesAno)` | Base | Alias para findByMesAno (formato YYYY-MM) |
| `getTotalVendasMes($mesAno)` | Base | SUM(valor) do mês |
| `somaVendasMes($ano, $mes)` | Base | SUM(valor) por ano+mês |
| `getEstatisticas()` | Base | COUNT, SUM, AVG globais |
| `vendasPorMes($meses)` | Base | GROUP BY mês (gráficos) |
| `vendasPorCliente()` | Base | GROUP BY cliente |
| `paginate($page, $perPage, $filters)` | Base | Paginação básica (LEGADO) |
| `findByCliente($clienteId)` | Base | Filtro por cliente |
| `getRecentes($limit)` | Base | Últimas vendas (arrays brutos) |
| `getVendasPorMes($meses)` | Base | Alias para vendasPorMes |
| `getMaisRentaveis($limit)` | Base | TOP N por rentabilidade_hora |

### Métodos do VendaService chamados no Controller

| Método no Controller | Existe no Service? | Status |
|---------------------|--------------------|--------|
| `listarPaginado($filtros)` | ✅ | **NOVO M1** — substitui listar() no index |
| `listar($filtros)` | ✅ | LEGADO — mantido para Dashboard/relatório |
| `buscar($id)` | ✅ | Usa `findOrFail()` |
| `buscarComRelacionamentos($id)` | ✅ | **NOVO Fase 1** — usa `findWithRelations()` |
| `registrar($dados)` | ✅ | Fluxo de 8 passos — corrigido Fase 1 |
| `atualizar($id, $dados)` | ✅ | Recalcula meta se valor mudou (V6 fix) |
| `excluir($id)` | ✅ | Reverte arte + recalcula meta (V7 fix) |
| `getEstatisticas()` | ✅ | Delega ao Repository (com try/catch + fallback) |
| `getVendasMensais($meses)` | ✅ | Chama `getVendasPorMes()` (com try/catch) |
| `getRankingRentabilidade($limite)` | ✅ | Chama `getMaisRentaveis()` (com try/catch) |
| `getTotalMes($mesAno)` | ✅ | Chamado pelo Dashboard |

---

## 📌 PADRÕES APLICADOS (Lições dos Módulos Anteriores)

| Padrão | Origem | Aplicação em Vendas |
|--------|--------|---------------------|
| B8 workaround (`$_SESSION['_errors']` direto) | Clientes/Artes | ✅ Fase 1 — Controller |
| B9 workaround (`limparDadosFormulario()`) | Clientes/Artes | ✅ Fase 1 — Controller |
| Conversão `(int) $id` | Artes (Router bug) | ✅ Fase 1 — Controller |
| Normalização filtros `?? null ?: null` | Artes (T1) | ✅ M3 — Service |
| Paginação 12/página + helper URL | Tags/Clientes/Artes | ✅ M1 — View + Service |
| Headers clicáveis + whitelist ORDER BY | Tags/Clientes/Artes | ✅ M2 — Repository + View |
| WHERE dinâmico com AND | Artes M1/M3 | ✅ M3 — Repository |
| `$GLOBALS['_key']` vs `global` em helpers | **NOVO** Vendas M2 | ✅ View (corrige bug de escopo) |
| Chart.js 4.4.7 + container 280px fixo | Tags/Metas/Artes | 📋 M6 (planejada) |

---

## 📌 CHECKLIST DE TESTES M1+M2+M3 (14 cenários)

### CRUD Básico (T1–T5)
| # | Teste | Como Verificar | Status |
|---|-------|----------------|--------|
| T1 | Lista carrega | GET /vendas → tabela com dados | ✅ OK |
| T2 | Paginação funciona | Inserir >12 vendas → ver controles de página | ✅ OK |
| T3 | Criar funciona | POST /vendas/criar → venda aparece na lista | ✅ OK |
| T4 | Editar funciona | PUT /vendas/{id}/editar → valor atualizado | ✅ OK |
| T5 | Excluir funciona | DELETE → venda removida, arte volta 'disponivel' | ✅ OK |

### Ordenação M2 (T6–T8)
| # | Teste | Como Verificar | Status |
|---|-------|----------------|--------|
| T6 | Ordenar por Valor | Clicar "Valor" → URL tem `?ordenar=valor&direcao=DESC` | ✅ OK |
| T7 | Toggle direção | Clicar "Valor" de novo → `direcao=ASC` | ✅ OK (fix global scope) |
| T8 | Ícone muda | Coluna ativa = ícone azul, outras = cinza neutro | ✅ OK |

### Filtros M3 (T9–T12)
| # | Teste | Como Verificar | Status |
|---|-------|----------------|--------|
| T9 | Filtro por cliente | Selecionar cliente no dropdown → só vendas dele | ✅ OK |
| T10 | Filtro por período | Preencher De/Até → vendas no intervalo | ✅ OK |
| T11 | Filtros combinados | Cliente + Forma pgto → interseção (AND) | ✅ OK |
| T12 | Limpar filtros | Clicar ✕ → URL volta para /vendas limpa | ✅ OK |

### Integração M1+M2+M3 (T13–T14)
| # | Teste | Como Verificar | Status |
|---|-------|----------------|--------|
| T13 | Filtro + paginação | Aplicar filtro → mudar página → filtro preservado na URL | ✅ OK |
| T14 | Ordenação + paginação | Ordenar → mudar página → ordenação preservada na URL | ✅ OK |

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                         → COMPLETO (6/6)
2. ✅ Clientes     — independente                         → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)  → COMPLETO (6/6)
4. ✅ Artes        — depende de Tags (✅)                   → COMPLETO (6/6 + cross-module OK)
5. 🔧 VENDAS      — depende de Artes + Clientes + Metas  → FASE 1 + M1+M2+M3 COMPLETAS ★
6. 🔄 Dashboard   — depende de TODOS                     → Funcional, revisitar após Vendas M4-M6
```

### Impacto de Vendas em Outros Módulos

```
Vendas → Artes:
  ├── store() → ArteRepository::update(status='vendida')
  ├── destroy() → ArteRepository::update(status='disponivel') ✅ V7 FIX
  └── Artes M5 cross-module → cards Lucro/Rentabilidade ✅ IMPLEMENTADO (22/02/2026)

Vendas → Metas:
  ├── store() → MetaRepository::atualizarProgresso() via recalcularMetaMes()
  ├── update() → recalcula meta se valor mudou ✅ V6 FIX
  ├── destroy() → recalcularMetaMes() (re-soma vendas)
  └── Metas M1 (superado) → transição automática depende do valor acumulado

Vendas → Dashboard:
  ├── Faturamento Mensal (gráfico barras)
  ├── Vendas do Mês (card)
  ├── Evolução de Vendas (gráfico linha+barras)
  ├── Ranking Rentabilidade (top 5)
  └── Top Clientes (via JOIN com clientes)

Vendas → Clientes:
  └── Histórico de compras no ClienteController::show()
```

### Histórico das Sessões

| # | Data | Foco | Entregas |
|---|------|------|----------|
| 1 | 22/02 | Fase 1 — Análise + Correções + Testes T1-T12 | VendaController + VendaService (6 bugs) + diagnostico + relatorio fix |
| 2 | 22/02 | Cross-module Artes M5 | ArteService (+VendaRepository +3 métodos) + show.php (2 cards novos) |
| 3 | 23/02 | M1+M2+M3 — Paginação + Ordenação + Filtros | VendaRepository (+2), VendaService (+1+const), VendaController (index reescrito), index.php (reescrito) |
| 4 | 23/02 | Revisão anti-regressão + Bug fix global scope | 18 regressões corrigidas nos 4 arquivos + fix `$GLOBALS` nos helpers da view |

---

## 📌 SEQUÊNCIA RECOMENDADA (PRÓXIMOS PASSOS)

```
MELHORIAS RESTANTES (sequência recomendada)
├── M5: Estatísticas show.php (independente — cards de métricas por venda)
├── M6: Gráficos index.php (independente — Chart.js faturamento + forma pgto)
└── M4: Relatório aprimorado (beneficia-se de M6 pronto para reaproveitar gráficos)

PÓS-VENDAS
├── ⚠️ Verificar bug global scope em Tags, Clientes, Artes (index.php helpers)
└── 🏠 Dashboard — revisitar com dados de Vendas completos (5 de 8 métricas dependem de Vendas)
```

---

**Última atualização:** 24/02/2026  
**Status:** ✅ FASE 1 + M1+M2+M3 COMPLETAS — 14/14 testes OK  
**Cross-module:** ✅ Cards Lucro + Rentabilidade implementados no módulo Artes  
**Próxima ação:** Melhoria 5 — Estatísticas por venda (cards no show.php)  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅
