import { test, expect } from '@playwright/test'

test.describe('登录测试', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login')
  })

  test('显示登录页面', async ({ page }) => {
    await expect(page.locator('h1')).toContainText('AdminSystem')
    await expect(page.locator('.login-header p')).toContainText('运营后台管理系统')
  })

  test('表单验证 - 空用户名', async ({ page }) => {
    await page.fill('input[type="password"]', 'Admin@123456')
    await page.click('button:has-text("登 录")')
    await expect(page.locator('.el-form-item__error')).toContainText('请输入用户名')
  })

  test('表单验证 - 空密码', async ({ page }) => {
    await page.fill('input[placeholder="用户名"]', 'admin')
    await page.click('button:has-text("登 录")')
    await expect(page.locator('.el-form-item__error')).toContainText('请输入密码')
  })

  test('表单验证 - 密码长度不足', async ({ page }) => {
    await page.fill('input[placeholder="用户名"]', 'admin')
    await page.fill('input[type="password"]', '12345')
    await page.click('button:has-text("登 录")')
    await expect(page.locator('.el-form-item__error')).toContainText('密码长度不能少于6位')
  })

  test('登录失败 - 错误的用户名', async ({ page }) => {
    await page.fill('input[placeholder="用户名"]', 'wronguser')
    await page.fill('input[type="password"]', 'Admin@123456')
    await page.click('button:has-text("登 录")')
    await expect(page.locator('.el-message--error')).toBeVisible()
  })

  test('登录失败 - 错误的密码', async ({ page }) => {
    await page.fill('input[placeholder="用户名"]', 'admin')
    await page.fill('input[type="password"]', 'WrongPassword')
    await page.click('button:has-text("登 录")')
    await expect(page.locator('.el-message--error')).toBeVisible()
  })

  test('登录成功', async ({ page }) => {
    await page.fill('input[placeholder="用户名"]', 'admin')
    await page.fill('input[type="password"]', 'Admin@123456')
    await page.click('button:has-text("登 录")')

    // 等待登录成功消息
    await expect(page.locator('.el-message--success')).toContainText('登录成功')

    // 验证跳转到仪表盘
    await expect(page).toHaveURL(/\/dashboard/)
  })

  test('登录后可访问受保护页面', async ({ page }) => {
    // 先登录
    await page.fill('input[placeholder="用户名"]', 'admin')
    await page.fill('input[type="password"]', 'Admin@123456')
    await page.click('button:has-text("登 录")')
    await expect(page).toHaveURL(/\/dashboard/)

    // 访问用户管理页面
    await page.goto('/system/user')
    await expect(page).toHaveURL(/\/system\/user/)
  })
})
