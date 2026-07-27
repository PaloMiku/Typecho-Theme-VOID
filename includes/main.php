<?php
/**
 * main.php
 * 
 * 内容页面主要区域，PJAX 作用区域
 * 
 * @author      熊猫小A
 * @version     2019-01-15 0.1
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$setting = $GLOBALS['VOIDSetting'];
?>

<main id="pjax-container">
    <title hidden>
        <?php Contents::title($this); ?>
    </title>

    <?php $this->need('includes/ldjson.php'); ?>
    <?php $this->need('includes/banner.php'); ?>
    <?php $this->need('includes/banner-source.php'); ?>

    <div class="wrapper container">
        <div class="contents-wrap"> <!--start .contents-wrap-->
            <section id="post" class="float-up">
                <article class="post yue">

                    <?php $postCheck = Utils::isOutdated($this); if($this->is('post') && $postCheck["is"] && Utils::shouldShowOutdatedNotice($this)): ?>
                        <p class="notice">请注意，本文编写于 <?php echo $postCheck["created"]; ?> 天前，最后修改于 <?php echo $postCheck["updated"]; ?> 天前，其中某些信息可能已经过时。</p>
                    <?php endif; ?>

                    <div class="articleBody" class="full">
                        <?php $this->content(); ?>
                    </div>
                    
                    <?php $tags = Contents::getTags($this->cid); if (count($tags) > 0) {
                        echo '<section class="tags">';
                        foreach ($tags as $tag) {
                            echo '<a href="'.$tag['permalink'].'" rel="tag" class="tag-item">'.$tag['name'].'</a>';
                        }
                        echo '</section>';
                    } ?>

                    <?php if($this->is('post') && $setting['VOIDPlugin']):
                        // 从 votes 表聚合文章的 emoji 反应计数
                        $postReactions = array();
                        try {
                            $_db = Typecho_Db::get();
                            $_rows = $_db->fetchAll($_db->select('type', 'COUNT(*) AS cnt')
                                ->from('table.votes')
                                ->where('id = ?', $this->cid)
                                ->where('table = ?', 'contents')
                                ->group('type'));
                            foreach ($_rows as $_r) {
                                if ($_r['type'] !== 'up' && $_r['type'] !== 'down') {
                                    $postReactions[$_r['type']] = (int)$_r['cnt'];
                                }
                            }
                        } catch (\Throwable $e) {}
                    ?>
                    <div class="post-reactions" data-post-id="<?php echo $this->cid;?>">
                        <?php foreach ($postReactions as $emoji => $count):
                            if ($count > 0): ?>
                            <a no-pjax target="_self" class="reaction-btn comment-reaction vote-button"
                                href="javascript:void(0)"
                                data-action="vote"
                                data-item-id="<?php echo $this->cid;?>"
                                data-type="<?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8');?>"
                                data-table="content"
                            ><?php echo $emoji; ?> <span class="count"><?php echo $count;?></span></a>
                        <?php endif; endforeach; ?>
                        <div class="reaction-add-wrapper">
                            <button class="reaction-add-btn" type="button" data-action="toggle-picker" title="添加表态">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11v1a10 10 0 1 1-9-10"></path><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><path d="M16 5h6"></path><path d="M19 2v6"></path></svg>
                            </button>
                            <div class="reaction-picker">
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="👍" data-table="content" data-cid="<?php echo $this->cid;?>">👍</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="❤️" data-table="content" data-cid="<?php echo $this->cid;?>">❤️</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="😂" data-table="content" data-cid="<?php echo $this->cid;?>">😂</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="🎉" data-table="content" data-cid="<?php echo $this->cid;?>">🎉</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="🔥" data-table="content" data-cid="<?php echo $this->cid;?>">🔥</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="👀" data-table="content" data-cid="<?php echo $this->cid;?>">👀</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="🤡" data-table="content" data-cid="<?php echo $this->cid;?>">🤡</span>
                                <span class="reaction-picker-emoji" data-action="react" data-emoji="🤔" data-table="content" data-cid="<?php echo $this->cid;?>">🤔</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="social-button" 
                        data-url="<?php $this->permalink(); ?>"
                        data-title="<?php Contents::title($this); ?>" 
                        data-excerpt="<?php $this->fields->excerpt(); ?>"
                        data-img="<?php $this->fields->banner(); ?>" 
                        data-twitter="<?php if($setting['twitterId']!='') echo $setting['twitterId']; else $this->author(); ?>"
                        data-weibo="<?php if($setting['weiboId']!='') echo $setting['weiboId']; else $this->author(); ?>"
                        <?php if($this->fields->banner != '') echo 'data-image="'.$this->fields->banner.'"';?>>
                        <?php if(!empty($setting['reward'])):?>
                            <a data-fancybox="gallery-reward" role=button aria-label="赞赏" data-src="#reward" href="javascript:;" class="btn btn-normal btn-highlight">赏杯咖啡</a>
                            <div hidden id="reward"><img src="<?php echo $setting['reward']; ?>"></div>
                        <?php endif; ?>
                        
                        <div class="share-bar">
                            <span class="share-label">分享到</span>
                            <div class="share-buttons">
                                <a aria-label="分享到 X" href="javascript:void(0);" data-action="share" data-method="toX" class="share-btn share-btn-x" data-color="#000000">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <a aria-label="分享到 Facebook" href="javascript:void(0);" data-action="share" data-method="toFacebook" class="share-btn share-btn-facebook" data-color="#1877F2">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a aria-label="分享到 LinkedIn" href="javascript:void(0);" data-action="share" data-method="toLinkedIn" class="share-btn share-btn-linkedin" data-color="#0A66C2">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                                </a>
                                <a aria-label="分享到 Telegram" href="javascript:void(0);" data-action="share" data-method="toTelegram" class="share-btn share-btn-telegram" data-color="#26A5E4">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                                </a>
                                <a aria-label="分享到 WhatsApp" href="javascript:void(0);" data-action="share" data-method="toWhatsApp" class="share-btn share-btn-whatsapp" data-color="#25D366">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                                <a aria-label="分享到微博" href="javascript:void(0);" data-action="share" data-method="toWeibo" class="share-btn share-btn-weibo" data-color="#E6162D">
                                    <i class="voidicon-weibo"></i>
                                </a>
                                <button aria-label="复制链接" type="button" data-action="share" data-method="copyLink" class="share-btn share-btn-copy" data-color="#6c757d">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                                </button>
                                <button aria-label="生成二维码" type="button" data-action="share" data-method="showQRCode" class="share-btn share-btn-qr" data-color="#6c757d">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="3" height="3"/><rect x="18" y="14" width="3" height="3"/><rect x="14" y="18" width="3" height="3"/><rect x="18" y="18" width="3" height="3"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </article>

                <script>
                (function () {
                    $.each($('iframe'), function(i, item){
                        var src = $(item).attr('src');
                        if (typeof src === 'string' && src.indexOf('player.bilibili.com') > -1) {
                            // $(item).addClass('bili-player');
                            // if (src.indexOf('&high_quality') < 0) {
                            //     src += '&high_quality=1'; // 启用高质量
                            //     $(item).attr('src', src);
                            // }
                            $(item).wrap('<div class="bili-player"></div>');
                        }
                    });
                })();
                </script>

                <!--分页-->
                <?php if(!$this->is('page')): ?>
                <div class="post-pager"><?php $prev = Contents::thePrev($this); ?>
                    <?php if($prev): ?>
                        <div class="prev">
                            <a href="<?php $prev->permalink(); ?>"><h2><?php $prev->title(); ?></h2></a>
                            <?php echo $prev->fields->excerpt != '' ? "<p>{$prev->fields->excerpt}</p>" : ''; ?>
                        </div>
                    <?php else: ?>
                        <div class="prev">
                            <h2>没有了</h2>
                        </div>
                    <?php endif; ?>
                    <?php $next = Contents::theNext($this); ?>
                    <?php if($next): ?>
                        <div class="next">
                            <a href="<?php $next->permalink(); ?>"><h2><?php $next->title(); ?></h2></a>
                            <?php echo $next->fields->excerpt != '' ? "<p>{$next->fields->excerpt}</p>" : ''; ?>
                        </div>
                    <?php else: ?>
                        <div class="next">
                            <h2>没有了</h2>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </section>
        </div> <!--end .contents-wrap-->
        <!--目录，可选-->
        <?php if($this->fields->showTOC == '1'): ?>
            <div class="toc-mask" data-action="close-toc"></div>
            <div aria-label="文章目录" class="TOC"></div>
            <style>
            #toggle-toc { display: block; }
            </style>
        <?php endif;?>
    </div>
    <!--评论区，可选-->
    <?php $this->need('includes/comments.php'); ?>
</main>
