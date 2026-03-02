<template>
  <div class="error-page">
    <div class="error-content">
      <h1>404</h1>
      <p>抱歉，您访问的页面不存在</p>
      <el-button type="primary" @click="goHome">返回首页</el-button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const router = useRouter()

function goHome() {
  router.push('/')
}
</script>

<style scoped>
.error-page {
  width: 100%;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #f5f7fa;
}

.error-content {
  text-align: center;
}

.error-content h1 {
  font-size: 120px;
  font-weight: bold;
  color: #409eff;
  margin-bottom: 20px;
}

.error-content p {
  font-size: 18px;
  color: #666;
  margin-bottom: 30px;
}
</style>
