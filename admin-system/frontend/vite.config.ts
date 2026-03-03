import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  return {
    plugins: [vue()],
    base: '/', // 资源路径基础
    build: {
      outDir: '../public', // 输出到 Laravel public 目录
      emptyOutDir: false, // 不清空 public 目录，避免删除 Laravel 入口文件
      manifest: true, // 生成 manifest.json 用于 Blade 读取资源路径
      rollupOptions: {
        input: {
          main: path.resolve(__dirname, 'index.html'),
        },
      },
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src'),
      },
    },
    server: {
      port: 3000,
      proxy: {
        '/api': {
          target: env.VITE_API_BASE_URL || 'http://127.0.0.1:8000',
          changeOrigin: true,
        },
      },
    },
  }
})
