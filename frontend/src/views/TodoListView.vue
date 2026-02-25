<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'

const router = useRouter()
const todos = ref([])
const pagination = ref({ page: 1, pageCount: 1, count: 0 })
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    const data = await api.getTodos()
    todos.value = data.todos || []
    pagination.value = data.pagination || {}
  } catch (e) {
    error.value = e.message || '一覧の取得に失敗しました。'
  } finally {
    loading.value = false
  }
})

function formatDate(d) {
  if (!d) return '—'
  const date = new Date(d)
  return date.toLocaleString('ja-JP')
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
      <h2 class="text-2xl font-bold text-slate-800">Todos</h2>
      <router-link :to="{ name: 'TodoAdd' }" class="btn-primary">New TODO</router-link>
    </div>
    <div v-if="error" class="rounded-lg bg-red-50 text-red-800 border border-red-200 p-4 mb-4">
      {{ error }}
    </div>
    <div v-if="loading" class="text-slate-500">読み込み中...</div>
    <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">ID</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Title</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">完了</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">作成日時</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">更新日時</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">操作</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-slate-200">
            <tr v-for="todo in todos" :key="todo.id" class="hover:bg-slate-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">{{ todo.id }}</td>
              <td class="px-6 py-4 text-sm text-slate-900">{{ todo.title }}</td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                    todo.completed ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800',
                  ]"
                >
                  {{ todo.completed ? 'Done' : 'Not done' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ formatDate(todo.created) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ formatDate(todo.modified) }}</td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                <button type="button" class="text-blue-600 hover:text-blue-900" @click="goView(todo.id)">View</button>
                <button type="button" class="text-blue-600 hover:text-blue-900" @click="goEdit(todo.id)">Edit</button>
                <button
                  type="button"
                  class="text-red-600 hover:text-red-900"
                  @click="remove(todo.id, todo.title)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <p v-if="pagination.count !== undefined" class="text-sm text-slate-600 mt-6">
      Page {{ pagination.page }} of {{ pagination.pageCount }}, showing {{ todos.length }} record(s) out of {{ pagination.count }} total
    </p>
  </div>
</template>
