# Git 工作流

## 提交消息格式
```
<类型>: <描述>

<可选正文>
```

类型：feat, fix, refactor, docs, test, chore, perf, ci


## Pull Request 工作流

创建PR时：
1. 分析完整提交历史（不仅是最新提交）
2. 使用 `git diff [base-branch]...HEAD` 查看所有变更
3. 起草全面的PR摘要
4. 包含带有TODO的测试计划
5. 如果是新分支，使用 `-u` 标志推送
