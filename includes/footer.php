<?php
/**
 * footer.php
 *
 * 底栏
 *
 * @author      熊猫小A
 * @version     2019-01-15 0.1
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$setting = $GLOBALS['VOIDSetting'];
?>
        <footer>
            <div class="container wide">
                <section>
                    <p>© <?php echo date('Y '); ?> <span class="brand"><?php echo $this->options->title; ?></span></p>
                    <p>感谢陪伴：<span id="uptime"></span></p>
                </section>
                <section>
                    <p>Powered by <a href="http://typecho.org/">Typecho</a> • <a href="https://blog.imalan.cn/archives/247/">Theme VOID</a></p>
                    <p><?php echo $setting['footer']; ?></p>
                </section>
            </div>
        </footer>

        <!--右下角控制按钮组-->
        <aside id="ctrler-panel">
            <div class="ctrler-item" id="go-top">
                <a target="_self" aria-label="返回顶部" href="javascript:void(0);" data-action="scroll-top"><i class="voidicon-up"></i></a>
            </div>

            <?php if($this->user->hasLogin()): ?>
                <div class="ctrler-item hidden-xs">
                    <a target="_blank" aria-label="进入后台" href="<?php $this->options->adminUrl(); ?>"><i class="voidicon-login"></i></a>
                </div>
                <div class="ctrler-item hidden-xs">
                    <a target="_blank" aria-label="管理评论" href="<?php $this->options->adminUrl('manage-comments.php'); ?>"><i class="voidicon-comment"></i></a>
                </div>
            <?php endif; ?>

            <div aria-label="展开或关闭设置面板" id="toggle-setting-pc" class="ctrler-item hidden-xs">
                <a target="_self" href="javascript:void(0);" data-action="toggle-setting-panel"><i class="voidicon-cog"></i></a>
            </div>
            <div aria-label="展开或关闭文章目录" class="ctrler-item" id="toggle-toc">
                <a target="_self" href="javascript:void(0);" data-action="toggle-toc"><i class="voidicon-left"></i></a>
            </div>
        </aside>

        <!--设置面板-->
        <aside hidden id="setting-panel">
            <!-- 外观设置 -->
            <section id="appearance-settings">
                <div id="toggle-night">
                    <a target="_self" href="javascript:void(0)" data-action="toggle-night"><i></i></a>
                </div>
                <div id="adjust-text-container">
                    <div class="adjust-text-item">
                        <span class="adjust-label">字号</span>
                        <input type="range" id="font-size-slider" min="1" max="5" step="1" value="3"
                               data-action="adjust-text-slider"
                               aria-label="字号调节滑块">
                        <span id="current_textsize"></span>
                    </div>
                    <div class="adjust-text-item">
                        <span class="adjust-label">字体</span>
                        <a target="_self" class="font-indicator <?php if(!Utils::isSerif($setting)) echo ' checked'; ?>" href="javascript:void(0)" data-action="toggle-serif" data-serif="false">Sans</a>
                        <a target="_self" class="font-indicator <?php if(Utils::isSerif($setting)) echo ' checked'; ?>" href="javascript:void(0)" data-action="toggle-serif" data-serif="true">Serif</a>
                    </div>
                </div>
            </section>

            <!-- 链接区 -->
            <section id="links">
                <?php if(!$this->user->hasLogin()): ?>
                    <a target="_self" class="link" href="javascript:void(0)" data-action="toggle-login-form" title="登录"><i class="voidicon-user"></i></a>
                <?php endif; ?>
                <a class="link" title="RSS" target="_blank" href="<?php $this->options->feedUrl(); ?>"><i class="voidicon-rss"></i></a>
                <?php
                    foreach ($setting['link'] as $link) {
                        echo "<a class=\"link\" title=\"{$link['name']}\" target=\"{$link['target']}\" href=\"{$link['href']}\"><i class=\"voidicon-{$link['icon']}\"></i></a>";
                    }
                ?>
            </section>

            <!-- 登录面板 -->
            <section id="login-panel" <?php if($this->user->hasLogin()) echo 'class="force-show"'; ?>>
                <?php if(!$this->user->hasLogin()): ?>
                    <form action="<?php $this->options->loginAction()?>" id="loggin-form" method="post" name="login" role="form">
                        <div id="loggin-inputs">
                            <input type="text" name="name" autocomplete="username" placeholder="请输入用户名" required/>
                            <input type="password" name="password" autocomplete="current-password" placeholder="请输入密码" required/>
                            <input type="hidden" name="referer" value="<?php
                                if($this->is('index')) $this->options->siteUrl();
                                else $this->permalink();
                            ?>">
                        </div>
                        <div class="buttons" id="loggin-buttons">
                            <button class="btn btn-normal" type="button" data-action="close-login-panel">关闭</button>
                            <button class="btn btn-normal" type="submit" data-action="remember-pos">登录</button>
                            <span hidden id="wait" class="btn btn-normal">请稍等……</span>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="buttons" id="manage-buttons">
                        <a class="btn btn-normal" no-pjax target="_blank" href="<?php $this->options->adminUrl(); ?>">后台</a>
                        <a class="btn btn-normal" no-pjax title="登出" data-action="remember-pos" href="<?php $this->options->logoutUrl(); ?>">登出</a>
                    </div>
                <?php endif; ?>
            </section>
        </aside>

        <?php
        // 主题已移除 Service Worker 功能：清理历史遗留的 SW 注册，避免缓存旧资源
        ?>
        <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for (var i = 0; i < registrations.length; i++) {
                    registrations[i].unregister();
                }
            }).catch(function() {});
        }
        </script>
        <script data-manual src="<?php Utils::indexTheme('/assets/bundle.js'); ?>"></script>
        <?php if($setting['enableMath']): ?>
        <script>
            window.MathJax = {
                startup: {
                    typeset: false
                },
                tex: {
                    inlineMath: [['$', '$'], ['\\(', '\\)']],
                    displayMath: [['$$', '$$'], ['\\[', '\\]']],
                    processEscapes: true
                },
                svg: {
                    fontCache: 'global'
                }
            };
        </script>
        <script id="MathJax-script" src='<?php Utils::indexTheme('/assets/libs/mathjax/4.1.1/tex-svg.js'); ?>'></script>
        <?php endif; ?>
        <script src="<?php Utils::indexTheme('/assets/VOID.js'); ?>"></script>
        <?php if($setting['pjax']): ?>
        <script>
            $(document).on('pjax:complete', function(event, xhr, status, options){
                if (options && options.container && options.container !== '#pjax-container') {
                    return;
                }
                <?php echo $setting['pjaxreload']; ?>
            })
            <?php if(Utils::isPluginAvailable('ExSearch')): ?>
            function ExSearchCall(item){
                if (item && item.length) {
                    $('.ins-close').click(); // 关闭搜索框
                    let url = item.attr('data-url'); // 获取目标页面 URL
                    if (window.VoidPjax && typeof window.VoidPjax.visit === 'function') {
                        window.VoidPjax.visit({
                            url: url,
                            container: '#pjax-container',
                            fragment: '#pjax-container',
                            timeout: 8000
                        }); // 发起一次 PJAX 请求
                    } else {
                        window.open(url, '_self');
                    }
                }
            }
            <?php endif; ?>
        </script>
        <?php endif; ?>
        <?php $this->footer(); ?>
    </body>
</html>
