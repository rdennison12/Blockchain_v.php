<?php
/**
 * Created by: Rick Dennison
 * Project   : blockchain
 * Date      : 11/18/2026
 */
?>
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h1 class="card-title h3 mb-3"><?= htmlspecialchars($title ?? 'Home') ?></h1>
        <p class="card-text text-muted"><?= htmlspecialchars($message ?? 'Welcome to your MVC application!') ?></p>
       <div class="card-text">
        <?php foreach ($blocks as $block): ?>
            <p>Block <?= (int) $block['index'] ?>: <?= htmlspecialchars($block['hash'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endforeach; ?>
       </div>
    </div>
</div>