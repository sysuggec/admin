<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :model="searchForm" inline>
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="请输入用户名" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option label="成功" :value="1" />
            <el-option label="失败" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="登录时间">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="至"
            start-placeholder="开始日期"
            end-placeholder="结束日期"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
          <el-button v-permission="'login-log:export'" @click="handleExport">导出</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 表格 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="tableData" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="120" />
        <el-table-column prop="ip" label="IP地址" width="140" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="message" label="消息" min-width="150" show-overflow-tooltip />
        <el-table-column prop="login_time" label="登录时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.login_time) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button v-permission="'login-log:detail'" type="primary" link @click="handleDetail(row)">
              详情
            </el-button>
            <el-button v-permission="'login-log:delete'" type="danger" link @click="handleDelete(row)">
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.pageSize"
          :total="pagination.total"
          :page-sizes="[10, 20, 50, 100]"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="fetchData"
          @current-change="fetchData"
        />
      </div>
    </div>

    <!-- 详情对话框 -->
    <el-dialog v-model="detailDialogVisible" title="登录日志详情" width="600px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="ID">{{ detailData.id }}</el-descriptions-item>
        <el-descriptions-item label="用户ID">{{ detailData.user_id || '-' }}</el-descriptions-item>
        <el-descriptions-item label="用户名">{{ detailData.username }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="detailData.status === 1 ? 'success' : 'danger'" size="small">
            {{ detailData.status === 1 ? '成功' : '失败' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ detailData.ip }}</el-descriptions-item>
        <el-descriptions-item label="登录时间">
          {{ formatDate(detailData.login_time) }}
        </el-descriptions-item>
        <el-descriptions-item label="消息" :span="2">
          {{ detailData.message || '-' }}
        </el-descriptions-item>
        <el-descriptions-item label="浏览器" :span="2">
          {{ detailData.user_agent || '-' }}
        </el-descriptions-item>
      </el-descriptions>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getLoginLogList,
  getLoginLogDetail,
  deleteLoginLog,
  exportLoginLogs,
} from '@/api/login-log'
import type { LoginLog } from '@/types'

const loading = ref(false)
const detailDialogVisible = ref(false)
const tableData = ref<LoginLog[]>([])
const detailData = ref<LoginLog>({} as LoginLog)

const dateRange = ref<string[]>([])

const searchForm = reactive({
  username: '',
  status: undefined as number | undefined,
  start_time: undefined as string | undefined,
  end_time: undefined as string | undefined,
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
})

// 监听日期范围变化
watch(dateRange, (val) => {
  if (val && val.length === 2) {
    searchForm.start_time = val[0]
    searchForm.end_time = val[1]
  } else {
    searchForm.start_time = undefined
    searchForm.end_time = undefined
  }
})

function formatDate(date: string) {
  if (!date) return '-'
  return new Date(date).toLocaleString('zh-CN')
}

async function fetchData() {
  loading.value = true
  try {
    const response = await getLoginLogList({
      page: pagination.page,
      page_size: pagination.pageSize,
      ...searchForm,
    })
    tableData.value = response.data.data.list
    pagination.total = response.data.data.total
  } catch {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  pagination.page = 1
  fetchData()
}

function handleReset() {
  searchForm.username = ''
  searchForm.status = undefined
  searchForm.start_time = undefined
  searchForm.end_time = undefined
  dateRange.value = []
  handleSearch()
}

async function handleDetail(row: LoginLog) {
  try {
    const response = await getLoginLogDetail(row.id)
    detailData.value = response.data.data
    detailDialogVisible.value = true
  } catch {
    // 错误已在拦截器中处理
  }
}

async function handleDelete(row: LoginLog) {
  try {
    await ElMessageBox.confirm('确定要删除该日志吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteLoginLog(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch {
    // 取消删除
  }
}

async function handleExport() {
  try {
    const response = await exportLoginLogs({
      username: searchForm.username,
      status: searchForm.status,
      start_time: searchForm.start_time,
      end_time: searchForm.end_time,
    })

    const exportData = response.data.data
    if (!exportData || exportData.length === 0) {
      ElMessage.warning('没有数据可导出')
      return
    }

    // 创建 CSV 内容
    const headers = Object.keys(exportData[0] as Record<string, unknown>)
    const csvContent = [
      headers.join(','),
      ...exportData.map((row: Record<string, unknown>) => headers.map(h => `"${row[h] || ''}"`).join(',')),
    ].join('\n')

    // 下载文件
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `login-log-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)

    ElMessage.success('导出成功')
  } catch {
    // 错误已在拦截器中处理
  }
}

onMounted(() => {
  fetchData()
})
</script>
