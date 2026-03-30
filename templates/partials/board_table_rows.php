<?php

declare(strict_types=1);

/** @var list<array<string,mixed>> $rows */
foreach ($rows as $row) {
    $id = (int) ($row['job_catalog_id'] ?? 0);
    $eff = (string) ($row['last_status_efetivo'] ?? '');
    $sla = sla_visual($row);
    $seen = $row['last_seen_at'] ?? null;
    $seenIso = $seen !== null ? (string) $seen : null;
    $ls = $row['last_success_at'] ?? null;
    $lsIso = $ls !== null ? (string) $ls : null;
    $dss = days_since_iso($lsIso);
    ?>
    <tr class="hover:bg-slate-900/80 <?= h(status_row_class($eff)) ?>">
        <td class="whitespace-nowrap px-3 py-2 text-slate-300"><?= h((string) ($row['empresa'] ?? '')) ?></td>
        <td class="whitespace-nowrap px-3 py-2 text-slate-300"><?= h((string) ($row['servidor'] ?? '')) ?></td>
        <td class="px-3 py-2 font-medium text-slate-100">
            <a class="text-emerald-400 hover:underline" href="index.php?route=job&amp;catalog_id=<?= $id ?>"><?= h((string) ($row['job'] ?? '')) ?></a>
        </td>
        <td class="whitespace-nowrap px-3 py-2">
            <span class="inline-flex rounded px-2 py-0.5 text-xs font-semibold <?= h(status_badge_class($eff)) ?>"><?= h($eff !== '' ? $eff : '—') ?></span>
        </td>
        <td class="whitespace-nowrap px-3 py-2">
            <?php if (!empty($row['is_incident_open'])) : ?>
                <span class="inline-flex items-center rounded-full bg-orange-600 px-2.5 py-0.5 text-xs font-bold text-white ring-2 ring-orange-400/60">SIM</span>
            <?php else : ?>
                <span class="text-slate-500">não</span>
            <?php endif; ?>
        </td>
        <td class="whitespace-nowrap px-3 py-2 text-slate-400"><?= h((string) ($row['incident_severity'] ?? '—')) ?></td>
        <td class="max-w-[14rem] truncate px-3 py-2 font-mono text-xs text-slate-400" title="<?= h((string) ($row['last_snapshot_id'] ?? '')) ?>"><?= h((string) ($row['last_snapshot_id'] ?? '—')) ?></td>
        <td class="whitespace-nowrap px-3 py-2 text-slate-400" data-relative-at="<?= h($seenIso ?? '') ?>"><?= h(relative_time_pt($seenIso)) ?></td>
        <td class="whitespace-nowrap px-3 py-2 text-slate-400"><?= h(relative_time_pt($lsIso)) ?></td>
        <td class="whitespace-nowrap px-3 py-2 text-center font-mono text-xs text-slate-400"><?= $dss !== null ? h((string) $dss) : '—' ?></td>
        <td class="max-w-[8rem] truncate px-3 py-2 font-mono text-xs text-slate-400" title="<?= h((string) ($row['last_run_processed_data'] ?? '')) ?>"><?= h((string) ($row['last_run_processed_data'] ?? '—')) ?></td>
        <td class="max-w-[8rem] truncate px-3 py-2 font-mono text-xs text-sky-300/80" title="<?= h((string) ($row['last_run_added_to_repo'] ?? '')) ?>"><?= h((string) ($row['last_run_added_to_repo'] ?? '—')) ?></td>
        <td class="whitespace-nowrap px-3 py-2 font-mono text-xs text-slate-400"><?= h(format_duration(isset($row['last_duration_seconds']) ? (int) $row['last_duration_seconds'] : null)) ?></td>
        <td class="whitespace-nowrap px-3 py-2 text-center text-slate-300"><?= h((string) (int) ($row['consecutive_warning_count'] ?? 0)) ?></td>
        <td class="whitespace-nowrap px-3 py-2 text-center text-slate-300"><?= h((string) (int) ($row['consecutive_error_count'] ?? 0)) ?></td>
        <td class="max-w-[12rem] truncate px-3 py-2 text-xs text-slate-500" title="<?= h((string) ($row['last_warning_signature'] ?? '')) ?>"><?= h((string) ($row['last_warning_signature'] ?? '—')) ?></td>
        <td class="whitespace-nowrap px-3 py-2">
            <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium <?= h($sla['class']) ?>"><?= h($sla['label']) ?></span>
        </td>
    </tr>
    <?php
}
