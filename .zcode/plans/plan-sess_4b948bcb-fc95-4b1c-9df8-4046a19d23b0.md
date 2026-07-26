## 移除表情包功能计划

### 需要修改的文件（10 个）+ 删除的目录（1 个）

---

### 1. 删除 OwO 资源目录
- **删除** `assets/libs/owo/` 整个目录（含 owo_01.js、owo.min.css、OwO_01.json、biaoqing/ 下所有图片）

### 2. `libs/Contents.php`
- **删除** 第 144 行 `$text = self::parseBiaoQing($text);`（contentEx 中的调用）
- **删除** 第 168 行 `$text = self::parseBiaoQing($text);`（excerptEx 中的调用）
- **删除** 第 240-309 行：`parseBiaoQing()` 函数及其 5 个回调函数（parsePaopaoBiaoqingCallback、parseAruBiaoqingCallback、parseQuyinBiaoqingCallback、parseBilibiliBiaoqingCallback、parseMihoyoBiaoqingCallback）

### 3. `libs/Comments.php`
- **修改** 第 210 行：`Contents::parseBiaoQing($this->content)` → `$this->content`

### 4. `libs/Utils.php` — `addButton()` 函数
- **删除** 注入 owo_01.js 的 script 标签（第 141-143 行）
- **删除** 注入 owo.min.css 的 link 标签（第 149-151 行）
- 保留 editor.js 和 editor-admin.css 的注入

### 5. `includes/comments.php`
- **删除** 第 72 行 `<span class="OwO" aria-label="表情按钮" role="button"></span>`

### 6. `includes/footer.php`
- **删除** 第 153-157 行 `initOwO` 初始化脚本块

### 7. `assets/VOID.js`
- **删除** `initOwO` 函数定义（第 550-568 行）
- **删除** 3 处 `VOID.initOwO()` 调用（第 528、608、864 行）

### 8. `assets/editor.js`
- **修改** `initEditorToolbar()` 函数（第 33-47 行）：移除 OwO 按钮和 `new OwO(...)` 实例化代码，仅保留图集按钮
- **修改** `stripPreviewMarkup()` 函数（第 827 行）：移除表情短代码正则 `/::\((.*?)\)|:@\((.*?)\)|:&\((.*?)\)|:\$\((.*?)\)|:!\((.*?)\)/g`

### 9. `assets/parts/_comments.scss`
- **删除** `.OwO` 整个样式块（第 115-178 行）
- **删除** `img.biaoqing` 样式块（第 286-292 行）

### 10. `assets/parts/_article.scss`
- **删除** `img.biaoqing` 样式块（第 216-222 行）

### 11. `assets/editor-admin.css`
- **删除** `.OwO` 相关样式（第 1-36 行）
- **删除** 响应式中的 `.OwO` 样式（第 803-809 行）

### 12. `gulpfile.js`
- **修改** `move` 任务（第 96 行）：从 glob 中移除 `'./assets/libs/owo/**/*'`，仅保留 `'./assets/libs/mathjax/**/*'`

---

### 不修改的文件
- `functions.php`：第 93-94 行的 `addButton` 钩子保留（addButton 函数本身仍被使用，只是内部移除了 OwO 注入）
- `advanceSetting.sample.json`：无表情包相关配置
- `.gitignore`：无 OwO 相关忽略规则
- `build/` 目录：构建产物，重新 build 后自动更新