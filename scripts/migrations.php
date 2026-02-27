<?php

declare(strict_types=1);

use Backend\Envs;
use Backend\Infrastructure\Managers\MigrationManager;

const ROOT_DIR = __DIR__ . '/../';
require(ROOT_DIR . 'vendor/autoload.php');

new Envs(ROOT_DIR);

try {
    $manager = new MigrationManager($_ENV['DB_DRIVER'], ROOT_DIR);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $manager->handlePostAction($_POST);
    }

    $state = $manager->getViewState();
} catch (Exception $e) {
    $state = MigrationManager::emptyState();
    $state['logs'][] = "Error: " . $e->getMessage();
}

extract($state);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
</head>

<body class="dark bg-neutral-950 text-neutral-200 font-sans leading-relaxed p-12">
    <div class="max-w-4xl mx-auto">
        <header class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">Migration Manager</h1>
        </header>

        <?php if ($dbInfo): ?>
            <div class="grid grid-cols-[repeat(auto-fit,minmax(140px,1fr))] gap-3 bg-neutral-900 border border-neutral-800 rounded-lg p-4 mb-4">
                <div class="flex flex-col gap-0.5">
                    <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">Driver</span>
                    <span class="font-mono text-sm"><?= htmlspecialchars($dbInfo['driver']) ?></span>
                </div>
                <?php if (isset($dbInfo['database'])): ?>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">Database</span>
                        <span class="font-mono text-sm"><?= htmlspecialchars($dbInfo['database']) ?></span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">Host</span>
                        <span class="font-mono text-sm"><?= htmlspecialchars($dbInfo['host']) ?></span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">User</span>
                        <span class="font-mono text-sm"><?= htmlspecialchars($dbInfo['user']) ?></span>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">Path</span>
                        <span class="font-mono text-sm"><?= htmlspecialchars($dbInfo['path']) ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">Migrations</span>
                    <span class="font-mono text-sm <?= $dbInfo['executed'] > 0 ? 'text-green-500' : '' ?>"><?= $dbInfo['executed'] ?> / <?= $dbInfo['total'] ?></span>
                </div>
                <div class="flex flex-col gap-0.5">
                    <span class="text-[0.625rem] font-semibold uppercase tracking-wider text-neutral-500">Last executed</span>
                    <span class="font-mono text-sm"><?= $dbInfo['last_migration'] ? htmlspecialchars($dbInfo['last_migration']) : '—' ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6 mb-4">
                <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-4">Actions</h2>
                <div class="flex gap-3">
                    <button type="submit" name="run_migrations" class="bg-white text-black text-sm font-medium px-4 py-2 rounded-lg cursor-pointer hover:opacity-85 transition-opacity">Run Migrations</button>
                    <button type="submit" name="reset_migrations" class="bg-red-600 text-white text-sm font-medium px-4 py-2 rounded-lg cursor-pointer hover:opacity-85 transition-opacity">Reset Database</button>
                </div>
            </div>

            <div class="grid grid-cols-[2fr_3fr] gap-4">
                <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-4">Migrations</h2>
                    <?php if (empty($allMigrations)): ?>
                        <p class="text-neutral-500 text-sm">No migration files found.</p>
                    <?php else: ?>
                        <ul class="list-none">
                            <?php foreach ($allMigrations as $migration):
                                $isExecuted = in_array($migration, $executedMigrations);
                                $isOutdated = in_array($migration, $outdatedMigrations);
                            ?>
                                <li class="font-mono text-[0.8125rem] py-2 border-b border-neutral-800 last:border-b-0 <?= $isOutdated ? 'bg-amber-500/5 pl-2 -ml-2 rounded' : '' ?>">
                                    <?php if ($isOutdated): ?>
                                        <span class="mr-2 text-amber-500">&#9888;</span>
                                    <?php elseif ($isExecuted): ?>
                                        <span class="mr-2 text-green-500">&#10003;</span>
                                    <?php else: ?>
                                        <span class="mr-2 text-neutral-500">&#9675;</span>
                                    <?php endif; ?>
                                    <span class="break-all"><?= htmlspecialchars($migration) ?></span>
                                    <?php if ($isOutdated): ?>
                                        <span class="font-sans text-[0.625rem] font-semibold uppercase tracking-wider text-amber-500 bg-amber-500/10 px-1.5 py-0.5 rounded ml-2" title="File modified after execution">modified</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 mb-4">Logs</h2>
                    <div class="font-mono text-[0.8125rem] bg-neutral-950 rounded-lg p-4 whitespace-pre-wrap break-words min-h-[2.5rem]"><?= !empty($logs) ? htmlspecialchars(implode("\n", $logs)) : '' ?></div>
                </div>
            </div>
        </form>
    </div>
</body>

</html>