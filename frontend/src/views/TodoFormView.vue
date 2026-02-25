<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'

const route = useRoute()
const router = useRouter()
const mode = computed(() => route.meta.mode || 'add')
const isEdit = computed(() => mode.value === 'edit')

const title = ref('')
const completed = ref(false)
const loading = ref(false)
const error = ref('')

const id = computed(() => route.params.id)

onMounted(async () => {
  if (isEdit.value && id.value) {
    try {
      const data = await api.getTodo(id.value)
      const t = data.todo
      title.value = t.title ?? ''
      completed.value = !!t.completed
    } catch (e) {
      error.value = e.message || '取得に失敗しました。'
    }
  }
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    if (isEdit.value) {
      await api.updateTodo(id.value, { title: title.value, completed: completed.value })
    } else {
      await api.createTodo({ title: title.value, completed: completed.value })
    }
    router.push({ name: 'TodoList' })
  } catch (e) {
    error.value = e.message || '保存に失敗しました。'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="todos-form">
    <div class="mb-6">
      <router-link :to="{ name: 'TodoList' }" class="text-slate-600 hover:text-slate-900">List Todos</router-link>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 max-w-xl">
      <h2 class="text-2xl font-bold text-slate-800 mb-6">
        {{ isEdit ? 'Edit Todo' : 'Add Todo' }}
      </h2>
      <form class="space-y-6" @submit.prevent="submit">
        <div v-if="error" class="rounded-lg bg-red-50 text-red-800 border border-red-200 p-4 text-sm">
          {{ error }}
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
            <input v-model="title" type="text" required class="form-input" />
          </div>
          <div>
            <label class="flex items-center gap-2">
              <input v-model="completed" type="checkbox" class="form-checkbox" />
              <span class="text-sm text-slate-700">Completed</span>
            </label>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ isEdit ? 'Save' : 'Submit' }}
          </button>
          <router-link v-if="isEdit" :to="{ name: 'TodoList' }" class="btn-secondary">Cancel</router-link>
        </div>
      </form>
    </div>
  </div>
</template>
