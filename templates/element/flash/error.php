<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="flash-message flash-error rounded-lg p-4 mb-4 cursor-pointer" onclick="this.classList.add('hidden');"><?= $message ?></div>
