<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'

const route = useRoute()
const router = useRouter()
const todo = ref(null)
const loading = ref(true)
const error = ref('')

const id = computed(() => route.params.id)

onMounted(async () => {
  try {
    const data = await api.getTodo(id.value)
    todo.value = data.todo
  } catch (e) {
    error.value = e.message || '取得に失敗しました。'
  } finally {
    loading.value = false
  }
})

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('ja-JP')
}

function formatDueDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('ja-JP')
}

function isOverdue(t) {
  if (!t?.due_date || t.completed) return false
  return new Date(t.due_date) < new Date(new Date().toDateString())
}

const priorityLabel = { high: '高 (High)', medium: '中 (Medium)', low: '低 (Low)' }
const priorityClass = {
  high: 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
  medium: 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
  low: 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
}

function goEdit() {
  router.push({ name: 'TodoEdit', params: { id: id.value } })
}

async function remove() {
  if (!confirm(`ID ${todo.value?.id} を削除してもよろしいですか？`)) return
  try {
    await api.deleteTodo(id.value)
    router.push({ name: 'TodoList' })
  } catch (e) {
    alert(e.message || '削除に失敗しました。')
  }
}
</script>

<template>
  <div class="todos-view">
    <div class="flex gap-3 mb-6 flex-wrap">
      <button type="button" class="btn-primary" @click="goEdit">Edit Todo</button>
      <button type="button" class="btn-danger" @click="remove">Delete Todo</button>
      <router-link :to="{ name: 'TodoList' }" class="btn-secondary">List Todos</router-link>
      <router-link :to="{ name: 'TodoAdd' }" class="btn-secondary">New Todo</router-link>
    </div>
    <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 mb-4">
      {{ error }}
    </div>
    <div v-if="loading" class="text-slate-500 dark:text-slate-400">読み込み中...</div>
    <div v-else-if="todo" class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
      <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">{{ todo.title }}</h2>
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <!-- 優先度 -->
        <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">優先度</dt>
          <dd class="mt-1">
            <span
              v-if="todo.priority"
              :class="[
                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                priorityClass[todo.priority] ?? 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
              ]"
            >
              {{ priorityLabel[todo.priority] ?? todo.priority }}
            </span>
            <span v-else class="text-sm text-slate-900 dark:text-slate-100">—</span>
          </dd>
        </div>
        <!-- 完了 -->
        <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">完了</dt>
          <dd class="mt-1">
            <span
              :class="[
                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                todo.completed ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200' : 'bg-slate-100 dark:bg-slate-700 text-slate-800 dark:text-slate-200',
              ]"
            >
              {{ todo.completed ? 'Yes' : 'No' }}
            </span>
          </dd>
        </div>
        <!-- 期限日 -->
        <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">期限日</dt>
          <dd
            class="mt-1 text-sm"
            :class="isOverdue(todo) ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-slate-900 dark:text-slate-100'"
          >
            {{ formatDueDate(todo.due_date) }}
            <span v-if="isOverdue(todo)" class="text-xs ml-1 text-red-500">⚠ 期限切れ</span>
          </dd>
        </div>
        <!-- ID -->
        <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">ID</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ todo.id }}</dd>
        </div>
        <!-- 作成日時 -->
        <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">作成日時</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDate(todo.created) }}</dd>
        </div>
        <!-- 更新日時 -->
        <div class="border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">更新日時</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100">{{ formatDate(todo.modified) }}</dd>
        </div>
        <!-- 説明 -->
        <div class="sm:col-span-2 border-b border-slate-200 dark:border-slate-700 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">説明</dt>
          <dd class="mt-1 text-sm text-slate-900 dark:text-slate-100 whitespace-pre-wrap">{{ todo.description || '—' }}</dd>
        </div>
        <!-- タグ -->
        <div class="sm:col-span-2 pb-4">
          <dt class="text-sm font-medium text-slate-500 dark:text-slate-400">タグ</dt>
          <dd class="mt-1 flex flex-wrap gap-1">
            <span v-if="!todo.tags?.length" class="text-sm text-slate-500 dark:text-slate-400">—</span>
            <span
              v-for="tag in todo.tags"
              :key="tag.id"
              class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full text-white"
              :style="{ backgroundColor: tag.color }"
            >
              {{ tag.name }}
            </span>
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>
