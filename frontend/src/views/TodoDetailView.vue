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
    <div class="flex gap-4 mb-6">
      <button type="button" class="btn-primary" @click="goEdit">Edit Todo</button>
      <button type="button" class="btn-danger" @click="remove">Delete Todo</button>
      <router-link :to="{ name: 'TodoList' }" class="btn-secondary">List Todos</router-link>
      <router-link :to="{ name: 'TodoAdd' }" class="btn-secondary">New Todo</router-link>
    </div>
    <div v-if="error" class="rounded-lg bg-red-50 text-red-800 border border-red-200 p-4 mb-4">
      {{ error }}
    </div>
    <div v-if="loading" class="text-slate-500">読み込み中...</div>
    <div v-else-if="todo" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
      <h2 class="text-2xl font-bold text-slate-800 mb-6">{{ todo.title }}</h2>
      <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="border-b border-slate-200 pb-4">
          <dt class="text-sm font-medium text-slate-500">Title</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ todo.title }}</dd>
        </div>
        <div class="border-b border-slate-200 pb-4">
          <dt class="text-sm font-medium text-slate-500">Id</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ todo.id }}</dd>
        </div>
        <div class="border-b border-slate-200 pb-4">
          <dt class="text-sm font-medium text-slate-500">Created</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ formatDate(todo.created) }}</dd>
        </div>
        <div class="border-b border-slate-200 pb-4">
          <dt class="text-sm font-medium text-slate-500">Modified</dt>
          <dd class="mt-1 text-sm text-slate-900">{{ formatDate(todo.modified) }}</dd>
        </div>
        <div class="pb-4">
          <dt class="text-sm font-medium text-slate-500">Completed</dt>
          <dd class="mt-1">
            <span
              :class="[
                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                todo.completed ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800',
              ]"
            >
              {{ todo.completed ? 'Yes' : 'No' }}
            </span>
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>
