<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api, checkAuth } from '../api'

const router = useRouter()
const loggedIn = ref(false)
const isDark = ref(false)

function applyTheme(dark) {
  document.documentElement.classList.toggle('dark', dark)
}

function toggleDark() {
  isDark.value = !isDark.value
}

watch(isDark, (val) => {
  try { localStorage.setItem('theme', val ? 'dark' : 'light') } catch {}
  applyTheme(val)
})

async function logout() {
  await api.logout()
  loggedIn.value = false
  router.push({ name: 'Login' })
}

onMounted(async () => {
  try { isDark.value = localStorage.getItem('theme') === 'dark' } catch {}
  applyTheme(isDark.value)
  loggedIn.value = await checkAuth()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-950">
    <!-- ナビゲーション -->
    <nav class="bg-white dark:bg-slate-900 border-b-2 border-slate-100 dark:border-slate-800 sticky top-0 z-20 shadow-sm">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <!-- ロゴ -->
          <router-link to="/todos" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center shadow-md shadow-blue-500/40">
              <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
              </svg>
            </div>
            <span class="text-base font-bold text-slate-800 dark:text-slate-100">Todo App</span>
          </router-link>

          <!-- ナビリンク -->
          <div class="flex items-center gap-1">
            <router-link
              v-for="{ to, label } in [
                { to: '/todos', label: 'Todo一覧' },
                { to: '/calendar', label: 'カレンダー' },
                { to: '/stats', label: '統計' },
                { to: '/tags', label: 'タグ' },
                { to: '/account', label: 'アカウント' },
              ]"
              :key="to"
              :to="to"
              class="px-3 py-2 rounded-lg text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              active-class="text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 hover:bg-blue-100 dark:hover:bg-blue-950 hover:text-blue-700 dark:hover:text-blue-300"
            >
              {{ label }}
            </router-link>

            <!-- ダークモード -->
            <button
              type="button"
              class="ml-2 w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
              :aria-label="isDark ? 'ライトモードに切り替え' : 'ダークモードに切り替え'"
              @click="toggleDark"
            >
              <svg v-if="isDark" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z" />
              </svg>
              <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>

            <button
              type="button"
              class="ml-1 px-3 py-2 rounded-lg text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors"
              @click="logout"
            >
              ログアウト
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- メインコンテンツ -->
    <main class="max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 flex-1">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <footer class="border-t border-slate-100 dark:border-slate-800 py-5 text-center text-slate-400 dark:text-slate-500 text-xs">
      &copy; {{ new Date().getFullYear() }} Todo App
    </footer>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
