import { defineConfig } from 'vite';
import { resolve } from 'node:path';

// VOID 主题构建配置
//
// 阶段 3：Vite 接管 SCSS 编译（替代 npx sass），JS 仍由 Gulp uglify 处理。
// 阶段 6 ESM 化后，JS 入口将转入此处打包。
//
// 产物保持原文件名（无 hash），因 head.php/footer.php/Utils.php 硬编码引用
// assets/VOID.css、assets/editor-admin.css。vendor 库（jQuery/Prism/tocbot 等）
// 由 gulpfile.js 的 bundle 任务拼接，不经过 Vite。
export default defineConfig({
    build: {
        emptyOutDir: false,
        outDir: 'assets',
        rollupOptions: {
            input: {
                // 仅 SCSS 入口，产出 VOID.css（被 .gitignore 忽略）
                // editor-admin.css 是入库源文件，仍由 Gulp 在 build/ 阶段处理
                // JS 待阶段 6 ESM 化后接入
                'VOID': resolve(__dirname, 'assets/VOID.scss'),
            },
            output: {
                // 保持原文件名，不加 hash
                assetFileNames: '[name].[ext]',
                entryFileNames: '[name].js',
                chunkFileNames: '[name].js',
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                // VOID.scss 已用 @use，无需额外配置
                quietDeps: true,
            },
        },
    },
});
