import type { Directive, DirectiveBinding } from 'vue'
import { useUserStore } from '@/stores/user'

/**
 * 权限指令
 * 使用方式: v-permission="'user:create'" 或 v-permission="['user:create', 'user:edit']"
 */
export const permission: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding<string | string[]>) {
    const userStore = useUserStore()
    const { value } = binding

    if (!value) {
      return
    }

    let hasPermission = false

    if (typeof value === 'string') {
      hasPermission = userStore.hasPermission(value)
    } else if (Array.isArray(value)) {
      hasPermission = userStore.hasAnyPermission(value)
    }

    if (!hasPermission) {
      el.parentNode?.removeChild(el)
    }
  },
}

/**
 * 权限指令(需要所有权限)
 * 使用方式: v-permission-all="['user:create', 'user:edit']"
 */
export const permissionAll: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding<string[]>) {
    const userStore = useUserStore()
    const { value } = binding

    if (!value || !Array.isArray(value)) {
      return
    }

    if (!userStore.hasAllPermissions(value)) {
      el.parentNode?.removeChild(el)
    }
  },
}

// 注册全局指令
export function setupPermissionDirectives(app: import('vue').App) {
  app.directive('permission', permission)
  app.directive('permission-all', permissionAll)
}
