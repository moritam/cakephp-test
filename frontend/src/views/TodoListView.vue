<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { VueDraggable } from 'vue-draggable-plus'
import { api } from '../api'

const router = useRouter()
const todos = ref([])
const pagination = ref({ page: 1, pageCount: 1, count: 0 })
const loading = ref(true)
const error = ref('')
const availableTags = ref([])
const currentPage = ref(1)
const PER_PAGE = 20
const selectedIds = ref(new Set())

const filters = ref({
  q: '',
  priority: '',
  completed: '',
  tag_id: '',
  sort: 'modified',
  direction: 'desc',
})

const isManualSort = computed(() => filters.value.sort === 'position')
const hasActiveFilters = computed(() =>
  filters.value.q || filters.value.priority || filters.value.completed || filters.value.tag_id,
)
const isAllSelected = computed(
  () => todos.value.length > 0 && todos.value.every((t) => selectedIds.value.has(t.id)),
)

function toggleSelectAll() {
  if (isAllSelected.value) {
    selectedIds.value = new Set()
  } else {
    selectedIds.value = new Set(todos.value.map((t) => t.id))
  }
}

function toggleSelect(id) {
  const next = new Set(selectedIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  selectedIds.value = next
}

async function bulkComplete() {
  const ids = [...selectedIds.value]
  if (!ids.length) return
  try {
    await api.bulkTodos('complete', ids)
    todos.value.forEach((t) => { if (selectedIds.value.has(t.id)) t.completed = true })
    selectedIds.value = new Set()
  } catch (e) {
    error.value = e.message || '一括完了に失敗しました。'
  }
}

async function bulkDelete() {
  const ids = [...selectedIds.value]
  if (!ids.length) return
  if (!confirm(`${ids.length} 件の Todo を削除してもよろしいですか？`)) return
  try {
    await api.bulkTodos('delete', ids)
    todos.value = todos.value.filter((t) => !selectedIds.value.has(t.id))
    selectedIds.value = new Set()
  } catch (e) {
    error.value = e.message || '一括削除に失敗しました。'
  }
}

let searchTimer = null

async function loadTodos() {
  loading.value = true
  error.value = ''
  selectedIds.value = new Set()
  try {
    const params = { ...filters.value, page: currentPage.value, per_page: PER_PAGE }
    const data = await api.getTodos(params)
    todos.value = data.todos || []
    pagination.value = data.pagination || { page: 1, pageCount: 1, count: 0 }
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

// q はデバウンス、他は即時 (ページをリセット)
watch(
  () => filters.value.q,
  () => {
    clearTimeout(searchTimer)
    currentPage.value = 1
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
  () => {
    currentPage.value = 1
    loadTodos()
  },
)

function toggleSort(field) {
  if (filters.value.sort === field) {
    filters.value.direction = filters.value.direction === 'asc' ? 'desc' : 'asc'
  } else {
    filters.value.sort = field
    filters.value.direction = field === 'position' ? 'asc' : 'desc'
  }
}

function sortIcon(field) {
  if (filters.value.sort !== field) return '↕'
  return filters.value.direction === 'asc' ? '↑' : '↓'
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--
    loadTodos()
  }
}
function nextPage() {
  if (currentPage.value < pagination.value.pageCount) {
    currentPage.value++
    loadTodos()
  }
}

async function onDragEnd() {
  if (hasActiveFilters.value) return
  try {
    await api.reorderTodos(todos.value.map((t) => t.id))
  } catch (e) {
    error.value = e.message || '並び替えの保存に失敗しました。'
  }
}

async function toggleComplete(todo) {
  const original = todo.completed
  todo.completed = !original
  try {
    await api.updateTodo(todo.id, { completed: todo.completed })
  } catch (e) {
    todo.completed = original
    error.value = e.message || '更新に失敗しました。'
  }
}

function exportCsv() {
  const params = { ...filters.value }
  delete params.sort
  delete params.direction
  const qs = new URLSearchParams()
  Object.entries(params).forEach(([k, v]) => {
    if (v !== '' && v !== null && v !== undefined && v !== false && v !== 0) {
      qs.append(k, String(v))
    }
  })
  const url = `/api/todos/export${qs.toString() ? '?' + qs.toString() : ''}`
  const a = document.createElement('a')
  a.href = url
  a.download = ''
  a.click()
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
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="btn-secondary text-sm"
          title="現在のフィルター条件でCSVをダウンロード"
          @click="exportCsv"
        >
          ↓ CSV
        </button>
        <router-link :to="{ name: 'TodoAdd' }" class="btn-primary">New TODO</router-link>
      </div>
    </div>

    <!-- 検索・フィルターパネル -->
    <div class="page-card p-4 mb-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
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
        <div>
          <select v-model="filters.sort" class="form-input">
            <option value="modified">更新日順</option>
            <option value="created">作成日順</option>
            <option value="due_date">期限日順</option>
            <option value="priority">優先度順</option>
            <option value="position">手動順</option>
          </select>
        </div>
      </div>
    </div>

    <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 mb-4">
      {{ error }}
    </div>

    <!-- 手動順でフィルター中の注意 -->
    <div v-if="isManualSort && hasActiveFilters" class="rounded-lg bg-yellow-50 dark:bg-yellow-950 text-yellow-800 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-800 p-3 mb-4 text-sm">
      フィルターが有効なときはドラッグ＆ドロップによる並び替えは無効です。
    </div>

    <div v-if="loading" class="text-slate-500 dark:text-slate-400">読み込み中...</div>
    <div v-else class="page-card overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
          <thead class="bg-slate-50/80 dark:bg-slate-900/80">
            <tr>
              <th class="px-3 py-3 w-8">
                <input
                  type="checkbox"
                  :checked="isAllSelected"
                  :indeterminate="selectedIds.size > 0 && !isAllSelected"
                  class="rounded border-slate-300 dark:border-slate-600 text-blue-600"
                  title="全選択 / 全解除"
                  @change="toggleSelectAll"
                />
              </th>
              <th v-if="isManualSort && !hasActiveFilters" class="px-2 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase w-8"></th>
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
          <!-- 手動順かつフィルターなし → ドラッグ可能 -->
          <VueDraggable
            v-if="isManualSort && !hasActiveFilters"
            v-model="todos"
            tag="tbody"
            handle=".drag-handle"
            class="divide-y divide-slate-200 dark:divide-slate-700"
            @end="onDragEnd"
          >
            <tr
              v-for="todo in todos"
              :key="todo.id"
              :class="[
                'transition-colors',
                isOverdue(todo)
                  ? 'bg-red-50 dark:bg-red-950 hover:bg-red-100 dark:hover:bg-red-900'
                  : selectedIds.has(todo.id)
                    ? 'bg-blue-50 dark:bg-blue-950'
                    : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700',
              ]"
            >
              <td class="px-3 py-3 w-8">
                <input
                  type="checkbox"
                  :checked="selectedIds.has(todo.id)"
                  class="rounded border-slate-300 dark:border-slate-600 text-blue-600"
                  @change="toggleSelect(todo.id)"
                />
              </td>
              <td class="px-2 py-3 w-8">
                <span class="drag-handle cursor-grab text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 select-none text-lg">⠿</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  v-if="todo.priority"
                  :class="[
                    'inline-flex px-2 py-0.5 text-xs font-medium rounded-full',
                    priorityClass[todo.priority] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
                  ]"
                >{{ priorityLabel[todo.priority] ?? todo.priority }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-100 max-w-xs truncate">{{ todo.title }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="tag in todo.tags" :key="tag.id"
                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full text-white"
                    :style="{ backgroundColor: tag.color }"
                  >{{ tag.name }}</span>
                  <span v-if="!todo.tags?.length" class="text-slate-400 text-xs">—</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <button
                  type="button"
                  :class="['inline-flex px-2 py-1 text-xs font-medium rounded-full transition-colors hover:opacity-80', todo.completed ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200']"
                  :title="todo.completed ? 'クリックで未完了に戻す' : 'クリックで完了にする'"
                  @click="toggleComplete(todo)"
                >
                  {{ todo.completed ? 'Done' : 'Not done' }}
                </button>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm" :class="isOverdue(todo) ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-slate-500 dark:text-slate-400'">
                {{ formatDueDate(todo.due_date) }}<span v-if="isOverdue(todo)" class="text-xs ml-1">⚠</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ formatDate(todo.modified) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" @click="goView(todo.id)">View</button>
                <button type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" @click="goEdit(todo.id)">Edit</button>
                <button type="button" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" @click="remove(todo.id, todo.title)">Delete</button>
              </td>
            </tr>
            <tr v-if="todos.length === 0">
              <td colspan="9" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">Todoが見つかりません。</td>
            </tr>
          </VueDraggable>
          <!-- 通常の tbody -->
          <tbody v-else class="divide-y divide-slate-200 dark:divide-slate-700">
            <tr
              v-for="todo in todos"
              :key="todo.id"
              :class="[
                'transition-colors',
                isOverdue(todo)
                  ? 'bg-red-50 dark:bg-red-950 hover:bg-red-100 dark:hover:bg-red-900'
                  : selectedIds.has(todo.id)
                    ? 'bg-blue-50 dark:bg-blue-950'
                    : 'bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700',
              ]"
            >
              <td class="px-3 py-3 w-8">
                <input
                  type="checkbox"
                  :checked="selectedIds.has(todo.id)"
                  class="rounded border-slate-300 dark:border-slate-600 text-blue-600"
                  @change="toggleSelect(todo.id)"
                />
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  v-if="todo.priority"
                  :class="[
                    'inline-flex px-2 py-0.5 text-xs font-medium rounded-full',
                    priorityClass[todo.priority] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
                  ]"
                >{{ priorityLabel[todo.priority] ?? todo.priority }}</span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-100 max-w-xs truncate">{{ todo.title }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="tag in todo.tags" :key="tag.id"
                    class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full text-white"
                    :style="{ backgroundColor: tag.color }"
                  >{{ tag.name }}</span>
                  <span v-if="!todo.tags?.length" class="text-slate-400 text-xs">—</span>
                </div>
              </td>
              <td class="px-4 py-3 whitespace-nowrap">
                <button
                  type="button"
                  :class="['inline-flex px-2 py-1 text-xs font-medium rounded-full transition-colors hover:opacity-80', todo.completed ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200']"
                  :title="todo.completed ? 'クリックで未完了に戻す' : 'クリックで完了にする'"
                  @click="toggleComplete(todo)"
                >
                  {{ todo.completed ? 'Done' : 'Not done' }}
                </button>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm" :class="isOverdue(todo) ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-slate-500 dark:text-slate-400'">
                {{ formatDueDate(todo.due_date) }}<span v-if="isOverdue(todo)" class="text-xs ml-1">⚠</span>
              </td>
              <td class="px-4 py-3 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ formatDate(todo.modified) }}</td>
              <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" @click="goView(todo.id)">View</button>
                <button type="button" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300" @click="goEdit(todo.id)">Edit</button>
                <button type="button" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300" @click="remove(todo.id, todo.title)">Delete</button>
              </td>
            </tr>
            <tr v-if="todos.length === 0">
              <td colspan="8" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400 text-sm">Todoが見つかりません。</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 一括操作バー -->
    <div
      v-if="selectedIds.size > 0"
      class="fixed bottom-6 left-1/2 -translate-x-1/2 z-10 flex items-center gap-3 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-600 rounded-full shadow-lg px-5 py-3"
    >
      <span class="text-sm text-slate-600 dark:text-slate-300 font-medium">{{ selectedIds.size }} 件選択中</span>
      <button
        type="button"
        class="btn-secondary text-sm px-3 py-1"
        @click="bulkComplete"
      >
        完了にする
      </button>
      <button
        type="button"
        class="text-sm px-3 py-1 rounded-lg bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 hover:bg-red-200 dark:hover:bg-red-800 transition-colors"
        @click="bulkDelete"
      >
        削除
      </button>
      <button
        type="button"
        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg leading-none"
        title="選択解除"
        @click="selectedIds = new Set()"
      >
        ×
      </button>
    </div>

    <!-- ページネーション -->
    <div v-if="!loading" class="flex items-center justify-between mt-4">
      <p class="text-sm text-slate-600 dark:text-slate-400">
        {{ todos.length }} 件表示 / 全 {{ pagination.count }} 件
      </p>
      <div v-if="pagination.pageCount > 1" class="flex items-center gap-2">
        <button
          type="button"
          class="btn-secondary text-sm px-3 py-1 disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentPage <= 1"
          @click="prevPage"
        >
          ← 前
        </button>
        <span class="text-sm text-slate-600 dark:text-slate-400">
          {{ currentPage }} / {{ pagination.pageCount }} ページ
        </span>
        <button
          type="button"
          class="btn-secondary text-sm px-3 py-1 disabled:opacity-40 disabled:cursor-not-allowed"
          :disabled="currentPage >= pagination.pageCount"
          @click="nextPage"
        >
          次 →
        </button>
      </div>
    </div>
  </div>
</template>
