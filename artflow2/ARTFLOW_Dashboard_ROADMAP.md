# ARTFLOW 2.0 — DASHBOARD ROADMAP

**Módulo:** Dashboard  
**Rota principal:** `GET /` e `GET /dashboard`  
**Controller:** `DashboardController`  
**View:** `views/dashboard/index.php`  
**Componente:** `views/components/alerta-meta-risco.php`  
**Criado:** 03/03/2026  
**Status:** ✅ FASE 1 COMPLETA — MELHORIAS PENDENTES

---

## 🏗️ ARQUITETURA DO MÓDULO

### Natureza Especial

O Dashboard **NÃO é um módulo CRUD** — não tem Model, Repository, Service ou Validator próprios. É um **módulo agregador** que consome dados de TODOS os 5 módulos via seus respectivos Services.

### Estrutura de Arquivos

```
src/
└── Controllers/
    └── DashboardController.php       ✅ Controller com 7 actions (index + 6 AJAX)

views/
└── dashboard/
    ├── index.php                     ✅ View principal (cards + gráficos + tabelas)
    └── ../components/
        └── alerta-meta-risco.php     ✅ Componente condicional (Metas M4)

views/
└── layouts/
    └── main.php                      ✅ Chart.js 4.4.7 centralizado (FIX D2)

config/
└── routes.php                        ✅ 8 rotas Dashboard registradas
```

### Rotas (8 total)

```
DASHBOARD (1 principal + 7 AJAX)
  GET  /                         → DashboardController@index           (página principal)
  GET  /dashboard                → DashboardController@index           (alias)
  GET  /dashboard/refresh        → DashboardController@refresh         (AJAX — atualiza cards)
  GET  /dashboard/artes          → DashboardController@estatisticasArtes    (AJAX)
  GET  /dashboard/vendas         → DashboardController@estatisticasVendas   (AJAX)
  GET  /dashboard/meta           → DashboardController@progressoMeta       (AJAX)
  GET  /dashboard/atividades     → DashboardController@atividadesRecentes  (AJAX)
  GET  /dashboard/busca          → DashboardController@busca               (AJAX)
```

### Dependências entre Classes

```
DashboardController
├── __construct(ArteService, VendaService, MetaService, ClienteService)  ← 4 dependências
│
├── index()
│   ├── ArteService::getEstatisticas()           → countByStatus (adaptado via adaptarArtesStats)
│   ├── ArteService::getDisponiveisParaVenda()   → artes com status 'disponivel' + 'em_producao'
│   ├── VendaService::getVendasMesAtual()        → array vendas do mês corrente
│   ├── VendaService::getTotalMes()              → float faturamento mensal
│   ├── VendaService::getVendasMensais(6)        → array para gráfico barras
│   ├── VendaService::getRankingRentabilidade(5) → top 5 mais rentáveis
│   ├── MetaService::getResumoDashboard()        → meta atual + % + projeção
│   ├── MetaService::getMetasEmRisco()           → alerta meta em risco
│   └── ClienteService::getTopClientes(5)        → top 5 compradores
│
├── refresh()               → JSON cards atualizados (AJAX polling)
├── estatisticasArtes()     → JSON stats artes por status
├── estatisticasVendas()    → JSON vendas mensais + ranking
├── progressoMeta()         → JSON meta + dias restantes + projeção
├── atividadesRecentes()    → JSON últimas vendas como "atividades"
└── busca()                 → JSON busca global (TODO: parcial)
```

### Métodos Auxiliares do Controller

```
DashboardController (private)
├── limparDadosFormulario()    → FIX D1: limpa $_SESSION['_old_input'] e $_SESSION['_errors']
└── adaptarArtesStats(array)   → FIX CHAVES: converte formato countByStatus → formato Dashboard
```

### Variáveis Passadas à View (index)

| Variável | Tipo | Origem | Uso na View |
|----------|------|--------|-------------|
| `$artesStats` | array | ArteService (adaptado) | 4 cards principais + doughnut status |
| `$vendasMes` | array | VendaService | Card "Vendas do Mês" (count) |
| `$faturamentoMes` | float | VendaService | Card "Vendas do Mês" (valor) |
| `$metaAtual` | array | MetaService | Card "Meta do Mês" + semi-doughnut |
| `$metaEmRisco` | array | MetaService | Alerta condicional (componente) |
| `$topClientes` | array | ClienteService | Tabela Top 5 Clientes |
| `$artesDisponiveis` | array | ArteService | Card "À Venda" + lista |
| `$vendasMensais` | array | VendaService | Gráfico barras + gráfico evolução |
| `$maisRentaveis` | array | VendaService | Lista ranking rentabilidade |

---

## 📊 ESTADO ATUAL DO DASHBOARD

### O Que Já Existe (Funcional)

| # | Componente | Tipo | Descrição | Status |
|---|-----------|------|-----------|--------|
| 1 | Card Total de Artes | Card | Total + disponíveis | ✅ Funcional |
| 2 | Card Vendas do Mês | Card | Quantidade + faturamento | ✅ Funcional |
| 3 | Card À Venda | Card | Artes disponíveis para venda | ✅ Funcional |
| 4 | Card Meta do Mês | Card | Porcentagem atingida | ✅ Funcional |
| 5 | Gráfico Faturamento Mensal | Chart.js Bar | Últimos 6 meses (valor) | ✅ Funcional |
| 6 | Gráfico Status Artes | Chart.js Doughnut | Disponível/Produção/Vendida | ✅ Funcional |
| 7 | Gráfico Meta do Mês | Chart.js Semi-doughnut | Realizado vs Falta | ✅ Funcional |
| 8 | Gráfico Evolução Vendas | Chart.js Misto | Faturamento (linha) + Quantidade (barra) | ✅ Funcional |
| 9 | Tabela Top Clientes | Tabela | #, Nome, Compras, Total | ✅ Funcional |
| 10 | Lista Artes Disponíveis | Lista | Arte + preço + link | ✅ Funcional |
| 11 | Lista Mais Rentáveis | Lista | Arte + R$/h | ✅ Funcional |
| 12 | Alerta Meta em Risco | Componente | Banner condicional (Metas M4) | ✅ Funcional |
| 13 | Botões Nova Arte / Venda | Header | Atalhos rápidos | ✅ Funcional |
| 14 | 6 Endpoints AJAX | API JSON | Refresh, artes, vendas, meta, atividades, busca | ✅ Funcional |

---

## ✅ FASE 1 — ESTABILIZAÇÃO (COMPLETA — 03/03/2026)

### Bugs Investigados

| # | Suspeita | Resultado | Ação |
|---|----------|-----------|------|
| D1 | `limparDadosFormulario()` ausente | 🔴 **CONFIRMADO** | Método privado adicionado ao Controller |
| D2 | Chart.js duplicado em 5 arquivos | 🔴 **CONFIRMADO — CRÍTICO** | Centralizado no layout v4.4.7, removido de 4 views |
| D3 | `$vendasMes` tipo misto | ✅ Descartado | View já tem verificação defensiva `is_array()` |
| D4 | `$metaAtual` pode ser null/vazio | ✅ Descartado | View usa operador `??` em todos os acessos |
| D5 | `$artesStats` chaves inconsistentes | 🔴 **CONFIRMADO — CRÍTICO** | `adaptarArtesStats()` converte formato no Controller |
| D6 | Endpoint `busca()` ausente | ✅ Descartado | Método implementado, retorna `resultados => []` (TODO) |
| D7 | `topClientes` formato array/objeto | ✅ Descartado | View tem dual-path `is_object()`/`is_array()` |
| D8 | Queries duplicadas em `refresh()` | 🔴 **CONFIRMADO** | Variáveis locais eliminam 4 queries por request |

### Correções Aplicadas

#### FIX D1 — `limparDadosFormulario()` (Severidade: Baixa)

**Problema:** DashboardController não limpava `$_SESSION['_old_input']` e `$_SESSION['_errors']`. Se o usuário navegava Criar Venda → Erro validação → Dashboard → outro módulo, dados residuais contaminavam formulários.

**Solução:** Método `private limparDadosFormulario()` adicionado ao DashboardController (padrão dos outros controllers — é privado e copy-pasted, NÃO existe no BaseController). Chamado no início de `index()`.

**Arquivo:** `src/Controllers/DashboardController.php`

#### FIX D2 — Chart.js Centralizado (Severidade: CRÍTICA)

**Problema:** Chart.js carregado em 5 arquivos com versões diferentes:
- `main.php`: `chart.js` (SEM versão = latest)
- `dashboard/index.php`: `chart.js@4.4.0`
- `artes/index.php`: `chart.js@4.4.7` (condicional)
- `metas/index.php`: `chart.js` (SEM versão, condicional)
- `tags/index.php`: `chart.js@4.4.7` (condicional)

Dashboard carregava 2× (latest + 4.4.0). Artes carregava 2× (latest + 4.4.7). Páginas sem gráficos carregavam ~200KB desnecessários.

**Solução (Opção A — Centralizar no layout):**
- `views/layouts/main.php`: fixado em `chart.js@4.4.7/dist/chart.umd.min.js`
- Removido `<script>` de Chart.js de: `dashboard/index.php`, `artes/index.php`, `metas/index.php`, `tags/index.php`

**Arquivos alterados:** 5 (main.php + 4 views)

#### FIX D5/CHAVES — `adaptarArtesStats()` (Severidade: CRÍTICA)

**Problema:** `ArteService::getEstatisticas()` delega para `ArteRepository::countByStatus()` que retorna:
```
['disponivel' => N, 'em_producao' => N, 'vendida' => N, 'reservada' => N]
```

Dashboard esperava formato diferente:
```
['total' => N, 'disponiveis' => N, 'em_producao' => N, 'vendidas' => N, 'reservadas' => N]
```

Diferenças: falta `total` (soma), chaves no singular vs plural (`disponivel`→`disponiveis`, `vendida`→`vendidas`).

Resultado: Card "Total de Artes" = 0, Gráfico doughnut "Nenhuma arte cadastrada".

**Solução:** Método privado `adaptarArtesStats()` no DashboardController converte o formato. NÃO alteramos ArteService/Repository para não afetar módulo Artes que está estável.

**Contexto histórico:** O ArteRepository original tinha `getEstatisticas()` com formato correto, mas foi removido acidentalmente na refatoração M6 do módulo Artes (substituído por `countByStatus()` + `getResumoFinanceiro()`). O método foi restaurado no Repository como backup, mas o Service continua usando `countByStatus()` — por isso a adaptação é feita no Controller consumidor.

**Arquivo:** `src/Controllers/DashboardController.php`

#### FIX D8 — Queries Duplicadas no `refresh()` (Severidade: Média)

**Problema:** `refresh()` chamava `getEstatisticas()` 2× e `getResumoDashboard()` 2× = 4 queries SQL desnecessárias por request AJAX.

**Solução:** Variáveis locais armazenam resultado da primeira chamada e reutilizam.

**Arquivo:** `src/Controllers/DashboardController.php`

### Bugs Anteriores (Já Corrigidos Antes da Fase 1)

| Bug | Causa | Correção | Data |
|-----|-------|----------|------|
| Top Clientes zerado | `topClientes()` retornava objetos que perdiam campos calculados | Retorno array bruto | 31/01/2026 |
| Gráficos infinitos | Canvas `responsive:true` sem altura fixa → loop resize | Container `height:280px` | 31/01/2026 |
| Dashboard quebrado | Objeto Cliente usado como array | Verificação defensiva `is_object()`/`is_array()` | 31/01/2026 |
| TypeError getValor() | Vendas às vezes retornam arrays em vez de objetos | Verificação defensiva no resumo | 31/01/2026 |

### Arquivos Alterados na Fase 1

| Arquivo | Caminho | Fixes |
|---------|---------|-------|
| DashboardController.php | `src/Controllers/` | D1 + D5/CHAVES + D8 |
| main.php | `views/layouts/` | D2 (Chart.js fixado 4.4.7) |
| index.php | `views/dashboard/` | D2 (removido `<script>` Chart.js) |
| index.php | `views/artes/` | D2 (removido `<script>` Chart.js) |
| index.php | `views/metas/` | D2 (removido `<script>` Chart.js) |
| index.php | `views/tags/` | D2 (removido `<script>` Chart.js) |

### Testes T1–T12 — TODOS PASSARAM ✅

| # | Área | Teste | Resultado |
|---|------|-------|-----------|
| **T1** | Cards | Página carrega sem erros | ✅ OK |
| **T2** | Cards — Dados | Valores batem com banco | ✅ OK (após fix D5/CHAVES) |
| **T3** | Gráfico Faturamento | Barras renderizam | ✅ OK |
| **T4** | Gráfico Status Artes | Doughnut renderiza | ✅ OK (após fix D5/CHAVES) |
| **T5** | Gráfico Meta | Semi-doughnut renderiza | ✅ OK |
| **T6** | Gráfico Evolução | Misto renderiza | ✅ OK |
| **T7** | Top Clientes | Tabela correta | ✅ OK |
| **T8** | Artes Disponíveis | Lista correta | ✅ OK |
| **T9** | Ranking Rentáveis | Lista correta | ✅ OK |
| **T10** | Alerta Meta Risco | Condicional funciona | ✅ OK |
| **T11** | Reflexo CRUD | Criar venda reflete | ✅ OK |
| **T12** | Banco vazio | — | ⏭️ Não executado (opcional) |

### Lições Aprendidas na Fase 1

1. **`limparDadosFormulario()` é privado e per-controller:** Não existe no BaseController. Cada controller define o seu. Se esquecer de adicionar, dados residuais de sessão vazam entre módulos.

2. **Chart.js deve ter versão fixa e única:** Carregar via CDN sem versão (`chart.js` sem `@4.4.7`) pega `latest` que pode mudar a qualquer momento. Versão fixada em 4.4.7 no layout, removida de todas as views individuais.

3. **Dashboard é consumidor, não dono dos dados:** Quando o formato dos dados muda no Service/Repository de outro módulo (como aconteceu com `countByStatus()` substituindo `getEstatisticas()`), a adaptação deve ser feita no Dashboard — não no módulo fonte que já está estável.

4. **`array_sum()` é aliado:** Para calcular `total` a partir de `countByStatus()` que retorna contagens por status, `array_sum($raw)` soma todas as contagens sem precisar de query extra.

5. **Erros silenciosos por `?? 0`:** O operador null coalescing mascara bugs — cards mostravam 0 sem nenhum erro no console ou no PHP. Sempre verificar dados reais no phpMyAdmin quando cards mostram zeros.

---

## 📋 MELHORIAS PLANEJADAS (M1–M6)

### Visão Geral

O Dashboard é diferente dos módulos CRUD — suas melhorias focam em **conteúdo visual, UX e inteligência analítica**, não em CRUD features.

| # | Melhoria | Complexidade | Descrição | Status |
|---|----------|-------------|-----------|--------|
| M1 | Cards aprimorados | Baixa | +2 cards (Lucro Mês, Ticket Médio) + indicadores de tendência (↑↓) | 🔲 Pendente |
| M2 | Layout e responsividade | Média | Reorganização visual, collapse sections, mobile-first | 🔲 Pendente |
| M3 | Seção Atividades Recentes | Média | Timeline visual das últimas ações (vendas, artes criadas, metas) | 🔲 Pendente |
| M4 | KPIs e métricas avançadas | Média | Cards inteligentes: margem média, R$/h médio, projeção, taxa conversão | 🔲 Pendente |
| M5 | Período selecionável | Média | Dropdown: Mês Atual / Últimos 3 Meses / Últimos 6 Meses / Ano | 🔲 Pendente |
| M6 | Auto-refresh e polish | Baixa | Polling AJAX opcional, animações, estados vazios melhorados | 🔲 Pendente |

---

### 📋 MELHORIA 1 — CARDS APRIMORADOS

**Complexidade:** Baixa  
**Pré-requisito:** Fase 1 ✅  
**Arquivos:** DashboardController.php (editar), views/dashboard/index.php (editar)

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **+Card Lucro do Mês** | SUM(lucro_calculado) das vendas do mês corrente |
| **+Card Ticket Médio** | AVG(valor) das vendas do mês corrente |
| **Indicadores de tendência** | Compara mês atual vs mês anterior: ↑ +15% (verde) ou ↓ -8% (vermelho) |
| **Subtextos informativos** | Cada card mostra contexto: "12 vendas este mês", "vs R$ 350 mês passado" |

#### Dados Necessários (já disponíveis)

| Dado | Origem | Método |
|------|--------|--------|
| Lucro do mês | VendaService | `getVendasMensais(2)` → último mês tem campo `lucro` |
| Ticket médio | VendaService | `getEstatisticas()['ticket_medio']` |
| Mês anterior | VendaService | `getVendasMensais(2)` → compara posições [0] vs [1] |

#### Layout: 6 Cards (2 linhas de 3)

```
Linha 1: [Total Artes] [Vendas do Mês] [Faturamento Mês]
Linha 2: [Lucro do Mês] [Ticket Médio]  [Meta do Mês %]
```

---

### 📋 MELHORIA 2 — LAYOUT E RESPONSIVIDADE

**Complexidade:** Média  
**Pré-requisito:** M1 ✅  
**Arquivos:** views/dashboard/index.php (reescrever)

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Seções colapsáveis** | Gráficos em collapse (padrão Vendas M6 / Artes M6) |
| **Ordem lógica** | Cards → Gráficos → Top Clientes + Ranking → Artes Disponíveis |
| **Responsividade** | Cards: col-6 em mobile, col-lg-3 em desktop. Gráficos full-width em mobile |
| **Breadcrumb** | Manter consistência visual com outros módulos |
| **Ícones informativos** | Tooltips nos cards explicando cada métrica |

---

### 📋 MELHORIA 3 — SEÇÃO ATIVIDADES RECENTES

**Complexidade:** Média  
**Pré-requisito:** Fase 1 ✅  
**Arquivos:** DashboardController.php (editar), views/dashboard/index.php (editar), +1 Repository opcional

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Timeline visual** | Últimas 10 atividades do sistema em formato timeline vertical |
| **Tipos de atividade** | Venda registrada, Arte criada, Meta atingida, Cliente cadastrado |
| **Ícones + cores** | 🛒 verde (venda), 🎨 azul (arte), 🎯 amarelo (meta), 👤 info (cliente) |
| **Timestamps** | "Há 2 horas", "Ontem às 14:30", etc (formato relativo) |

#### Abordagem

Opção A — **Query UNION** combinando últimos registros de cada tabela por `created_at`:
```sql
(SELECT 'venda' as tipo, id, created_at FROM vendas ORDER BY created_at DESC LIMIT 5)
UNION ALL
(SELECT 'arte' as tipo, id, created_at FROM artes ORDER BY created_at DESC LIMIT 5)
UNION ALL ...
ORDER BY created_at DESC LIMIT 10
```

Opção B — **Tabela activity_log** (já existe na migration 008) — registrar ações automaticamente.

---

### 📋 MELHORIA 4 — KPIs E MÉTRICAS AVANÇADAS

**Complexidade:** Média  
**Pré-requisito:** M1 ✅  
**Arquivos:** DashboardController.php (editar), VendaService.php (+1 método), views/dashboard/index.php (editar)

#### Especificação

| Métrica | Cálculo | Card/Seção |
|---------|---------|------------|
| **Margem Média (%)** | AVG(lucro/valor × 100) do mês | Card novo ou dentro de KPIs |
| **R$/Hora Médio** | AVG(rentabilidade_hora) das vendas com horas > 0 | Card novo |
| **Taxa de Conversão** | vendas_mes / artes_disponíveis × 100 | Card novo |
| **Projeção Mensal** | (faturamento_atual / dias_passados) × dias_total | Card ou sub-info |
| **Melhor Dia da Semana** | GROUP BY DAYOFWEEK(data_venda) | Insight textual |
| **Forma Pgto Preferida** | MODE(forma_pagamento) do mês | Insight textual |

#### Seção "Insights Rápidos"

Card com 3-4 insights textuais automáticos:
- "📈 Seu melhor dia de vendas é Sexta-feira"
- "💳 PIX é a forma de pagamento mais usada (68%)"
- "⏱️ Sua rentabilidade média é R$ 45,20/h"
- "🎯 Projeção do mês: R$ 3.200 (85% da meta)"

---

### 📋 MELHORIA 5 — PERÍODO SELECIONÁVEL

**Complexidade:** Média  
**Pré-requisito:** M1 ✅  
**Arquivos:** DashboardController.php (editar), views/dashboard/index.php (editar)

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Dropdown de período** | "Mês Atual", "Últimos 3 Meses", "Últimos 6 Meses", "Este Ano", "Tudo" |
| **Filtro aplicado a** | Cards de valor (faturamento, lucro, ticket) + gráficos |
| **Cards fixos** | Total Artes e Meta Mês NÃO mudam com período (são sempre atuais) |
| **URL parameter** | `?periodo=3m` / `?periodo=6m` / `?periodo=ano` / `?periodo=total` |
| **Gráficos dinâmicos** | Barras/linha ajustam para mostrar o período selecionado |

---

### 📋 MELHORIA 6 — AUTO-REFRESH E POLISH

**Complexidade:** Baixa  
**Pré-requisito:** M2 ✅  
**Arquivos:** views/dashboard/index.php (editar JS)

#### Especificação

| Recurso | Descrição |
|---------|-----------|
| **Auto-refresh** | Toggle para atualizar cards a cada 60s via `/dashboard/refresh` |
| **Animações** | CountUp nos números dos cards (animação de contagem) |
| **Estados vazios** | Ilustrações SVG amigáveis quando não há dados |
| **Loading skeleton** | Placeholder animado enquanto dados carregam |
| **Timestamp** | "Atualizado há 5 min" no rodapé |

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos dos Services Usados pelo Dashboard

| Método | Service | Retorno Real | Adaptação no Dashboard | Usado em |
|--------|---------|-------------|----------------------|----------|
| `getEstatisticas()` | ArteService | `['disponivel', 'em_producao', 'vendida', 'reservada']` | `adaptarArtesStats()` → `['total', 'disponiveis', 'vendidas', ...]` | Cards + Doughnut |
| `getDisponiveisParaVenda()` | ArteService | Array de Arte objects (disponivel + em_producao) | Nenhuma | Card + Lista |
| `getVendasMesAtual()` | VendaService | Array de Venda objects | Nenhuma | Card (count) |
| `getTotalMes(?mesAno)` | VendaService | float | Nenhuma | Card faturamento |
| `getVendasMensais(6)` | VendaService | `[['mes', 'quantidade', 'total', 'lucro'], ...]` | Nenhuma | Gráficos |
| `getRankingRentabilidade(5)` | VendaService | `[['arte_nome', 'valor', 'rentabilidade_hora'], ...]` | Nenhuma | Lista ranking |
| `getResumoDashboard()` | MetaService | `['valor_meta', 'valor_realizado', 'porcentagem', 'status']` | Nenhuma | Card + Semi-doughnut |
| `getMetasEmRisco()` | MetaService | `['alerta' => bool, 'projecao' => float, ...]` | Nenhuma | Alerta condicional |
| `getTopClientes(5)` | ClienteService | Array de arrays com `nome, total_compras, valor_total_compras` | Nenhuma | Tabela |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível
**Relevância Dashboard:** ✅ RESOLVIDO — `limparDadosFormulario()` adicionado no FIX D1.

### Bug B9: Dados Residuais
**Relevância Dashboard:** ✅ RESOLVIDO — Mesmo fix D1 cobre este cenário.

### Bug Global Scope ($GLOBALS)
**Relevância Dashboard:** ⚠️ NÃO AFETA — View do Dashboard não usa `global $variavel` nem helpers com `extract()`.

### Bug D5: Chaves ArteService inconsistentes
**Relevância Dashboard:** ✅ RESOLVIDO — `adaptarArtesStats()` converte formato no Controller sem alterar módulo Artes.

---

## 📌 DIFERENÇAS vs MÓDULOS CRUD

| Aspecto | Módulos CRUD | Dashboard |
|---------|-------------|-----------|
| Model/Repository/Service | Próprios | Nenhum (usa dos outros) |
| Fase 1 | 12 testes CRUD | 12 testes de exibição + integridade |
| Melhorias M1-M6 | Paginação, ordenação, filtros, stats, gráficos | Cards, layout, atividades, KPIs, período, polish |
| Formulários | Create/Edit/Delete | Nenhum (somente leitura) |
| Risco de regressão | Médio (FK, constraints) | Alto (depende de 4 Services — qualquer mudança pode quebrar) |
| Testes | phpMyAdmin + browser | Browser + console JS + verificação cruzada com banco |

---

## 📌 SEQUÊNCIA RECOMENDADA

```
ESTABILIZAÇÃO
├── ✅ Fase 1: Testes browser T1-T12 + correção de bugs (03/03/2026)

MELHORIAS (sequência recomendada)
├── M1: Cards aprimorados (base para tudo — +2 cards + tendências)
├── M2: Layout e responsividade (reorganiza visual, collapse)
├── M3: Atividades recentes (timeline — independente)
├── M4: KPIs avançados (insights inteligentes — depende de M1)
├── M5: Período selecionável (filtro global — depende de M1+M2)
└── M6: Auto-refresh e polish (final — depende de M2)
```

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                             → COMPLETO (Fase 1 + 6/6)
2. ✅ Clientes     — independente                             → COMPLETO (Fase 1 + 6/6)
3. ✅ Metas        — independente (atualizado por Vendas)      → COMPLETO (Fase 1 + 6/6)
4. ✅ Artes        — depende de Tags (✅)                       → COMPLETO (Fase 1 + 6/6)
5. ✅ Vendas       — depende de Artes + Clientes + Metas       → COMPLETO (Fase 1 + 6/6)
6. ✅ DASHBOARD   — depende de TODOS (Artes+Vendas+Metas+Clientes) → FASE 1 COMPLETA ★
```

### Por que Dashboard foi o último?

1. **Dependência máxima:** Consome dados de todos os 4 Services
2. **Sem CRUD próprio:** Só exibe — bugs nos outros módulos se propagam aqui
3. **Agora é seguro:** Com todos os 5 módulos CRUD estabilizados (6/6 cada), os dados que o Dashboard consome são confiáveis

---

## 🗂️ ARQUIVOS ENTREGUES

### Fase 1

| Arquivo | Caminho | Fixes Aplicados |
|---------|---------|-----------------|
| DashboardController.php | `src/Controllers/` | D1 + D5/CHAVES + D8 |
| main.php | `views/layouts/` | D2 (Chart.js 4.4.7 centralizado) |
| index.php | `views/dashboard/` | D2 (removido `<script>` Chart.js duplicado) |
| index.php | `views/artes/` | D2 (removido `<script>` Chart.js duplicado) |
| index.php | `views/metas/` | D2 (removido `<script>` Chart.js duplicado) |
| index.php | `views/tags/` | D2 (removido `<script>` Chart.js duplicado) |

---

**Última atualização:** 03/03/2026  
**Status:** ✅ Fase 1 completa — Melhorias M1-M6 pendentes  
**Próxima ação:** Melhoria M1 (Cards Aprimorados)  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅, Vendas ✅
