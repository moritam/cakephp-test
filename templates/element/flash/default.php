<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
$class = 'flash-message rounded-lg p-4 mb-4 bg-slate-100 text-slate-800 border border-slate-200';
if (!empty($params['class'])) {
    $class .= ' ' . $params['class'];
}
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="<?= h($class) ?>" onclick="this.classList.add('hidden');"><?= $message ?></div>
