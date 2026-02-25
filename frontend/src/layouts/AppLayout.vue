<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api, checkAuth } from '../api'

const router = useRouter()
const loggedIn = ref(false)

async function logout() {
  await api.logout()
  loggedIn.value = false
  router.push({ name: 'Login' })
}

onMounted(async () => {
  loggedIn.value = await checkAuth()
})
</script>

<template>
  <div class="min-h-screen flex flex-col bg-slate-50 text-slate-900">
    <nav class="bg-white shadow-sm border-b border-slate-200">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-14 items-center">
          <router-link to="/todos" class="text-xl font-semibold text-slate-800 hover:text-slate-600">
            Todo App
          </router-link>
          <div class="flex gap-4">
            <router-link
              to="/todos"
              class="text-slate-600 hover:text-slate-900 font-medium"
            >
              Todo一覧
            </router-link>
            <button
              type="button"
              class="text-slate-600 hover:text-slate-900 font-medium"
              @click="logout"
            >
              ログアウト
            </button>
          </div>
        </div>
      </div>
    </nav>
    <main class="max-w-4xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 flex-1">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    <footer class="border-t border-slate-200 py-6 text-center text-slate-500 text-sm">
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
