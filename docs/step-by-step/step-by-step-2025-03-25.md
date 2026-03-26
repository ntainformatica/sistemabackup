# Step-by-step — MVP Dashboard NOC Backups

**Data de referência:** 25/03/2025

## Objetivo

MVP executável em PHP puro + PDO (PostgreSQL) + Tailwind CDN: painel NOC com lista de jobs, filtros, auto-refresh via JSON e página de detalhe do job.

## Arquivos criados

| Arquivo | Função |
|---------|--------|
| `index.php` | Roteador mínimo: `board` (padrão), `job`, `api_board`. |
| `bootstrap.php` | Carrega config, helpers e services. |
| `config/database.php` | Factory `db()` com PDO; lê `DB_*` ou `config/database.local.php`. |
| `config/database.local.example.php` | Modelo de credenciais (copiar para `database.local.php`). |
| `src/helpers.php` | `h()`, tempo relativo, duração, classes de status/SLA (heurístico). |
| `src/Services/JobBoardService.php` | Query principal + opções de filtro (empresa/servidor). |
| `src/Services/JobDetailService.php` | Cabeçalho, execuções, alertas, série 14d. |
| `templates/layout_header.php` / `layout_footer.php` | Shell HTML + Tailwind CDN. |
| `templates/board.php` | Página NOC, filtros, tabela, fetch 30s. |
| `templates/partials/board_table_rows.php` | Linhas da tabela (SSR inicial). |
| `templates/job_detail.php` | Drill-down: execuções, alertas, série, timeline. |
| `templates/error.php` | Erro de conexão/config. |
| `.gitignore` | Ignora `database.local.php` e `.env`. |

## Rotas

- `index.php` ou `index.php?route=board` — NOC.
- `index.php?route=job&catalog_id={id}` — Detalhe HTML.
- **Contrato limpo (recomendado):**
  - `GET /api/jobs/board` — mesmo payload do board (filtros via query string).
  - `GET /api/jobs/{id}` — JSON: `catalog`, `state`, `executions`, `alerts`.
- Implementação: pasta `api/index.php` + `api/.htaccess` (rewrite mínimo).
- **Sem rewrite:** `api/index.php?__path=jobs/board` ou `?__path=jobs/123`.
- Legado interno: `index.php?route=api_board` — ainda funciona (delega para `ApiJobs`).

## Configuração

1. Copiar `config/database.local.example.php` → `config/database.local.php` e ajustar DSN/usuário/senha.
2. Ou definir `DB_DSN`, `DB_USER`, `DB_PASS` no ambiente do Apache/PHP.

## Próximos passos sugeridos

- Índices e `EXPLAIN` nas queries de produção.
- Refinar SLA (hoje heurístico pelo status efetivo).
- Autenticação / RBAC se exposto à internet.
