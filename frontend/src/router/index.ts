import { createRouter, createWebHistory } from 'vue-router'

import { useAuthStore } from '@/stores/auth'

import OauthCallbackView from '../views/OauthCallbackView.vue'
import LoginView from '../views/LoginView.vue'
import DashboardView from '../views/DashboardView.vue'
import FoldersIndexView from '../views/FoldersIndexView.vue'
import FolderCreateView from '../views/FolderCreateView.vue'
import FolderShowView from '../views/FolderShowView.vue'
import FolderEditView from '../views/FolderEditView.vue'
import ExploreView from '../views/ExploreView.vue'
import PublicFolderView from '../views/PublicFolderView.vue'
import AdminLayout from '../layouts/AdminLayout.vue'
import AdminUsersView from '../views/AdminUsersView.vue'
import AdminExploreView from '../views/AdminExploreView.vue'
import AdminLogsView from '../views/AdminLogsView.vue'
import LegalTermsView from '../views/LegalTermsView.vue'
import LegalPrivacyView from '../views/LegalPrivacyView.vue'
import UtilitiesView from '../views/utilities/UtilitiesView.vue'
import SignSizingView from '../views/utilities/SignSizingView.vue'
import NameTagFormatterView from '../views/utilities/NameTagFormatterView.vue'
import SettingsView from '../views/SettingsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, _from, savedPosition) {
    if (savedPosition) {
      return savedPosition
    }

    if (to.hash) {
      return {
        el: to.hash,
        top: 16,
      }
    }

    return { top: 0 }
  },
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
      name: 'login',
      component: LoginView,
      meta: { title: 'Sign In — SignVault' },
    },
    {
      path: '/auth/discord/callback',
      name: 'discord-callback',
      component: OauthCallbackView,
      props: { provider: 'discord' },
      meta: { title: 'Signing In — SignVault' },
    },
    {
      path: '/auth/trackmania/callback',
      name: 'trackmania-callback',
      component: OauthCallbackView,
      props: { provider: 'trackmania' },
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
      path: '/settings',
      name: 'settings',
      component: SettingsView,
      meta: {
        requiresAuth: true,
        title: 'Settings — SignVault',
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
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        { path: '', redirect: '/admin/users' },
        {
          path: 'users',
          name: 'admin-users',
          component: AdminUsersView,
          meta: { title: 'Users — SignVault Admin' },
        },
        {
          path: 'folders',
          name: 'admin-folders',
          component: AdminExploreView,
          meta: { title: 'All Folders — SignVault Admin' },
        },
        {
          path: 'logs',
          name: 'admin-logs',
          component: AdminLogsView,
          meta: { title: 'Activity Log — SignVault Admin' },
        },
      ],
    },
    { path: '/admin/explore', redirect: '/admin/folders' },
    {
      path: '/utilities',
      component: UtilitiesView,
      children: [
        {
          path: '',
          redirect: '/utilities/sign-sizing',
        },
        {
          path: 'sign-sizing',
          name: 'utilities-sign-sizing',
          component: SignSizingView,
          meta: { title: 'Sign Sizing Guide — SignVault' },
        },
        {
          path: 'name-tag-formatter',
          name: 'utilities-name-tag-formatter',
          component: NameTagFormatterView,
          meta: { title: 'Name Tag Formatter — SignVault' },
        },
      ],
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

  if (to.name === 'discord-callback' || to.name === 'trackmania-callback') {
    // Allow through — the callback view handles linking vs login.
    return true
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
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
