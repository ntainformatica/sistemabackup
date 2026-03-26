<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var array<string,mixed> $header
 * @var list<array<string,mixed>> $executions
 * @var list<array<string,mixed>> $alerts
 * @var list<array{bucket:string,status_reportado:string,cnt:int}> $series
 */

require __DIR__ . '/layout_header.php';

$id = (int) ($header['job_catalog_id'] ?? 0);
$eff = (string) ($header['last_status_efetivo'] ?? '');
$sla = sla_visual($header);

$seriesMax = 0;
foreach ($series as $s) {
    $seriesMax = max($seriesMax, (int) ($s['cnt'] ?? 0));
}

?>
<div class="min-h-full">
    <header class="border-b border-slate-800 bg-slate-950/90">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-between gap-3 px-4 py-3">
            <div>
                <a class="text-sm text-emerald-400 hover:underline" href="index.php">← NOC</a>
                <h1 class="mt-1 text-xl font-semibold text-white"><?= h((string) ($header['job'] ?? '')) ?></h1>
                <p class="text-sm text-slate-400"><?= h((string) ($header['empresa'] ?? '')) ?> · <?= h((string) ($header['servidor'] ?? '')) ?> · <?= h((string) ($header['repositorio'] ?? '')) ?></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex rounded px-2 py-1 text-xs font-semibold <?= h(status_badge_class($eff)) ?>"><?= h($eff !== '' ? $eff : '—') ?></span>
                <?php if (!empty($header['is_incident_open'])) : ?>
                    <span class="inline-flex rounded-full bg-orange-600 px-3 py-1 text-xs font-bold text-white ring-2 ring-orange-400/60">Incidente aberto</span>
                <?php endif; ?>
                <span class="inline-flex rounded px-2 py-1 text-xs font-medium <?= h($sla['class']) ?>"><?= h($sla['label']) ?></span>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-[1600px] px-4 py-6">
        <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Última execução</div>
                <div class="mt-1 font-mono text-sm text-slate-200"><?= h(relative_time_pt($header['last_seen_at'] !== null ? (string) $header['last_seen_at'] : null)) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Duração</div>
                <div class="mt-1 font-mono text-sm text-slate-200"><?= h(format_duration(isset($header['last_duration_seconds']) ? (int) $header['last_duration_seconds'] : null)) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Snapshot</div>
                <div class="mt-1 truncate font-mono text-xs text-slate-300" title="<?= h((string) ($header['last_snapshot_id'] ?? '')) ?>"><?= h((string) ($header['last_snapshot_id'] ?? '—')) ?></div>
            </div>
            <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-4">
                <div class="text-xs uppercase text-slate-500">Timezone / janela</div>
                <div class="mt-1 text-sm text-slate-200"><?= h((string) ($header['timezone'] ?? '—')) ?> · <?= h((string) ($header['schedule_type'] ?? '—')) ?></div>
                <div class="mt-0.5 font-mono text-xs text-slate-400"><?= h((string) ($header['expected_start_time'] ?? '—')) ?> (grace <?= h((string) ($header['grace_minutes'] ?? '—')) ?> min)</div>
            </div>
        </div>

        <nav class="mb-4 flex flex-wrap gap-2 text-sm">
            <a class="rounded-lg bg-slate-800 px-3 py-1.5 text-slate-100" href="#execucoes">Execuções</a>
            <a class="rounded-lg bg-slate-800 px-3 py-1.5 text-slate-100" href="#alertas">Alertas</a>
            <a class="rounded-lg bg-slate-800 px-3 py-1.5 text-slate-100" href="#serie">Série (14d)</a>
            <a class="rounded-lg bg-slate-800 px-3 py-1.5 text-slate-100" href="#timeline">Timeline alertas</a>
        </nav>

        <section id="execucoes" class="mb-10">
            <h2 class="mb-3 text-lg font-semibold text-white">Histórico de execuções</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/30">
                <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-900/90 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Recebido em</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2">Snapshot</th>
                            <th class="px-3 py-2">Exit backup</th>
                            <th class="px-3 py-2">Exit forget</th>
                            <th class="px-3 py-2">Repo lock</th>
                            <th class="px-3 py-2">Warn src</th>
                            <th class="px-3 py-2">Acesso negado</th>
                            <th class="px-3 py-2">Arquivo em uso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($executions as $ex) : ?>
                            <tr class="hover:bg-slate-900/60">
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-300"><?= h((string) ($ex['received_at'] ?? '')) ?></td>
                                <td class="px-3 py-2"><span class="rounded px-2 py-0.5 text-xs <?= h(status_badge_class((string) ($ex['status_reportado'] ?? ''))) ?>"><?= h((string) ($ex['status_reportado'] ?? '—')) ?></span></td>
                                <td class="max-w-[12rem] truncate px-3 py-2 font-mono text-xs" title="<?= h((string) ($ex['snapshot_id'] ?? '')) ?>"><?= h((string) ($ex['snapshot_id'] ?? '—')) ?></td>
                                <td class="px-3 py-2 font-mono text-xs"><?= h((string) ($ex['exit_code_backup'] ?? '—')) ?></td>
                                <td class="px-3 py-2 font-mono text-xs"><?= h((string) ($ex['exit_code_forget'] ?? '—')) ?></td>
                                <td class="px-3 py-2"><?php
                                    $rl = $ex['repository_locked'] ?? null;
                                    $locked = $rl === true || $rl === 't' || $rl === 1 || $rl === '1';
                                    echo $locked ? 'sim' : 'não';
                                ?></td>
                                <td class="px-3 py-2"><?= h((string) ($ex['warning_source_read'] ?? '—')) ?></td>
                                <td class="px-3 py-2"><?= h((string) ($ex['warnings_access_denied'] ?? '—')) ?></td>
                                <td class="px-3 py-2"><?= h((string) ($ex['warnings_file_in_use'] ?? '—')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($executions) === 0) : ?>
                            <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500">Nenhum evento.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="alertas" class="mb-10">
            <h2 class="mb-3 text-lg font-semibold text-white">Histórico de alertas</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/30">
                <table class="min-w-full divide-y divide-slate-800 text-left text-sm">
                    <thead class="bg-slate-900/90 text-xs uppercase text-slate-400">
                        <tr>
                            <th class="px-3 py-2">Enviado</th>
                            <th class="px-3 py-2">Tipo</th>
                            <th class="px-3 py-2">Severidade</th>
                            <th class="px-3 py-2">Título</th>
                            <th class="px-3 py-2">Ack / Resolvido</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach ($alerts as $a) : ?>
                            <tr class="hover:bg-slate-900/60">
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-300"><?= h((string) ($a['sent_at'] ?? '')) ?></td>
                                <td class="px-3 py-2"><?= h((string) ($a['alert_type'] ?? '—')) ?></td>
                                <td class="px-3 py-2"><?= h((string) ($a['severity'] ?? '—')) ?></td>
                                <td class="max-w-xl px-3 py-2">
                                    <div class="font-medium text-slate-100"><?= h((string) ($a['title'] ?? '')) ?></div>
                                    <div class="text-xs text-slate-500"><?= h((string) ($a['message'] ?? '')) ?></div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-400">
                                    <?= $a['acknowledged_at'] ? h((string) $a['acknowledged_at']) : '—' ?> /
                                    <?= $a['resolved_at'] ? h((string) $a['resolved_at']) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (count($alerts) === 0) : ?>
                            <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">Nenhum alerta.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="serie" class="mb-10">
            <h2 class="mb-3 text-lg font-semibold text-white">Execuções por dia e status (14 dias)</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900/30 p-4">
                <?php if (count($series) === 0) : ?>
                    <p class="text-slate-500">Sem dados no período.</p>
                <?php else : ?>
                    <div class="space-y-2">
                        <?php foreach ($series as $s) : ?>
                            <?php
                            $cnt = (int) ($s['cnt'] ?? 0);
                            $w = $seriesMax > 0 ? (int) round(($cnt / $seriesMax) * 100) : 0;
                            ?>
                            <div class="flex items-center gap-3 text-sm">
                                <div class="w-28 shrink-0 font-mono text-xs text-slate-400"><?= h($s['bucket']) ?></div>
                                <div class="w-32 shrink-0 text-slate-300"><?= h($s['status_reportado'] ?: '—') ?></div>
                                <div class="min-w-0 flex-1">
                                    <div class="h-2 overflow-hidden rounded bg-slate-800">
                                        <div class="h-full rounded bg-emerald-600/80" style="width: <?= $w ?>%"></div>
                                    </div>
                                </div>
                                <div class="w-10 shrink-0 text-right font-mono text-xs text-slate-400"><?= h((string) $cnt) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section id="timeline">
            <h2 class="mb-3 text-lg font-semibold text-white">Timeline de alertas</h2>
            <ol class="relative border-l border-slate-700 pl-6">
                <?php foreach ($alerts as $a) : ?>
                    <li class="mb-6 ml-1">
                        <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-emerald-500 ring-4 ring-slate-950"></div>
                        <time class="mb-1 font-mono text-xs text-slate-500"><?= h((string) ($a['sent_at'] ?? '')) ?></time>
                        <p class="text-sm font-medium text-slate-100"><?= h((string) ($a['title'] ?? '')) ?></p>
                        <p class="text-xs text-slate-500"><?= h((string) ($a['severity'] ?? '')) ?> · <?= h((string) ($a['alert_type'] ?? '')) ?></p>
                    </li>
                <?php endforeach; ?>
                <?php if (count($alerts) === 0) : ?>
                    <li class="text-slate-500">Sem alertas.</li>
                <?php endif; ?>
            </ol>
        </section>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
