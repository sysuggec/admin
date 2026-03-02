/* eslint-disable @typescript-eslint/ban-ts-comment */
// @ts-nocheck
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import ElementPlus from 'element-plus'
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

import App from './App.vue'
import router from './router'
import { setupPlugins } from './plugins'
import '@/styles/index.css'
import 'element-plus/dist/index.css'

const app = createApp(App)
const pinia = createPinia()

// 注册 Pinia
app.use(pinia)

// 注册路由
app.use(router)

// 注册 Element Plus
app.use(ElementPlus, { locale: zhCn })

// 注册 Element Plus 图标
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
}

// 注册插件(指令等)
setupPlugins(app)

app.mount('#app')
