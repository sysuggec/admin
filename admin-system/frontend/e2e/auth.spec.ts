import { test, expect } from '@playwright/test'

// 这个测试文件完全独立，不依赖任何 beforeEach 钩子
// 用于验证未登录用户的路由守卫行为

test.describe('权限控制', () => {
  // 确保使用全新的浏览器上下文
  test.use({ storageState: { cookies: [], origins: [] } })

  test('未登录访问受保护页面跳转登录', async ({ page }) => {
    // 直接访问受保护页面
    await page.goto('/system/user')
    
    // 等待 Vue 应用加载和路由守卫执行
    await page.waitForTimeout(1500)

    // 应该重定向到登录页
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 })
  })
})
