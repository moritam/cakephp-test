<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'

const route = useRoute()
const router = useRouter()
const mode = computed(() => route.meta.mode || 'add')
const isEdit = computed(() => mode.value === 'edit')

const title = ref('')
const description = ref('')
const completed = ref(false)
const priority = ref('medium')
const dueDate = ref('')
const selectedTagIds = ref([])
const availableTags = ref([])
const loading = ref(false)
const error = ref('')

const id = computed(() => route.params.id)

onMounted(async () => {
  const tagsData = await api.getTags().catch(() => ({ tags: [] }))
  availableTags.value = tagsData.tags || []

  if (isEdit.value && id.value) {
    try {
      const data = await api.getTodo(id.value)
      const t = data.todo
      title.value = t.title ?? ''
      description.value = t.description ?? ''
      completed.value = !!t.completed
      priority.value = t.priority ?? 'medium'
      dueDate.value = t.due_date ? String(t.due_date).substring(0, 10) : ''
      selectedTagIds.value = (t.tags || []).map((tag) => tag.id)
    } catch (e) {
      error.value = e.message || '取得に失敗しました。'
    }
  }
})

async function submit() {
  error.value = ''
  loading.value = true
  try {
    const payload = {
      title: title.value,
      description: description.value || null,
      completed: completed.value,
      priority: priority.value,
      due_date: dueDate.value || null,
      tags: selectedTagIds.value.map((tagId) => ({ id: tagId })),
    }
    if (isEdit.value) {
      await api.updateTodo(id.value, payload)
    } else {
      await api.createTodo(payload)
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
      <router-link :to="{ name: 'TodoList' }" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100">← List Todos</router-link>
    </div>
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 max-w-2xl">
      <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">
        {{ isEdit ? 'Edit Todo' : 'Add Todo' }}
      </h2>
      <form class="space-y-5" @submit.prevent="submit">
        <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 text-sm">
          {{ error }}
        </div>

        <!-- タイトル -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
            タイトル <span class="text-red-500">*</span>
          </label>
          <input v-model="title" type="text" required class="form-input" />
        </div>

        <!-- 説明 -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">説明</label>
          <textarea v-model="description" rows="3" class="form-input resize-none" placeholder="詳細を入力..." />
        </div>

        <!-- 優先度 -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">優先度</label>
          <select v-model="priority" class="form-input">
            <option value="high">高 (High)</option>
            <option value="medium">中 (Medium)</option>
            <option value="low">低 (Low)</option>
          </select>
        </div>

        <!-- 期限日 -->
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">期限日</label>
          <input v-model="dueDate" type="date" class="form-input" />
        </div>

        <!-- 完了 -->
        <div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="completed" type="checkbox" class="form-checkbox" />
            <span class="text-sm text-slate-700 dark:text-slate-300">完了済み</span>
          </label>
        </div>

        <!-- タグ -->
        <div v-if="availableTags.length > 0">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">タグ</label>
          <div class="flex flex-wrap gap-2">
            <label
              v-for="tag in availableTags"
              :key="tag.id"
              class="flex items-center gap-1.5 cursor-pointer"
            >
              <input
                type="checkbox"
                :value="tag.id"
                v-model="selectedTagIds"
                class="form-checkbox"
              />
              <span
                class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full text-white"
                :style="{ backgroundColor: tag.color }"
              >
                {{ tag.name }}
              </span>
            </label>
          </div>
        </div>
        <div v-else class="text-sm text-slate-500 dark:text-slate-400">
          タグがありません。
          <router-link :to="{ name: 'TagList' }" class="text-blue-600 dark:text-blue-400 hover:underline">タグを管理</router-link>
        </div>

        <div class="flex gap-3 pt-2">
          <button type="submit" class="btn-primary" :disabled="loading">
            {{ loading ? '保存中...' : (isEdit ? '保存' : '作成') }}
          </button>
          <router-link :to="{ name: 'TodoList' }" class="btn-secondary">キャンセル</router-link>
        </div>
      </form>
    </div>
  </div>
</template>
