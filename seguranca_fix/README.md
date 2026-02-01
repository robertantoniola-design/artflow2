# 🔒 ArtFlow 2.0 - Correções de Segurança

## Problemas Corrigidos

| Problema | Severidade | Solução |
|----------|------------|---------|
| `public/uploads` não existe | Baixa | Diretório criado |
| `/config/routes.php` exposto | **CRÍTICA** | .htaccess de proteção |
| `/src/` acessível | **CRÍTICA** | .htaccess de proteção |
| `/storage/` acessível | Alta | .htaccess de proteção |

## 🚀 Instalação Rápida

### Opção 1: Script Automático (Recomendado)

```batch
cd C:\xampp\htdocs\artflow2
corrigir_seguranca.bat
```

### Opção 2: Manual

```batch
cd C:\xampp\htdocs\artflow2

REM Criar diretório uploads
mkdir public\uploads

REM Copiar arquivos .htaccess
copy seguranca_fix\config\.htaccess config\
copy seguranca_fix\src\.htaccess src\
copy seguranca_fix\storage\.htaccess storage\
```

## 📁 Conteúdo

```
seguranca_fix/
├── config/
│   └── .htaccess      ← Protege routes.php e outros
├── src/
│   └── .htaccess      ← Protege código fonte
├── storage/
│   └── .htaccess      ← Protege logs e cache
├── public/
│   └── uploads/
│       └── .gitkeep   ← Mantém pasta no Git
├── corrigir_seguranca.bat   ← Script automático
└── README.md
```

## ✅ Verificação

Após aplicar, execute os testes novamente:

```
http://localhost/artflow2/tests.php
```

Os testes devem mostrar:
- ✅ `Diretório public/uploads` → OK
- ✅ `Arquivo /config/routes.php` → Protegido (403)

## 🔐 O Que o .htaccess Faz

```apache
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
```

Isso bloqueia **todo acesso HTTP** aos arquivos da pasta.
Os arquivos ainda podem ser incluídos via PHP (`require`, `include`).

## ⚠️ Importante

Estas pastas **nunca** devem ser acessíveis via navegador:

- `/config/` - Contém configurações e rotas
- `/src/` - Código fonte PHP
- `/storage/` - Logs e cache (podem conter dados sensíveis)
- `/.env` - Credenciais do banco (já protegido)

---

*Correções de segurança para ArtFlow 2.0*
