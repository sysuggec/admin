import request from './request'
import type { ApiResponse, PaginatedResponse, Role } from '@/types'

// 获取角色列表
export function getRoleList(params: {
  page?: number
  page_size?: number
  name?: string
  display_name?: string
  status?: number
}) {
  return request.get<ApiResponse<PaginatedResponse<Role>>>('/roles', { params })
}

// 获取所有角色(下拉选择用)
export function getAllRoles() {
  return request.get<ApiResponse<Pick<Role, 'id' | 'name' | 'display_name'>[]>>('/roles/all')
}

// 获取角色详情
export function getRoleDetail(id: number) {
  return request.get<ApiResponse<Role>>(`/roles/${id}`)
}

// 创建角色
export function createRole(data: Partial<Role> & { permission_ids?: number[] }) {
  return request.post<ApiResponse<{ id: number; name: string }>>('/roles', data)
}

// 更新角色
export function updateRole(id: number, data: Partial<Role> & { permission_ids?: number[] }) {
  return request.put<ApiResponse<null>>(`/roles/${id}`, data)
}

// 删除角色
export function deleteRole(id: number) {
  return request.delete<ApiResponse<null>>(`/roles/${id}`)
}

// 获取角色权限
export function getRolePermissions(id: number) {
  return request.get<ApiResponse<number[]>>(`/roles/${id}/permissions`)
}

// 设置角色权限
export function syncRolePermissions(id: number, permission_ids: number[]) {
  return request.put<ApiResponse<null>>(`/roles/${id}/permissions`, { permission_ids })
}
