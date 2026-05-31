import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import RootRedirectView from '../views/RootRedirectView.vue'
import LoginView from '../views/LoginView.vue'
import DiscordCallbackView from '../views/DiscordCallbackView.vue'
import DashboardView from '../views/DashboardView.vue'
import FoldersIndexView from '../views/FoldersIndexView.vue'
import FolderCreateView from '../views/FolderCreateView.vue'
import FolderShowView from '../views/FolderShowView.vue'
import FolderEditView from '../views/FolderEditView.vue'
import PublicFolderView from '../views/PublicFolderView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'root',
      component: RootRedirectView,
      meta: { layout: 'auth' },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { layout: 'auth' },
    },
    {
      path: '/auth/discord/callback',
      name: 'discord-callback',
      component: DiscordCallbackView,
      meta: { layout: 'auth' },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/folders',
      name: 'folders',
      component: FoldersIndexView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/folders/new',
      name: 'folders-new',
      component: FolderCreateView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/folders/:id',
      name: 'folders-show',
      component: FolderShowView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/folders/:id/edit',
      name: 'folders-edit',
      component: FolderEditView,
      meta: {
        requiresAuth: true,
      },
    },
    {
      path: '/public/folders/:slug',
      name: 'public-folder',
      component: PublicFolderView,
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user && !auth.isLoading) {
    await auth.fetchUser()
  }

  if (to.name === 'login' || to.name === 'discord-callback') {
    if (auth.isAuthenticated) {
      return { name: 'dashboard' }
    }

    return true
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  return true
})

export default router
