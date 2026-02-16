# ArtFlow 2.0 — Módulo Artes: Documentação Completa

**Data:** 16/02/2026  
**Status Geral:** ✅ FASE 1 + MELHORIAS 1, 2 e 3 COMPLETAS — Ordenação dinâmica 6 colunas implementada  
**Versão Base:** CRUD estabilizado + Paginação + Filtros combinados + Ordenação dinâmica  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Artes do ArtFlow 2.0 é o módulo central do sistema — gerencia o portfólio de obras artísticas, incluindo dados de produção (tempo, complexidade, custo), status de disponibilidade e categorização via Tags (relacionamento N:N). O módulo depende de Tags (seletor no formulário) e é pré-requisito para o módulo de Vendas (select de arte_id no formulário de venda) e para o Dashboard (estatísticas e gráficos).

O módulo passou por uma fase de estabilização com **11 bugs corrigidos** em 4 sessões de trabalho (15/02/2026), cobrindo backend (Controller, Service, Validator) e frontend (4 views). Todos os 12 testes CRUD passaram com sucesso. A **Melhoria 1 (Paginação)** foi implementada em 16/02/2026 com 12/12 testes OK, incluindo filtros combinados (status + tag + busca simultâneos) que antecipam a Melhoria 3. A **Melhoria 2 (Ordenação Dinâmica)** foi implementada em 16/02/2026 com 10/10 testes OK, adicionando 6 colunas ordenáveis com headers clicáveis e botões de ordenação.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 11 bugs corrigidos, 12/12 testes | ✅ COMPLETA (15/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | ✅ COMPLETA (16/02/2026) |
| Melhoria 2 | Ordenação dinâmica (6 colunas clicáveis) | ✅ COMPLETA (16/02/2026) |
| Melhoria 3 | Filtros combinados (status + tag + busca simultâneos) | ✅ COMPLETA (via M1) — UI já funcional |
| Melhoria 4 | Upload de imagem + galeria visual | 📋 PLANEJADA |
| Melhoria 5 | Estatísticas por arte (cards financeiros no show.php) | 📋 PLANEJADA |
| Melhoria 6 | Gráfico de distribuição (Chart.js — status + complexidade) | 📋 PLANEJADA |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Dependência | Status |
|---|----------|--------------|-------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | — | ✅ COMPLETA |
| 2 | Ordenação dinâmica (6 colunas) | Baixa | Melhoria 1 ✅ | ✅ COMPLETA |
| 3 | Filtros combinados (status + tag + busca) | Média | Melhoria 1 ✅ | ✅ COMPLETA (via M1) |
| 4 | Upload de imagem + galeria visual | Média | — | 📋 PLANEJADA |
| 5 | Estatísticas por arte (cards no show.php) | Média | — | 📋 PLANEJADA |
| 6 | Gráfico de distribuição (Doughnut + Barras) | Baixa | — | 📋 PLANEJADA |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Arte.php                       ✅ Original
├── Repositories/
│   └── ArteRepository.php             🔧 Melhoria 1 (+ allPaginated, countAll — filtros combinados + whitelist 6 colunas)
├── Services/
│   └── ArteService.php                🔧 Melhoria 1 (+ listarPaginado, POR_PAGINA=12)
├── Controllers/
│   └── ArteController.php             🔧 Melhoria 1 (index usa listarPaginado + passa $paginacao + ordenar/direcao)
└── Validators/
    └── ArteValidator.php              🔧 CORRIGIDO Fase 1 (A1: status reservada no ENUM)

views/
└── artes/
    ├── index.php                      🔧 Melhoria 2 (+ arteSortUrl, arteSortIcon, headers clicáveis, botões ordenação)
    ├── create.php                     🔧 CORRIGIDO Fase 1 (dropdown dinâmico via $statusList)
    ├── show.php                       🔧 CORRIGIDO Fase 1 (url() helper, botão excluir, cards status/horas)
    └── edit.php                       🔧 CORRIGIDO Fase 1 (dropdown dinâmico, maxlength 150, campo hidden vendida)

database/
├── migrations/
│   ├── 001_create_artes_table.php     ✅ Executada
│   └── 006_create_arte_tags_table.php ✅ Executada (pivot N:N)
└── seeds/
    └── ArteSeeder.php                 ✅ Executado
```

### Dependências entre Classes

```
ArteController → ArteService + TagService
ArteService    → ArteRepository + TagRepository + ArteValidator
(Depende de Tags para seletor no formulário)

ArteController::index()     usa ArteService::listarPaginado() + TagService::listar() [M1]
ArteController::create()    usa TagService::listar() para checkboxes de tags
ArteController::store()     usa ArteService::criar() que sincroniza tags via ArteRepository
ArteController::show()      usa ArteService::getTags() + calcularCustoPorHora() + calcularPrecoSugerido()
ArteController::edit()      usa TagService::listar() + TagService::getTagIdsArte()
ArteController::update()    usa ArteService::atualizar() que faz sync de tags
ArteController::alterarStatus()  usa ArteService::alterarStatus()
ArteController::adicionarHoras() usa ArteService::adicionarHoras()
```

**Nota sobre acoplamento:** O módulo Artes depende de Tags (✅ COMPLETO) para o seletor de categorias. NÃO depende de Vendas ou Metas.

**Quem depende de Artes:**
- VendaService usa ArteRepository para buscar arte e atualizar status para 'vendida'
- VendaController precisa de ArteService para listar artes disponíveis no formulário de venda
- DashboardController usa ArteService.getEstatisticas() e ArteService.getDisponiveisParaVenda()

### Tabela `artes` (Banco de Dados)

```sql
CREATE TABLE artes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,                                 -- Nome da arte
    descricao TEXT NULL,                                         -- Descrição detalhada
    tempo_medio_horas DECIMAL(6,2) NULL,                        -- Tempo estimado em horas
    complexidade ENUM('baixa','media','alta') DEFAULT 'media',  -- Nível de dificuldade
    preco_custo DECIMAL(10,2) DEFAULT 0,                        -- Custo de produção em R$
    horas_trabalhadas DECIMAL(8,2) DEFAULT 0,                   -- Horas já investidas
    status ENUM('disponivel','em_producao','vendida','reservada') DEFAULT 'disponivel',
    imagem VARCHAR(255) NULL,                                   -- Caminho do arquivo (upload NÃO implementado)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_artes_nome (nome),
    INDEX idx_artes_status (status),
    INDEX idx_artes_complexidade (complexidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
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

**CASCADE:** Ao deletar arte ou tag, remove automaticamente a associação na pivot.

### Campos do Formulário (create.php / edit.php)

| Campo | Tipo HTML | Validação | Obrigatório | Notas |
|-------|-----------|-----------|-------------|-------|
| nome | text | max:150 | ✅ | — |
| descricao | textarea | — | ❌ | Texto livre |
| tempo_medio_horas | number (step 0.5) | min:0 | ❌ | Estimativa de produção |
| complexidade | select | in:baixa,media,alta | ✅ | Default: media |
| preco_custo | number (step 0.01) | min:0 | ✅ | Em R$ |
| horas_trabalhadas | number (step 0.5) | min:0 | ❌ | Acumulativo |
| status | select | in:disponivel,em_producao,vendida,reservada | ✅ | Default: disponivel |
| tags[] | checkbox multiple | IDs existentes | ❌ | Relacionamento N:N |

### Rotas (9 total)

```
ARTES (7 RESTful + 2 extras)
  GET    /artes              → ArteController@index         (listar com filtros + ordenação)
  GET    /artes/criar        → ArteController@create        (formulário criação)
  POST   /artes              → ArteController@store         (salvar nova)
  GET    /artes/{id}         → ArteController@show          (detalhes + tags + cálculos)
  GET    /artes/{id}/editar  → ArteController@edit          (formulário edição)
  PUT    /artes/{id}         → ArteController@update        (atualizar + sync tags)
  DELETE /artes/{id}         → ArteController@destroy       (excluir — CASCADE remove arte_tags)
  POST   /artes/{id}/status  → ArteController@alterarStatus (muda status sem editar tudo)
  POST   /artes/{id}/horas   → ArteController@adicionarHoras(incrementa horas_trabalhadas)
```

---

## ✅ FASE 1 — ESTABILIZAÇÃO CRUD (COMPLETA)

**Status:** ✅ 12/12 testes passando  
**Data de conclusão:** 15/02/2026  
**Sessões de trabalho:** 4 sessões no mesmo dia  
**Total de bugs corrigidos:** 11

### Checklist de Testes

| # | Operação | Rota | O que verificar | Status |
|---|----------|------|-----------------|--------|
| T1 | Listar | `GET /artes` | Carrega sem erros, exibe dados dos seeds, tags no filtro | ✅ |
| T2 | Criar (form) | `GET /artes/criar` | Formulário abre, checkboxes de tags aparecem | ✅ |
| T3 | Criar (salvar) | `POST /artes` | Validação funciona, salva no banco com tags associadas | ✅ |
| T4 | Visualizar | `GET /artes/{id}` | Exibe dados + tags + cálculos (custo/hora, preço sugerido) | ✅ |
| T5 | Editar (form) | `GET /artes/{id}/editar` | Preenche valores existentes, tags marcadas corretamente | ✅ |
| T6 | Editar (salvar) | `PUT /artes/{id}` | Atualiza dados + sync de tags funciona | ✅ |
| T7 | Excluir | `DELETE /artes/{id}` | Confirmação funciona, arte some, arte_tags CASCADE | ✅ |
| T8 | Filtro status | `GET /artes?status=disponivel` | Filtra corretamente | ✅ |
| T9 | Filtro tag | `GET /artes?tag_id=X` | Retorna artes da tag selecionada | ✅ |
| T10 | Busca | `GET /artes?termo=X` | Busca por nome e descrição | ✅ |
| T11 | Alterar status | `POST /artes/{id}/status` | Muda status sem editar toda a arte | ✅ |
| T12 | Adicionar horas | `POST /artes/{id}/horas` | Incrementa horas_trabalhadas | ✅ |

### Bugs Corrigidos — Detalhe Completo

#### Sessão 1: Análise de Código-Fonte (9 bugs identificados)

Análise estática do código antes de testes no navegador, baseada nos padrões de bugs encontrados nos módulos Tags e Clientes.

#### Sessão 2: Correção Backend (ArteController.php + ArteValidator.php)

**Bug A1: Status 'reservada' ausente no Validator**
- **Arquivo:** `ArteValidator.php`
- **Problema:** `$statusValidos` tinha apenas 3 dos 4 valores do ENUM da migration
- **Causa:** Migration define `ENUM('disponivel','em_producao','vendida','reservada')` mas validator original só listava 3
- **Correção:** Adicionado `'reservada'` ao array `$statusValidos` e mensagens de erro atualizadas
- **Impacto:** Criar/editar arte com status 'reservada' era rejeitado silenciosamente

**Bug B8 Workaround: Validação Invisível**
- **Arquivo:** `ArteController.php`
- **Problema:** Classe `Response` grava erros em `$_SESSION['_flash']`, helpers leem `$_SESSION['_errors']`
- **Correção:** Controller grava erros diretamente em `$_SESSION['_errors']` (mesmo workaround do ClienteController)
- **Impacto:** Erros de validação agora são exibidos corretamente nos formulários

**Bug B9 Workaround: Dados Residuais**
- **Arquivo:** `ArteController.php`
- **Problema:** Após validação falhar no create, dados ficam em `$_SESSION['_old_input']` e contaminam o edit de outra arte
- **Correção:** `limparDadosFormulario()` chamado em `index()`, `edit()` e `show()` — NUNCA em `create()`

**Conversão string→int**
- **Arquivo:** `ArteController.php`
- **Correção:** `$id = (int) $id` adicionado em todos os métodos que recebem ID do Router

**$statusList passado para views**
- **Arquivo:** `ArteController.php`
- **Correção:** Array `$statusList` com os 4 status enviado para create.php e edit.php, permitindo dropdown dinâmico

#### Sessão 3: Correção Views (4 arquivos)

**index.php:**
- Dropdown de filtro: adicionada opção `<option value="reservada">Reservada</option>`
- Mapa de cores/labels: adicionado `'reservada' => 'info'` e `'reservada' => 'Reservada'`

**create.php:**
- Dropdown de status: agora usa `$statusList` dinâmico do controller (4 opções em vez de 2)

**edit.php:**
- Dropdown dinâmico via `$statusList` com fallback para 4 status
- Badge de status no header inclui "reservada" no `match()`
- Campo hidden para status "vendida" quando select está disabled
- `maxlength` do nome corrigido de 100 para 150 (consistente com `VARCHAR(150)`)

**show.php:**
- Todas as URLs hardcoded (`/artes/X/editar`) substituídas por `url()` helper
- Botão Excluir adicionado com form `DELETE` + modal de confirmação
- Card "Alterar Status" adicionado com form `POST url('/artes/{id}/status')`
- Card "Adicionar Horas" adicionado com form `POST url('/artes/{id}/horas')`
- Status "reservada" adicionado em todos os `match()` de cores/labels
- Token CSRF padronizado para `_token`

#### Sessão 4: Re-teste + Correções Finais (T1 e T11)

**Bug T1: Busca retorna 0 resultados**
- **Arquivo:** `ArteService.php` — método `listar()`
- **Problema:** URL `?termo=artemis&status=&tag_id=` gerava `$filtros['status'] = ""` (string vazia).
  O operador `??` só testa null, não empty. Logo `"" ?? null = ""` (retorna "" porque "" NÃO é null).
  O Repository recebia `$status = ""` e adicionava `AND status = ''` → 0 resultados.
- **Correção:** Normalização de filtros com encadeamento `?? null ?: null`:
  ```php
  $status = $filtros['status'] ?? null ?: null;  // "" → null, "disponivel" → "disponivel"
  $termo  = $filtros['termo']  ?? null ?: null;
  $tagId  = $filtros['tag_id'] ?? null ?: null;
  ```
- **Lição:** O operador `??` (null coalesce) testa APENAS null/undefined. O operador `?:` (Elvis/falsy coalesce) testa todos os valores falsy ("", 0, false, null).

**Bug T11: Transição de status 'reservada' bloqueada**
- **Arquivo:** `ArteService.php` — método `validarTransicaoStatus()`
- **Problema:** O array `$transicoesPermitidas` não continha a chave `'reservada'` e 'reservada' não aparecia como destino válido em nenhum status. Resultado: qualquer transição FROM ou TO 'reservada' era rejeitada.
- **Correção:** Array expandido com regras completas:
  ```php
  $transicoesPermitidas = [
      'disponivel'  => ['em_producao', 'vendida', 'reservada'],   // +reservada
      'em_producao' => ['disponivel', 'vendida', 'reservada'],    // +reservada
      'reservada'   => ['disponivel', 'em_producao', 'vendida'],  // NOVO
      'vendida'     => []                                          // Estado final
  ];
  ```
- **Lógica de negócio:** reservada é um estado intermediário — cliente reservou mas não comprou. Pode voltar para disponivel (cancelou), em_producao (retomou trabalho) ou vendida (confirmou compra).

### Resumo de Arquivos Modificados na Fase 1

| Arquivo | Caminho | Bugs Corrigidos |
|---------|---------|-----------------|
| **ArteController.php** | `src/Controllers/ArteController.php` | B8 workaround, B9 limparDados, conversão int, $statusList |
| **ArteValidator.php** | `src/Validators/ArteValidator.php` | A1 status reservada no ENUM |
| **ArteService.php** | `src/Services/ArteService.php` | T1 normalização filtros, T11 transições reservada |
| **index.php** | `views/artes/index.php` | Dropdown filtro 4 status, cores/labels reservada |
| **create.php** | `views/artes/create.php` | Dropdown dinâmico $statusList |
| **show.php** | `views/artes/show.php` | url() helper, botão excluir, cards status/horas, reservada |
| **edit.php** | `views/artes/edit.php` | Dropdown dinâmico, maxlength 150, hidden vendida |

### Histórico das Sessões

| # | Data | Foco | Entregas |
|---|------|------|----------|
| 1 | 15/02 manhã | Análise de bugs no código-fonte | Relatório com 9 bugs identificados |
| 2 | 15/02 manhã | Correção backend | ArteController.php + ArteValidator.php (7 bugs fixados) |
| 3 | 15/02 tarde | Correção views | 4 views corrigidas (index, show, create, edit) |
| 4 | 15/02 noite | Re-teste + fixes finais | T1 (busca) + T11 (transição status) → 12/12 OK |
| 5 | 16/02 manhã | Melhoria 1 — Paginação | 4 arquivos (Repository, Service, Controller, view) → 12/12 testes OK |
| 6 | 16/02 tarde | Melhoria 2 — Ordenação | 1 arquivo (view index.php) → 10/10 testes OK |

---

## ✅ MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (COMPLETA)

**Implementada em:** 16/02/2026  
**Complexidade:** Baixa  
**Padrão:** Idêntico a Tags e Clientes (12 itens por página)  
**Arquivos alterados:** ArteRepository, ArteService, ArteController, views/artes/index.php  
**Testes:** 12/12 OK (P1–P12)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **12 itens por página** | Mesmo padrão dos módulos Tags e Clientes |
| **Controles de navegação** | Anterior, números (janela de 5), próxima, reticências |
| **Preserva filtros** | Status, tag_id e busca mantidos ao mudar de página |
| **Indicador de total** | "Mostrando X–Y de Z artes" |
| **Filtros combinados** | Status + Tag + Busca aplicados simultaneamente (antecipa M3) |
| **Botão Limpar Filtros** | Remove todos os filtros de uma vez |

### Métodos Adicionados

**ArteRepository:**
```php
// Busca paginada com 3 filtros combinados (WHERE dinâmico com AND)
allPaginated(int $pagina, int $porPagina, ?string $termo, ?string $status, 
             ?int $tagId, string $ordenarPor, string $direcao): array

// Contagem total com mesmos filtros (para cálculo de páginas)
countAll(?string $termo, ?string $status, ?int $tagId): int
```

**ArteService:**
```php
const POR_PAGINA = 12;

// Coordena paginação + filtros
listarPaginado(array $filtros): array
// Retorna: ['artes' => [...], 'paginacao' => ['total', 'porPagina', 'paginaAtual', 'totalPaginas', 'temAnterior', 'temProxima']]
```

**ArteController::index():**
```php
$filtros = [
    'termo'   => $request->get('termo'),
    'status'  => $request->get('status'),
    'tag_id'  => $request->get('tag_id'),
    'pagina'  => (int) ($request->get('pagina') ?? 1),
    'ordenar' => $request->get('ordenar') ?? 'created_at',
    'direcao' => $request->get('direcao') ?? 'DESC'
];
$resultado = $this->arteService->listarPaginado($filtros);
```

**views/artes/index.php:**
```php
// Helper para montar URLs preservando filtros
function arteUrl(array $filtros, array $params = []): string
// Paginação Bootstrap 5 com janela de 5 páginas
// Indicador "Mostrando X–Y de Z artes"
```

### Decisões Técnicas

| Decisão | Justificativa |
|---------|---------------|
| **Subquery para tag_id** | `IN (SELECT arte_id FROM arte_tags WHERE tag_id = :tag_id)` evita duplicatas no JOIN N:N |
| **Filtros combinados na M1** | O `allPaginated()` já usa `WHERE ... AND ... AND ...` em vez de if/elseif, antecipando M3 |
| **Whitelist com 6 colunas** | `$camposPermitidos` já inclui nome, complexidade, preco_custo, horas_trabalhadas, status, created_at — preparado para M2 |
| **`listar()` mantido** | Compatibilidade com Dashboard e Vendas que usam `ArteService::listar()` |

### Testes Realizados (12/12 OK)

| # | Teste | Resultado |
|---|-------|-----------|
| P1 | Listagem paginada (>12 artes) | ✅ |
| P2 | Navegação entre páginas | ✅ |
| P3 | Filtro por status | ✅ |
| P4 | Filtro por tag | ✅ |
| P5 | Busca por termo | ✅ |
| P6 | Filtros preservados ao paginar | ✅ |
| P7 | Indicador "Mostrando X–Y de Z" | ✅ |
| P8 | Limpar filtros | ✅ |
| P9 | Sem resultados (termo inexistente) | ✅ |
| P10 | Menos de 12 artes (sem paginação) | ✅ |
| P11 | CRUD intacto (criar, editar, excluir) | ✅ |
| P12 | Cards de status corretos | ✅ |

### Lição Aprendida (Tags/Clientes)

> Preservação de estado é essencial: paginação, busca, ordenação e filtros devem persistir via URL params ao navegar entre páginas. O Router passa strings — o Controller deve converter para int onde necessário.

---

## ✅ MELHORIA 2 — ORDENAÇÃO DINÂMICA (COMPLETA)

**Implementada em:** 16/02/2026  
**Complexidade:** Baixa  
**Padrão:** Idêntico a Tags e Clientes (headers clicáveis com indicador visual)  
**Arquivos alterados:** views/artes/index.php (apenas view — backend já pronto via M1)  
**Pré-requisito:** Melhoria 1 ✅ COMPLETA  
**Testes:** 10/10 OK (T1–T10)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **6 botões de ordenação** | Nome, Complexidade, Custo, Horas, Status, Data |
| **Headers da tabela clicáveis** | Cada `<th>` é um link que ordena pela coluna correspondente |
| **Toggle automático** | Clicar na coluna ativa inverte ASC↔DESC |
| **Indicador visual** | Botão ativo fica azul (`btn-primary`) + ícone de seta contextual |
| **Preserva filtros** | Busca + status + tag + paginação mantidos ao mudar ordenação |
| **Setas contextuais** | `bi-sort-alpha-down/up` para texto, `bi-sort-numeric-down/up` para valores, `bi-sort-down/up` para data |
| **Direções padrão inteligentes** | Texto começa ASC (A→Z), numérico/data começa DESC (maior primeiro) |

### Funções Helper Adicionadas na View

```php
// Monta URL preservando TODOS os parâmetros (busca + filtros + ordenação + paginação)
arteUrl(array $filtros, array $params = []): string  // [já existia M1, ajustada M2]

// Gera URL de ordenação com toggle ASC↔DESC automático
arteSortUrl(array $filtros, string $coluna): string   // [NOVA M2]

// Retorna ícone HTML de seta para a coluna (ativa = colorida, inativa = cinza)
arteSortIcon(array $filtros, string $coluna): string   // [NOVA M2]
```

### Colunas Ordenáveis (Whitelist no Repository)

| Botão | Coluna no BD | Direção padrão ao ativar | Tipo de ícone |
|-------|-------------|--------------------------|---------------|
| Nome | `nome` | ASC (A→Z) | `bi-sort-alpha-down/up` |
| Complexidade | `complexidade` | ASC (baixa→alta) | `bi-sort-alpha-down/up` |
| Custo | `preco_custo` | DESC (maior primeiro) | `bi-sort-numeric-down/up` |
| Horas | `horas_trabalhadas` | DESC (mais horas primeiro) | `bi-sort-numeric-down/up` |
| Status | `status` | ASC (ordenação ENUM) | `bi-sort-alpha-down/up` |
| Data | `created_at` | DESC (recentes primeiro) — **PADRÃO** | `bi-sort-down/up` |

### Whitelist no Repository (já implementada M1)

```php
// Colunas permitidas para ordenação (proteção contra SQL injection)
private array $ordenacaoPermitida = [
    'nome', 'complexidade', 'preco_custo', 
    'horas_trabalhadas', 'status', 'created_at'
];
```

### Integração com Filtros de Busca

O formulário de busca agora inclui campos `<input type="hidden">` para `ordenar` e `direcao`, garantindo que ao buscar um termo a ordenação ativa é mantida.

```html
<!-- Preserva ordenação durante busca -->
<input type="hidden" name="ordenar" value="<?= e($ordenarAtual) ?>">
<input type="hidden" name="direcao" value="<?= e($direcaoAtual) ?>">
```

### Decisões Técnicas

| Decisão | Justificativa |
|---------|---------------|
| **Backend inalterado** | Whitelist e params `ordenar`/`direcao` já prontos desde M1 |
| **Dois pontos de ordenação** | Botões no card de filtros + headers na tabela = dupla usabilidade |
| **`arteUrl()` sempre inclui ordenar/direcao** | Lição do módulo Clientes: sem isso, paginação perdia ordenação |
| **Direções padrão por tipo** | Texto ASC, numérico/data DESC — comportamento intuitivo |
| **Ícones por tipo de dado** | Alfa para texto, numérico para valores, genérico para data |
| **Mapas no topo do arquivo** | `$statusLabels`, `$complexLabels` extraídos do foreach para reutilização |

### Testes Realizados (10/10 OK)

| # | Teste | O que verificar | Resultado |
|---|-------|-----------------|-----------|
| T1 | Acessar `/artes` | Botão "Data" ativo (azul), seta DESC | ✅ |
| T2 | Clicar "Nome" | Reordena A→Z, botão "Nome" fica azul | ✅ |
| T3 | Clicar "Nome" de novo | Inverte Z→A, seta muda | ✅ |
| T4 | Clicar "Custo" | Reordena maior→menor (DESC) | ✅ |
| T5 | Clicar "Custo" de novo | Inverte menor→maior (ASC) | ✅ |
| T6 | Filtrar + ordenar | Ordenação preservada após filtro | ✅ |
| T7 | Paginar + ordenar | Ordenação preservada ao mudar página | ✅ |
| T8 | Header "Horas" na tabela | Mesma funcionalidade dos botões | ✅ |
| T9 | Limpar filtros | Default `created_at DESC` restaurado | ✅ |
| T10 | CRUD intacto | Criar, editar, excluir funcionam | ✅ |

### Correção Aplicada: Preservação de Filtros na Paginação

A função `arteUrl()` foi ajustada para **sempre incluir** `ordenar` e `direcao` na URL, sem lógica de limpeza de defaults. Isso garante que a ordenação é preservada ao navegar entre páginas.

**Antes (M1 — funcionava mas podia perder ordenação):**
```
/artes?pagina=2          ← ordenar/direcao poderiam ser omitidos
```

**Depois (M2 — sempre presente):**
```
/artes?ordenar=nome&direcao=ASC&pagina=2     ← sempre preservado
/artes?status=disponivel&ordenar=preco_custo&direcao=DESC&pagina=3   ← tudo mantido
```

---

## ✅ MELHORIA 3 — FILTROS COMBINADOS (BACKEND PRONTO VIA M1)

**Complexidade:** Média  
**Status:** ✅ BACKEND + UI JÁ FUNCIONAIS — Implementados junto com Melhoria 1  
**Arquivos alterados:** Mesmos da Melhoria 1

### Situação

O `ArteService::listar()` original usava `if/elseif`, tornando os filtros mutuamente exclusivos. Isso foi **resolvido na Melhoria 1**: o novo `allPaginated()` constrói `WHERE` dinâmico com `AND`, aplicando todos os filtros simultaneamente.

### Problema Original (Resolvido)

```php
// ANTES (ArteService::listar) — filtros mutuamente exclusivos
if ($status && !$termo) { return findByStatus($status); }
if ($termo) { return search($termo, $status); }
if ($tagId) { return findByTag($tagId); }

// DEPOIS (ArteRepository::allPaginated) — filtros combinados
// WHERE status = :status AND (nome LIKE :t OR descricao LIKE :t) AND id IN (SELECT...)
```

### O Que Falta (Opcional)

A Melhoria 3 pode ser considerada **COMPLETA** pois:
- ✅ Backend: `allPaginated()` + `countAll()` já combinam status + tag + busca
- ✅ UI: Barra de filtros já funciona com os 3 dropdowns + botão Limpar
- ✅ Paginação: Filtros preservados ao navegar entre páginas

Se desejado futuramente, melhorias adicionais poderiam incluir:
- Filtro por complexidade (adicionar dropdown)
- Filtro por faixa de preço (min/max)
- Indicadores visuais de filtros ativos (badges)

---

## 📋 MELHORIA 4 — UPLOAD DE IMAGEM + GALERIA (PLANEJADA)

**Complexidade:** Média  
**Arquivos a alterar:** ArteService, ArteController, ArteValidator, views/artes/create.php, edit.php, show.php, index.php  
**Pré-requisito:** Fase 1 ✅

### Justificativa

O campo `imagem VARCHAR(255)` já existe na tabela `artes` desde a migration 001, mas o upload **nunca foi implementado**. Para um sistema de gestão de arte, a imagem da obra é essencial para identificação, catálogo e apresentação a clientes.

### Especificação Técnica

| Aspecto | Detalhe |
|---------|---------|
| **Storage** | `storage/uploads/artes/` (diretório no servidor) |
| **Formatos aceitos** | JPG, JPEG, PNG, WEBP |
| **Tamanho máximo** | 2MB por arquivo |
| **Nomenclatura** | `arte_{id}_{timestamp}.{ext}` (evita colisões) |
| **Thumbnail** | Opcional — redimensionar para 300x300 na listagem |
| **Campo no banco** | `imagem` já existe — armazena caminho relativo |

### Funcionalidades

| Feature | View | Descrição |
|---------|------|-----------|
| **Upload com preview** | create.php / edit.php | Input file com preview JavaScript antes de enviar |
| **Thumbnail na listagem** | index.php | Imagem pequena (50x50) ao lado do nome na tabela |
| **Imagem ampliada** | show.php | Imagem em destaque no card de detalhes |
| **Remoção** | edit.php | Checkbox "Remover imagem" que limpa o campo |
| **Placeholder** | Todas | Ícone genérico quando não há imagem |

### Validação (ArteValidator)

```php
// Novas regras para upload
'imagem' => [
    'tipo' => ['image/jpeg', 'image/png', 'image/webp'],
    'max_tamanho' => 2 * 1024 * 1024,  // 2MB
    'extensoes' => ['jpg', 'jpeg', 'png', 'webp']
]
```

### Cuidado com Segurança

- Verificar MIME type real do arquivo (não confiar só na extensão)
- Sanitizar nome do arquivo
- Armazenar FORA da pasta `public/` por segurança, ou usar `.htaccess` para proteger o diretório de uploads
- Alternativa: servir via controller com verificação de permissão

---

## 📋 MELHORIA 5 — ESTATÍSTICAS POR ARTE (PLANEJADA)

**Complexidade:** Média  
**Arquivos a alterar:** ArteService, ArteController, views/artes/show.php  
**Pré-requisito:** Fase 1 ✅

### Cards de Métricas no show.php

| Card | Dado | Cálculo | Condição |
|------|------|---------|----------|
| **Custo/Hora** | R$/hora investida | `preco_custo / horas_trabalhadas` | Só se horas > 0 |
| **Preço Sugerido** | Preço mínimo de venda | `preco_custo × multiplicador` (ex: 2.5×) | Sempre visível |
| **Progresso** | % do tempo estimado | `horas_trabalhadas / tempo_medio_horas × 100` | Só se tempo estimado > 0 |
| **Lucro (se vendida)** | Lucro real da venda | Buscar na tabela `vendas` | Só se status = 'vendida' |
| **Rentabilidade** | R$/hora de lucro | `lucro / horas_trabalhadas` | Só se vendida + horas > 0 |

### Dados Adicionais

- **Tags associadas:** Badges coloridas com ícone (usando dados do módulo Tags ✅)
- **Histórico de status:** Se implementarmos um log de mudanças (futuro)
- **Comparação tempo estimado vs real:** Barra de progresso visual

### Nota Técnica

Os métodos `calcularCustoPorHora()` e `calcularPrecoSugerido()` já existem no ArteService e foram verificados durante a Fase 1. O controller `show()` já os utiliza corretamente.

---

## 📋 MELHORIA 6 — GRÁFICO DE DISTRIBUIÇÃO (PLANEJADA)

**Complexidade:** Baixa  
**Arquivos a alterar:** ArteService, ArteController, views/artes/index.php  
**Pré-requisito:** Fase 1 ✅  
**Biblioteca:** Chart.js 4.4.7 via CDN (mesmo padrão de Tags e Metas)

### Gráficos Planejados

| Gráfico | Tipo Chart.js | Dados | Localização |
|---------|--------------|-------|-------------|
| **Distribuição por Status** | Doughnut | disponivel / em_producao / vendida / reservada | index.php (topo) |
| **Distribuição por Complexidade** | Barras horizontais | baixa / media / alta | index.php (topo) |

### Indicadores (Cards de Resumo)

| Indicador | Cálculo |
|-----------|---------|
| **Total de Artes** | COUNT(*) |
| **Valor em Estoque** | SUM(preco_custo) WHERE status IN ('disponivel', 'em_producao', 'reservada') |
| **Horas Totais Investidas** | SUM(horas_trabalhadas) |
| **Artes Disponíveis** | COUNT WHERE status = 'disponivel' |

### Dados do Repository

O método `countByStatus()` já existe no ArteRepository — retorna GROUP BY status. Para complexidade, criar `countByComplexidade()` seguindo o mesmo padrão.

### Padrão de Implementação

```php
// Controller: só passa dados se houver artes no banco
$temDadosGrafico = !empty($estatisticas) && array_sum(array_column($estatisticas, 'total')) > 0;

// View: Chart.js só carregado se $temDadosGrafico for true
// Container com altura fixa de 300px (evita loop de redimensionamento — lição do Dashboard)
```

---

## 📌 BUGS SISTÊMICOS CONHECIDOS

### Bug B8: Validação Invisível (Afeta TODOS os módulos)

**Problema:** A classe `Response` armazena erros de validação em `$_SESSION['_flash']`, mas as funções helper `has_error()` e `errors()` leem de `$_SESSION['_errors']`. Resultado: validação falha silenciosamente.

**Status no módulo Artes:** ✅ Workaround aplicado no ArteController (grava direto em `$_SESSION['_errors']`).

**Solução ideal (futura):** Corrigir a classe Response no framework para gravar em `$_SESSION['_errors']`.

### Bug B9: Dados Residuais no Edit

**Problema:** Após validação falhar no create, dados ficam em `$_SESSION['_old_input']` e contaminam o edit de outra arte.

**Status no módulo Artes:** ✅ Workaround aplicado — `limparDadosFormulario()` chamado em index(), edit() e show().

---

## 📌 MAPA DE MÉTODOS — VERIFICAÇÃO CRUZADA

### Métodos chamados no Controller vs existência no Service

| Método chamado no Controller | Existe no Service? | Status |
|------------------------------|--------------------|--------|
| `ArteService::listarPaginado($filtros)` | ✅ Sim | ✅ Adicionado Melhoria 1 |
| `ArteService::listar($filtros)` | ✅ Sim | ✅ Mantido para compatibilidade |
| `ArteService::buscar($id)` | ✅ Sim | ✅ Verificado |
| `ArteService::criar($dados)` | ✅ Sim | ✅ Verificado |
| `ArteService::atualizar($id, $dados)` | ✅ Sim | ✅ Verificado |
| `ArteService::remover($id)` | ✅ Sim | ✅ Verificado |
| `ArteService::alterarStatus($id, $status)` | ✅ Sim | ✅ Verificado + Corrigido (T11) |
| `ArteService::adicionarHoras($id, $horas)` | ✅ Sim | ✅ Verificado |
| `ArteService::getEstatisticas()` | ✅ Sim | ✅ Verificado |
| `ArteService::getTags($id)` | ✅ Sim | ✅ Verificado |
| `ArteService::calcularCustoPorHora($arte)` | ✅ Sim | ✅ Verificado |
| `ArteService::calcularPrecoSugerido($arte)` | ✅ Sim | ✅ Verificado |
| `TagService::listar()` | ✅ Sim (módulo Tags completo) | ✅ Verificado |
| `TagService::getTagIdsArte($id)` | ✅ Sim | ✅ Verificado |

---

## 📌 LIÇÕES APRENDIDAS NA FASE 1

### Padrão ?? vs ?: no PHP

```php
// ?? (null coalesce) — SÓ testa null/undefined
"" ?? null     // → "" (string vazia NÃO é null!)
null ?? "foo"  // → "foo"

// ?: (Elvis/falsy coalesce) — testa TODOS os valores falsy
"" ?: null     // → null ("" é falsy)
0 ?: null      // → null (0 é falsy)
"foo" ?: null  // → "foo" (não é falsy)

// Combinação segura para filtros de URL:
$filtros['status'] ?? null ?: null  // undefined→null, ""→null, "valor"→"valor"
```

### Transições de Status como Máquina de Estados

O status de uma arte segue uma máquina de estados com transições explícitas. Ao adicionar novos status (como 'reservada'), é preciso atualizar em **3 lugares**: Validator (valores válidos), Service (transições permitidas) e Views (labels/cores/badges).

### Padrões dos Módulos Anteriores (Aplicar nas Melhorias)

| Padrão | Origem | Aplicação |
|--------|--------|-----------|
| Paginação 12/página | Tags M1, Clientes M1 | ✅ Aplicado Melhoria 1 |
| Headers clicáveis ▲/▼ | Tags M2, Clientes M2 | ✅ Aplicado Melhoria 2 |
| Whitelist de colunas para ORDER BY | Tags M2 | ✅ Aplicado Melhoria 1 (6 colunas prontas para M2) |
| `limparDadosFormulario()` | Clientes B9 | ✅ Aplicado Fase 1 |
| `$_SESSION['_errors']` direto | Clientes B8 | ✅ Aplicado Fase 1 |
| Chart.js 4.4.7 CDN + container 300px | Tags M6, Dashboard | Melhoria 6 |
| Preservação de estado via URL params | Tags M1, Clientes M1 | ✅ Aplicado Melhoria 1 (arteUrl helper) |
| Conversão string→int no Controller | Tags (Router bug fix) | ✅ Aplicado Fase 1 |
| Filtros combinados via WHERE dinâmico | Artes M1 (antecipou M3) | ✅ Aplicado Melhoria 1 |
| Sempre incluir ordenar/direcao na URL | Clientes M2 (fix preservação) | ✅ Aplicado Melhoria 2 |

---

## 📌 PRÓXIMAS AÇÕES

1. **Iniciar Melhoria 4 — Upload de Imagem + Galeria**
   - Campo `imagem VARCHAR(255)` já existe na tabela
   - Storage em `storage/uploads/artes/`
   - Altera 7 arquivos (Service, Controller, Validator, 4 views)

2. **Sequência recomendada:**
   ```
   ✅ Fase 1 (COMPLETA — 12/12 testes OK)
   ✅ Melhoria 1 (COMPLETA — Paginação 12/página + filtros combinados)
   ✅ Melhoria 2 (COMPLETA — Ordenação dinâmica 6 colunas + headers clicáveis)
   ✅ Melhoria 3 (COMPLETA VIA M1 — backend + UI já funcionais)
   
   Melhoria 4 (upload de imagem — independente)
   Melhoria 5 (estatísticas — independente)
   Melhoria 6 (gráficos — independente)
   ```

3. **Após módulo Artes completo:** Iniciar módulo Vendas (depende de Artes + Clientes + Metas)

---

## 📌 CONTEXTO NO SISTEMA

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                        → COMPLETO (6/6)
2. ✅ Clientes     — independente                        → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas) → COMPLETO (6/6)
4. 🔧 ARTES        — depende de Tags (✅ pronto)          → FASE 1 + M1 + M2 + M3 COMPLETAS, M4/M5/M6 PENDENTES
5. ⏳ Vendas       — depende de Artes + Clientes + Metas → NÃO TESTADO
```

---

**Última atualização:** 16/02/2026  
**Status:** ✅ FASE 1 + MELHORIAS 1, 2 e 3 COMPLETAS — Próximo: Melhoria 4 (Upload de Imagem)  
**Próxima ação:** Implementar upload de imagem + galeria visual
