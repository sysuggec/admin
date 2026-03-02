/* eslint-disable @typescript-eslint/no-explicit-any */
import type { App } from 'vue'
import { setupPermissionDirectives } from '@/directives/permission'

// 全局组件
const globalComponents = import.meta.glob('./components/**/*.vue', { eager: true })

export function registerGlobalComponents(app: App) {
  Object.entries(globalComponents).forEach(([path, module]) => {
    const name = path.replace(/^\.\/components\/(.+)\.vue$/, '$1')
    app.component(name, (module as { default: any }).default)
  })
}

// 插件安装
export function setupPlugins(app: App) {
  // 注册权限指令
  setupPermissionDirectives(app)
}
