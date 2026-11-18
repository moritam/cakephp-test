<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Tags Model
 *
 * @method \App\Model\Entity\Tag newEmptyEntity()
 * @method \App\Model\Entity\Tag newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Tag get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Tag patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Tag|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TagsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('tags');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Todos', [
            'foreignKey' => 'tag_id',
            'targetForeignKey' => 'todo_id',
            'joinTable' => 'tags_todos',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => 'このタグ名はすでに使用されています。',
            ]);

        $validator
            ->scalar('color')
            ->maxLength('color', 7)
            ->allowEmptyString('color')
            ->add('color', 'format', [
                'rule' => ['custom', '/^#[0-9a-fA-F]{6}$/'],
                'message' => '色は #RRGGBB 形式で入力してください。',
            ]);

        return $validator;
    }
}
