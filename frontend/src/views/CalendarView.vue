<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'

const router = useRouter()

const today = new Date()
const currentYear = ref(today.getFullYear())
const currentMonth = ref(today.getMonth() + 1) // 1-based

const todos = ref([])
const loading = ref(false)
const error = ref('')

const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土']

const monthLabel = computed(() => `${currentYear.value}年${currentMonth.value}月`)

function prevMonth() {
  if (currentMonth.value === 1) {
    currentYear.value--
    currentMonth.value = 12
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 12) {
    currentYear.value++
    currentMonth.value = 1
  } else {
    currentMonth.value++
  }
}

function goToday() {
  currentYear.value = today.getFullYear()
  currentMonth.value = today.getMonth() + 1
}

// カレンダーグリッド: 日曜始まり、42マス (6週)
const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month - 1, 1).getDay() // 0=Sun
  const daysInMonth = new Date(year, month, 0).getDate()
  const daysInPrev = new Date(year, month - 1, 0).getDate()

  const cells = []
  // 前月の末日から埋める
  for (let i = firstDay - 1; i >= 0; i--) {
    const d = daysInPrev - i
    cells.push({ date: `${year}-${String(month - 1 || 12).padStart(2, '0')}-${String(d).padStart(2, '0')}`, day: d, currentMonth: false })
  }
  // 当月
  for (let d = 1; d <= daysInMonth; d++) {
    cells.push({ date: `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`, day: d, currentMonth: true })
  }
  // 次月で残りを埋める
  const remaining = 42 - cells.length
  for (let d = 1; d <= remaining; d++) {
    cells.push({ date: `${year}-${String(month + 1 > 12 ? 1 : month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`, day: d, currentMonth: false })
  }
  return cells
})

// 日付文字列 → Todo 配列
const todosByDate = computed(() => {
  const map = {}
  for (const todo of todos.value) {
    if (!todo.due_date) continue
    const key = todo.due_date.substring(0, 10)
    if (!map[key]) map[key] = []
    map[key].push(todo)
  }
  return map
})

const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

function isToday(dateStr) {
  return dateStr === todayStr
}

function priorityChipClass(priority) {
  if (priority === 'high') return 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300'
  if (priority === 'low') return 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300'
  return 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300'
}

async function loadTodos() {
  loading.value = true
  error.value = ''
  try {
    todos.value = await api.getCalendarTodos(currentYear.value, currentMonth.value)
  } catch (e) {
    error.value = e.message || '取得に失敗しました。'
  } finally {
    loading.value = false
  }
}

watch([currentYear, currentMonth], loadTodos)
onMounted(loadTodos)
</script>

<template>
  <div>
    <!-- ヘッダー -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-100">カレンダー</h2>
        <p class="text-sm text-slate-400 dark:text-slate-500 mt-0.5">期限日のある Todo を月別に表示</p>
      </div>
      <div class="flex items-center gap-2">
        <button class="btn-secondary px-3 py-1.5 text-sm" @click="goToday">今日</button>
        <button
          class="w-9 h-9 rounded-xl border-2 border-slate-200 dark:border-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
          @click="prevMonth"
          aria-label="前の月"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <span class="text-base font-bold text-slate-700 dark:text-slate-200 w-28 text-center">{{ monthLabel }}</span>
        <button
          class="w-9 h-9 rounded-xl border-2 border-slate-200 dark:border-slate-600 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
          @click="nextMonth"
          aria-label="次の月"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="error" class="rounded-xl bg-red-50 dark:bg-red-950 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800 p-3 text-sm mb-4">
      {{ error }}
    </div>

    <!-- カレンダー本体 -->
    <div class="page-card overflow-hidden">
      <!-- 曜日ヘッダー -->
      <div class="grid grid-cols-7 border-b border-slate-100 dark:border-slate-700">
        <div
          v-for="(wd, i) in WEEKDAYS"
          :key="wd"
          class="py-2 text-center text-xs font-semibold uppercase tracking-wide"
          :class="i === 0 ? 'text-red-500' : i === 6 ? 'text-blue-500' : 'text-slate-500 dark:text-slate-400'"
        >
          {{ wd }}
        </div>
      </div>

      <!-- 日付グリッド -->
      <div class="grid grid-cols-7 divide-x divide-y divide-slate-100 dark:divide-slate-700/60">
        <div
          v-for="cell in calendarDays"
          :key="cell.date"
          class="min-h-[90px] p-1.5 relative"
          :class="cell.currentMonth ? 'bg-white dark:bg-slate-800' : 'bg-slate-50 dark:bg-slate-800/40'"
        >
          <!-- 日付番号 -->
          <div class="mb-1 flex justify-start">
            <span
              class="w-6 h-6 flex items-center justify-center text-xs font-semibold rounded-full"
              :class="[
                isToday(cell.date) ? 'bg-blue-600 text-white' : '',
                !isToday(cell.date) && cell.currentMonth ? 'text-slate-700 dark:text-slate-200' : '',
                !isToday(cell.date) && !cell.currentMonth ? 'text-slate-300 dark:text-slate-600' : '',
              ]"
            >
              {{ cell.day }}
            </span>
          </div>

          <!-- Todo チップ (ローディング中はスケルトン) -->
          <template v-if="loading">
            <div v-if="cell.currentMonth" class="h-4 bg-slate-100 dark:bg-slate-700 rounded animate-pulse mb-1"></div>
          </template>
          <template v-else>
            <button
              v-for="todo in (todosByDate[cell.date] || []).slice(0, 3)"
              :key="todo.id"
              class="w-full text-left text-xs px-1.5 py-0.5 rounded mb-0.5 truncate font-medium transition-opacity hover:opacity-70"
              :class="[priorityChipClass(todo.priority), todo.completed ? 'line-through opacity-50' : '']"
              @click="router.push(`/todos/${todo.id}`)"
            >
              {{ todo.title }}
            </button>
            <span
              v-if="(todosByDate[cell.date] || []).length > 3"
              class="text-xs text-slate-400 dark:text-slate-500 pl-1"
            >
              +{{ (todosByDate[cell.date] || []).length - 3 }} 件
            </span>
          </template>
        </div>
      </div>
    </div>

    <!-- 凡例 -->
    <div class="flex items-center gap-4 mt-4 text-xs text-slate-500 dark:text-slate-400">
      <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded bg-red-100 dark:bg-red-900/50"></span>高優先度
      </span>
      <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded bg-yellow-100 dark:bg-yellow-900/50"></span>中優先度
      </span>
      <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded bg-green-100 dark:bg-green-900/50"></span>低優先度
      </span>
    </div>
  </div>
</template>
