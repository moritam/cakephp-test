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
 * @property bool|null $completed
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Todo extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * user_id はリクエストから一括代入させない (コントローラーでログインユーザーを設定する)
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'title' => true,
        'completed' => true,
        'created' => true,
        'modified' => true,
    ];
}
