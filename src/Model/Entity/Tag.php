<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Tag Entity
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\Todo[] $todos
 */
class Tag extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'color' => true,
        'created' => true,
        'modified' => true,
    ];
}
