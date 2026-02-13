# ArtFlow 2.0 — Módulo Clientes: Documentação Completa

**Data:** 13/02/2026  
**Status Geral:** ✅ Fase 1 COMPLETA + Melhorias 1 e 3 COMPLETAS  
**Versão Base:** CRUD estabilizado com paginação e campos UI expandidos  
**Ambiente:** XAMPP (Apache + MySQL + PHP 8.x)  
**Banco de dados:** `artflow2_db`

---

## 📋 RESUMO EXECUTIVO

O módulo de Clientes do ArtFlow 2.0 gerencia a base de clientes do negócio de arte, incluindo dados de contato, localização e histórico de compras. O módulo opera de forma independente (não depende de outros módulos), mas é consumido pelo módulo de Vendas (select de clientes nos formulários) e pelo Dashboard (Top Clientes por valor de compras).

O módulo passou por uma fase de estabilização com 9 bugs corrigidos, seguida de melhorias de UI e paginação.

### Status das Fases

| Fase | Descrição | Status |
|------|-----------|--------|
| Fase 1 | Estabilização CRUD — 9 bugs corrigidos | ✅ COMPLETA (13/02/2026) |
| Melhoria 1 | Paginação na listagem (12/página) | ✅ COMPLETA (13/02/2026) |
| Melhoria 2 | Ordenação dinâmica | 📋 PLANEJADA |
| Melhoria 3 | Campos adicionais no formulário UI | ✅ COMPLETA (13/02/2026) |
| Melhoria 4 | Exibição do histórico de compras na view show.php | ✅ JÁ FUNCIONAL (Fase 1) |
| Melhoria 5 | Estatísticas do cliente (cards com métricas) | ✅ JÁ FUNCIONAL (Fase 1) |
| Melhoria 6 | Máscara de telefone + validação client-side | 📋 PLANEJADA |

### Melhorias — Visão Geral

| # | Melhoria | Complexidade | Status |
|---|----------|--------------|--------|
| 1 | Paginação na listagem (12/página) | Baixa | ✅ COMPLETA |
| 2 | Ordenação dinâmica (nome, data, cidade) | Baixa | 📋 PLANEJADA |
| 3 | Campos adicionais no formulário UI | Baixa | ✅ COMPLETA |
| 4 | Exibição do histórico de compras no show.php | Baixa | ✅ JÁ FUNCIONAL |
| 5 | Estatísticas do cliente (cards financeiros) | Média | ✅ JÁ FUNCIONAL |
| 6 | Máscara de telefone + validação client-side | Baixa | 📋 PLANEJADA |

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
│   └── ClienteService.php             ✅ Melhoria 1 (+ listarPaginado)
├── Controllers/
│   └── ClienteController.php          ✅ Melhoria 1 (index com paginação)
└── Validators/
    └── ClienteValidator.php           ✅ Fase 1

views/
└── clientes/
    ├── index.php                      ✅ Melhoria 1 + 3 (paginação + localização nos cards)
    ├── create.php                     ✅ Melhoria 3 (+ endereço, cidade, estado, observações)
    ├── show.php                       ✅ Melhoria 3 (+ novos campos no card Informações)
    └── edit.php                       ✅ Melhoria 3 (+ endereço, cidade, estado, observações)

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

## 📋 MELHORIAS PENDENTES

### Melhoria 2 — Ordenação Dinâmica

**Complexidade:** Baixa  
**Status:** 📋 PLANEJADA

**O que fazer:**
- Links clicáveis nos headers: Nome (A-Z/Z-A), Data de cadastro (recentes/antigos), Cidade
- Indicador visual da ordenação ativa (seta ▲/▼)
- Preservar filtros de busca e paginação
- Backend já suporta (parâmetros `ordenar` e `direcao` no Controller)

**Arquivos a alterar:** views/clientes/index.php (apenas view)

---

### Melhoria 6 — Máscara de Telefone + Validação Client-Side

**Complexidade:** Baixa  
**Status:** 📋 PLANEJADA

**Problema atual:** O atributo `data-mask="telefone"` está correto, mas o app.js global pode não estar ativo.

**O que fazer:**
- Verificar app.js para máscara global
- Adicionar validação HTML5: `pattern` e `minlength`
- Feedback visual: borda vermelha se incompleto
- Bloquear submit se telefone preenchido mas incompleto

**Arquivos a alterar:** views/clientes/create.php, edit.php, public/assets/js/app.js

---

## 🔧 NOTAS TÉCNICAS

### Compatibilidade PHP 8.2+

O método `show()` do Controller foi ajustado para não usar propriedades dinâmicas. A view `show.php` agora suporta tanto arrays quanto objetos Venda, detectando automaticamente o tipo.

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

### Melhoria 3 (Campos UI)

| Arquivo | Caminho |
|---------|---------|
| create.php | `views/clientes/` |
| edit.php | `views/clientes/` |
| show.php | `views/clientes/` |
| index.php | `views/clientes/` |

---

## 📌 PRÓXIMAS AÇÕES

1. **Melhoria 2 (Ordenação)** — Apenas alteração na view index.php
2. **Melhoria 6 (Máscara)** — Correção de alinhamento data-mask
3. **Investigar bug sistêmico B8** — Verificar ArteController e MetaController

---

**Última atualização:** 13/02/2026  
**Status:** ✅ Fase 1 + Melhorias 1 e 3 COMPLETAS  
**Próxima ação:** Melhoria 2 (Ordenação) ou outro módulo
