// API 响应类型
export interface ApiResponse<T = unknown> {
  code: number
  message: string
  data: T
}

// 分页响应类型
export interface PaginatedResponse<T> {
  list: T[]
  total: number
  page: number
  page_size: number
}

// 用户类型
export interface User {
  id: number
  username: string
  email: string | null
  phone: string | null
  avatar: string | null
  status: number
  created_at: string
  updated_at: string
  roles?: Role[]
}

// 角色类型
export interface Role {
  id: number
  name: string
  display_name: string
  description: string | null
  sort_order: number
  status: number
  created_at: string
  updated_at: string
  permissions?: Permission[]
}

// 权限类型
export interface Permission {
  id: number
  name: string
  display_name: string
  type: 'menu' | 'button' | 'api'
  parent_id: number
  path: string | null
  api_path: string | null
  icon: string | null
  sort_order: number
  status: number
  created_at: string
  updated_at: string
  children?: Permission[]
}

// 登录响应类型
export interface LoginResponse {
  access_token: string
  token_type: string
  expires_in: number
  user: {
    id: number
    username: string
    email: string
    avatar: string | null
  }
}

// 用户信息响应类型
export interface UserInfoResponse {
  id: number
  username: string
  email: string | null
  phone: string | null
  avatar: string | null
  roles: string[]
  permissions: string[]
  menus: Permission[]
}

// 操作日志类型
export interface OperationLog {
  id: number
  user_id: number
  username: string
  module: string
  action: string
  title: string
  method: string
  url: string
  params: string | null
  response: string | null
  ip: string
  user_agent: string | null
  status: number
  error_msg: string | null
  duration: number
  created_at: string
}

// 登录日志类型
export interface LoginLog {
  id: number
  user_id: number
  username: string
  ip: string
  user_agent: string | null
  login_time: string
  status: number
  message: string | null
}
