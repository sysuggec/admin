import request from './request'
import type { ApiResponse, Permission } from '@/types'

// 获取权限列表(树形)
export function getPermissionList(params?: { name?: string; display_name?: string; type?: string; status?: number; tree?: boolean }) {
  return request.get<ApiResponse<Permission[]>>('/permissions', { params })
}

// 获取所有权限(树形，用于分配权限)
export function getAllPermissions() {
  return request.get<ApiResponse<Permission[]>>('/permissions/all')
}

// 获取权限详情
export function getPermissionDetail(id: number) {
  return request.get<ApiResponse<Permission>>(`/permissions/${id}`)
}

// 创建权限
export function createPermission(data: Partial<Permission>) {
  return request.post<ApiResponse<{ id: number; name: string }>>('/permissions', data)
}

// 更新权限
export function updatePermission(id: number, data: Partial<Permission>) {
  return request.put<ApiResponse<null>>(`/permissions/${id}`, data)
}

// 删除权限
export function deletePermission(id: number) {
  return request.delete<ApiResponse<null>>(`/permissions/${id}`)
}
