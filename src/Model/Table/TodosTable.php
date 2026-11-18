<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Todos Model
 *
 * @method \App\Model\Entity\Todo newEmptyEntity()
 * @method \App\Model\Entity\Todo newEntity(array $data, array $options = [])
 * @method array<\App\Model\Entity\Todo> newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Todo get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Todo findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \App\Model\Entity\Todo patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\App\Model\Entity\Todo> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Todo|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\Todo saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\App\Model\Entity\Todo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Todo>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Todo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Todo> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Todo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Todo>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\App\Model\Entity\Todo>|\Cake\Datasource\ResultSetInterface<\App\Model\Entity\Todo> deleteManyOrFail(iterable $entities, array $options = [])
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\TagsTable&\Cake\ORM\Association\BelongsToMany $Tags
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TodosTable extends Table
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

        $this->setTable('todos');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsToMany('Tags', [
            'foreignKey' => 'todo_id',
            'targetForeignKey' => 'tag_id',
            'joinTable' => 'tags_todos',
        ]);
    }

    /**
     * 指定ユーザーが所有する Todo だけに絞り込む
     *
     * 使い方: $this->Todos->find('ownedBy', userId: $userId)
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param int $userId オーナーのユーザー ID
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findOwnedBy(SelectQuery $query, int $userId): SelectQuery
    {
        return $query->where([$this->aliasField('user_id') => $userId]);
    }

    /**
     * 検索・フィルター・ソート用カスタムファインダー
     *
     * 使い方: $this->Todos->find('filtered', q: '...', priority: 'high', ...)
     *
     * @param \Cake\ORM\Query\SelectQuery $query The query.
     * @param string $q キーワード検索
     * @param string $priority 優先度 (high|medium|low|'')
     * @param string $completed 完了状態 ('0'|'1'|'')
     * @param int $tagId タグ ID でフィルター (0 = 未指定)
     * @param bool $overdue 期限切れのみ
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findFiltered(
        SelectQuery $query,
        string $q = '',
        string $priority = '',
        string $completed = '',
        int $tagId = 0,
        bool $overdue = false,
    ): SelectQuery {
        if ($q !== '') {
            $query->where(['Todos.title LIKE' => '%' . $q . '%']);
        }
        if ($priority !== '') {
            $query->where(['Todos.priority' => $priority]);
        }
        if ($completed !== '') {
            $query->where(['Todos.completed' => (bool)(int)$completed]);
        }
        if ($tagId > 0) {
            $query->matching('Tags', fn(SelectQuery $q2) => $q2->where(['Tags.id' => $tagId]));
        }
        if ($overdue) {
            $query->where(['Todos.due_date <' => Date::today()->toDateString(), 'Todos.completed' => false]);
        }

        return $query;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
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
            ->scalar('title')
            ->maxLength('title', 255)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->boolean('completed')
            ->allowEmptyString('completed');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->inList('priority', ['high', 'medium', 'low'])
            ->allowEmptyString('priority');

        $validator
            ->date('due_date')
            ->allowEmptyDate('due_date');

        return $validator;
    }
}
