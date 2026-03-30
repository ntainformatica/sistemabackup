# Ambientes, GitHub e deploy — SistemaBackup

**Data:** 30/03/2026  
**Objetivo:** registrar como o projeto se relaciona com **máquina local**, **GitHub** e **servidor** (PostgreSQL só na VPS), para testes e próximas pesquisas.

---

## 1. Evidências verificadas no repositório (30/03/2026)

| Item | Evidência |
|------|-----------|
| Pasta local do projeto | `C:\xampp1\htdocs\SistemaBackup` (workspace confirmado) |
| Remoto Git | `git remote -v` → `https://github.com/ntainformatica/sistemabackup.git` |
| Branch | `main` |
| Credenciais de BD fora do Git | `.gitignore` contém `/config/database.local.php` e `/.env` |
| Modelo de DSN | `config/database.local.example.php` — PostgreSQL via PDO |
| Imagem Docker opcional | `Dockerfile` (PHP 8.2 + Apache + extensão `pdo_pgsql`) |
| Ficheiro Coolify no repo | **Nenhum** (`coolify*` não encontrado) — comportamento do Coolify é **configuração no painel**, não no Git |

---

## 2. Por que “testar com o banco real” normalmente implica servidor

- O dashboard PHP lê o PostgreSQL (`monitoramento_bkps` na VPS, conforme documentação do projeto).
- Sem PostgreSQL local com o **mesmo schema e dados**, o PHP em XAMPP só valida **sintaxe** e páginas vazias/erro de conexão.
- **Opções reais para teste integrado:**
  1. **Deploy** do código na mesma rede/VPS onde o PostgreSQL aceita conexões (recomendado no desenho atual).
  2. **Túnel SSH** da porta 5432 (não documentado como ativo — PENDENTE se usarem).
  3. **Expor** PostgreSQL à internet (evitado pelo próprio relatório interno do projeto).

Isto **não** estava explícito no `step-by-step-2025-03-25.md`; daí este ficheiro.

---

## 3. Fluxo local → GitHub (versionamento)

Comandos típicos no **PowerShell**, na pasta do projeto:

```powershell
cd C:\xampp1\htdocs\SistemaBackup
php -l index.php
php -l src\ApiJobs.php
php -l src\Services\JobBoardService.php
php -l src\Services\JobDetailService.php
php -l src\helpers.php
# templates: php -l não valida HTML embutido, mas valida blocos PHP se houver ficheiros .php puros
git add .
git commit -m "mensagem descritiva do que mudou"
git push origin main
```

- **`git push`** exige autenticação GitHub (conta/token/SSH) — a criação da conta GitHub para isso é coerente com o fluxo descrito pelo outro assistente.
- Mensagens de commit: preferir descrição útil (ex.: `dashboard: volumes, último SUCCESS, backup_paths no payload`).

---

## 4. GitHub → servidor (modos possíveis)

| Modo | O que fazer após `push` |
|------|---------------------------|
| **A — SSH + git** | No servidor: `cd` na pasta da app → `git pull origin main` → recarregar Apache/PHP-FPM se necessário. |
| **B — Painel (Coolify / similar)** | `push` → no painel: **Deploy** / **Redeploy** do serviço ligado ao repo (se existir webhook ou pipeline). |
| **C — Só Git como backup** | Copiar ficheiros para o servidor por outro meio; o Git não atualiza a app sozinho. |

### 4.1 Confirmado em 26/03/2026 — Coolify + produção (relato operacional)

O deploy funcional do SistemaBackup ficou **confirmado** via **GitHub + Coolify**, usando o repositório `https://github.com/ntainformatica/sistemabackup.git`, branch **`main`**, e o **`Dockerfile`** versionado na raiz do projeto.

- **Publicação:** após `git push origin main`, o deploy/redeploy foi acionado **manualmente** pelo painel do Coolify. **Não** ficou confirmado **auto-deploy** por push nesta configuração.
- **Build:** uso do `Dockerfile` do próprio repositório (não uma stack PHP genérica sem esse ficheiro).
- **Base de dados no container:** em produção o app **não** usa `config/database.local.php` (ficheiro ausente no container) e lê **`DB_DSN`**, **`DB_USER`** e **`DB_PASS`** do ambiente — alinhado a `config/database.php` (prioridade a `getenv`, depois `database.local.php` se `DB_DSN` vazio):

```21:33:config/database.php
    $dsn = getenv('DB_DSN') ?: '';
    $user = getenv('DB_USER') ?: '';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    if ($dsn === '') {
        $local = __DIR__ . '/database.local.php';
        if (is_file($local)) {
            /** @var array{dsn:string,user:string,pass:string} $cfg */
            $cfg = require $local;
            $dsn = $cfg['dsn'];
            $user = $cfg['user'];
            $pass = $cfg['pass'];
        }
    }
```

- **Rede até ao PostgreSQL (n8n):** conexão operacional com IP interno **`172.16.32.2`** após ajuste manual de rede entre o container da app e a rede do PostgreSQL do n8n. **Hostname interno persistente não ficou funcional** neste estado.
- **Avaliação:** solução **funcional** mas **frágil** (IP/rede manual). **Próximo passo recomendado (arquitetura):** migrar o database **`monitoramento_bkps`** para um **PostgreSQL dedicado** ao SistemaBackup no Coolify (ou DNS/rede estável documentada), e alinhar credenciais nos workflows **n8n** que hoje apontam para a mesma instância.

**PENDENTE menor (opcional):** caminho absoluto de deploy dentro do container (só útil para debug de ficheiros); não bloqueia operação.

---

## 5. Configuração da BD — desenvolvimento vs produção

| Ambiente | Forma típica |
|----------|----------------|
| **Local (XAMPP)** | `config/database.local.php` (gitignored), a partir de `database.local.example.php`. |
| **Produção (Coolify confirmado 26/03/2026)** | Variáveis **`DB_DSN`**, **`DB_USER`**, **`DB_PASS`** no serviço; DSN deve incluir `dbname=monitoramento_bkps` (ou o nome efetivo do database). |

Nunca commitar passwords nem `database.local.php`.

---

## 6. PowerShell vs Linux (nota rápida)

- `ls -la` é típico de **bash**. No PowerShell: `Get-ChildItem -Force` ou `dir`.
- Encadear comandos: no PowerShell clássico usar `;` em vez de `&&` (dependendo da versão).

---

## 7. Ligação com outros documentos

- Arquitetura do produto: `docs/step-by-step/step-by-step-2026-03-30-consolidacao-monitoramento-backups.md`
- MVP PHP original: `docs/step-by-step/step-by-step-2025-03-25.md`

---

## 8. Resumo em uma frase

**O código versiona-se no GitHub; o PostgreSQL de monitoramento está na infraestrutura remota — o teste integrado do dashboard em produção passa por deploy Coolify (redeploy manual após push, conforme 26/03/2026) e variáveis `DB_*` no container; em local usa-se `database.local.php` ou `DB_*`.**

---

**Atualizado em 30/03/2026** (incorpora confirmação operacional de **26/03/2026**).
