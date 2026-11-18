<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Todo Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property bool|null $completed
 * @property string $priority
 * @property int $position
 * @property \Cake\I18n\Date|null $due_date
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\Tag[] $tags
 */
class Todo extends Entity
{
    /**
     * user_id はリクエストから一括代入させない (コントローラーでログインユーザーを設定する)
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'title' => true,
        'description' => true,
        'completed' => true,
        'priority' => true,
        'position' => true,
        'due_date' => true,
        'created' => true,
        'modified' => true,
        'tags' => true,
    ];
}
