<script setup>
import { ref, onMounted } from 'vue'
import { api } from '../api'

const tags = ref([])
const loading = ref(true)
const error = ref('')
const newName = ref('')
const newColor = ref('#3b82f6')
const saving = ref(false)
const saveError = ref('')

onMounted(async () => {
  await loadTags()
})

async function loadTags() {
  loading.value = true
  error.value = ''
  try {
    const data = await api.getTags()
    tags.value = data.tags || []
  } catch (e) {
    error.value = e.message || '取得に失敗しました。'
  } finally {
    loading.value = false
  }
}

async function createTag() {
  if (!newName.value.trim()) return
  saveError.value = ''
  saving.value = true
  try {
    await api.createTag({ name: newName.value.trim(), color: newColor.value })
    newName.value = ''
    newColor.value = '#3b82f6'
    await loadTags()
  } catch (e) {
    saveError.value = e.message || '作成に失敗しました。'
  } finally {
    saving.value = false
  }
}

async function removeTag(id, name) {
  if (!confirm(`「${name}」を削除してもよろしいですか？`)) return
  try {
    await api.deleteTag(id)
    tags.value = tags.value.filter((t) => t.id !== id)
  } catch (e) {
    alert(e.message || '削除に失敗しました。')
  }
}
</script>

<template>
  <div class="tags-index">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">タグ管理</h2>
      <router-link to="/todos" class="btn-secondary">← Todo一覧</router-link>
    </div>

    <!-- 新規作成フォーム -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5 mb-6">
      <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">新しいタグを作成</h3>
      <div v-if="saveError" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-3 text-sm mb-3">
        {{ saveError }}
      </div>
      <form class="flex items-end gap-3" @submit.prevent="createTag">
        <div class="flex-1">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">タグ名</label>
          <input v-model="newName" type="text" required class="form-input" placeholder="例: 仕事" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">色</label>
          <input v-model="newColor" type="color" class="h-10 w-16 rounded-lg border border-slate-300 dark:border-slate-600 cursor-pointer" />
        </div>
        <button type="submit" class="btn-primary" :disabled="saving">
          {{ saving ? '作成中...' : '作成' }}
        </button>
      </form>
    </div>

    <!-- タグ一覧 -->
    <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 mb-4">
      {{ error }}
    </div>
    <div v-if="loading" class="text-slate-500 dark:text-slate-400">読み込み中...</div>
    <div v-else class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
      <div v-if="tags.length === 0" class="p-8 text-center text-slate-500 dark:text-slate-400 text-sm">
        タグがありません。上のフォームから作成してください。
      </div>
      <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
        <li
          v-for="tag in tags"
          :key="tag.id"
          class="flex items-center justify-between px-5 py-3"
        >
          <div class="flex items-center gap-3">
            <span
              class="inline-flex h-5 w-5 rounded-full flex-shrink-0"
              :style="{ backgroundColor: tag.color }"
            />
            <span
              class="inline-flex px-3 py-1 text-sm font-medium rounded-full text-white"
              :style="{ backgroundColor: tag.color }"
            >
              {{ tag.name }}
            </span>
          </div>
          <button
            type="button"
            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium"
            @click="removeTag(tag.id, tag.name)"
          >
            削除
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
