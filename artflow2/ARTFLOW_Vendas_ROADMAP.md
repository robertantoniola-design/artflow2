# ArtFlow 2.0 — Módulo Vendas: Documentação Completa

**Data:** 22/02/2026  
**Status Geral:** ✅ FASE 1 COMPLETA — CRUD estabilizado, 12/12 testes OK  
**Versão Base:** Código corrigido na Fase 1 (22/02/2026), 6 bugs fixados  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Vendas do ArtFlow 2.0 é o **módulo mais acoplado** do sistema — registra transações de venda de artes, calcula lucro e rentabilidade automaticamente, atualiza o status da arte para "vendida" e incrementa o progresso das metas mensais. É o único módulo com 3 dependências no Controller e 4 no Service, orquestrando operações que afetam 3 tabelas simultaneamente (`vendas`, `artes`, `metas`).

O módulo é **pré-requisito** para:
1. **Dashboard completo** — 5 dos 8 dados do Dashboard vêm de Vendas
2. ~~**Cards Lucro + Rentabilidade** do módulo Artes (M5 pendência cross-module)~~ ✅ IMPLEMENTADO (22/02/2026)
3. **Metas funcionais** — o `valor_realizado` e `porcentagem_atingida` dependem de vendas registradas

A Fase 1 foi concluída em 22/02/2026 com **6 bugs corrigidos** e **12/12 testes manuais OK**. Todas as integrações cross-module funcionam nos dois sentidos: registrar venda → arte vendida + meta incrementada, excluir venda → arte disponível + meta decrementada.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 6 bugs corrigidos, 12/12 testes | ✅ COMPLETA (22/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | 📋 PLANEJADA |
| Melhoria 2 | Ordenação dinâmica (data, valor, cliente, forma pgto) | 📋 PLANEJADA |
| Melhoria 3 | Filtros combinados (período + cliente + forma pgto) | 📋 PLANEJADA |
| Melhoria 4 | Relatório aprimorado (resumo financeiro + exportação) | 📋 PLANEJADA |
| Melhoria 5 | Estatísticas por venda (cards métricas no show.php) | 📋 PLANEJADA |
| Melhoria 6 | Gráficos de vendas (Chart.js — faturamento + ranking) | 📋 PLANEJADA |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | Fase 1 ✅ | 📋 PLANEJADA |
| 2 | Ordenação dinâmica (5+ colunas) | Baixa | Melhoria 1 | 📋 PLANEJADA |
| 3 | Filtros combinados (período + cliente + pgto) | Média | Melhoria 1 | 📋 PLANEJADA |
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
│   └── VendaRepository.php            ✅ Implementado (CRUD + filtros + estatísticas + relatórios)
├── Services/
│   └── VendaService.php               🔧 Fase 1 (V1+V2+V3+V7+V9+findByMesAno corrigidos)
├── Controllers/
│   └── VendaController.php            🔧 Fase 1 (B8+B9+int cast+limparDados+buscarComRelacionamentos)
└── Validators/
    └── VendaValidator.php             ✅ Implementado (arte_id, valor, data, forma_pgto)

views/
└── vendas/
    ├── index.php                      ✅ Funcional (lista com filtros e resumo)
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
├── index()     usa VendaService::listar() + getEstatisticas() + ClienteService::getParaSelect()
├── create()    usa ArteService::getDisponiveisParaVenda() + ClienteService::getParaSelect()
├── store()     usa VendaService::registrar() [orquestra 3 tabelas]
├── show()      usa VendaService::buscarComRelacionamentos() [V9 fix]
├── edit()      usa VendaService::buscarComRelacionamentos() + ClienteService::getParaSelect()
├── update()    usa VendaService::atualizar()
├── destroy()   usa VendaService::excluir() [V7 fix: reverte arte + recalcula meta]
└── relatorio() usa VendaService::getVendasMensais() + getEstatisticas() + getRankingRentabilidade()

VendaService ← ORQUESTRA 3 REPOSITORIES
├── VendaRepository   — CRUD vendas
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

### Checklist de Testes

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

## 📋 MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (PLANEJADA)

**Complexidade:** Baixa  
**Padrão:** Idêntico a Tags, Clientes e Artes (12 itens por página)  
**Pré-requisito:** Fase 1 ✅

### Especificação

| Recurso | Descrição |
|---------|-----------|
| **12 vendas por página** | Controles Bootstrap 5 com janela de 5 páginas |
| **Preservação de filtros** | Período + cliente + forma pgto mantidos ao paginar |
| **Indicador** | "Mostrando X–Y de Z vendas" |
| **Helper URL** | `vendaUrl()` para montar URLs preservando parâmetros |

### Nota

O `VendaRepository::findPaginated()` já existe com paginação básica! Pode ser aproveitado/adaptado para o padrão `allPaginated()` + `countAll()` dos outros módulos.

### Arquivos a Alterar

| Arquivo | Ação |
|---------|------|
| VendaRepository | Adaptar `findPaginated()` ou criar `allPaginated()` + `countAll()` |
| VendaService | Criar `listarPaginado($filtros)` retornando `['vendas' => [...], 'paginacao' => [...]]` |
| VendaController | index() usa `listarPaginado()` |
| views/vendas/index.php | + controles de paginação + helper vendaUrl() |

---

## 📋 MELHORIA 2 — ORDENAÇÃO DINÂMICA (PLANEJADA)

**Complexidade:** Baixa  
**Padrão:** Idêntico a Tags, Clientes e Artes (headers clicáveis)  
**Pré-requisito:** Melhoria 1 ✅

### Colunas Ordenáveis

| Coluna | Campo BD | Direção padrão | Tipo ícone |
|--------|----------|----------------|------------|
| Data | data_venda | DESC (recentes) — **PADRÃO** | bi-sort-down/up |
| Arte | arte_nome (via JOIN) | ASC (A→Z) | bi-sort-alpha-down/up |
| Cliente | cliente_nome (via JOIN) | ASC (A→Z) | bi-sort-alpha-down/up |
| Valor | valor | DESC (maior primeiro) | bi-sort-numeric-down/up |
| Lucro | lucro_calculado | DESC (maior primeiro) | bi-sort-numeric-down/up |
| Forma Pgto | forma_pagamento | ASC (ordem ENUM) | bi-sort-alpha-down/up |

### Arquivos a Alterar

| Arquivo | Ação |
|---------|------|
| VendaRepository | Whitelist de colunas + ORDER BY dinâmico no `allPaginated()` |
| views/vendas/index.php | + headers clicáveis + helpers `vendaSortUrl()` e `vendaSortIcon()` |

---

## 📋 MELHORIA 3 — FILTROS COMBINADOS (PLANEJADA)

**Complexidade:** Média  
**Padrão:** WHERE dinâmico com AND (mesmo de Artes M1/M3)  
**Pré-requisito:** Melhoria 1 ✅

### Filtros Combinados

| Filtro | Tipo | Campo BD | UI |
|--------|------|----------|-----|
| Período | date range | data_venda BETWEEN | 2 inputs date |
| Cliente | select | cliente_id = ? | Dropdown com clientes |
| Forma pagamento | select | forma_pagamento = ? | Dropdown com 6 opções |
| Busca (termo) | text | arte_nome LIKE ou observacoes LIKE | Input text |

**Problema atual:** `VendaService::listar()` usa if/elseif mutuamente exclusivo. A M3 deve converter para WHERE dinâmico com AND, idêntico ao padrão de Artes.

### Arquivos a Alterar

| Arquivo | Ação |
|---------|------|
| VendaRepository | `allPaginated()` com WHERE dinâmico (AND) + JOINs para filtro por nome |
| VendaService | `listarPaginado()` normaliza filtros com `?? null ?: null` |
| VendaController | index() extrai todos os filtros |
| views/vendas/index.php | Barra de filtros com 4 campos + botão "Limpar" |

---

## 📋 MELHORIA 4 — RELATÓRIO APRIMORADO (PLANEJADA)

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅  
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

### Cards de Resumo no index.php

| Indicador | Cálculo |
|-----------|---------|
| **Total de Vendas** | COUNT(*) |
| **Faturamento Total** | SUM(valor) |
| **Lucro Total** | SUM(lucro_calculado) |
| **Ticket Médio** | AVG(valor) ou SUM/COUNT |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível

**Status no módulo Vendas:** ✅ Workaround aplicado no VendaController (grava direto em `$_SESSION['_errors']`).

### Bug B9: Dados Residuais no Edit

**Status no módulo Vendas:** ✅ Workaround aplicado — `limparDadosFormulario()` chamado em index(), show(), edit(), relatorio().

### Conversão string→int do Router

**Status no módulo Vendas:** ✅ Corrigido — `$id = (int) $id` em show(), edit(), update(), destroy().

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos do VendaService chamados no Controller

| Método no Controller | Existe no Service? | Status |
|---------------------|--------------------|--------|
| `listar($filtros)` | ✅ | Filtros mutuamente exclusivos — corrigir M3 |
| `buscar($id)` | ✅ | Usa `findOrFail()` |
| `buscarComRelacionamentos($id)` | ✅ | **NOVO Fase 1** — usa `findWithRelations()` |
| `registrar($dados)` | ✅ | Fluxo de 8 passos — corrigido Fase 1 |
| `atualizar($id, $dados)` | ✅ | Recalcula meta se valor mudou (V6 fix) |
| `excluir($id)` | ✅ | Reverte arte + recalcula meta (V7 fix) |
| `getEstatisticas()` | ✅ | Delega ao Repository |
| `getVendasMensais($meses)` | ✅ | Chama `getVendasPorMes()` |
| `getRankingRentabilidade($limite)` | ✅ | Chama `getMaisRentaveis()` |
| `getTotalMes($mesAno)` | ✅ | Chamado pelo Dashboard |

---

## 📌 PADRÕES APLICADOS (Lições dos Módulos Anteriores)

| Padrão | Origem | Aplicação em Vendas |
|--------|--------|---------------------|
| B8 workaround (`$_SESSION['_errors']` direto) | Clientes/Artes | ✅ Fase 1 — Controller |
| B9 workaround (`limparDadosFormulario()`) | Clientes/Artes | ✅ Fase 1 — Controller |
| Conversão `(int) $id` | Artes (Router bug) | ✅ Fase 1 — Controller |
| Normalização filtros `?? null ?: null` | Artes (T1) | 📋 Melhoria 3 — Service |
| Paginação 12/página + helper URL | Tags/Clientes/Artes | 📋 Melhoria 1 |
| Headers clicáveis + whitelist ORDER BY | Tags/Clientes/Artes | 📋 Melhoria 2 |
| WHERE dinâmico com AND | Artes M1/M3 | 📋 Melhoria 3 |
| Chart.js 4.4.7 + container 280px fixo | Tags/Metas/Artes | 📋 Melhoria 6 |

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                         → COMPLETO (6/6)
2. ✅ Clientes     — independente                         → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)  → COMPLETO (6/6)
4. ✅ Artes        — depende de Tags (✅)                   → COMPLETO (6/6 + cross-module OK)
5. ✅ VENDAS       — depende de Artes + Clientes + Metas  → FASE 1 COMPLETA ★
6. 🔄 Dashboard   — depende de TODOS                     → Funcional, revisitar após Vendas M1-M6
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

---

## 📌 SEQUÊNCIA RECOMENDADA (PRÓXIMOS PASSOS)

```
MELHORIAS (sequência recomendada)
├── M1: Paginação (base para M2 e M3)
├── M2: Ordenação (depende de M1)
├── M3: Filtros combinados (depende de M1)
├── M5: Estatísticas show.php (independente)
├── M6: Gráficos index.php (independente)
└── M4: Relatório aprimorado (pode incorporar gráficos de M6)
```

---

**Última atualização:** 22/02/2026  
**Status:** ✅ FASE 1 COMPLETA — CRUD estabilizado, 12/12 testes OK  
**Cross-module:** ✅ Cards Lucro + Rentabilidade implementados no módulo Artes  
**Próxima ação:** Melhoria 1 — Paginação (12/página)  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅
