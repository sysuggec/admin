<template>
  <div class="page-container">
    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :model="searchForm" inline>
        <el-form-item label="权限名称">
          <el-input v-model="searchForm.display_name" placeholder="请输入权限名称" clearable />
        </el-form-item>
        <el-form-item label="类型">
          <el-select v-model="searchForm.type" placeholder="请选择" clearable>
            <el-option label="菜单" value="menu" />
            <el-option label="按钮" value="button" />
            <el-option label="接口" value="api" />
          </el-select>
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
        <el-button v-permission="'permission:create'" type="primary" @click="handleAdd">
          新增权限
        </el-button>
      </div>

      <el-table
        v-loading="loading"
        :data="tableData"
        row-key="id"
        stripe
        default-expand-all
      >
        <el-table-column prop="display_name" label="权限名称" min-width="200" />
        <el-table-column prop="name" label="权限标识" width="180" />
        <el-table-column label="类型" width="100">
          <template #default="{ row }">
            <el-tag :type="getTypeTag(row.type)">
              {{ getTypeName(row.type) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="path" label="路由路径" width="180" />
        <el-table-column prop="icon" label="图标" width="100" />
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="row.status === 1 ? 'success' : 'danger'">
              {{ row.status === 1 ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <el-button v-permission="'permission:edit'" type="primary" link @click="handleEdit(row)">
              编辑
            </el-button>
            <el-button
              v-permission="'permission:delete'"
              type="danger"
              link
              @click="handleDelete(row)"
            >
              删除
            </el-button>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="500px"
      @close="resetForm"
    >
      <el-form ref="formRef" :model="form" :rules="rules" label-width="80px">
        <el-form-item label="权限类型" prop="type">
          <el-select v-model="form.type" placeholder="请选择类型" style="width: 100%">
            <el-option label="菜单" value="menu" />
            <el-option label="按钮" value="button" />
            <el-option label="接口" value="api" />
          </el-select>
        </el-form-item>
        <el-form-item label="父级权限" prop="parent_id">
          <el-tree-select
            v-model="form.parent_id"
            :data="permissionTree"
            :props="{ label: 'display_name', value: 'id', children: 'children' }"
            placeholder="请选择父级权限"
            check-strictly
            clearable
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="权限标识" prop="name">
          <el-input v-model="form.name" placeholder="如: user:create" />
        </el-form-item>
        <el-form-item label="权限名称" prop="display_name">
          <el-input v-model="form.display_name" placeholder="请输入权限名称" />
        </el-form-item>
        <el-form-item v-if="form.type === 'menu'" label="路由路径" prop="path">
          <el-input v-model="form.path" placeholder="如: /system/user" />
        </el-form-item>
        <el-form-item v-if="form.type === 'menu'" label="图标" prop="icon">
          <el-input v-model="form.icon" placeholder="请输入图标名称" />
        </el-form-item>
        <el-form-item v-if="form.type === 'api'" label="API路径" prop="api_path">
          <el-input v-model="form.api_path" placeholder="如: /api/users" />
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
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox, type FormInstance, type FormRules } from 'element-plus'
import { getPermissionList, createPermission, updatePermission, deletePermission } from '@/api/permission'
import type { Permission } from '@/types'

const loading = ref(false)
const submitLoading = ref(false)
const dialogVisible = ref(false)
const isEdit = ref(false)
const tableData = ref<Permission[]>([])
const permissionTree = ref<Permission[]>([])

const formRef = ref<FormInstance>()

const searchForm = reactive({
  display_name: '',
  type: undefined as string | undefined,
  status: undefined as number | undefined,
})

const form = reactive({
  id: 0,
  name: '',
  display_name: '',
  type: 'menu' as 'menu' | 'button' | 'api',
  parent_id: 0,
  path: '',
  api_path: '',
  icon: '',
  sort_order: 0,
  status: 1,
})

const rules: FormRules = {
  name: [
    { required: true, message: '请输入权限标识', trigger: 'blur' },
  ],
  display_name: [
    { required: true, message: '请输入权限名称', trigger: 'blur' },
  ],
  type: [
    { required: true, message: '请选择权限类型', trigger: 'change' },
  ],
}

const dialogTitle = computed(() => (isEdit.value ? '编辑权限' : '新增权限'))

function getTypeTag(type: string) {
  const map: Record<string, string> = {
    menu: 'primary',
    button: 'success',
    api: 'warning',
  }
  return map[type] || ''
}

function getTypeName(type: string) {
  const map: Record<string, string> = {
    menu: '菜单',
    button: '按钮',
    api: '接口',
  }
  return map[type] || type
}

async function fetchData() {
  loading.value = true
  try {
    const { data } = await getPermissionList({ ...searchForm, tree: true })
    tableData.value = data as any
  } catch {
    // 错误已在拦截器中处理
  } finally {
    loading.value = false
  }
}

async function fetchPermissionTree() {
  try {
    const { data } = await getPermissionList({ status: 1, tree: true })
    permissionTree.value = [
      { id: 0, display_name: '顶级权限', children: data as any } as Permission,
    ]
  } catch {
    // 错误已在拦截器中处理
  }
}

function handleSearch() {
  fetchData()
}

function handleReset() {
  searchForm.display_name = ''
  searchForm.type = undefined
  searchForm.status = undefined
  handleSearch()
}

function handleAdd() {
  isEdit.value = false
  form.id = 0
  form.name = ''
  form.display_name = ''
  form.type = 'menu'
  form.parent_id = 0
  form.path = ''
  form.api_path = ''
  form.icon = ''
  form.sort_order = 0
  form.status = 1
  dialogVisible.value = true
}

function handleEdit(row: Permission) {
  isEdit.value = true
  form.id = row.id
  form.name = row.name
  form.display_name = row.display_name
  form.type = row.type
  form.parent_id = row.parent_id
  form.path = row.path || ''
  form.api_path = row.api_path || ''
  form.icon = row.icon || ''
  form.sort_order = row.sort_order
  form.status = row.status
  dialogVisible.value = true
}

async function handleDelete(row: Permission) {
  try {
    await ElMessageBox.confirm('确定要删除该权限吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    })
    await deletePermission(row.id)
    ElMessage.success('删除成功')
    fetchData()
  } catch {
    // 取消删除
  }
}

async function handleSubmit() {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    submitLoading.value = true
    try {
      if (isEdit.value) {
        await updatePermission(form.id, {
          name: form.name,
          display_name: form.display_name,
          type: form.type,
          parent_id: form.parent_id,
          path: form.path || null,
          api_path: form.api_path || null,
          icon: form.icon || null,
          sort_order: form.sort_order,
          status: form.status,
        })
        ElMessage.success('更新成功')
      } else {
        await createPermission({
          name: form.name,
          display_name: form.display_name,
          type: form.type,
          parent_id: form.parent_id,
          path: form.path || null,
          api_path: form.api_path || null,
          icon: form.icon || null,
          sort_order: form.sort_order,
          status: form.status,
        })
        ElMessage.success('创建成功')
      }
      dialogVisible.value = false
      fetchData()
      fetchPermissionTree()
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
