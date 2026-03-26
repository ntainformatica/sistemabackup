<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Erro';
require __DIR__ . '/layout_header.php';
?>
<div class="min-h-full flex items-center justify-center p-6">
    <div class="max-w-lg rounded-xl border border-red-900/50 bg-red-950/30 p-6 text-center">
        <h1 class="text-lg font-semibold text-red-200">Erro ao carregar</h1>
        <p class="mt-2 text-sm text-red-100/80">
            Verifique <code class="rounded bg-slate-900 px-1 py-0.5 text-xs">config/database.local.php</code>
            ou variáveis <code class="rounded bg-slate-900 px-1 py-0.5 text-xs">DB_DSN</code>.
        </p>
        <?php if (!empty($errorMessage ?? '')) : ?>
            <pre class="mt-4 max-h-40 overflow-auto rounded-lg bg-slate-900 p-3 text-left text-xs text-slate-400"><?= h((string) $errorMessage) ?></pre>
        <?php endif; ?>
        <a class="mt-6 inline-block text-sm text-emerald-400 hover:underline" href="index.php">Voltar ao NOC</a>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
