import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useUserStore } from '@/stores/user'

// 路由配置
const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/login/index.vue'),
    meta: { title: '登录', public: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/dashboard/index.vue'),
        meta: { title: '仪表盘' },
      },
      {
        path: 'system',
        name: 'System',
        redirect: '/system/user',
        meta: { title: '系统管理' },
        children: [
          {
            path: 'user',
            name: 'User',
            component: () => import('@/views/system/user/index.vue'),
            meta: { title: '用户管理', permission: 'user' },
          },
          {
            path: 'role',
            name: 'Role',
            component: () => import('@/views/system/role/index.vue'),
            meta: { title: '角色管理', permission: 'role' },
          },
          {
            path: 'permission',
            name: 'Permission',
            component: () => import('@/views/system/permission/index.vue'),
            meta: { title: '权限管理', permission: 'permission' },
          },
          {
            path: 'log',
            name: 'OperationLog',
            component: () => import('@/views/system/log/index.vue'),
            meta: { title: '操作日志', permission: 'log' },
          },
        ],
      },
    ],
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/error/403.vue'),
    meta: { title: '无权限', public: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/error/404.vue'),
    meta: { title: '页面不存在', public: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// 路由守卫
router.beforeEach(async (to, _from, next) => {
  // 设置页面标题
  document.title = to.meta.title ? `${to.meta.title} - AdminSystem` : 'AdminSystem'

  const userStore = useUserStore()
  const isPublic = to.meta.public

  // 公开页面直接放行
  if (isPublic) {
    next()
    return
  }

  // 检查是否登录
  if (!userStore.isLoggedIn) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
    return
  }

  // 获取用户信息
  if (!userStore.userInfo) {
    try {
      await userStore.fetchUserInfo()
    } catch {
      next({ name: 'Login' })
      return
    }
  }

  // 检查路由权限
  const permission = to.meta.permission
  if (permission && !userStore.hasPermission(permission as string)) {
    next({ name: 'Forbidden' })
    return
  }

  next()
})

export default router
