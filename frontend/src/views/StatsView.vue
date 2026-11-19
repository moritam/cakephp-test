<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../api'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

onMounted(async () => {
  try {
    stats.value = await api.getTodoStats()
  } catch (e) {
    error.value = e.message || '統計の取得に失敗しました。'
  } finally {
    loading.value = false
  }
})

const completionRate = computed(() => {
  if (!stats.value || stats.value.total === 0) return 0
  return Math.round((stats.value.completed / stats.value.total) * 100)
})

const priorityMax = computed(() => {
  if (!stats.value) return 1
  return Math.max(1, ...Object.values(stats.value.by_priority))
})

const priorityConfig = {
  high: { label: '高', barClass: 'bg-red-500 dark:bg-red-400' },
  medium: { label: '中', barClass: 'bg-yellow-500 dark:bg-yellow-400' },
  low: { label: '低', barClass: 'bg-green-500 dark:bg-green-400' },
}
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100 mb-6">統計</h2>

    <div v-if="error" class="rounded-lg bg-red-50 dark:bg-red-950 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800 p-4 mb-4">
      {{ error }}
    </div>
    <div v-if="loading" class="text-slate-500 dark:text-slate-400">読み込み中...</div>

    <template v-else-if="stats">
      <!-- サマリーカード -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">合計</p>
          <p class="text-3xl font-bold text-slate-800 dark:text-slate-100">{{ stats.total }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">完了</p>
          <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ stats.completed }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">未完了</p>
          <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ stats.pending }}</p>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-5">
          <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1">期限切れ</p>
          <p class="text-3xl font-bold" :class="stats.overdue > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-400'">
            {{ stats.overdue }}
          </p>
        </div>
      </div>

      <!-- 完了率 -->
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6 mb-6">
        <div class="flex justify-between items-center mb-3">
          <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300">完了率</h3>
          <span class="text-2xl font-bold text-slate-800 dark:text-slate-100">{{ completionRate }}%</span>
        </div>
        <div class="h-4 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
          <div
            class="h-full bg-green-500 dark:bg-green-400 rounded-full transition-all duration-700"
            :style="{ width: completionRate + '%' }"
          ></div>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
          {{ stats.completed }} / {{ stats.total }} 件完了
        </p>
      </div>

      <!-- 優先度別内訳 -->
      <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-slate-200 dark:border-slate-700 p-6">
        <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-5">優先度別件数</h3>
        <div class="space-y-4">
          <div v-for="(cfg, key) in priorityConfig" :key="key" class="flex items-center gap-4">
            <span class="w-6 text-sm font-medium text-slate-600 dark:text-slate-400 text-right">{{ cfg.label }}</span>
            <div class="flex-1 h-6 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :class="cfg.barClass"
                :style="{ width: (stats.by_priority[key] / priorityMax * 100) + '%' }"
              ></div>
            </div>
            <span class="w-8 text-sm font-semibold text-slate-700 dark:text-slate-300 text-right">
              {{ stats.by_priority[key] }}
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
