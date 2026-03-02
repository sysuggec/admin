import request from './request'
import type { ApiResponse, PaginatedResponse, OperationLog } from '@/types'

// 获取操作日志列表
export function getOperationLogList(params: {
  page?: number
  page_size?: number
  username?: string
  module?: string
  action?: string
  status?: number
  start_time?: string
  end_time?: string
  ip?: string
}) {
  return request.get<ApiResponse<PaginatedResponse<OperationLog>>>('/operation-logs', { params })
}

// 获取操作日志详情
export function getOperationLogDetail(id: number) {
  return request.get<ApiResponse<OperationLog>>(`/operation-logs/${id}`)
}

// 删除操作日志
export function deleteOperationLog(id: number) {
  return request.delete<ApiResponse<null>>(`/operation-logs/${id}`)
}

// 批量删除操作日志
export function batchDeleteOperationLogs(ids: number[]) {
  return request.delete<ApiResponse<null>>('/operation-logs/batch', { data: { ids } })
}

// 清理过期日志
export function cleanOperationLogs(days: number = 90) {
  return request.post<ApiResponse<{ deleted_count: number }>>('/operation-logs/clean', { days })
}

// 导出操作日志
export function exportOperationLogs(params: {
  username?: string
  module?: string
  action?: string
  status?: number
  start_time?: string
  end_time?: string
}) {
  return request.get<ApiResponse<Record<string, unknown>[]>>('/operation-logs/export', { params })
}

// 获取模块列表
export function getOperationModules() {
  return request.get<ApiResponse<string[]>>('/operation-logs/modules')
}

// 获取操作类型列表
export function getOperationActions() {
  return request.get<ApiResponse<string[]>>('/operation-logs/actions')
}
