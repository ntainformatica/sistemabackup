<?php

declare(strict_types=1);

/**
 * @var string $pageTitle
 * @var bool $loginError
 * @var string $loginReturnUrl
 */

$authHideNav = true;
require __DIR__ . '/layout_header.php';
?>
<div class="min-h-full flex flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-md rounded-xl border border-slate-800 bg-slate-900/50 p-8 shadow-xl shadow-black/30">
        <h1 class="text-center text-xl font-semibold text-white">NOC — Acesso</h1>
        <p class="mt-1 text-center text-sm text-slate-500">Introduza as credenciais para continuar.</p>

        <?php if (!empty($loginError)) : ?>
            <div class="mt-4 rounded-lg border border-red-900/60 bg-red-950/40 px-3 py-2 text-center text-sm text-red-200" role="alert">
                Credenciais inválidas. Tente novamente.
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?route=login" class="mt-6 space-y-4">
            <input type="hidden" name="return" value="<?= h($loginReturnUrl) ?>">
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-400">Utilizador</span>
                <input type="text" name="username" required autocomplete="username"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-600 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                    placeholder="nome de utilizador">
            </label>
            <label class="block">
                <span class="mb-1 block text-xs font-medium text-slate-400">Palavra-passe</span>
                <input type="password" name="password" required autocomplete="current-password"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-600 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600"
                    placeholder="••••••••">
            </label>
            <button type="submit" class="w-full rounded-lg bg-emerald-600 py-2.5 text-sm font-medium text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-950">
                Entrar
            </button>
        </form>
    </div>
</div>
<?php require __DIR__ . '/layout_footer.php'; ?>
