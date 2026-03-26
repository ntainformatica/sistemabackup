<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var JobBoardService $boardSvc — não usado diretamente após fetch
 * @var array{empresas:list<string>,servidores:list<string>} $opts
 * @var array<string,string> $filters
 * @var list<array<string,mixed>> $rows
 */

$script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$base = rtrim(str_replace('\\', '/', dirname($script)), '/');
$apiBoardUrl = ($base === '' ? '' : $base) . '/api/jobs/board';

require __DIR__ . '/layout_header.php';
?>
<div class="min-h-full">
    <header class="sticky top-0 z-20 border-b border-slate-800 bg-slate-950/90 backdrop-blur">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold tracking-tight text-white">NOC — Backups</h1>
                <span id="noc-clock" class="rounded bg-slate-800 px-2 py-0.5 font-mono text-xs text-slate-400"></span>
            </div>
            <div class="flex items-center gap-2 text-sm text-slate-400">
                <span id="refresh-status">Última atualização: —</span>
                <button type="button" id="btn-refresh" class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-1.5 text-slate-200 hover:bg-slate-800">Atualizar</button>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-[1600px] px-4 py-4">
        <form method="get" action="index.php" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4" id="filters-form">
            <input type="hidden" name="route" value="board">
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
                <select name="servidor" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
                    <option value="">Todos</option>
                    <?php foreach ($opts['servidores'] as $s) : ?>
                        <option value="<?= h($s) ?>"<?= $filters['servidor'] === $s ? ' selected' : '' ?>><?= h($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Status efetivo
                <select name="status" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
                    <option value="">Todos</option>
                    <?php foreach (['OK', 'WARNING', 'ERROR', 'MISSED'] as $st) : ?>
                        <option value="<?= h($st) ?>"<?= $filters['status'] === $st ? ' selected' : '' ?>><?= h($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Incidente
                <select name="incident" class="rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100">
                    <option value="">Todos</option>
                    <option value="1"<?= $filters['incident'] === '1' ? ' selected' : '' ?>>Aberto</option>
                    <option value="0"<?= $filters['incident'] === '0' ? ' selected' : '' ?>>Fechado</option>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs text-slate-400">
                Severidade
                <input name="severity" value="<?= h($filters['severity']) ?>" placeholder="ex.: critical" class="w-40 rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-sm text-slate-100 placeholder:text-slate-600">
            </label>
            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-emerald-500">Filtrar</button>
            <a href="index.php?route=board" class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-800">Limpar</a>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/30 shadow-xl shadow-black/20">
            <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-900/95 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-2">Empresa</th>
                        <th class="whitespace-nowrap px-3 py-2">Servidor</th>
                        <th class="whitespace-nowrap px-3 py-2">Job</th>
                        <th class="whitespace-nowrap px-3 py-2">Status</th>
                        <th class="whitespace-nowrap px-3 py-2">Incidente</th>
                        <th class="whitespace-nowrap px-3 py-2">Severidade</th>
                        <th class="whitespace-nowrap px-3 py-2">Snapshot</th>
                        <th class="whitespace-nowrap px-3 py-2">Última exec.</th>
                        <th class="whitespace-nowrap px-3 py-2">Duração</th>
                        <th class="whitespace-nowrap px-3 py-2">Warn seq.</th>
                        <th class="whitespace-nowrap px-3 py-2">Err seq.</th>
                        <th class="whitespace-nowrap px-3 py-2">Assinatura warn.</th>
                        <th class="whitespace-nowrap px-3 py-2">SLA</th>
                    </tr>
                </thead>
                <tbody id="board-body" class="divide-y divide-slate-800">
                    <?php
                    $rows = $rows ?? [];
                    require __DIR__ . '/partials/board_table_rows.php';
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const apiBoardUrl = <?= json_encode($apiBoardUrl, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) ?>;
    const form = document.getElementById('filters-form');
    const body = document.getElementById('board-body');
    const statusEl = document.getElementById('refresh-status');
    const clockEl = document.getElementById('noc-clock');
    const btn = document.getElementById('btn-refresh');

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
        });
    }

    function relTime(iso) {
        if (!iso) return '—';
        const t = Date.parse(iso);
        if (Number.isNaN(t)) return '—';
        let sec = Math.floor((Date.now() - t) / 1000);
        if (sec < 0) return 'agora';
        if (sec < 60) return 'há ' + sec + ' s';
        const min = Math.floor(sec / 60);
        if (min < 60) return 'há ' + min + ' min';
        const h = Math.floor(min / 60);
        if (h < 48) return 'há ' + h + ' h';
        const d = Math.floor(h / 24);
        return 'há ' + d + ' d';
    }

    function fmtDur(sec) {
        if (sec === null || sec === undefined || sec === '') return '—';
        const n = parseInt(sec, 10);
        if (Number.isNaN(n)) return '—';
        if (n < 60) return n + 's';
        const m = Math.floor(n / 60);
        const s = n % 60;
        if (m < 60) return m + 'm ' + s + 's';
        const h = Math.floor(m / 60);
        const mm = m % 60;
        return h + 'h ' + mm + 'm';
    }

    function rowClass(eff) {
        const e = String(eff || '').toUpperCase();
        if (e === 'OK') return 'border-l-4 border-l-emerald-500';
        if (e === 'WARNING') return 'border-l-4 border-l-amber-400';
        if (e === 'ERROR') return 'border-l-4 border-l-red-500';
        if (e === 'MISSED') return 'border-l-4 border-l-violet-500';
        return 'border-l-4 border-l-slate-600';
    }

    function badgeClass(eff) {
        const e = String(eff || '').toUpperCase();
        if (e === 'OK') return 'bg-emerald-600/90 text-white';
        if (e === 'WARNING') return 'bg-amber-500/90 text-slate-900';
        if (e === 'ERROR') return 'bg-red-600/90 text-white';
        if (e === 'MISSED') return 'bg-violet-600/90 text-white';
        return 'bg-slate-600 text-slate-100';
    }

    function slaFromEff(eff) {
        const e = String(eff || '').toUpperCase();
        if (e === 'ERROR' || e === 'MISSED') return { label: 'SLA: fora', cls: 'bg-red-900/50 text-red-200 ring-1 ring-red-500/40' };
        if (e === 'WARNING') return { label: 'SLA: atenção', cls: 'bg-amber-900/40 text-amber-100 ring-1 ring-amber-500/30' };
        if (e === 'OK') return { label: 'SLA: ok', cls: 'bg-emerald-900/40 text-emerald-100 ring-1 ring-emerald-500/30' };
        return { label: 'SLA: —', cls: 'bg-slate-700/50 text-slate-300 ring-1 ring-slate-500/30' };
    }

    function buildRows(items) {
        return items.map(function (row) {
            const id = row.job_catalog_id;
            const eff = row.last_status_efetivo || '';
            const sla = slaFromEff(eff);
            const inc = row.is_incident_open;
            const incHtml = inc
                ? '<span class="inline-flex items-center rounded-full bg-orange-600 px-2.5 py-0.5 text-xs font-bold text-white ring-2 ring-orange-400/60">SIM</span>'
                : '<span class="text-slate-500">não</span>';
            const seen = row.last_seen_at ? String(row.last_seen_at) : '';
            return '<tr class="hover:bg-slate-900/80 ' + esc(rowClass(eff)) + '">' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-300">' + esc(row.empresa) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-300">' + esc(row.servidor) + '</td>' +
                '<td class="px-3 py-2 font-medium text-slate-100">' +
                    '<a class="text-emerald-400 hover:underline" href="index.php?route=job&amp;catalog_id=' + esc(id) + '">' + esc(row.job) + '</a></td>' +
                '<td class="whitespace-nowrap px-3 py-2"><span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold ' + esc(badgeClass(eff)) + '">' + esc(eff || '—') + '</span></td>' +
                '<td class="whitespace-nowrap px-3 py-2">' + incHtml + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-400">' + esc(row.incident_severity || '—') + '</td>' +
                '<td class="max-w-[14rem] truncate px-3 py-2 font-mono text-xs text-slate-400" title="' + esc(row.last_snapshot_id) + '">' + esc(row.last_snapshot_id || '—') + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-slate-400">' + esc(relTime(seen)) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-400">' + esc(fmtDur(row.last_duration_seconds)) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-center text-slate-300">' + esc(String(parseInt(row.consecutive_warning_count || 0, 10))) + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2 text-center text-slate-300">' + esc(String(parseInt(row.consecutive_error_count || 0, 10))) + '</td>' +
                '<td class="max-w-[12rem] truncate px-3 py-2 text-xs text-slate-500" title="' + esc(row.last_warning_signature) + '">' + esc(row.last_warning_signature || '—') + '</td>' +
                '<td class="whitespace-nowrap px-3 py-2"><span class="inline-flex rounded px-2 py-0.5 text-xs font-medium ' + esc(sla.cls) + '">' + esc(sla.label) + '</span></td>' +
                '</tr>';
        }).join('');
    }

    async function loadBoard() {
        const fd = new FormData(form);
        const p = new URLSearchParams();
        for (const [k, v] of fd.entries()) {
            if (v !== '' && k !== 'route') {
                p.set(k, String(v));
            }
        }
        const q = p.toString();
        const res = await fetch(apiBoardUrl + (q ? '?' + q : ''), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Falha');
        body.innerHTML = buildRows(data.items || []);
        const t = new Date(data.generated_at || Date.now());
        statusEl.textContent = 'Última atualização: ' + t.toLocaleTimeString('pt-BR');
    }

    setInterval(function () {
        clockEl.textContent = new Date().toLocaleString('pt-BR');
    }, 1000);
    clockEl.textContent = new Date().toLocaleString('pt-BR');

    let timer = setInterval(loadBoard, 30000);
    btn.addEventListener('click', function () { loadBoard().catch(function () { statusEl.textContent = 'Falha ao atualizar'; }); });

    form.addEventListener('submit', function () {
        clearInterval(timer);
        timer = setInterval(loadBoard, 30000);
    });
})();
</script>
<?php require __DIR__ . '/layout_footer.php'; ?>
