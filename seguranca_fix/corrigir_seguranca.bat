@echo off
REM ============================================
REM ARTFLOW 2.0 - CORREÇÕES DE SEGURANÇA
REM ============================================
REM Execute este arquivo na pasta artflow2
REM ============================================

echo.
echo ========================================
echo  ARTFLOW 2.0 - Correcoes de Seguranca
echo ========================================
echo.

REM Verifica se está na pasta correta
if not exist "config\routes.php" (
    echo ERRO: Execute este script na pasta artflow2
    echo Exemplo: cd C:\xampp\htdocs\artflow2
    pause
    exit /b 1
)

echo [1/4] Criando diretorio public\uploads...
if not exist "public\uploads" (
    mkdir public\uploads
    echo       Criado!
) else (
    echo       Ja existe.
)

echo.
echo [2/4] Protegendo pasta config...
(
echo # Protecao da pasta config - Bloqueia acesso HTTP
echo ^<IfModule mod_authz_core.c^>
echo     Require all denied
echo ^</IfModule^>
echo ^<IfModule !mod_authz_core.c^>
echo     Order deny,allow
echo     Deny from all
echo ^</IfModule^>
) > config\.htaccess
echo       .htaccess criado em config\

echo.
echo [3/4] Protegendo pasta src...
(
echo # Protecao da pasta src - Bloqueia acesso HTTP
echo ^<IfModule mod_authz_core.c^>
echo     Require all denied
echo ^</IfModule^>
echo ^<IfModule !mod_authz_core.c^>
echo     Order deny,allow
echo     Deny from all
echo ^</IfModule^>
) > src\.htaccess
echo       .htaccess criado em src\

echo.
echo [4/4] Protegendo pasta storage...
(
echo # Protecao da pasta storage - Bloqueia acesso HTTP
echo ^<IfModule mod_authz_core.c^>
echo     Require all denied
echo ^</IfModule^>
echo ^<IfModule !mod_authz_core.c^>
echo     Order deny,allow
echo     Deny from all
echo ^</IfModule^>
) > storage\.htaccess
echo       .htaccess criado em storage\

echo.
echo ========================================
echo  CONCLUIDO!
echo ========================================
echo.
echo Pastas protegidas:
echo   - config\  (routes.php, etc)
echo   - src\     (codigo fonte)
echo   - storage\ (logs, cache)
echo.
echo Diretorio criado:
echo   - public\uploads\
echo.
echo Execute os testes novamente para verificar!
echo.
pause
