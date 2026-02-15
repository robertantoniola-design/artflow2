# ArtFlow 2.0 — Módulo Clientes: Documentação Completa

**Data:** 14/02/2026  
**Status Geral:** ✅ MÓDULO 100% COMPLETO — Fase 1 + 6 Melhorias implementadas  
**Versão Base:** CRUD estabilizado com paginação, ordenação, campos UI, máscara de telefone  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Clientes do ArtFlow 2.0 gerencia a base de clientes do negócio de arte, incluindo dados de contato, localização e histórico de compras. O módulo opera de forma independente (não depende de outros módulos), mas é consumido pelo módulo de Vendas (select de clientes nos formulários) e pelo Dashboard (Top Clientes por valor de compras).

O módulo passou por uma fase de estabilização com 9 bugs corrigidos, seguida de 6 melhorias de UI, paginação, ordenação dinâmica, campos expandidos e validação de telefone.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 9 bugs corrigidos | ✅ COMPLETA (13/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | ✅ COMPLETA (13/02/2026) |
| Melhoria 2 | Ordenação dinâmica (nome, data, cidade) | ✅ COMPLETA (13/02/2026) |
| Melhoria 3 | Campos adicionais no formulário UI | ✅ COMPLETA (13/02/2026) |
| Melhoria 4 | Exibição do histórico de compras na view show.php | ✅ JÁ FUNCIONAL (Fase 1) |
| Melhoria 5 | Estatísticas do cliente (cards com métricas) | ✅ JÁ FUNCIONAL (Fase 1) |
| Melhoria 6 | Máscara de telefone + validação client-side | ✅ COMPLETA (14/02/2026) |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | ✅ COMPLETA |
| 2 | Ordenação dinâmica (nome, data, cidade) | Baixa | ✅ COMPLETA |
| 3 | Campos adicionais no formulário UI | Baixa | ✅ COMPLETA |
| 4 | Exibição do histórico de compras no show.php | Baixa | ✅ JÁ FUNCIONAL |
| 5 | Estatísticas do cliente (cards financeiros) | Média | ✅ JÁ FUNCIONAL |
| 6 | Máscara de telefone + validação client-side | Baixa | ✅ COMPLETA |

---

## 🏗️ ARQUITETURA DO MÓDULO

### Estrutura de Arquivos

```
src/
├── Models/
│   └── Cliente.php                    ✅ Original
├── Repositories/
│   └── ClienteRepository.php          ✅ Melhoria 1 (+ allPaginated, countAll)
├── Services/
│   └── ClienteService.php             ✅ Melhoria 6 (+ fix validateUpdate)
├── Controllers/
│   └── ClienteController.php          ✅ Melhoria 1 (index com paginação + ordenação)
└── Validators/
    └── ClienteValidator.php           ✅ Fase 1

views/
└── clientes/
    ├── index.php                      ✅ Melhoria 1 + 2 + 3 (paginação + ordenação + localização)
    ├── create.php                     ✅ Melhoria 3 + 6 (campos UI + atributos HTML5 telefone)
    ├── show.php                       ✅ Melhoria 3 (+ novos campos no card Informações)
    └── edit.php                       ✅ Melhoria 3 + 6 (campos UI + atributos HTML5 telefone)

public/
└── assets/js/
    └── app.js                         ✅ Melhoria 6 (Seção 5 expandida: máscara + validação)

config/
└── routes.php                         ✅ Original (resource + buscar antes do resource)
```

### Dependências entre Classes

```
ClienteController → ClienteService
ClienteService    → ClienteRepository + ClienteValidator
(Independente: NÃO depende de outros módulos)
```

**Quem depende de Clientes:**
- VendaController precisa listar clientes para select no formulário de venda
- DashboardController usa ClienteService.getTopClientes() para card "Top Clientes"

### Tabela `clientes` (Banco de Dados)

```sql
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(20) NULL,
    empresa VARCHAR(100) NULL,
    endereco VARCHAR(255) NULL,
    cidade VARCHAR(100) NULL,
    estado VARCHAR(2) NULL,
    observacoes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_clientes_nome (nome),
    INDEX idx_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## ✅ FASE 1 — ESTABILIZAÇÃO CRUD (COMPLETA)

**Implementada em:** 12-13/02/2026  
**Arquivos alterados:** ClienteController, ClienteRepository, ClienteService, ClienteValidator  
**Total de bugs:** 9 corrigidos

### Resumo dos Bugs Corrigidos

| Bug | Severidade | Descrição |
|-----|-----------|-----------|
| B1 | 🔴 CRÍTICO | Busca quebrada — Controller lia 'q', view envia 'termo' |
| B2 | 🔴 CRÍTICO | Campos cidade/estado/endereco/observacoes nunca salvos |
| B3 | 🟡 MÉDIO | Validação incompleta — sem cidade, estado, endereco, obs |
| B4 | 🟡 MÉDIO | Histórico de compras não exibido no show() |
| B5 | 🔴 CRÍTICO | Método getTopCompradores() não existia no Repository |
| B6 | 🔴 CRÍTICO | Métodos hasVendas() e emailExists() inexistentes |
| B7 | 🟡 MÉDIO | Busca search() não incluía telefone e cidade |
| B8 | 🔴 CRÍTICO | Erros de validação invisíveis — desalinhamento de sessão |
| B9 | 🔴 CRÍTICO | Edit carregava dados do último create que falhou |

---

## ✅ MELHORIA 1 — PAGINAÇÃO NA LISTAGEM (COMPLETA)

**Implementada em:** 13/02/2026  
**Arquivos alterados:** ClienteRepository, ClienteService, ClienteController, views/clientes/index.php

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **12 itens por página** | Mesmo padrão do módulo Tags |
| **Controles de navegação** | Primeira, anterior, números (até 5), próxima, última |
| **Preserva filtros** | Busca é mantida ao mudar de página |
| **Compatibilidade** | View funciona com ou sem paginação |

### Métodos Adicionados

**ClienteRepository:**
```php
allPaginated(int $pagina, int $porPagina, ?string $termo, string $ordenarPor, string $direcao): array
countAll(?string $termo): int
```

**ClienteService:**
```php
listarPaginado(array $filtros): array  // Retorna ['clientes' => [...], 'paginacao' => [...]]
```

**ClienteController (index):**
```php
$filtros = [
    'termo'   => $request->get('termo'),
    'pagina'  => (int) ($request->get('pagina') ?? 1),
    'ordenar' => $request->get('ordenar') ?? 'nome',
    'direcao' => $request->get('direcao') ?? 'ASC'
];
$resultado = $this->clienteService->listarPaginado($filtros);
```

---

## ✅ MELHORIA 2 — ORDENAÇÃO DINÂMICA (COMPLETA)

**Implementada em:** 13/02/2026  
**Arquivos alterados:** views/clientes/index.php (apenas view — backend já suportava)

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **3 botões de ordenação** | Nome (A-Z/Z-A), Data (recentes/antigos), Cidade (A-Z/Z-A) |
| **Toggle automático** | Clicar na coluna ativa inverte ASC↔DESC |
| **Indicador visual** | Botão ativo fica azul (`btn-primary`) + ícone de seta (▲/▼) |
| **Preserva filtros** | Busca + paginação mantidos ao mudar ordenação |
| **Setas contextuais** | `bi-sort-alpha-down/up` para texto, `bi-sort-down/up` para data |

### Funções Helper Adicionadas na View

```php
// Monta URL preservando TODOS os parâmetros (busca + ordenação + paginação)
clienteUrl(array $filtros, array $params = []): string

// Gera URL de ordenação com toggle ASC↔DESC automático
clienteSortUrl(array $filtros, string $coluna): string

// Retorna ícone HTML de seta para a coluna (ativa = colorida, inativa = cinza)
clienteSortIcon(array $filtros, string $coluna): string
```

### Colunas Suportadas (whitelist no Repository)

| Botão | Coluna no BD | Direção padrão ao ativar |
|-------|-------------|--------------------------|
| Nome | `nome` | ASC (A→Z) |
| Data | `created_at` | DESC (recentes primeiro) |
| Cidade | `cidade` | ASC (A→Z) |

### Correção Aplicada: Preservação de Filtros na Paginação

A função `clienteUrl()` foi ajustada para **sempre incluir** `ordenar` e `direcao` na URL, sem lógica de limpeza de defaults. Isso garante que a ordenação é preservada ao navegar entre páginas.

**Antes (problemático):**
```
/clientes?pagina=2          ← ordenar/direcao removidos por serem "default"
```

**Depois (correto):**
```
/clientes?ordenar=nome&direcao=ASC&pagina=2     ← sempre presente
/clientes?ordenar=cidade&direcao=DESC&pagina=3   ← preserva tudo
```

### Integração com Busca

O formulário de busca agora inclui campos `<input type="hidden">` para `ordenar` e `direcao`, garantindo que ao buscar um termo a ordenação ativa é mantida.

---

## ✅ MELHORIA 3 — CAMPOS ADICIONAIS NO FORMULÁRIO UI (COMPLETA)

**Implementada em:** 13/02/2026  
**Arquivos alterados:** views/clientes/create.php, edit.php, show.php, index.php

### Campos Adicionados nos Formulários

| Campo | Tipo | Seção |
|-------|------|-------|
| `endereco` | text (max 255) | Endereço |
| `cidade` | text (max 100) | Endereço |
| `estado` | select (27 UFs) | Endereço |
| `observacoes` | textarea | Informações Adicionais |

### Layout dos Formulários

Formulários organizados em 4 seções com ícones Bootstrap:
1. 👤 **Dados Básicos** — nome, empresa
2. 📞 **Contato** — email, telefone
3. 📍 **Endereço** — endereço, cidade, estado (select com 27 UFs)
4. 💬 **Informações Adicionais** — observações

### Exibição na show.php

Card "Informações" agora exibe:
- 📧 Email
- 📞 Telefone
- 🏢 Empresa
- 📍 Localização (Cidade/UF)
- 🏠 Endereço (se preenchido)
- 📅 Cliente desde
- 💬 Observações (se preenchidas, em caixa destacada)

### Exibição na index.php

Cards de clientes agora exibem localização (Cidade/UF) quando disponível.

---

## ✅ MELHORIA 6 — MÁSCARA DE TELEFONE + VALIDAÇÃO (COMPLETA)

**Implementada em:** 14/02/2026  
**Arquivos alterados:** public/assets/js/app.js, views/clientes/create.php, views/clientes/edit.php, src/Services/ClienteService.php

### O Que Foi Implementado

| Recurso | Descrição |
|---------|-----------|
| **Máscara progressiva** | Formata `(XX) XXXXX-XXXX` enquanto digita |
| **Validação visual** | Borda vermelha + feedback "Telefone incompleto" se parcial |
| **Bloqueio de submit** | Impede envio com telefone incompleto (1-9 dígitos) |
| **Atributos HTML5** | `pattern`, `maxlength`, `minlength`, `title`, `autocomplete` |
| **Script centralizado** | Lógica toda no `app.js` — views sem `<script>` inline |

### Camadas de Validação (5 níveis)

```
1. app.js → Máscara (só permite dígitos, limita 11, formata progressivamente)
2. app.js → Validação visual (blur: borda vermelha se incompleto)
3. app.js → Bloqueio de submit (preventDefault se 1-9 dígitos)
4. HTML5  → pattern + minlength (validação nativa do navegador)
5. Server → ClienteValidator::validarTelefoneBR() (10-11 dígitos obrigatórios)
```

### Formatos Aceitos

| Tipo | Formato | Dígitos |
|------|---------|---------|
| Fixo | `(XX) XXXX-XXXX` | 10 |
| Celular | `(XX) XXXXX-XXXX` | 11 |
| Vazio | _(campo opcional)_ | 0 |

### Alterações por Arquivo

**app.js (Seção 5 expandida — +135 linhas):**
- Máscara de telefone reescrita com formatação progressiva
- `validarTelefoneVisual(input)` — feedback em tempo real com Bootstrap 5
- Bloqueio de submit em forms com `input[data-mask="telefone"]`
- Todas as alterações marcadas com `[MELHORIA 6]`
- Zero impacto nas demais seções (1-4, 6-7, utilitárias, Dashboard)

**create.php e edit.php (3 mudanças cada):**
1. Comentário de header atualizado
2. Campo telefone: +5 atributos HTML5 (`pattern`, `maxlength`, `minlength`, `title`, `autocomplete`)
3. Script `<script>` inline removido (substituído por comentário)

### Bug Corrigido: validateUpdate() sem Efeito

**Descoberto em:** 14/02/2026 (durante auditoria completa do módulo)  
**Arquivo:** `src/Services/ClienteService.php` → método `atualizar()`  
**Severidade:** 🔴 CRÍTICO

**Problema:** `validateUpdate()` retorna `bool`, mas o retorno era ignorado no Service. Dados inválidos (nome vazio, email malformado, UF inexistente, telefone incompleto) passavam direto na edição.

**Antes (bugado):**
```php
$this->validator->validateUpdate($dados); // ← retorno bool ignorado!
```

**Depois (corrigido):**
```php
if (!$this->validator->validateUpdate($dados)) {
    throw new ValidationException($this->validator->getErrors());
}
```

**Impacto:** Agora a validação server-side funciona corretamente tanto na criação (`validate()` lança exceção) quanto na edição (`validateUpdate()` retorna bool → verificado e convertido em exceção).

---

## 🔧 NOTAS TÉCNICAS

### Compatibilidade PHP 8.2+

O método `show()` do Controller foi ajustado para não usar propriedades dinâmicas. A view `show.php` agora suporta tanto arrays quanto objetos Venda, detectando automaticamente o tipo.

**⚠️ Alerta futuro:** Na `show.php`, o acesso `$venda->arte_nome` usa propriedade dinâmica que será deprecated no PHP 8.2+ e erro fatal no PHP 9.0. Solução futura: adicionar propriedade `arte_nome` ao Model Venda ou tratar no `fromArray()`.

### Desalinhamento Sistêmico: Response vs Helpers (B8)

Este bug afeta **todo o framework**, não apenas Clientes:

```
Response::withErrors()  → salva em $_SESSION['_flash']['errors']
Helper errors()         → lê de $_SESSION['_errors']
```

**Solução aplicada:** Controller escreve direto em `$_SESSION['_errors']` e `$_SESSION['_old_input']`.

### Limpeza Seletiva de Sessão (B9)

| Método | Limpa Sessão? | Motivo |
|--------|---------------|--------|
| `create()` | ❌ NÃO | Precisa dos erros do store() |
| `edit()` | ✅ SIM | Evita contaminação do create() |
| `index()` | ✅ SIM | Navegação limpa dados |
| `show()` | ✅ SIM | Navegação limpa dados |

---

## 🗂️ ARQUIVOS ENTREGUES

### Fase 1

| Arquivo | Caminho |
|---------|---------|
| ClienteController.php | `src/Controllers/` |
| ClienteRepository.php | `src/Repositories/` |
| ClienteService.php | `src/Services/` |
| ClienteValidator.php | `src/Validators/` |

### Melhoria 1 (Paginação)

| Arquivo | Caminho |
|---------|---------|
| ClienteRepository.php | `src/Repositories/` |
| ClienteService.php | `src/Services/` |
| ClienteController.php | `src/Controllers/` |
| index.php | `views/clientes/` |

### Melhoria 2 (Ordenação Dinâmica)

| Arquivo | Caminho |
|---------|---------|
| index.php | `views/clientes/` |

### Melhoria 3 (Campos UI)

| Arquivo | Caminho |
|---------|---------|
| create.php | `views/clientes/` |
| edit.php | `views/clientes/` |
| show.php | `views/clientes/` |
| index.php | `views/clientes/` |

### Melhoria 6 (Máscara de Telefone + Validação)

| Arquivo | Caminho | Mudança |
|---------|---------|---------|
| app.js | `public/assets/js/` | Seção 5 expandida (+135 linhas) |
| create.php | `views/clientes/` | +5 atributos HTML5, -script inline |
| edit.php | `views/clientes/` | +5 atributos HTML5, -script inline |
| ClienteService.php | `src/Services/` | Fix validateUpdate() (1 mudança cirúrgica) |

---

## ✅ VERIFICAÇÃO CRUZADA FINAL (10 ARQUIVOS)

Auditoria completa realizada em 14/02/2026 com todos os 10 arquivos do módulo:

| Verificação | Resultado |
|-------------|-----------|
| Campos DB ↔ Model (10 campos) | ✅ Alinhado |
| Model ↔ Repository ($fillable = 8 campos editáveis) | ✅ Alinhado |
| Repository ↔ Service (todos métodos chamados) | ✅ Alinhado |
| Service ↔ Controller (todas operações coordenadas) | ✅ Alinhado |
| Controller ↔ Views (variáveis passadas = consumidas) | ✅ Alinhado |
| Views ↔ app.js (data-mask="telefone" capturado) | ✅ Alinhado |
| Validação CREATE (5 camadas: JS → HTML5 → Server) | ✅ Completo |
| Validação UPDATE (5 camadas: JS → HTML5 → Server) | ✅ Corrigido (14/02) |
| Paginação preserva estado (filtros + ordenação) | ✅ Funcional |
| Delete protection (hasVendas + modal) | ✅ Funcional |
| CSRF em todos os forms | ✅ Protegido |
| XSS (output com e()/htmlspecialchars) | ✅ Protegido |

---

## 📌 MÓDULO COMPLETO — PRÓXIMO PASSO

### ✅ Módulo Clientes: FINALIZADO (14/02/2026)

Todas as 6 melhorias planejadas foram implementadas e testadas. Nenhuma pendência restante.

### 🎯 Próximo Módulo Recomendado: ARTES

**Justificativa baseada na ordem de dependências:**

```
Ordem de estabilização (menor → maior acoplamento):

1. ✅ Tags         — independente                     → COMPLETO (6/6)
2. ✅ Clientes     — independente                     → COMPLETO (6/6)
3. ✅ Metas        — independente (atualizado por Vendas) → COMPLETO (6/6)
4. 🎯 ARTES       — depende de Tags (✅ pronto)       → NÃO TESTADO NO NAVEGADOR
5. ⏳ Vendas       — depende de Artes + Clientes + Metas → NÃO TESTADO
```

**Por que Artes agora:**

| Fator | Detalhe |
|-------|---------|
| **Dependência satisfeita** | Tags (seletor de tags no form) já está 100% completo |
| **É pré-requisito** | Vendas precisa de Artes funcional para o select de arte_id |
| **CRUD não testado** | Nenhuma operação testada no navegador ainda |
| **Complexidade média** | Tem relação M:N com Tags (tabela `arte_tags`) |
| **Campos especiais** | Status (disponivel/em_producao/vendida/reservada), complexidade, preço |

**O que esperar no módulo Artes:**
1. **Fase 1** — Testar CRUD completo no navegador e corrigir bugs
2. **Melhorias** — Paginação, ordenação, filtro por status/tags, upload de imagens

---

**Última atualização:** 14/02/2026  
**Status:** ✅ MÓDULO 100% COMPLETO (Fase 1 + 6/6 Melhorias)  
**Próximo módulo:** 🎯 Artes (Fase 1 — estabilização CRUD)
