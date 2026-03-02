<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :model="searchForm" inline>
        <el-form-item label="操作用户">
          <el-input v-model="searchForm.username" placeholder="请输入用户名" clearable />
        </el-form-item>
        <el-form-item label="操作模块">
          <el-select v-model="searchForm.module" placeholder="请选择" clearable>
            <el-option v-for="item in modules" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item>
        <el-form-item label="操作类型">
          <el-select v-model="searchForm.action" placeholder="请选择" clearable>
            <el-option v-for="item in actions" :key="item" :label="item" :value="item" />
          </el-select>
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option label="成功" :value="1" />
            <el-option label="失败" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item label="操作时间">
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
          <el-button v-permission="'log:export'" @click="handleExport">导出</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 表格 -->
    <div class="table-container">
      <el-table v-loading="loading" :data="tableData" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="操作用户" width="120" />
        <el-table-column prop="module" label="操作模块" width="100" />
        <el-table-column prop="title" label="操作标题" min-width="150" />
        <el-table-column prop="method" label="请求方法" width="80">
          <template #default="{ row }">
            <el-tag :type="getMethodTag(row.method)" size="small">
              {{ row.method }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="ip" label="IP地址" width="130" />
        <el-table-column label="状态" width="80">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'" size="small">
              {{ row.status === 1 ? '成功' : '失败' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="duration" label="耗时(ms)" width="100" />
        <el-table-column prop="created_at" label="操作时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="120" fixed="right">
          <template #default="{ row }">
            <el-button v-permission="'log:detail'" type="primary" link @click="handleDetail(row)">
              详情
            </el-button>
            <el-button v-permission="'log:delete'" type="danger" link @click="handleDelete(row)">
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
    <el-dialog v-model="detailDialogVisible" title="操作日志详情" width="700px">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="ID">{{ detailData.id }}</el-descriptions-item>
        <el-descriptions-item label="操作用户">{{ detailData.username }}</el-descriptions-item>
        <el-descriptions-item label="操作模块">{{ detailData.module }}</el-descriptions-item>
        <el-descriptions-item label="操作类型">{{ detailData.action }}</el-descriptions-item>
        <el-descriptions-item label="操作标题">{{ detailData.title }}</el-descriptions-item>
        <el-descriptions-item label="请求方法">{{ detailData.method }}</el-descriptions-item>
        <el-descriptions-item label="IP地址">{{ detailData.ip }}</el-descriptions-item>
        <el-descriptions-item label="状态">
          {{ detailData.status === 1 ? '成功' : '失败' }}
        </el-descriptions-item>
        <el-descriptions-item label="耗时">{{ detailData.duration }}ms</el-descriptions-item>
        <el-descriptions-item label="操作时间">
          {{ formatDate(detailData.created_at) }}
        </el-descriptions-item>
        <el-descriptions-item label="请求URL" :span="2">
          {{ detailData.url }}
        </el-descriptions-item>
        <el-descriptions-item label="浏览器" :span="2">
          {{ detailData.user_agent }}
        </el-descriptions-item>
      </el-descriptions>

      <div v-if="detailData.params" style="margin-top: 16px">
        <div class="detail-label">请求参数</div>
        <el-input
          :model-value="formatJson(detailData.params)"
          type="textarea"
          :rows="5"
          readonly
        />
      </div>

      <div v-if="detailData.response" style="margin-top: 16px">
        <div class="detail-label">响应结果</div>
        <el-input
          :model-value="formatJson(detailData.response)"
          type="textarea"
          :rows="5"
          readonly
        />
      </div>

      <div v-if="detailData.error_msg" style="margin-top: 16px">
        <div class="detail-label">错误信息</div>
        <el-input
          :model-value="detailData.error_msg"
          type="textarea"
          :rows="3"
          readonly
        />
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getOperationLogList,
  getOperationLogDetail,
  deleteOperationLog,
  exportOperationLogs,
  getOperationModules,
  getOperationActions,
} from '@/api/operation-log'
import type { OperationLog } from '@/types'

const loading = ref(false)
const detailDialogVisible = ref(false)
const tableData = ref<OperationLog[]>([])
const modules = ref<string[]>([])
const actions = ref<string[]>([])
const detailData = ref<OperationLog>({} as OperationLog)

const dateRange = ref<string[]>([])

const searchForm = reactive({
  username: '',
  module: undefined as string | undefined,
  action: undefined as string | undefined,
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
  return new Date(date).toLocaleString('zh-CN')
}

function getMethodTag(method: string) {
  const map: Record<string, string> = {
    GET: 'success',
    POST: 'primary',
    PUT: 'warning',
    DELETE: 'danger',
  }
  return map[method] || ''
}

function formatJson(str: string | null) {
  if (!str) return ''
  try {
    return JSON.stringify(JSON.parse(str), null, 2)
  } catch {
    return str
  }
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await getOperationLogList({
      page: pagination.page,
      page_size: pagination.pageSize,
      ...searchForm,
    })
    tableData.value = data.data.list
    pagination.total = data.data.total
  } catch {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}

async function fetchModules() {
  try {
    const { data } = await getOperationModules()
    modules.value = data.data
  } catch {
    // 错误已在拦截器中处理
  }
}

async function fetchActions() {
  try {
    const { data } = await getOperationActions()
    actions.value = data.data
  } catch {
    // 错误已在拦截器中处理
  }
}

function handleSearch() {
  pagination.page = 1
  fetchData()
}

function handleReset() {
  searchForm.username = ''
  searchForm.module = undefined
  searchForm.action = undefined
  searchForm.status = undefined
  searchForm.start_time = undefined
  searchForm.end_time = undefined
  dateRange.value = []
  handleSearch()
}

async function handleDetail(row: OperationLog) {
  try {
    const { data } = await getOperationLogDetail(row.id)
    detailData.value = data.data
    detailDialogVisible.value = true
  } catch {
    // 错误已在拦截器中处理
  }
}

async function handleDelete(row: OperationLog) {
  try {
    await ElMessageBox.confirm('确定要删除该日志吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteOperationLog(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch {
    // 取消删除
  }
}

async function handleExport() {
  try {
    const { data } = await exportOperationLogs({
      username: searchForm.username,
      module: searchForm.module,
      action: searchForm.action,
      status: searchForm.status,
      start_time: searchForm.start_time,
      end_time: searchForm.end_time,
    })

    const exportData = data.data
    // 创建 CSV 内容
    const headers = Object.keys(exportData[0] || {})
    const csvContent = [
      headers.join(','),
      ...exportData.map((row: any) => headers.map(h => `"${row[h] || ''}"`).join(',')),
    ].join('\n')

    // 下载文件
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `operation-log-${new Date().toISOString().slice(0, 10)}.csv`
    link.click()
    URL.revokeObjectURL(url)

    ElMessage.success('导出成功')
  } catch {
    // 错误已在拦截器中处理
  }
}

onMounted(() => {
  fetchData()
  fetchModules()
  fetchActions()
})
</script>

<style scoped>
.detail-label {
  font-weight: bold;
  margin-bottom: 8px;
  color: #303133;
}
</style>
