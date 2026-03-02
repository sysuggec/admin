<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :model="searchForm" inline>
        <el-form-item label="角色名称">
          <el-input v-model="searchForm.display_name" placeholder="请输入角色名称" clearable />
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
        <el-button v-permission="'role:create'" type="primary" @click="handleAdd">
          新增角色
        </el-button>
      </div>

      <el-table v-loading="loading" :data="tableData" stripe>
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="角色标识" width="150" />
        <el-table-column prop="display_name" label="角色名称" width="150" />
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="250" fixed="right">
          <template #default="{ row }">
            <el-button v-permission="'role:edit'" type="primary" link @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button v-permission="'role:edit'" type="warning" link @click="handlePermission(row)">
              权限
            </el-button>
            <el-button
              v-permission="'role:delete'"
              type="danger"
              link
              :disabled="row.name === 'super_admin'"
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
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="角色标识" prop="name">
          <el-input v-model="form.name" :disabled="isEdit" placeholder="请输入角色标识" />
        </el-form-item>
        <el-form-item label="角色名称" prop="display_name">
          <el-input v-model="form.display_name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="form.description" type="textarea" placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="form.sort_order" :min="0" />
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

    <!-- 权限分配对话框 -->
    <el-dialog v-model="permissionDialogVisible" title="分配权限" width="500px">
      <el-tree
        ref="permissionTreeRef"
        :data="permissionTree"
        :props="{ label: 'display_name', children: 'children' }"
        show-checkbox
        node-key="id"
        default-expand-all
      />
      <template #footer>
        <el-button @click="permissionDialogVisible = false">取消</el-button>
        <el-button type="primary" :loading="permissionLoading" @click="handlePermissionSubmit">
          确定
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { getRoleList, createRole, updateRole, deleteRole, getRolePermissions, syncRolePermissions } from '@/api/role'
import { getAllPermissions } from '@/api/permission'
import type { Role, Permission } from '@/types'

const loading = ref(false)
const submitLoading = ref(false)
const permissionLoading = ref(false)
const dialogVisible = ref(false)
const permissionDialogVisible = ref(false)
const isEdit = ref(false)
const tableData = ref<Role[]>([])
const permissionTree = ref<Permission[]>([])

const formRef = ref<FormInstance>()
const permissionTreeRef = ref()

const currentRoleId = ref(0)

const searchForm = reactive({
  display_name: '',
  status: undefined as number | undefined,
})

const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
})

const form = reactive({
  id: 0,
  name: '',
  display_name: '',
  description: '',
  sort_order: 0,
  status: 1,
})

const rules: FormRules = {
  name: [
    { required: true, message: '请输入角色标识', trigger: 'blur' },
    { min: 2, max: 50, message: '角色标识长度在 2 到 50 个字符', trigger: 'blur' },
  ],
  display_name: [
    { required: true, message: '请输入角色名称', trigger: 'blur' },
  ],
}

const dialogTitle = computed(() => (isEdit.value ? '编辑角色' : '新增角色'))

function formatDate(date: string) {
  return new Date(date).toLocaleString('zh-CN')
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await getRoleList({
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

async function fetchPermissionTree() {
  try {
    const { data } = await getAllPermissions()
    permissionTree.value = data.data
  } catch {
    // 错误已在拦截器中处理
  }
}

function handleSearch() {
  pagination.page = 1
  fetchData()
}

function handleReset() {
  searchForm.display_name = ''
  searchForm.status = undefined
  handleSearch()
}

function handleAdd() {
  isEdit.value = false
  form.id = 0
  form.name = ''
  form.display_name = ''
  form.description = ''
  form.sort_order = 0
  form.status = 1
  dialogVisible.value = true
}

function handleEdit(row: Role) {
  isEdit.value = true
  form.id = row.id
  form.name = row.name
  form.display_name = row.display_name
  form.description = row.description || ''
  form.sort_order = row.sort_order
  form.status = row.status
  dialogVisible.value = true
}

async function handleDelete(row: Role) {
  try {
    await ElMessageBox.confirm('确定要删除该角色吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deleteRole(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch {
    // 取消删除
  }
}

async function handlePermission(row: Role) {
  currentRoleId.value = row.id
  permissionDialogVisible.value = true

  // 获取角色当前权限
  try {
    const { data } = await getRolePermissions(row.id)
    // 设置选中的权限节点
    setTimeout(() => {
      permissionTreeRef.value?.setCheckedKeys(data.data)
    }, 0)
  } catch {
    // 错误已在拦截器中处理
  }
}

async function handlePermissionSubmit() {
  permissionLoading.value = true
  try {
    const checkedKeys = permissionTreeRef.value?.getCheckedKeys() || []
    await syncRolePermissions(currentRoleId.value, checkedKeys)
    ElMessage.success('权限分配成功')
    permissionDialogVisible.value = false
  } catch {
    // 错误已在拦截器中处理
  } finally {
    permissionLoading.value = false
  }
}

async function handleSubmit() {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    submitLoading.value = true
    try {
      if (isEdit.value) {
        await updateRole(form.id, {
          display_name: form.display_name,
          description: form.description,
          sort_order: form.sort_order,
          status: form.status,
        })
        ElMessage.success('更新成功')
      } else {
        await createRole({
          name: form.name,
          display_name: form.display_name,
          description: form.description,
          sort_order: form.sort_order,
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

function resetForm() {
  formRef.value?.resetFields()
}

onMounted(() => {
  fetchData()
  fetchPermissionTree()
})
</script>

<style scoped>
.table-header {
  margin-bottom: 16px;
}
</style>
