import { test, expect } from '@playwright/test'

// 测试前登录
test.beforeEach(async ({ page }) => {
  await page.goto('/login')
  await page.fill('input[placeholder="用户名"]', 'admin')
  await page.fill('input[type="password"]', 'Admin@123456')
  await page.click('button:has-text("登 录")')
  await expect(page.locator('.el-message--success')).toBeVisible()
})

test.describe('仪表盘模块', () => {
  test('访问仪表盘', async ({ page }) => {
    await page.goto('/dashboard')
    await expect(page).toHaveURL(/\/dashboard/)
  })

  test('仪表盘数据展示', async ({ page }) => {
    await page.goto('/dashboard')
    // 等待数据加载
    await page.waitForTimeout(1000)

    // 验证页面有内容
    const content = await page.textContent('body')
    expect(content?.length).toBeGreaterThan(0)
  })
})

test.describe('导航功能', () => {
  test('侧边栏菜单展示', async ({ page }) => {
    await page.goto('/dashboard')
    // 使用更精确的选择器，避免匹配到多个元素
    await expect(page.locator('.sidebar-menu.el-menu')).toBeVisible()
  })

  test('菜单导航 - 用户管理', async ({ page }) => {
    await page.goto('/dashboard')

    // 点击系统管理菜单
    await page.click('.el-sub-menu:has-text("系统管理")')
    await page.waitForTimeout(300)

    // 点击用户管理
    await page.click('.el-menu-item:has-text("用户管理")')
    await expect(page).toHaveURL(/\/system\/user/)
  })

  test('菜单导航 - 角色管理', async ({ page }) => {
    await page.goto('/dashboard')

    await page.click('.el-sub-menu:has-text("系统管理")')
    await page.waitForTimeout(300)

    await page.click('.el-menu-item:has-text("角色管理")')
    await expect(page).toHaveURL(/\/system\/role/)
  })

  test('菜单导航 - 权限管理', async ({ page }) => {
    await page.goto('/dashboard')

    await page.click('.el-sub-menu:has-text("系统管理")')
    await page.waitForTimeout(300)

    await page.click('.el-menu-item:has-text("权限管理")')
    await expect(page).toHaveURL(/\/system\/permission/)
  })
})

test.describe('页面响应', () => {
  test('404 页面', async ({ page }) => {
    await page.goto('/non-existent-page')
    await expect(page).toHaveURL(/\/non-existent-page/)
    // 验证显示 404 内容
    const content = await page.textContent('body')
    expect(content).toContain('404')
  })
})
