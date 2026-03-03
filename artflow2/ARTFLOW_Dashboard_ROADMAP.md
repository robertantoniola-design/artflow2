# ARTFLOW 2.0 — DASHBOARD ROADMAP

**Módulo:** Dashboard  
**Rota principal:** `GET /` e `GET /dashboard`  
**Controller:** `DashboardController`  
**View:** `views/dashboard/index.php`  
**Componente:** `views/components/alerta-meta-risco.php`  
**Criado:** 03/03/2026  
**Status:** 🔄 ESTABILIZAÇÃO PENDENTE

---

## 🏗️ ARQUITETURA DO MÓDULO

### Natureza Especial

O Dashboard **NÃO é um módulo CRUD** — não tem Model, Repository, Service ou Validator próprios. É um **módulo agregador** que consome dados de TODOS os 5 módulos via seus respectivos Services.

### Estrutura de Arquivos

```
src/
└── Controllers/
    └── DashboardController.php       🔧 Controller com 7 actions (index + 6 AJAX)

views/
└── dashboard/
    ├── index.php                     🔧 View principal (cards + gráficos + tabelas)
    └── ../components/
        └── alerta-meta-risco.php     ✅ Componente condicional (Metas M4)

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
│   ├── ArteService::getEstatisticas()           → contagem por status
│   ├── ArteService::getDisponiveisParaVenda()   → artes com status 'disponivel'
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

### Variáveis Passadas à View (index)

| Variável | Tipo | Origem | Uso na View |
|----------|------|--------|-------------|
| `$artesStats` | array | ArteService | 4 cards principais + doughnut status |
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
| 8 | Gráfico Evolução Vendas | Chart.js Misto | Quantidade (linha) + Lucro (barra) | ✅ Funcional |
| 9 | Tabela Top Clientes | Tabela | #, Nome, Compras, Total | ✅ Funcional |
| 10 | Lista Artes Disponíveis | Lista | Arte + preço + link | ✅ Funcional |
| 11 | Lista Mais Rentáveis | Lista | Arte + R$/h | ✅ Funcional |
| 12 | Alerta Meta em Risco | Componente | Banner condicional (Metas M4) | ✅ Funcional |
| 13 | Botões Nova Arte / Venda | Header | Atalhos rápidos | ✅ Funcional |
| 14 | 6 Endpoints AJAX | API JSON | Refresh, artes, vendas, meta, atividades, busca | ✅ Funcional |

### Bugs Já Corrigidos (Anteriores)

| Bug | Causa | Correção | Data |
|-----|-------|----------|------|
| Top Clientes zerado | `topClientes()` retornava objetos que perdiam campos calculados | Retorno array bruto | 31/01/2026 |
| Gráficos infinitos | Canvas `responsive:true` sem altura fixa → loop resize | Container `height:280px` | 31/01/2026 |
| Dashboard quebrado | Objeto Cliente usado como array | Verificação defensiva `is_object()`/`is_array()` | 31/01/2026 |
| TypeError getValor() | Vendas às vezes retornam arrays em vez de objetos | Verificação defensiva no resumo | 31/01/2026 |

---

## 🔍 FASE 1 — ESTABILIZAÇÃO E TESTES BROWSER

### Abordagem

Diferente dos módulos CRUD (que tinham 12 testes padrão de create/edit/delete), o Dashboard requer testes de **exibição + integridade de dados + responsividade de gráficos**. O foco é garantir que:

1. Todos os dados exibidos estão corretos vs banco real
2. Gráficos renderizam sem erros JS no console
3. Links navegam corretamente
4. Cenários de banco vazio não quebram a página
5. Dados refletem operações recentes (criar/excluir venda atualiza Dashboard)

### Testes T1–T12

| # | Área | Teste | Verificação |
|---|------|-------|-------------|
| **T1** | Cards | Página carrega sem erros | Dashboard abre, 4 cards visíveis, sem erros no console |
| **T2** | Cards — Dados | Valores batem com banco | Conferir Total Artes, Vendas Mês, À Venda, Meta com phpMyAdmin |
| **T3** | Gráfico Faturamento | Barras renderizam | Gráfico azul com últimos 6 meses, tooltips mostram R$ |
| **T4** | Gráfico Status Artes | Doughnut renderiza | 3 fatias (Disponível/Produção/Vendida), legenda visível |
| **T5** | Gráfico Meta | Semi-doughnut renderiza | Verde (realizado) + vermelho (falta), valores na legenda |
| **T6** | Gráfico Evolução | Misto renderiza | Linha (quantidade) + barras (lucro), dual axis |
| **T7** | Top Clientes | Tabela correta | 5 clientes, ordenados por total compras DESC, links funcionam |
| **T8** | Artes Disponíveis | Lista correta | Artes com status='disponivel', link para show funciona |
| **T9** | Ranking Rentáveis | Lista correta | Top 5 por R$/h, badges com valores corretos |
| **T10** | Alerta Meta Risco | Condicional funciona | Se meta em risco → banner visível; se OK → sem banner |
| **T11** | Reflexo CRUD | Criar venda reflete | Crie venda, volte ao Dashboard: card Vendas Mês atualizado |
| **T12** | Banco vazio | Sem erros | Simule cenário sem vendas/metas: página carrega com "nenhum dado" |

### Potenciais Bugs (Investigar na Fase 1)

| # | Suspeita | Motivo | Verificação |
|---|----------|--------|-------------|
| D1 | Bug B8/B9 no controller | Dashboard não tem forms, mas `limparDadosFormulario()` pode estar ausente | Verificar se dados residuais afetam navegação Dashboard → outros módulos |
| D2 | Gráficos Chart.js duplicados | Se CDN carrega múltiplas vezes entre módulos (index já tem, Dashboard re-carrega) | Verificar `if (typeof Chart === 'undefined')` no script |
| D3 | `$vendasMes` tipo misto | `getVendasMesAtual()` retorna array de Venda objects mas `count()` é usado — OK, mas defensividade necessária | Verificar `is_array($vendasMes) ? count($vendasMes) : 0` |
| D4 | `$metaAtual` pode ser null/vazio | Se não existe meta do mês, `getResumoDashboard()` pode retornar `[]` ou `null` | Verificar `$metaAtual['porcentagem'] ?? 0` em toda a view |
| D5 | `$artesStats` chaves inconsistentes | Documentação diz `disponiveis`/`em_producao`/`vendidas`, mas ArteService pode usar nomes diferentes | Conferir vs `ArteService::getEstatisticas()` real |
| D6 | Endpoint `busca()` implementado? | Rota existe mas documentação marca como "TODO" | Testar `GET /dashboard/busca?q=teste` |
| D7 | `topClientes` formato array/objeto | Bug anterior corrigido mas pode regredir se `ClienteService::getTopClientes()` mudar | Verificação defensiva `is_object()`/`is_array()` na view |

---

## 📋 MELHORIAS PLANEJADAS (M1–M6)

### Visão Geral

O Dashboard é diferente dos módulos CRUD — suas melhorias focam em **conteúdo visual, UX e inteligência analítica**, não em CRUD features.

| # | Melhoria | Complexidade | Descrição |
|---|----------|-------------|-----------|
| M1 | Cards aprimorados | Baixa | +2 cards (Lucro Mês, Ticket Médio) + indicadores de tendência (↑↓) |
| M2 | Layout e responsividade | Média | Reorganização visual, collapse sections, mobile-first |
| M3 | Seção Atividades Recentes | Média | Timeline visual das últimas ações (vendas, artes criadas, metas) |
| M4 | KPIs e métricas avançadas | Média | Cards inteligentes: margem média, R$/h médio, projeção, taxa conversão |
| M5 | Período selecionável | Média | Dropdown: Mês Atual / Últimos 3 Meses / Últimos 6 Meses / Ano |
| M6 | Auto-refresh e polish | Baixa | Polling AJAX opcional, animações, estados vazios melhorados |

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

| Método | Service | Retorno | Usado em |
|--------|---------|---------|----------|
| `getEstatisticas()` | ArteService | `['total', 'disponiveis', 'em_producao', 'vendidas', 'media_horas']` | Cards + Doughnut |
| `getDisponiveisParaVenda()` | ArteService | Array de Arte objects | Card + Lista |
| `getVendasMesAtual()` | VendaService | Array de Venda objects | Card (count) |
| `getTotalMes(?mesAno)` | VendaService | float | Card faturamento |
| `getVendasMensais(6)` | VendaService | `[['mes', 'quantidade', 'total', 'lucro'], ...]` | Gráficos |
| `getRankingRentabilidade(5)` | VendaService | `[['arte_nome', 'valor', 'rentabilidade_hora'], ...]` | Lista ranking |
| `getEstatisticas()` | VendaService | `['total_vendas', 'valor_total', 'ticket_medio', 'lucro_total']` | KPIs (M4) |
| `getResumoDashboard()` | MetaService | `['valor_meta', 'valor_realizado', 'porcentagem', 'status']` | Card + Semi-doughnut |
| `getMetasEmRisco()` | MetaService | `['alerta' => bool, 'projecao' => float, ...]` | Alerta condicional |
| `getTopClientes(5)` | ClienteService | Array de arrays com `nome, total_compras, valor_total_compras` | Tabela |

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível
**Relevância Dashboard:** ⚠️ BAIXA — Dashboard não tem formulários de input. Mas se o usuário navegar Dashboard → Criar Venda → Erro → Voltar, `$_SESSION['_errors']` pode persistir. Não afeta o Dashboard diretamente.

### Bug B9: Dados Residuais
**Relevância Dashboard:** ⚠️ BAIXA — Mesma lógica do B8. O `index()` do Dashboard não chama `limparDadosFormulario()` atualmente. **Avaliar se necessário na Fase 1.**

### Bug Global Scope ($GLOBALS)
**Relevância Dashboard:** ⚠️ VERIFICAR — Se a view do Dashboard define funções helpers com `global $variavel`, o bug de escopo se aplica. **Verificar na Fase 1.**

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
├── Fase 1: Testes browser T1-T12 + correção de bugs encontrados

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

1. ✅ Tags         — independente                             → COMPLETO (6/6)
2. ✅ Clientes     — independente                             → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas)      → COMPLETO (6/6)
4. ✅ Artes        — depende de Tags (✅)                       → COMPLETO (6/6)
5. ✅ Vendas       — depende de Artes + Clientes + Metas       → COMPLETO (6/6)
6. 🎯 DASHBOARD   — depende de TODOS (Artes+Vendas+Metas+Clientes) → FASE 1 PENDENTE ★
```

### Por que Dashboard é o último?

1. **Dependência máxima:** Consome dados de todos os 4 Services
2. **Sem CRUD próprio:** Só exibe — bugs nos outros módulos se propagam aqui
3. **Agora é seguro:** Com todos os 5 módulos CRUD estabilizados (6/6 cada), os dados que o Dashboard consome são confiáveis

---

**Última atualização:** 03/03/2026  
**Status:** 🔄 Fase 1 pendente (testes browser)  
**Próxima ação:** Executar T1-T12 no browser  
**Dependências satisfeitas:** Tags ✅, Clientes ✅, Metas ✅, Artes ✅, Vendas ✅
