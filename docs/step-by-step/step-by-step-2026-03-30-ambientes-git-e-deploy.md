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

- **Rede até ao PostgreSQL (estado anterior):** conexão operacional com IP interno **`172.16.32.2`** após ajuste manual de rede entre o container da app e a rede do PostgreSQL do n8n. **Hostname interno persistente não ficou funcional** nessa fase.
- **Evolução:** migração do database **`monitoramento_bkps`** para **PostgreSQL dedicado** no Coolify (hostname de serviço, sem depender desse IP). Após migrar, **PHP e n8n** devem usar o **mesmo** destino — ver **§5.1–5.2**.

**PENDENTE menor (opcional):** caminho absoluto de deploy dentro do container (só útil para debug de ficheiros); não bloqueia operação.

---

## 5. Configuração da BD — desenvolvimento vs produção

| Ambiente | Forma típica |
|----------|----------------|
| **Local (XAMPP)** | `config/database.local.php` (gitignored), a partir de `database.local.example.php`. |
| **Produção (Coolify confirmado 26/03/2026)** | Variáveis **`DB_DSN`**, **`DB_USER`**, **`DB_PASS`** no serviço; DSN deve incluir `dbname=monitoramento_bkps` (ou o nome efetivo do database). |

Nunca commitar passwords nem `database.local.php`.

### 5.1 Dois conceitos de “banco” no n8n (não confundir)

| O quê | Função |
|-------|--------|
| **Base interna do n8n** (ex. database `n8n` no Postgres do stack n8n) | Metadados da **aplicação** n8n: utilizadores, workflows, execuções do próprio n8n, etc. **Não** é o `monitoramento_bkps`. |
| **Credencial Postgres nos nós dos workflows** (WF-01, WF-02) | Ligação **de cliente** ao servidor onde está o database **`monitoramento_bkps`** — é aqui que corre o INSERT/UPDATE do monitoramento. |

Ou seja: o n8n **não precisa** “usar o SistemaBackup como banco dele” no sentido de substituir a base interna do n8n. Precisa que os **workflows** consigam **atingir por rede** o PostgreSQL onde vive o `monitoramento_bkps`.

### 5.2 Quem fala com `monitoramento_bkps` (tem de ser o mesmo sítio)

| Componente | Papel no database `monitoramento_bkps` |
|------------|----------------------------------------|
| **Nós Postgres dos workflows** (ingestão + alertas) | **Escrevem** (e leem) eventos, estado, alertas. |
| **Dashboard PHP** | **Lê** (sobretudo) para o NOC/API. |

**Regra:** host + porta + database `monitoramento_bkps` (e user com permissões) devem ser **os mesmos** na credencial do n8n e nas env **`DB_*`** do PHP.  
**Se os workflows ainda apontarem para um Postgres antigo** enquanto o PHP aponta para o novo → dados **divergentes**.

**Checklist após migração:**

1. Credencial **Postgres** nos workflows → apontar ao **PostgreSQL dedicado** do monitoramento + `monitoramento_bkps`.
2. Variáveis **`DB_*`** no SistemaBackup → **o mesmo** servidor/database.
3. Teste: webhook → linha nova em `backup_execution_events` e visível no PHP.

### 5.3 Rede no Coolify (stacks separados — o outro chat está alinhado)

Por defeito, **stacks diferentes** podem estar em **redes Docker distintas**: comunicação **não** é automática entre projetos.

Opções comuns (documentação Coolify / prática Docker):

1. **Connect to Predefined Network** (ou equivalente) — ligar o stack do **n8n** (ou só o serviço necessário) à rede onde está o **Postgres do SistemaBackup**, para usar **hostname interno** e porta `5432` **sem** expor o Postgres à internet.
2. **Public URL** do Postgres — só se a rede interna não for viável; **aumenta superfície de ataque** — segunda escolha.

**“Um projeto só” no Coolify** não é obrigatório: o que resolve é **topologia de rede** (mesma rede ou rota segura entre stacks), não o nome do projeto no painel.

### 5.4 Agrupar serviços no Coolify (opcional)

**Não é obrigatório** para o sistema funcionar; às vezes **ajuda** a rede ficar trivial:

- **Opção A — Um projeto Coolify** com, por exemplo: serviço **PostgreSQL** (só `monitoramento_bkps` ou várias DBs) + serviço **SistemaBackup** (PHP). Rede e segredos ficam óbvios.
- **Opção B — Projectos separados** (ex.: n8n noutro projecto): continua válido **desde que** o n8n consiga resolver o **hostname** do Postgres novo na rede interna e a firewall/ACL permita `5432`.

O **Git** continua separado: repositório `sistemabackup` = só código PHP; **n8n** não vive nesse repo — vive na instância n8n. “Um projeto só” no Coolify é **agrupamento de serviços**, não “fundir Git com n8n”.

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

**O código versiona-se no GitHub; o PostgreSQL `monitoramento_bkps` está na infraestrutura remota — o n8n e o PHP são dois clientes do mesmo database; o teste integrado exige deploy Coolify, `DB_*` no app e credencial Postgres no n8n alinhadas ao mesmo host/db.**

---

## 9. Validação em produção (pós-migração)

**Data de registo:** 30/03/2026.

- Com **`DB_*`** no Coolify apontando para o PostgreSQL dedicado e dados migrados para **`monitoramento_bkps`**, o dashboard em produção (**sistemabackup.ntainformatica.com.br**) foi verificado no detalhe do job **DNT** (`backup_restic_dnt_pve2`): estado **OK**, **SLA: ok**, cartões de volume Restic, histórico de execuções **SUCCESS** e gráfico **Execuções por dia (14 dias)** com contagens coerentes — confirma **leitura** correta da nova base.
- Aviso de UI sobre **`backup_paths`** ausentes no payload: esperado até o PS1 enviar o campo; não indica falha de migração.
- **Recomendação:** confirmar por uma execução real ou teste de webhook que o **n8n** continua a **gravar** no mesmo destino após atualizar a credencial Postgres (validação de **escrita** complementar à leitura).

---

**Atualizado em 30/03/2026** (confirmação **26/03/2026** + regra n8n/PHP **mesmo BD** + migração dedicada + **§9 validação produção**).
