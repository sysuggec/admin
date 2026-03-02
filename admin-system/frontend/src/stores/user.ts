// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-nocheck
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as loginApi, logout as logoutApi, getUserInfo as getUserInfoApi } from '@/api/auth'
import type { UserInfoResponse } from '@/types'
import router from '@/router'

export const useUserStore = defineStore('user', () => {
  // 状态
  const token = ref<string>(localStorage.getItem('token') || '')
  const userInfo = ref<UserInfoResponse | null>(null)

  // 计算属性
  const isLoggedIn = computed(() => !!token.value)
  const permissions = computed(() => userInfo.value?.permissions || [])
  const menus = computed(() => userInfo.value?.menus || [])
  const roles = computed(() => userInfo.value?.roles || [])

  // 登录
  async function login(username: string, password: string) {
    const { data } = await loginApi({ username, password })
    token.value = data.data.access_token
    localStorage.setItem('token', data.data.access_token)
    return data.data
  }

  // 登出
  async function logout() {
    try {
      await logoutApi()
    } catch {
      // 忽略登出错误
    } finally {
      token.value = ''
      userInfo.value = null
      localStorage.removeItem('token')
    }
  }

  // 获取用户信息
  async function fetchUserInfo() {
    const { data } = await getUserInfoApi()
    userInfo.value = data.data
    return data.data
  }

  // 检查是否有权限
  function hasPermission(permission: string): boolean {
    return permissions.value.includes(permission)
  }

  // 检查是否有任意一个权限
  function hasAnyPermission(permissionList: string[]): boolean {
    return permissionList.some(p => permissions.value.includes(p))
  }

  // 检查是否有所有权限
  function hasAllPermissions(permissionList: string[]): boolean {
    return permissionList.every(p => permissions.value.includes(p))
  }

  return {
    token,
    userInfo,
    isLoggedIn,
    permissions,
    menus,
    roles,
    login,
    logout,
    fetchUserInfo,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions,
  }
})

// 导出 router 供其他模块使用
export { router }
