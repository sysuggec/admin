import request from './request'
import type { ApiResponse, LoginResponse, UserInfoResponse } from '@/types'

// 登录
export function login(data: { username: string; password: string }) {
  return request.post<ApiResponse<LoginResponse>>('/auth/login', data)
}

// 登出
export function logout() {
  return request.post<ApiResponse<null>>('/auth/logout')
}

// 刷新 Token
export function refreshToken() {
  return request.post<ApiResponse<{ access_token: string; token_type: string; expires_in: number }>>('/auth/refresh')
}

// 获取当前用户信息
export function getUserInfo() {
  return request.get<ApiResponse<UserInfoResponse>>('/auth/me')
}

// 修改密码
export function changePassword(data: { old_password: string; new_password: string }) {
  return request.post<ApiResponse<null>>('/users/change-password', data)
}
