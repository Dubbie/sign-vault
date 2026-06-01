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
      meta: { layout: 'auth', title: 'SignVault' },
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { layout: 'auth', title: 'Sign In — SignVault' },
    },
    {
      path: '/auth/discord/callback',
      name: 'discord-callback',
      component: DiscordCallbackView,
      meta: { layout: 'auth', title: 'Signing In — SignVault' },
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: {
        requiresAuth: true,
        title: 'Dashboard — SignVault',
      },
    },
    {
      path: '/folders',
      name: 'folders',
      component: FoldersIndexView,
      meta: {
        requiresAuth: true,
        title: 'My Folders — SignVault',
      },
    },
    {
      path: '/folders/new',
      name: 'folders-new',
      component: FolderCreateView,
      meta: {
        requiresAuth: true,
        title: 'New Folder — SignVault',
      },
    },
    {
      path: '/folders/:id',
      name: 'folders-show',
      component: FolderShowView,
      meta: {
        requiresAuth: true,
        title: 'Folder — SignVault',
      },
    },
    {
      path: '/folders/:id/edit',
      name: 'folders-edit',
      component: FolderEditView,
      meta: {
        requiresAuth: true,
        title: 'Edit Folder — SignVault',
      },
    },
    {
      path: '/public/folders/:slug',
      name: 'public-folder',
      component: PublicFolderView,
      meta: { title: 'Public Folder — SignVault' },
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

router.afterEach((to) => {
  const title = to.meta.title as string | undefined
  if (title) {
    document.title = title
  }
})

export default router
