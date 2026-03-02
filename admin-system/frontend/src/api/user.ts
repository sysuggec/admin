import request from './request'
import type { ApiResponse, PaginatedResponse, User } from '@/types'

// 获取用户列表
export function getUserList(params: {
  page?: number
  page_size?: number
  username?: string
  email?: string
  phone?: string
  status?: number
}) {
  return request.get<ApiResponse<PaginatedResponse<User>>>('/users', { params })
}

// 获取用户详情
export function getUserDetail(id: number) {
  return request.get<ApiResponse<User>>(`/users/${id}`)
}

// 创建用户
export function createUser(data: Partial<User> & { password: string; role_ids?: number[] }) {
  return request.post<ApiResponse<{ id: number; username: string }>>('/users', data)
}

// 更新用户
export function updateUser(id: number, data: Partial<User> & { password?: string; role_ids?: number[] }) {
  return request.put<ApiResponse<null>>(`/users/${id}`, data)
}

// 删除用户
export function deleteUser(id: number) {
  return request.delete<ApiResponse<null>>(`/users/${id}`)
}

// 切换用户状态
export function toggleUserStatus(id: number) {
  return request.put<ApiResponse<{ status: number }>>(`/users/${id}/status`)
}
