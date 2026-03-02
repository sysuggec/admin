<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :model="searchForm" inline>
        <el-form-item label="用户名">
          <el-input v-model="searchForm.username" placeholder="请输入用户名" clearable />
        </el-form-item>
        <el-form-item label="邮箱">
          <el-input v-model="searchForm.email" placeholder="请输入邮箱" clearable />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择" clearable>
            <el-option label="启用" :value="1" />
            <el-option label="禁用" :value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- 表格 -->
    <div class="table-container">
      <div class="table-header">
        <el-button v-permission="'user:create'" type="primary" @click="handleAdd">
          新增用户
        </el-button>
      </div>

      <el-table v-loading="loading" :data="tableData" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="username" label="用户名" width="150" />
        <el-table-column prop="email" label="邮箱" width="200" />
        <el-table-column prop="phone" label="手机号" width="150" />
        <el-table-column label="角色" min-width="150">
          <template #default="{ row }">
            <el-tag
              v-for="role in row.roles"
              :key="role.id"
              style="margin-right: 5px"
            >
              {{ role.display_name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-switch
              v-permission="'user:edit'"
              v-model="row.status"
              :active-value="1"
              :inactive-value="0"
              @change="handleStatusChange(row)"
            />
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button v-permission="'user:edit'" type="primary" link @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button
              v-permission="'user:delete'"
              type="danger"
              link
              :disabled="row.id === currentUserId"
              @click="handleDelete(row)"
            >
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

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
      @close="resetForm"
    >
      <el-form
        ref="formRef"
        :model="form"
        :rules="rules"
        label-width="80px"
      >
        <el-form-item label="用户名" prop="username">
          <el-input v-model="form.username" :disabled="isEdit" placeholder="请输入用户名" />
        </el-form-item>
        <el-form-item v-if="!isEdit" label="密码" prop="password">
          <el-input v-model="form.password" type="password" show-password placeholder="请输入密码" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="form.email" placeholder="请输入邮箱" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="form.phone" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="角色" prop="role_ids">
          <el-select v-model="form.role_ids" multiple placeholder="请选择角色" style="width: 100%">
            <el-option
              v-for="role in roleOptions"
              :key="role.id"
              :label="role.display_name"
              :value="role.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-switch v-model="form.status" :active-value="1" :inactive-value="0" />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="submitLoading" @click="handleSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { getUserList, createUser, updateUser, deleteUser, toggleUserStatus } from '@/api/user'
import { getAllRoles } from '@/api/role'
import { useUserStore } from '@/stores/user'
import type { User } from '@/types'

const userStore = useUserStore()
const currentUserId = computed(() => userStore.userInfo?.id)

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const tableData = ref<User[]>([])
const roleOptions = ref<{ id: number; name: string; display_name: string }[]>([])

const formRef = ref<FormInstance>()

const searchForm = reactive({
  username: '',
  email: '',
  status: undefined as number | undefined,
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
})

const form = reactive({
  id: 0,
  username: '',
  password: '',
  email: '',
  phone: '',
  role_ids: [] as number[],
  status: 1,
})

const rules: FormRules = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 2, max: 50, message: '用户名长度在 2 到 50 个字符', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于 6 个字符', trigger: 'blur' },
  ],
  email: [
    { type: 'email', message: '请输入正确的邮箱地址', trigger: 'blur' },
  ],
  role_ids: [
    { required: true, message: '请选择角色', trigger: 'change' },
  ],
}

const dialogTitle = computed(() => (isEdit.value ? '编辑用户' : '新增用户'))

// 格式化日期
function formatDate(date: string) {
  return new Date(date).toLocaleString('zh-CN')
}

// 获取数据
async function fetchData() {
  loading.value = true
  try {
    const { data } = await getUserList({
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

// 获取角色选项
async function fetchRoleOptions() {
  try {
    const { data } = await getAllRoles()
    roleOptions.value = data.data
  } catch {
    // 错误已在拦截器中处理
  }
}

// 搜索
function handleSearch() {
  pagination.page = 1
  fetchData()
}

// 重置
function handleReset() {
  searchForm.username = ''
  searchForm.email = ''
  searchForm.status = undefined
  handleSearch()
}

// 新增
function handleAdd() {
  isEdit.value = false
  form.id = 0
  form.username = ''
  form.password = ''
  form.email = ''
  form.phone = ''
  form.role_ids = []
  form.status = 1
  dialogVisible.value = true
}

// 编辑
function handleEdit(row: User) {
  isEdit.value = true
  form.id = row.id
  form.username = row.username
  form.password = ''
  form.email = row.email || ''
  form.phone = row.phone || ''
  form.role_ids = row.roles?.map(r => r.id) || []
  form.status = row.status
  dialogVisible.value = true
}

// 删除
async function handleDelete(row: User) {
  try {
    await ElMessageBox.confirm('确定要删除该用户吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteUser(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch {
    // 取消删除
  }
}

// 切换状态
async function handleStatusChange(row: User) {
  try {
    await toggleUserStatus(row.id)
    ElMessage.success('状态切换成功')
  } catch {
    // 恢复原状态
    row.status = row.status === 1 ? 0 : 1
  }
}

// 提交表单
async function handleSubmit() {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    submitLoading.value = true
    try {
      if (isEdit.value) {
        await updateUser(form.id, {
          username: form.username,
          email: form.email || null,
          phone: form.phone || null,
          role_ids: form.role_ids,
          status: form.status,
        })
        ElMessage.success('更新成功')
      } else {
        await createUser({
          username: form.username,
          password: form.password,
          email: form.email || null,
          phone: form.phone || null,
          role_ids: form.role_ids,
          status: form.status,
        })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      fetchData()
    } catch {
      // 错误已在拦截器中处理
    } finally {
      submitLoading.value = false
    }
  })
}

// 重置表单
function resetForm() {
  formRef.value?.resetFields()
}

onMounted(() => {
  fetchData()
  fetchRoleOptions()
})
</script>

<style scoped>
.table-header {
  margin-bottom: 16px;
}
</style>
