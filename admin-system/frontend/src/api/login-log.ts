import request from './request'
import type { ApiResponse, PaginatedResponse } from '@/types'
import type { LoginLog } from '@/types'

// 获取登录日志列表
export function getLoginLogList(params: {
  page?: number
  page_size?: number
  username?: string
  status?: number
  start_time?: string
  end_time?: string
}) {
  return request.get<ApiResponse<PaginatedResponse<LoginLog>>>('/login-logs', { params })
}

// 获取登录日志详情
export function getLoginLogDetail(id: number) {
  return request.get<ApiResponse<LoginLog>>(`/login-logs/${id}`)
}

// 删除登录日志
export function deleteLoginLog(id: number) {
  return request.delete<ApiResponse<null>>(`/login-logs/${id}`)
}

// 批量删除登录日志
export function batchDeleteLoginLogs(ids: number[]) {
  return request.delete<ApiResponse<null>>('/login-logs/batch', { data: { ids } })
}

// 清理登录日志
export function cleanLoginLogs(days: number) {
  return request.post<ApiResponse<{ deleted_count: number }>>('/login-logs/clean', { days })
}

// 导出登录日志
export function exportLoginLogs(params: {
  username?: string
  status?: number
  start_time?: string
  end_time?: string
}) {
  return request.get<ApiResponse<Record<string, unknown>[]>>('/login-logs/export', { params })
}
