<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'

const router = useRouter()
const todos = ref([])
const pagination = ref({ page: 1, pageCount: 1, count: 0 })
const loading = ref(true)
const error = ref('')
const availableTags = ref([])

const filters = ref({
  q: '',
  priority: '',
  completed: '',
  tag_id: '',
  sort: 'modified',
  direction: 'desc',
})

let searchTimer = null

async function loadTodos() {
  loading.value = true
  error.value = ''
  try {
    const data = await api.getTodos(filters.value)
    todos.value = data.todos || []
    pagination.value = data.pagination || {}
  } catch (e) {
    error.value = e.message || '一覧の取得に失敗しました。'
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  const tagsData = await api.getTags().catch(() => ({ tags: [] }))
  availableTags.value = tagsData.tags || []
  await loadTodos()
})

// q はデバウンス、他は即時
watch(
  () => filters.value.q,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(loadTodos, 300)
  },
)
watch(
  () => [
    filters.value.priority,
    filters.value.completed,
    filters.value.tag_id,
    filters.value.sort,
    filters.value.direction,
  ],
  loadTodos,
)

function toggleSort(field) {
  if (filters.value.sort === field) {
    filters.value.direction = filters.value.direction === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sort = field
    filters.value.direction = 'desc'
  }
}

function sortIcon(field) {
  if (filters.value.sort !== field) return '↕'
  return filters.value.direction === 'asc' ? '↑' : '↓'
}

const priorityLabel = { high: '高', medium: '中', low: '低' }
const priorityClass = {
  high: 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
  medium: 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
  low: 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
}

function isOverdue(todo) {
  if (!todo.due_date || todo.completed) return false
  return new Date(todo.due_date) < new Date(new Date().toDateString())
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('ja-JP')
}

function formatDueDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ja-JP')
}

function goView(id) {
  router.push({ name: 'TodoView', params: { id } })
}
function goEdit(id) {
  router.push({ name: 'TodoEdit', params: { id } })
}

async function remove(id, title) {
  if (!confirm(`「${title}」を削除してもよろしいですか？`)) return
  try {
    await api.deleteTodo(id)
    todos.value = todos.value.filter((t) => t.id !== id)
  } catch (e) {
    alert(e.message || '削除に失敗しました。')
  }
}
</script>

<template>
  <div class="todos-index">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">Todos</h2>
      <router-link :to="{ name: 'TodoAdd' }" class="btn-primary">New TODO</router-link>
    </div>

    <!-- 検索・フィルターパネル -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-4 mb-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="lg:col-span-1">
          <input
            v-model="filters.q"
            type="text"
            placeholder="タイトル検索..."
            class="form-input"
          />
        </div>
        <div>
          <select v-model="filters.priority" class="form-input">
            <option value="">全優先度</option>
            <option value="high">高</option>
            <option value="medium">中</option>
            <option value="low">低</option>
          </select>
        </div>
        <div>
          <select v-model="filters.completed" class="form-input">
            <option value="">全状態</option>
            <option value="1">完了</option>
            <option value="0">未完了</option>
          </select>
        </div>
        <div>
          <select v-model="filters.tag_id" class="form-input">
            <option value="">全タグ</option>
            <option v-for="tag in availableTags" :key="tag.id" :value="tag.id">
              {{ tag.name }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 mb-4">
      {{ error }}
    </div>
    <div v-if="loading" class="text-slate-500 dark:text-slate-400">読み込み中...</div>
    <div v-else class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50 dark:bg-slate-900">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                <button type="button" class="flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200" @click="toggleSort('priority')">
                  優先度 <span>{{ sortIcon('priority') }}</span>
                </button>
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">タイトル</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">タグ</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">完了</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                <button type="button" class="flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200" @click="toggleSort('due_date')">
                  期限日 <span>{{ sortIcon('due_date') }}</span>
                </button>
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
                <button type="button" class="flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200" @click="toggleSort('modified')">
                  更新日時 <span>{{ sortIcon('modified') }}</span>
                </button>
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="todo in todos"
              :key="todo.id"
              :class="[
                'transition-colors',
                isOverdue(todo)
                  ? 'bg-red-50 dark:bg-red-950 hover:bg-red-100 dark:hover:bg-red-900'
                  : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700',
              ]"
            >
              <!-- 優先度バッジ -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  v-if="todo.priority"
                  :class="[
                    'inline-flex px-2 py-0.5 text-xs font-medium rounded-full',
                    priorityClass[todo.priority] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
                  ]"
                >
                  {{ priorityLabel[todo.priority] ?? todo.priority }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <!-- タイトル -->
              <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-100 max-w-xs truncate">{{ todo.title }}</td>
              <!-- タグ -->
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="tag in todo.tags"
                    :key="tag.id"
                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full text-white"
                    :style="{ backgroundColor: tag.color }"
                  >
                    {{ tag.name }}
                  </span>
                  <span v-if="!todo.tags?.length" class="text-slate-400 text-xs">—</span>
                </div>
              </td>
              <!-- 完了 -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    todo.completed ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
                  ]"
                >
                  {{ todo.completed ? 'Done' : 'Not done' }}
                </span>
              </td>
              <!-- 期限日 -->
              <td
                class="px-4 py-3 whitespace-nowrap text-sm"
                :class="isOverdue(todo) ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-slate-500 dark:text-slate-400'"
              >
                {{ formatDueDate(todo.due_date) }}
                <span v-if="isOverdue(todo)" class="text-xs ml-1">⚠</span>
              </td>
              <!-- 更新日時 -->
              <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ formatDate(todo.modified) }}</td>
              <!-- 操作 -->
              <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" @click="goView(todo.id)">View</button>
                <button type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" @click="goEdit(todo.id)">Edit</button>
                <button
                  type="button"
                  class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                  @click="remove(todo.id, todo.title)"
                >
                  Delete
                </button>
              </td>
            </tr>
            <tr v-if="todos.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">
                Todoが見つかりません。
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <p v-if="!loading && pagination.count !== undefined" class="text-sm text-slate-600 dark:text-slate-400 mt-4">
      {{ todos.length }} 件表示 / 全 {{ pagination.count }} 件
    </p>
  </div>
</template>
