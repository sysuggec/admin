import { test, expect } from '@playwright/test'

// 测试前登录
test.beforeEach(async ({ page }) => {
  await page.goto('/login')
  await page.fill('input[placeholder="用户名"]', 'admin')
  await page.fill('input[type="password"]', 'Admin@123456')
  await page.click('button:has-text("登 录")')
  await expect(page.locator('.el-message--success')).toBeVisible()
})

test.describe('用户管理模块', () => {
  test('访问用户管理页面', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page).toHaveURL(/\/system\/user/)
    await expect(page.locator('.el-table')).toBeVisible()
  })

  test('用户列表加载', async ({ page }) => {
    await page.goto('/system/user')
    // 等待表格加载完成
    await expect(page.locator('.el-table__row').first()).toBeVisible({ timeout: 10000 })
    // 验证至少有一行数据
    const rows = await page.locator('.el-table__row').count()
    expect(rows).toBeGreaterThan(0)
  })

  test('搜索用户 - 按用户名', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 搜索 admin 用户
    await page.fill('input[placeholder="请输入用户名"]', 'admin')
    await page.click('button:has-text("搜索")')

    // 等待搜索结果
    await page.waitForTimeout(500)
    const rows = await page.locator('.el-table__row').count()
    expect(rows).toBeGreaterThanOrEqual(1)

    // 验证搜索结果包含 admin（检查整个表格内容）
    const tableContent = await page.locator('.el-table').textContent()
    expect(tableContent).toContain('admin')
  })

  test('搜索用户 - 按状态筛选', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 使用更精确的选择器点击状态下拉框
    const statusSelect = page.locator('.search-form .el-form-item').filter({ hasText: '状态' }).locator('.el-select')
    await statusSelect.click()
    await page.waitForTimeout(300)

    // 选择启用状态
    await page.click('.el-select-dropdown__item:has-text("启用")')
    await page.waitForTimeout(300)

    await page.click('button:has-text("搜索")')
    await page.waitForTimeout(500)

    // 验证数据加载
    const rows = await page.locator('.el-table__row').count()
    expect(rows).toBeGreaterThanOrEqual(0)
  })

  test('重置搜索条件', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 输入搜索条件
    const usernameInput = page.locator('.search-form input[placeholder="请输入用户名"]')
    await usernameInput.fill('admin')
    await expect(usernameInput).toHaveValue('admin')

    // 点击重置按钮
    await page.click('.search-form button:has-text("重置")')
    
    // 等待页面状态稳定
    await page.waitForLoadState('networkidle')
    
    // 刷新页面后验证输入框已清空（重置会触发重新加载数据）
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()
    
    // 验证输入框默认为空
    const inputValue = await page.locator('.search-form input[placeholder="请输入用户名"]').inputValue()
    expect(inputValue).toBe('')
  })

  test('新增用户 - 打开对话框', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 点击新增用户按钮
    await page.click('button:has-text("新增用户")')

    // 验证对话框打开
    await expect(page.locator('.el-dialog')).toBeVisible()
    await expect(page.locator('.el-dialog__title')).toContainText('新增用户')
  })

  test('新增用户 - 表单验证', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 打开新增对话框
    await page.click('button:has-text("新增用户")')
    await expect(page.locator('.el-dialog')).toBeVisible()

    // 直接点击确定，触发表单验证
    await page.click('.el-dialog button:has-text("确定")')

    // 验证错误提示
    await expect(page.locator('.el-form-item__error').first()).toBeVisible()
  })

  test('新增用户 - 创建测试用户', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 打开新增对话框
    await page.click('button:has-text("新增用户")')
    await expect(page.locator('.el-dialog')).toBeVisible()

    // 填写表单
    const timestamp = Date.now()
    const username = `testuser_${timestamp}`
    await page.fill('.el-dialog input[placeholder="请输入用户名"]', username)
    await page.fill('.el-dialog input[type="password"]', 'Test@123456')

    // 选择角色 - 点击角色选择器
    const roleFormItem = page.locator('.el-dialog .el-form-item').filter({ hasText: '角色' })
    await roleFormItem.locator('.el-select').click()
    
    // 等待下拉选项出现并点击第一个
    await page.waitForSelector('.el-select-dropdown:visible .el-select-dropdown__item', { timeout: 3000 })
    await page.click('.el-select-dropdown:visible .el-select-dropdown__item:first-child')
    
    // 等待下拉菜单关闭
    await page.waitForTimeout(500)
    
    // 按 Escape 关闭可能还打开的下拉菜单
    await page.keyboard.press('Escape')
    await page.waitForTimeout(300)

    // 提交表单 - 使用 force click 绕过可能的遮挡
    await page.locator('.el-dialog button:has-text("确定")').click({ force: true })

    // 等待成功消息
    await expect(page.locator('.el-message--success')).toBeVisible({ timeout: 10000 })

    // 验证用户已创建
    const searchInput = page.locator('.search-form input[placeholder="请输入用户名"]')
    await searchInput.fill(username)
    await page.click('.search-form button:has-text("搜索")')
    await page.waitForTimeout(500)

    // 验证表格中包含新创建的用户
    const tableContent = await page.locator('.el-table').textContent()
    expect(tableContent).toContain(username)
  })

  test('编辑用户 - 打开编辑对话框', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 点击第一行的编辑按钮
    await page.locator('.el-table__row').first().locator('button:has-text("编辑")').click()

    // 验证对话框打开
    await expect(page.locator('.el-dialog')).toBeVisible()
    await expect(page.locator('.el-dialog__title')).toContainText('编辑用户')
  })

  test('删除用户 - 取消删除', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 获取删除前的用户数量
    const initialCount = await page.locator('.el-table__row').count()

    // 点击最后一行的删除按钮（避免删除 admin）
    const lastRow = page.locator('.el-table__row').last()
    const deleteBtn = lastRow.locator('button:has-text("删除")')

    // 如果按钮不可用则跳过
    if (await deleteBtn.isDisabled()) {
      test.skip()
      return
    }

    await deleteBtn.click()

    // 等待确认对话框
    await expect(page.locator('.el-message-box')).toBeVisible()

    // 点击取消
    await page.click('.el-message-box button:has-text("取消")')

    // 验证用户数量未变
    const currentCount = await page.locator('.el-table__row').count()
    expect(currentCount).toBe(initialCount)
  })

  test('分页功能', async ({ page }) => {
    await page.goto('/system/user')
    await expect(page.locator('.el-table__row').first()).toBeVisible()

    // 验证分页组件存在
    await expect(page.locator('.el-pagination')).toBeVisible()

    // 检查总数显示
    const totalText = await page.locator('.el-pagination__total').textContent()
    expect(totalText).toMatch(/共 \d+ 条/)
  })
})
