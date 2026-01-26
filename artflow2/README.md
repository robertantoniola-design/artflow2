# 🎨 ArtFlow 2.0

Sistema profissional de gestão artística desenvolvido em PHP 8.0+ com arquitetura em camadas.

## 📋 Requisitos

- **PHP** 8.0 ou superior
- **MySQL** 5.7+ ou **MariaDB** 10.3+
- **Composer** (gerenciador de dependências PHP)
- **XAMPP** ou servidor web com Apache/Nginx

## 🚀 Instalação Rápida

### 1. Copie os arquivos para o XAMPP
```bash
# Copie a pasta artflow2 para:
C:\xampp\htdocs\artflow2
```

### 2. Instale as dependências
```bash
cd C:\xampp\htdocs\artflow2
composer install
```

### 3. Configure o ambiente
```bash
# Copie o arquivo de exemplo
copy .env.example .env

# Edite o .env com suas configurações de banco
```

### 4. Execute a instalação
```bash
php install.php
```

### 5. Acesse o sistema
```
http://localhost/artflow2/
```

## 📁 Estrutura do Projeto

```
artflow2/
├── config/              # Configurações
│   └── routes.php       # Definição de rotas
├── database/
│   ├── migrations/      # Migrations do banco
│   └── migrate.php      # Executor de migrations
├── public/              # Arquivos públicos (DocumentRoot)
│   ├── assets/          # CSS, JS, imagens
│   └── index.php        # Ponto de entrada
├── src/                 # Código-fonte
│   ├── Controllers/     # Controllers (apresentação)
│   ├── Core/            # Núcleo do sistema
│   ├── Exceptions/      # Exceções customizadas
│   ├── Helpers/         # Funções auxiliares
│   ├── Models/          # Entidades do domínio
│   ├── Repositories/    # Acesso a dados
│   ├── Services/        # Lógica de negócio
│   └── Validators/      # Validação de dados
├── storage/             # Logs e cache
├── tests/               # Testes automatizados
├── views/               # Templates HTML
├── .env                 # Configurações locais
├── composer.json        # Dependências PHP
└── install.php          # Script de instalação
```

## 🏗️ Arquitetura

O ArtFlow 2.0 segue uma arquitetura em camadas:

```
Request → Router → Controller → Service → Repository → Database
                        ↓            ↓
                    Validator      Model
                        ↓
                    Response → View
```

### Camadas:
- **Controllers**: Recebem requisições e retornam respostas
- **Services**: Contêm lógica de negócio
- **Repositories**: Acessam banco de dados
- **Models**: Representam entidades
- **Validators**: Validam dados de entrada

## 📊 Módulos

### 🎨 Artes
- CRUD completo de artes
- Controle de status (disponível, em produção, vendida)
- Rastreamento de horas trabalhadas
- Associação com tags

### 👥 Clientes
- Cadastro de clientes
- Histórico de compras
- Estatísticas por cliente

### 💰 Vendas
- Registro de vendas
- Cálculo automático de lucro
- Rentabilidade por hora
- Relatórios de faturamento

### 🎯 Metas
- Definição de metas mensais
- Acompanhamento de progresso
- Projeções e análises

### 🏷️ Tags
- Organização por categorias
- Sistema de cores
- Filtros rápidos

## 🔧 Comandos Úteis

```bash
# Instalar/reinstalar sistema
php install.php

# Executar migrations
php database/migrate.php

# Resetar banco (CUIDADO: apaga dados!)
php database/migrate.php fresh

# Reverter última migration
php database/migrate.php rollback
```

## 📱 Rotas Principais

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/` | Dashboard |
| GET | `/artes` | Listar artes |
| GET | `/artes/criar` | Formulário nova arte |
| POST | `/artes` | Salvar arte |
| GET | `/artes/{id}` | Detalhes da arte |
| GET | `/clientes` | Listar clientes |
| GET | `/vendas` | Listar vendas |
| GET | `/vendas/relatorio` | Relatórios |
| GET | `/metas` | Listar metas |
| GET | `/tags` | Listar tags |

## 🎨 Tecnologias

- **Backend**: PHP 8.0+, PSR-4 Autoload
- **Banco**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, Chart.js
- **Icons**: Bootstrap Icons
- **Fonts**: Inter (Google Fonts)

## 📈 Features

- ✅ Arquitetura MVC + Repository + Service Layer
- ✅ Dependency Injection Container
- ✅ Sistema de Migrations
- ✅ Validação em camadas
- ✅ Flash Messages
- ✅ CSRF Protection
- ✅ Dark Mode
- ✅ Responsivo (Mobile-first)
- ✅ AJAX updates no Dashboard
- ✅ Gráficos com Chart.js

## 🔒 Segurança

- Prepared Statements (proteção SQL Injection)
- CSRF Tokens em formulários
- Sanitização de inputs
- Validação server-side
- XSS Protection (escape de output)

## 📝 Licença

Projeto desenvolvido para fins educacionais e uso pessoal.

---

**ArtFlow 2.0** - Desenvolvido com ❤️ para artistas
