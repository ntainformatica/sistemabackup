<?php

declare(strict_types=1);

/**
 * Escape HTML.
 */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Tempo relativo em pt-BR (ex.: "há 5 min") a partir de um DateTimeInterface ou string ISO.
 */
function relative_time_pt(?string $isoUtc): string
{
    if ($isoUtc === null || $isoUtc === '') {
        return '—';
    }
    try {
        $then = new DateTimeImmutable($isoUtc, new DateTimeZone('UTC'));
    } catch (Throwable) {
        return '—';
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $sec = $now->getTimestamp() - $then->getTimestamp();
    if ($sec < 0) {
        return 'agora';
    }
    if ($sec < 60) {
        return 'há ' . $sec . ' s';
    }
    $min = intdiv($sec, 60);
    if ($min < 60) {
        return 'há ' . $min . ' min';
    }
    $h = intdiv($min, 60);
    if ($h < 48) {
        return 'há ' . $h . ' h';
    }
    $d = intdiv($h, 24);
    return 'há ' . $d . ' d';
}

/**
 * Formata segundos em texto curto.
 */
function format_duration(?int $seconds): string
{
    if ($seconds === null) {
        return '—';
    }
    if ($seconds < 60) {
        return $seconds . 's';
    }
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    if ($m < 60) {
        return $m . 'm ' . $s . 's';
    }
    $h = intdiv($m, 60);
    $m = $m % 60;
    return $h . 'h ' . $m . 'm';
}

/**
 * SLA visual heurístico para MVP (alinhado ao status efetivo; janela horária refinável depois).
 *
 * @return array{label:string,class:string}
 */
function sla_visual(array $row): array
{
    $eff = strtoupper((string) ($row['last_status_efetivo'] ?? ''));
    if (in_array($eff, ['ERROR', 'MISSED'], true)) {
        return ['label' => 'SLA: fora', 'class' => 'bg-red-900/50 text-red-200 ring-1 ring-red-500/40'];
    }
    if ($eff === 'WARNING') {
        return ['label' => 'SLA: atenção', 'class' => 'bg-amber-900/40 text-amber-100 ring-1 ring-amber-500/30'];
    }
    if ($eff === 'OK') {
        return ['label' => 'SLA: ok', 'class' => 'bg-emerald-900/40 text-emerald-100 ring-1 ring-emerald-500/30'];
    }
    return ['label' => 'SLA: —', 'class' => 'bg-slate-700/50 text-slate-300 ring-1 ring-slate-500/30'];
}

/**
 * Classes Tailwind por status efetivo.
 */
function status_row_class(?string $eff): string
{
    $e = strtoupper((string) $eff);
    return match ($e) {
        'OK' => 'border-l-4 border-l-emerald-500',
        'WARNING' => 'border-l-4 border-l-amber-400',
        'ERROR' => 'border-l-4 border-l-red-500',
        'MISSED' => 'border-l-4 border-l-violet-500',
        default => 'border-l-4 border-l-slate-600',
    };
}

/**
 * Badge de status (cor).
 */
function status_badge_class(?string $eff): string
{
    $e = strtoupper((string) $eff);
    return match ($e) {
        'OK' => 'bg-emerald-600/90 text-white',
        'WARNING' => 'bg-amber-500/90 text-slate-900',
        'ERROR' => 'bg-red-600/90 text-white',
        'MISSED' => 'bg-violet-600/90 text-white',
        default => 'bg-slate-600 text-slate-100',
    };
}
