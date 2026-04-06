<?php

declare(strict_types=1);

/** @var string $pageTitle */
/** @var bool $authHideNav Ocultar barra de sessão (ex.: página de login) */
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] }
                }
            },
            darkMode: 'class'
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased">
<?php if (empty($authHideNav) && class_exists(Auth::class, false) && Auth::isAuthenticated()) : ?>
    <div class="border-b border-slate-800 bg-slate-900/80">
        <div class="mx-auto flex max-w-[1600px] flex-wrap items-center justify-end gap-3 px-4 py-2 text-sm text-slate-400">
            <span class="truncate"><?= h(Auth::currentUsername() ?? '') ?></span>
            <a class="rounded-lg border border-slate-600 px-2 py-1 text-slate-200 hover:bg-slate-800" href="index.php?route=logout">Sair</a>
        </div>
    </div>
<?php endif; ?>
