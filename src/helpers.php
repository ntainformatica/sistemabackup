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
/**
 * Dias inteiros desde um instante ISO (UTC) até agora; null se inválido.
 */
function days_since_iso(?string $isoUtc): ?int
{
    if ($isoUtc === null || $isoUtc === '') {
        return null;
    }
    try {
        $then = new DateTimeImmutable($isoUtc, new DateTimeZone('UTC'));
    } catch (Throwable) {
        return null;
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $thenDay = $then->setTime(0, 0, 0);
    $nowDay = $now->setTime(0, 0, 0);
    return (int) $thenDay->diff($nowDay)->format('%r%a');
}

/**
 * Decodifica coluna json/jsonb vinda do PDO (string ou já array).
 *
 * @return array<string,mixed>|null
 */
function decode_db_json(mixed $raw): ?array
{
    if ($raw === null || $raw === '') {
        return null;
    }
    if (is_array($raw)) {
        return $raw;
    }
    if (!is_string($raw)) {
        return null;
    }
    try {
        $d = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }
    return is_array($d) ? $d : null;
}

/**
 * Lista de pastas do backup a partir do payload (chave backup_paths no JSON do agente).
 *
 * @return list<string>
 */
function backup_paths_from_payload(?array $payload): array
{
    if ($payload === null) {
        return [];
    }
    if (!isset($payload['backup_paths'])) {
        return [];
    }
    $v = $payload['backup_paths'];
    if (is_string($v) && $v !== '') {
        return [$v];
    }
    if (!is_array($v)) {
        return [];
    }
    $out = [];
    foreach ($v as $item) {
        if (is_string($item) && $item !== '') {
            $out[] = $item;
        }
    }
    return $out;
}

/**
 * Extrai texto a partir de `warning_details` no payload (string ou lista de strings).
 *
 * @param array<string,mixed>|null $payload Resultado de decode_db_json sobre raw_payload_json
 */
function warning_details_from_payload(?array $payload): string
{
    if ($payload === null || !isset($payload['warning_details'])) {
        return '';
    }
    $wd = $payload['warning_details'];
    if (is_string($wd)) {
        return trim($wd);
    }
    if (!is_array($wd)) {
        return '';
    }
    $parts = [];
    foreach ($wd as $item) {
        if (is_string($item)) {
            $t = trim($item);
            if ($t !== '') {
                $parts[] = $t;
            }
        }
    }
    if (count($parts) === 0) {
        return '';
    }

    return implode(' · ', $parts);
}

/**
 * Texto curto para coluna "ficheiro / detalhe" numa execução.
 * Ordem: caminho em warning_signature (tipo:path); depois warning_details no JSON; depois assinatura sozinha; depois contagem com fallback.
 */
function execution_error_hint(array $ex): string
{
    $sig = isset($ex['warning_signature']) ? trim((string) $ex['warning_signature']) : '';
    if ($sig !== '') {
        $pos = strpos($sig, ':');
        if ($pos !== false) {
            $rest = trim(substr($sig, $pos + 1));
            if ($rest !== '') {
                return $rest;
            }
        }
    }
    $payload = decode_db_json($ex['raw_payload_json'] ?? null);
    $wd = warning_details_from_payload($payload);
    if ($wd !== '') {
        return $wd;
    }
    if ($sig !== '') {
        return $sig;
    }
    $fiu = (int) ($ex['warnings_file_in_use'] ?? 0);
    $ad = (int) ($ex['warnings_access_denied'] ?? 0);
    if ($fiu > 0 || $ad > 0) {
        $bits = [];
        if ($fiu > 0) {
            $bits[] = $fiu . ' arquivo(s) em uso (path não enviado no JSON)';
        }
        if ($ad > 0) {
            $bits[] = $ad . ' acesso(s) negado (path não enviado no JSON)';
        }
        return implode(' · ', $bits);
    }
    return '—';
}

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
        'OK', 'SUCCESS' => 'bg-emerald-600/90 text-white',
        'WARNING' => 'bg-amber-500/90 text-slate-900',
        'ERROR' => 'bg-red-600/90 text-white',
        'MISSED' => 'bg-violet-600/90 text-white',
        default => 'bg-slate-600 text-slate-100',
    };
}

/**
 * Badge de severidade — módulo Segurança (Fase 1).
 */
function security_severity_badge_class(?string $severity): string
{
    $s = strtolower(trim((string) $severity));

    return match ($s) {
        'critical' => 'bg-red-600/90 text-white',
        'high' => 'bg-orange-600/90 text-white',
        'medium' => 'bg-amber-500/90 text-slate-900',
        'low' => 'bg-slate-600 text-slate-100',
        'info', 'informational' => 'bg-sky-600/90 text-white',
        default => 'bg-slate-600 text-slate-100',
    };
}
