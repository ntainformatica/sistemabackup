<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var array<string,mixed> $event
 */

$raw = $event['raw_payload_json'] ?? null;
if (is_array($raw)) {
    $prettyRaw = json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} elseif (is_string($raw)) {
    $dec = decode_db_json($raw);
    $prettyRaw = $dec !== null
        ? json_encode($dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
        : $raw;
} else {
    $prettyRaw = '{}';
}

require __DIR__ . '/layout_header.php';
?>
<div class="min-h-full">
    <header class="border-b border-slate-800 bg-slate-950/90">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div>
                <a class="text-sm text-emerald-400 hover:underline" href="index.php?route=security">← Segurança</a>
                <h1 class="mt-1 text-xl font-semibold text-white">Evento #<?= h((string) (int) ($event['id'] ?? 0)) ?></h1>
                <p class="text-sm text-slate-400"><?= h((string) ($event['title'] ?? '')) ?></p>
            </div>
            <span class="inline-flex rounded px-2 py-1 text-xs font-semibold <?= h(security_severity_badge_class((string) ($event['severity'] ?? ''))) ?>"><?= h((string) ($event['severity'] ?? '—')) ?></span>
        </div>
    </header>

    <div class="mx-auto max-w-[1600px] px-4 py-6">
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Origem / tipo</div>
                <div class="mt-1 text-sm text-slate-200"><?= h((string) ($event['event_source'] ?? '—')) ?> · <?= h((string) ($event['event_type'] ?? '—')) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Empresa / servidor</div>
                <div class="mt-1 text-sm text-slate-200"><?= h((string) ($event['empresa'] ?? '—')) ?> · <?= h((string) ($event['server_name'] ?? '—')) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">event_uid</div>
                <div class="mt-1 font-mono text-xs text-slate-300 break-all"><?= h((string) ($event['event_uid'] ?? '')) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Data/hora do evento (Brasil)</div>
                <div class="mt-1 font-mono text-sm text-slate-200"><?= h(noc_format_timestamptz_br(isset($event['event_timestamp']) ? (string) $event['event_timestamp'] : null)) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Recebido em (Brasil)</div>
                <div class="mt-1 font-mono text-sm text-slate-200"><?= h(noc_format_timestamptz_br(isset($event['received_at']) ? (string) $event['received_at'] : null)) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">IP origem / porta destino</div>
                <div class="mt-1 font-mono text-sm text-slate-200"><?= h((string) ($event['source_ip'] ?? '—')) ?> / <?= isset($event['destination_port']) && $event['destination_port'] !== null && $event['destination_port'] !== '' ? h((string) $event['destination_port']) : '—' ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4 sm:col-span-2">
                <div class="text-xs uppercase text-slate-500">Utilizador (campo)</div>
                <div class="mt-1 font-mono text-sm text-slate-200"><?= h((string) ($event['username'] ?? '—')) ?></div>
            </div>
        </div>

        <section class="mb-6 rounded-xl border border-slate-700/80 bg-slate-900/50 p-4">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Mensagem</h2>
            <p class="text-sm text-slate-200 whitespace-pre-wrap"><?= h((string) ($event['message'] ?? '')) ?></p>
        </section>

        <section class="rounded-xl border border-slate-700/80 bg-slate-900/50 p-4">
            <h2 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Payload bruto (JSON)</h2>
            <pre class="max-h-[480px] overflow-auto rounded-lg border border-slate-800 bg-slate-950 p-4 font-mono text-xs text-slate-300"><?= h($prettyRaw) ?></pre>
        </section>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
