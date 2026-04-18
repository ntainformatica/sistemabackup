<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var array{empresas:list<string>,server_names:list<string>} $opts
 * @var array<string,string|int> $filters
 * @var list<array<string,mixed>> $rows
 * @var string $securityLoginReturnUrl
 */

$script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$base = rtrim(str_replace('\\', '/', dirname($script)), '/');
$apiSecurityUrl = ($base === '' ? '' : $base) . '/api/security/events';
$securityLoginReturnUrl = $securityLoginReturnUrl ?? 'index.php?route=security';
$securityLoginPageUrl = 'index.php?route=login&return=' . rawurlencode($securityLoginReturnUrl);

require __DIR__ . '/layout_header.php';
?>
<div class="min-h-full">
    <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/90 backdrop-blur">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold tracking-tight text-white">Segurança</h1>
                <span id="noc-clock-sec" class="rounded bg-slate-800 px-2 py-0.5 font-mono text-xs text-slate-400"></span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-400">
                <span id="refresh-status-sec">Última atualização: —</span>
                <button type="button" id="btn-refresh-sec" class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-slate-200 hover:bg-slate-800">Atualizar</button>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-[1600px] px-4 py-4">
        <form method="get" action="index.php" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4" id="filters-form-sec">
            <input type="hidden" name="route" value="security">
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Empresa
                <select name="empresa" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
                    <option value="">Todas</option>
                    <?php foreach ($opts['empresas'] as $e) : ?>
                        <option value="<?= h($e) ?>"<?= $filters['empresa'] === $e ? ' selected' : '' ?>><?= h($e) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Servidor
                <select name="server_name" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
                    <option value="">Todos</option>
                    <?php foreach ($opts['server_names'] as $s) : ?>
                        <option value="<?= h($s) ?>"<?= $filters['server_name'] === $s ? ' selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Severidade
                <input name="severity" value="<?= h($filters['severity']) ?>" placeholder="ex.: high" class="w-36 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100 placeholder:text-slate-600">
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                De (data)
                <input type="date" name="date_from" value="<?= h($filters['date_from']) ?>" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Até (data)
                <input type="date" name="date_to" value="<?= h($filters['date_to']) ?>" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Limite (máx. <?= (int) \SecurityBoardService::LIMIT_MAX ?>)
                <input type="number" name="limit" min="1" max="<?= (int) \SecurityBoardService::LIMIT_MAX ?>" value="<?= h((string) ($filters['limit'] ?? \SecurityBoardService::LIMIT_DEFAULT)) ?>" class="w-24 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
            </label>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-emerald-500">Filtrar</button>
            <a href="index.php?route=security" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800">Limpar</a>
        </form>

        <p class="mb-3 text-xs text-slate-500">Fase 1: listagem e detalhe; ingestão via n8n. Sem incidentes nem correlação.</p>

        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/30 shadow-xl shadow-black/20">
            <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-900/95 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-2">Data/hora</th>
                        <th class="whitespace-nowrap px-3 py-2">Empresa</th>
                        <th class="whitespace-nowrap px-3 py-2">Servidor</th>
                        <th class="whitespace-nowrap px-3 py-2">Tipo</th>
                        <th class="whitespace-nowrap px-3 py-2">Severidade</th>
                        <th class="whitespace-nowrap px-3 py-2">IP origem</th>
                        <th class="min-w-[12rem] px-3 py-2">Mensagem</th>
                    </tr>
                </thead>
                <tbody id="sec-body" class="divide-y divide-slate-800">
                    <?php foreach ($rows as $row) : ?>
                        <?php
                        $rid = (int) ($row['id'] ?? 0);
                        $ts = (string) ($row['event_timestamp'] ?? '');
                        ?>
                        <tr class="hover:bg-slate-900/80 border-l-4 border-l-slate-600">
                            <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-300"><?= h($ts) ?></td>
                            <td class="whitespace-nowrap px-3 py-2 text-slate-300"><?= h((string) ($row['empresa'] ?? '')) ?></td>
                            <td class="whitespace-nowrap px-3 py-2 text-slate-300"><?= h((string) ($row['server_name'] ?? '')) ?></td>
                            <td class="whitespace-nowrap px-3 py-2 text-slate-400"><?= h((string) ($row['event_type'] ?? '')) ?></td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold <?= h(security_severity_badge_class((string) ($row['severity'] ?? ''))) ?>"><?= h((string) ($row['severity'] ?? '—')) ?></span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-400"><?= h((string) ($row['source_ip'] ?? '—')) ?></td>
                            <td class="max-w-xl px-3 py-2 text-slate-300">
                                <a class="text-emerald-400 hover:underline" href="index.php?route=security_event&amp;id=<?= $rid ?>"><?= h((string) ($row['message'] ?? '')) ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($rows) === 0) : ?>
                        <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">Nenhum evento. Ingestão via workflow n8n (ver <code class="text-slate-600">docs/workflow/security-ingest-phase1.json</code>).</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const apiUrl = <?= json_encode($apiSecurityUrl, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
    const loginPageUrl = <?= json_encode($securityLoginPageUrl, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
    const form = document.getElementById('filters-form-sec');
    const body = document.getElementById('sec-body');
    const statusEl = document.getElementById('refresh-status-sec');
    const clockEl = document.getElementById('noc-clock-sec');
    const btn = document.getElementById('btn-refresh-sec');

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }

    function badgeCls(sev) {
        const s = String(sev || '').toLowerCase();
        if (s === 'critical') return 'bg-red-600/90 text-white';
        if (s === 'high') return 'bg-orange-600/90 text-white';
        if (s === 'medium') return 'bg-amber-500/90 text-slate-900';
        if (s === 'low') return 'bg-slate-600 text-slate-100';
        if (s === 'info' || s === 'informational') return 'bg-sky-600/90 text-white';
        return 'bg-slate-600 text-slate-100';
    }

    function buildRows(items) {
        return items.map(function (row) {
            const id = row.id;
            const ts = row.event_timestamp ? String(row.event_timestamp) : '';
            return '<tr class="hover:bg-slate-900/80 border-l-4 border-l-slate-600">' +
                '<td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-300">' + esc(ts) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-300">' + esc(row.empresa) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-300">' + esc(row.server_name) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-400">' + esc(row.event_type) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2"><span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold ' + esc(badgeCls(row.severity)) + '">' + esc(row.severity || '—') + '</span></td>' +
                '<td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-400">' + esc(row.source_ip || '—') + '</td>' +
                '<td class="max-w-xl px-3 py-2 text-slate-300"><a class="text-emerald-400 hover:underline" href="index.php?route=security_event&amp;id=' + esc(id) + '">' + esc(row.message) + '</a></td>' +
                '</tr>';
        }).join('');
    }

    async function loadSec() {
        const fd = new FormData(form);
        const p = new URLSearchParams();
        for (const [k, v] of fd.entries()) {
            if (v !== '' && k !== 'route') p.set(k, String(v));
        }
        const q = p.toString();
        const res = await fetch(apiUrl + (q ? '?' + q : ''), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        });
        if (res.status === 401) {
            window.location.href = loginPageUrl;
            return;
        }
        const ct = res.headers.get('content-type');
        if (!ct || ct.indexOf('application/json') === -1) {
            statusEl.textContent = 'Resposta inválida';
            return;
        }
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Falha');
        const items = data.items || [];
        body.innerHTML = items.length ? buildRows(items) : '<tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">Nenhum evento.</td></tr>';
        const t = new Date(data.generated_at || Date.now());
        statusEl.textContent = 'Última atualização: ' + t.toLocaleTimeString('pt-BR');
    }

    setInterval(function () {
        clockEl.textContent = new Date().toLocaleString('pt-BR');
    }, 1000);
    clockEl.textContent = new Date().toLocaleString('pt-BR');

    let timer = setInterval(loadSec, 45000);
    btn.addEventListener('click', function () { loadSec().catch(function () { statusEl.textContent = 'Falha ao atualizar'; }); });
    form.addEventListener('submit', function () {
        clearInterval(timer);
        timer = setInterval(loadSec, 45000);
    });
})();
</script>
<?php require __DIR__ . '/layout_footer.php'; ?>
