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
        <div class="rounded-2xl bg-blue-600 p-5 text-white shadow-lg shadow-blue-500/30">
          <p class="text-xs font-semibold text-blue-100 uppercase tracking-wider mb-3">合計</p>
          <p class="text-5xl font-bold">{{ stats.total }}</p>
        </div>
        <div class="rounded-2xl bg-emerald-500 p-5 text-white shadow-lg shadow-emerald-500/30">
          <p class="text-xs font-semibold text-emerald-100 uppercase tracking-wider mb-3">完了</p>
          <p class="text-5xl font-bold">{{ stats.completed }}</p>
        </div>
        <div class="rounded-2xl bg-violet-600 p-5 text-white shadow-lg shadow-violet-500/30">
          <p class="text-xs font-semibold text-violet-100 uppercase tracking-wider mb-3">未完了</p>
          <p class="text-5xl font-bold">{{ stats.pending }}</p>
        </div>
        <div
          class="rounded-2xl p-5 text-white shadow-lg"
          :class="stats.overdue > 0 ? 'bg-red-500 shadow-red-500/30' : 'bg-slate-400 shadow-slate-400/30'"
        >
          <p class="text-xs font-semibold uppercase tracking-wider mb-3 opacity-80">期限切れ</p>
          <p class="text-5xl font-bold">{{ stats.overdue }}</p>
        </div>
      </div>

      <!-- 完了率 -->
      <div class="page-card p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide">完了率</h3>
          <span class="text-3xl font-bold bg-gradient-to-r from-green-500 to-emerald-600 bg-clip-text text-transparent">{{ completionRate }}%</span>
        </div>
        <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
          <div
            class="h-full bg-gradient-to-r from-green-400 to-emerald-500 rounded-full transition-all duration-700"
            :style="{ width: completionRate + '%' }"
          ></div>
        </div>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-2">
          {{ stats.completed }} / {{ stats.total }} 件完了
        </p>
      </div>

      <!-- 優先度別内訳 -->
      <div class="page-card p-6">
        <h3 class="text-sm font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wide mb-5">優先度別件数</h3>
        <div class="space-y-4">
          <div v-for="(cfg, key) in priorityConfig" :key="key" class="flex items-center gap-4">
            <span class="w-6 text-sm font-semibold text-slate-500 dark:text-slate-400 text-right">{{ cfg.label }}</span>
            <div class="flex-1 h-5 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-700"
                :class="cfg.barClass"
                :style="{ width: (stats.by_priority[key] / priorityMax * 100) + '%' }"
              ></div>
            </div>
            <span class="w-8 text-sm font-bold text-slate-700 dark:text-slate-300 text-right">
              {{ stats.by_priority[key] }}
            </span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
