<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api, checkAuth } from '../api'

const router = useRouter()
const loggedIn = ref(false)

// ダークモード
const isDark = ref(false)

function applyTheme(dark) {
  document.documentElement.classList.toggle('dark', dark)
}

function toggleDark() {
  isDark.value = !isDark.value
}

watch(isDark, (val) => {
  try {
    localStorage.setItem('theme', val ? 'dark' : 'light')
  } catch {}
  applyTheme(val)
})

async function logout() {
  await api.logout()
  loggedIn.value = false
  router.push({ name: 'Login' })
}

onMounted(async () => {
  // テーマ復元
  try {
    isDark.value = localStorage.getItem('theme') === 'dark'
  } catch {}
  applyTheme(isDark.value)

  loggedIn.value = await checkAuth()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100">
    <nav class="bg-white dark:bg-slate-800 shadow-sm border-b border-slate-200 dark:border-slate-700">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14 items-center">
          <router-link to="/todos" class="text-xl font-semibold text-slate-800 dark:text-slate-100 hover:text-slate-600 dark:hover:text-slate-300">
            Todo App
          </router-link>
          <div class="flex items-center gap-4">
            <router-link
              to="/todos"
              class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 font-medium"
            >
              Todo一覧
            </router-link>
            <router-link
              to="/tags"
              class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 font-medium"
            >
              タグ管理
            </router-link>
            <router-link
              to="/account"
              class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 font-medium"
            >
              アカウント
            </router-link>
            <!-- ダークモード切り替え -->
            <button
              type="button"
              class="text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100"
              :aria-label="isDark ? 'ライトモードに切り替え' : 'ダークモードに切り替え'"
              @click="toggleDark"
            >
              <!-- 太陽アイコン (ダーク時に表示) -->
              <svg v-if="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z" />
              </svg>
              <!-- 月アイコン (ライト時に表示) -->
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
              </svg>
            </button>
            <button
              type="button"
              class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 font-medium"
              @click="logout"
            >
              ログアウト
            </button>
          </div>
        </div>
      </div>
    </nav>
    <main class="max-w-5xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 flex-1">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    <footer class="border-t border-slate-200 dark:border-slate-700 py-6 text-center text-slate-500 dark:text-slate-400 text-sm">
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
