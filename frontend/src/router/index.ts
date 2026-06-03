import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import DiscordCallbackView from '../views/DiscordCallbackView.vue'
import DashboardView from '../views/DashboardView.vue'
import FoldersIndexView from '../views/FoldersIndexView.vue'
import FolderCreateView from '../views/FolderCreateView.vue'
import FolderShowView from '../views/FolderShowView.vue'
import FolderEditView from '../views/FolderEditView.vue'
import ExploreView from '../views/ExploreView.vue'
import PublicFolderView from '../views/PublicFolderView.vue'
import AdminUsersView from '../views/AdminUsersView.vue'
import AdminExploreView from '../views/AdminExploreView.vue'
import LegalTermsView from '../views/LegalTermsView.vue'
import LegalPrivacyView from '../views/LegalPrivacyView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'explore',
      component: ExploreView,
      meta: { title: 'Explore — SignVault' },
    },
    {
      path: '/explore',
      redirect: '/',
    },
    {
      path: '/login',
      redirect: '/',
    },
    {
      path: '/auth/discord/callback',
      name: 'discord-callback',
      component: DiscordCallbackView,
      meta: { title: 'Signing In — SignVault' },
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
    {
      path: '/admin/users',
      name: 'admin-users',
      component: AdminUsersView,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: 'Users — SignVault Admin',
      },
    },
    {
      path: '/admin/explore',
      name: 'admin-explore',
      component: AdminExploreView,
      meta: {
        requiresAuth: true,
        requiresAdmin: true,
        title: 'Explore — SignVault Admin',
      },
    },
    {
      path: '/terms',
      name: 'terms',
      component: LegalTermsView,
      meta: { title: 'Terms of Service — SignVault' },
    },
    {
      path: '/privacy',
      name: 'privacy',
      component: LegalPrivacyView,
      meta: { title: 'Privacy Policy — SignVault' },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user && !auth.isLoading) {
    await auth.fetchUser()
  }

  if (to.name === 'discord-callback') {
    if (auth.isAuthenticated) {
      return { name: 'dashboard' }
    }

    return true
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'explore' }
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'explore' }
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
