<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 评论归档
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 * @version $Id$
 */

/**
 * 评论归档组件
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class VOID_Widget_Comments_Archive extends Widget_Abstract_Comments
{
     /**
     * 当前页
     *
     * @access private
     * @var integer
     */
    private $_currentPage;

    /**
     * 所有文章个数
     *
     * @access private
     * @var integer
     */
    private $_total = false;

    /**
     * 子父级评论关系
     *
     * @access private
     * @var array
     */
    private $_threadedComments = array();

    /**
     * 多级评论回调函数
     * 
     * @access private
     * @var mixed
     */
    private $_customThreadedCommentsCallback = false;

    /**
     * _singleCommentOptions  
     * 
     * @var mixed
     * @access private
     */
    private $_singleCommentOptions = NULL;


    private $_commentAuthors = array();

    /**
     * 安全组件
     */
    private $_security = NULL;

    /**
     * 从 parentContent 读取字段（兼容 array / object）
     *
     * @access private
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    private function getParentContentField($key, $default = '')
    {
        $parentContent = $this->parameter->parentContent;
        if (is_array($parentContent)) {
            return isset($parentContent[$key]) ? $parentContent[$key] : $default;
        }
        if (is_object($parentContent)) {
            // 兼容 Typecho Widget 的 __get
            if (isset($parentContent->$key)) {
                return $parentContent->$key;
            }
            if (property_exists($parentContent, $key)) {
                return $parentContent->$key;
            }
            try {
                $value = $parentContent->$key;
                return ($value === NULL || $value === '') ? $default : $value;
            } catch (Exception $e) {
                return $default;
            } catch (Throwable $e) {
                return $default;
            }
        }
        return $default;
    }

    /**
     * 获取评论分页路由所需 permalink path（避免出现 /{permalink}/...）
     *
     * @access private
     * @return string
     */
    private function getParentPath()
    {
        $path = trim((string)$this->getParentContentField('path', ''));

        if ($path === '') {
            $path = trim((string)$this->getParentContentField('pathinfo', ''));
        }

        if ($path === '' || strpos($path, '{') !== false) {
            $permalink = trim((string)$this->getParentContentField('permalink', ''));
            if ($permalink !== '') {
                $parsedPath = parse_url($permalink, PHP_URL_PATH);
                if (is_string($parsedPath) && $parsedPath !== '') {
                    $path = $parsedPath;
                }
            }
        }

        $path = ltrim($path, '/');
        return $path;
    }

    /**
     * 构造函数,初始化组件
     *
     * @access public
     * @param mixed $request request对象
     * @param mixed $response response对象
     * @param mixed $params 参数列表
     * @return void
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->parameter->setDefault('parentId=0&commentPage=0&commentsNum=0&allowComment=1');
        
        Typecho_Widget::widget('Widget_Security')->to($this->_security);

        /** 初始化回调函数 */
        if (function_exists('threadedComments')) {
            $this->_customThreadedCommentsCallback = true;
        }
    }

    /**
     * 评论回调函数
     * 
     * @access private
     * @return void
     */
    private function threadedCommentsCallback()
    {
        $singleCommentOptions = $this->_singleCommentOptions;
        if (function_exists('threadedComments')) {
            return threadedComments($this, $singleCommentOptions);
        }

        $setting = $GLOBALS['VOIDSetting'];
        
        $avatarClass = '';
        if ($this->authorId) {
            if ($this->authorId == $this->ownerId) {
                $avatarClass .= ' star';
            }
        }

        if ($setting['VOIDPlugin']) {
            $metaArr = $this->getLikesAndDislikes();
            $likeCount = $this->getLikeCount();
            $dislikeCount = $this->getDislikeCount();
            if ($dislikeCount >= $setting['commentFoldThreshold'][0]
            && ($dislikeCount >= $likeCount*$setting['commentFoldThreshold'][1])) {
                $commentClass .= ' fold';
            }
        }
?>
<div id="<?php $this->theId(); ?>" class="comment-body<?php
    if ($this->levels > 0) {
        echo ' comment-child';
        $this->levelsAlt(' comment-level-odd', ' comment-level-even');
    } else {
        echo ' comment-parent';
    }
    $this->alt(' comment-odd', ' comment-even');
    echo $commentClass;
?>">
    <div class="comment-content-wrap">
        <div class="comment-meta">
            <div class="comment-author">
                <span class="comment-avatar<?php echo $avatarClass; ?>">
                    <img class="avatar" src="<?php echo $this->getAvatarUrl($singleCommentOptions->avatarSize); ?>" alt="<?php echo htmlspecialchars($this->author, ENT_QUOTES, 'UTF-8'); ?>" width="<?php echo $singleCommentOptions->avatarSize; ?>" height="<?php echo $singleCommentOptions->avatarSize; ?>">
                </span>
                <div class="comment-info">
                    <div class="comment-author-line">
                        <b><cite class="fn"><?php $singleCommentOptions->beforeAuthor();
                        echo htmlspecialchars($this->author, ENT_QUOTES, 'UTF-8');
                        $singleCommentOptions->afterAuthor(); ?></cite></b>
                        <span><?php echo $this->getParent(); ?></span>
                    </div>
                    <div class="comment-meta-line">
                        <a href="<?php $this->permalink(); ?>"><time datetime="<?php $this->date('c'); ?>"><?php $singleCommentOptions->beforeDate();
                        echo date('Y-m-d H:i', $this->created);
                        $singleCommentOptions->afterDate(); ?></time></a>
                        <span class="comment-icons"><?php echo $this->getCommentClientIcons(); ?></span>
                        <?php if ('waiting' == $this->status) { ?>
                        <em class="comment-awaiting-moderation"><?php $singleCommentOptions->commentStatus(); ?></em>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="comment-content yue">
            <?php if ($setting['VOIDPlugin'] && $dislikeCount >= $setting['commentFoldThreshold'][0]
            && ($dislikeCount >= $likeCount*$setting['commentFoldThreshold'][1])) { ?>
                <span class="fold">[该评论已被自动折叠 | <a no-pjax target="_self" href="javascript:void(0)" 
                onclick="VOID_Vote.toggleFoldComment(<?php echo $this->coid; ?>, this)">点击展开</a>]</span>
            <?php }?>
            <div class="comment-content-inner"><?php echo $this->content; ?></div>
        </div>
        <div class="comment-actions">
            <?php if ($setting['VOIDPlugin']) {
                $reactions = $this->getReactions();
            ?>
            <div class="comment-reactions" data-comment-id="<?php echo $this->coid;?>">
                <?php foreach ($reactions as $emoji => $count):
                    if ($count > 0): ?>
                    <a no-pjax target="_self" class="reaction-btn comment-reaction vote-button"
                        href="javascript:void(0)"
                        onclick="VOID_Vote.vote(this)"
                        data-item-id="<?php echo $this->coid;?>"
                        data-type="<?php echo htmlspecialchars($emoji, ENT_QUOTES, 'UTF-8');?>"
                        data-table="comment"
                    ><?php echo $emoji; ?> <span class="count"><?php echo $count;?></span></a>
                <?php endif; endforeach; ?>
                <div class="reaction-add-wrapper">
                    <button class="reaction-add-btn" type="button" onclick="VOID_Vote.togglePicker(this)" title="添加表态">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11v1a10 10 0 1 1-9-10"></path><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><line x1="9" y1="9" x2="9.01" y2="9"></line><line x1="15" y1="9" x2="15.01" y2="9"></line><path d="M16 5h6"></path><path d="M19 2v6"></path></svg>
                    </button>
                    <div class="reaction-picker">
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '👍', 'comment', <?php echo $this->coid;?>)">👍</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '👎', 'comment', <?php echo $this->coid;?>)">👎</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '🤡', 'comment', <?php echo $this->coid;?>)">🤡</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '❤️', 'comment', <?php echo $this->coid;?>)">❤️</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '🔥', 'comment', <?php echo $this->coid;?>)">🔥</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '👀', 'comment', <?php echo $this->coid;?>)">👀</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '😂', 'comment', <?php echo $this->coid;?>)">😂</span>
                        <span class="reaction-picker-emoji" onclick="VOID_Vote.reaction(this, '🤔', 'comment', <?php echo $this->coid;?>)">🤔</span>
                    </div>
                </div>
            </div>
            <?php } ?>
            <span class="comment-reply">
                <?php $this->reply($singleCommentOptions->replyWord); ?>
            </span>
        </div>
    </div>
    <?php if ($this->children) { ?>
    <div class="comment-children">
        <?php $this->threadedComments(); ?>
    </div>
    <?php } ?>
</div>
<?php
    }
  
    private function getParent(){
        if ($this->levels <= 1) {
            return '';
        }

        $parentId = 0;
        if (is_array($this->row) && isset($this->row['realParent'])) {
            $parentId = (int)$this->row['realParent'];
        } elseif (is_array($this->row) && isset($this->row['parent'])) {
            $parentId = (int)$this->row['parent'];
        }

        if ($parentId <= 0) {
            return '';
        }

        $author = '';
        if (isset($this->_commentAuthors[$parentId])) {
            $author = trim((string)$this->_commentAuthors[$parentId]);
        }

        if ($author === '') {
            $db = Typecho_Db::get();
            $parentRow = $db->fetchRow($db->select('author')->from('table.comments')->where('coid = ?', $parentId));
            if (is_array($parentRow) && !empty($parentRow['author'])) {
                $author = trim((string)$parentRow['author']);
            }
            $this->_commentAuthors[$parentId] = $author;
        }

        if ($author === '') {
            $author = '已删除的评论';
        }

        $safeAuthor = htmlspecialchars($author, ENT_QUOTES, 'UTF-8');
        return ' <span class="comment-parent-label">回复</span> <b class="comment-parent-author">@' . $safeAuthor . '</b> ';
    }

    /**
     * 获取评论赞踩
     */
    private function getLikesAndDislikes() {
        $db = Typecho_Db::get();
        $row = $db->fetchRow($db->select('likes, dislikes')
            ->from('table.comments')
            ->where('coid = ?', $this->coid));
        return array('likes' => $row['likes'], 'dislikes' => $row['dislikes']);
    }

    /**
     * 获取评论的 emoji 反应计数（从 votes 表聚合）
     * 返回 ['👍' => 3, '❤️' => 1, ...]
     */
    private function getReactions() {
        $db = Typecho_Db::get();
        $rows = $db->fetchAll($db->select('type', 'COUNT(*) AS cnt')
            ->from('table.votes')
            ->where('id = ?', $this->coid)
            ->where('table = ?', 'comments')
            ->group('type'));
        $reactions = array();
        foreach ($rows as $row) {
            // 跳过旧的 up/down 类型，只保留 emoji 反应
            if ($row['type'] !== 'up' && $row['type'] !== 'down') {
                $reactions[$row['type']] = (int)$row['cnt'];
            }
        }
        return $reactions;
    }

    /**
     * 获取 👎 反应数量（用于折叠判定，兼容旧 dislikes 字段 + 新 emoji 👎）
     */
    private function getDislikeCount() {
        // 先从 votes 表查 👎 emoji 反应
        $reactions = $this->getReactions();
        $emojiDislike = isset($reactions['👎']) ? (int)$reactions['👎'] : 0;
        // 兼容旧的 dislikes 字段
        $metaArr = $this->getLikesAndDislikes();
        return $emojiDislike + (int)$metaArr['dislikes'];
    }

    /**
     * 获取 👍 反应数量（兼容旧 likes 字段 + 新 emoji 👍）
     */
    private function getLikeCount() {
        $reactions = $this->getReactions();
        $emojiLike = isset($reactions['👍']) ? (int)$reactions['👍'] : 0;
        $metaArr = $this->getLikesAndDislikes();
        return $emojiLike + (int)$metaArr['likes'];
    }
    
    /**
     * 获取当前评论链接
     *
     * @access protected
     * @return string
     */
    protected function ___permalink() : string
    {

        if ($this->options->commentsPageBreak) {            
            $parentPath = $this->getParentPath();
            if (!empty($parentPath)) {
                $pageRow = array('permalink' => $parentPath, 'commentPage' => $this->_currentPage);
                return Typecho_Router::url('comment_page', $pageRow, $this->options->index) . '#' . $this->theId;
            }
        }

        return (string)$this->getParentContentField('permalink', '') . '#' . $this->theId;
    }

    /**
     * 子评论
     *
     * @access protected
     * @return array
     */
    protected function ___children(): array
    {
        return $this->options->commentsThreaded && !$this->isTopLevel && isset($this->_threadedComments[$this->coid]) 
            ? $this->_threadedComments[$this->coid] : array();
    }

    /**
     * 是否到达顶层
     *
     * @access protected
     * @return boolean
     */
    protected function ___isTopLevel(): bool
    {
        // 对齐 Typecho 1.3：顶层判定语义与核心一致
        return $this->levels > $this->options->commentsMaxNestingLevels - 2;
    }



    /**
     * 输出文章评论数
     *
     * @access public
     * @param string $string 评论数格式化数据
     * @return void
     */
    public function num()
    {
        $args = func_get_args();
        if (!$args) {
            $args[] = '%d';
        }

        $num = intval($this->_total);

        echo sprintf(isset($args[$num]) ? $args[$num] : array_pop($args), $num);
    }

    /**
     * 执行函数
     *
     * @access public
     * @return void
     */
    public function execute()
    {
        if (!$this->parameter->parentId) {
            return;
        }

        // 对齐 Typecho 1.3：仅显示已审核评论 + 当前访客自己的待审核评论
        $unapprovedCommentId = intval(Typecho_Cookie::get('__typecho_unapproved_comment', 0));
        $select = $this->select()->where('table.comments.cid = ?', $this->parameter->parentId)
            ->where(
                'table.comments.status = ? OR (table.comments.coid = ? AND table.comments.status <> ?)',
                'approved',
                $unapprovedCommentId,
                'approved'
            );

        $threadedSelect = NULL;
        
        if ($this->options->commentsShowCommentOnly) {
            $select->where('table.comments.type = ?', 'comment');
        }
        
        $select->order('table.comments.coid', 'ASC');
        $this->db->fetchAll($select, array($this, 'push'));
        
        /** 需要输出的评论列表 */
        $outputComments = array();
        
        /** 如果开启评论回复 */
        if ($this->options->commentsThreaded) {
        
            foreach ($this->stack as $coid => &$comment) {
                
                /** 取出父节点 */
                $parent = $comment['parent'];
            
                /** 如果存在父节点 */
                if (0 != $parent && isset($this->stack[$parent])) {
                
                    /** 如果当前节点深度大于最大深度, 则将其挂接在父节点上 */
                    // 对齐 Typecho 1.3：遵循 commentsMaxNestingLevels 配置，不再硬编码层级
                    if ($comment['levels'] >= (int)$this->options->commentsMaxNestingLevels) {
                        $comment['levels'] = $this->stack[$parent]['levels'];
                        $parent = $this->stack[$parent]['parent'];     // 上上层节点
                        $comment['parent'] = $parent;
                    }
                
                    /** 计算子节点顺序 */
                    $comment['order'] = isset($this->_threadedComments[$parent]) 
                        ? count($this->_threadedComments[$parent]) + 1 : 1;
                
                    /** 如果是子节点 */
                    $this->_threadedComments[$parent][$coid] = $comment;
                } else {
                    $outputComments[$coid] = $comment;
                }
                
            }
        
            $this->stack = $outputComments;
        }
        
        /** 评论排序 */
        if ('DESC' == $this->options->commentsOrder) {
            $this->stack = array_reverse($this->stack, true);
        }
        
        /** 评论总数 */
        $this->_total = count($this->stack);
        
        /** 对评论进行分页 */
        if ($this->options->commentsPageBreak) {
            if ('last' == $this->options->commentsPageDisplay && !$this->parameter->commentPage) {
                $this->_currentPage = ceil($this->_total / $this->options->commentsPageSize);
            } else {
                $this->_currentPage = $this->parameter->commentPage ? $this->parameter->commentPage : 1;
            }
            
            /** 截取评论 */
            $this->stack = array_slice($this->stack,
                ($this->_currentPage - 1) * $this->options->commentsPageSize, $this->options->commentsPageSize);
        }
        
        /** 评论置位 */
        $this->length = count($this->stack);
        $this->row = $this->length > 0 ? current($this->stack) : array();
        
        reset($this->stack);
    }

    /**
     * 将每行的值压入堆栈
     *
     * @access public
     * @param array $value 每行的值
     * @return array
     */
    public function push(array $value) : array
    {
        $value = $this->filter($value);
        
        /** 计算深度 */
        if (0 != $value['parent'] && isset($this->stack[$value['parent']]['levels'])) {
            $value['levels'] = $this->stack[$value['parent']]['levels'] + 1;
        } else {
            $value['levels'] = 0;
        }

        $value['realParent'] = $value['parent'];

        /** 重载push函数,使用coid作为数组键值,便于索引 */
        $this->stack[$value['coid']] = $value;
        $this->_commentAuthors[$value['coid']] = $value['author'];
        $this->length ++;
        
        return $value;
    }

    /**
     * 输出分页
     *
     * @access public
     * @param string $prev 上一页文字
     * @param string $next 下一页文字
     * @param int $splitPage 分割范围
     * @param string $splitWord 分割字符
     * @param string $template 展现配置信息
     * @return void
     */
    public function pageNav($prev = '&laquo;', $next = '&raquo;', $splitPage = 3, $splitWord = '...', $template = '')
    {
        if ($this->options->commentsPageBreak && $this->_total > $this->options->commentsPageSize) {
            $default = array(
                'wrapTag'       =>  'ol',
                'wrapClass'     =>  'page-navigator'
            );

            if (is_string($template)) {
                parse_str($template, $config);
            } else {
                $config = $template;
            }

            $template = array_merge($default, $config);
            $parentPath = $this->getParentPath();
            if (empty($parentPath)) {
                return;
            }
            $query = Typecho_Router::url('comment_page', array(
                'permalink' => $parentPath,
                'commentPage' => '{commentPage}'
            ), $this->options->index);

            /** 使用盒状分页 */
            $nav = new Typecho_Widget_Helper_PageNavigator_Box($this->_total,
                $this->_currentPage, $this->options->commentsPageSize, $query);
            $nav->setPageHolder('commentPage');
            $nav->setAnchor('comments');
            
            echo '<' . $template['wrapTag'] . (empty($template['wrapClass']) 
                    ? '' : ' class="' . $template['wrapClass'] . '"') . '>';
            $nav->render($prev, $next, $splitPage, $splitWord, $template);
            echo '</' . $template['wrapTag'] . '>';
        }
    }

    /**
     * 递归输出评论
     *
     * @access protected
     * @return void
     */
    public function threadedComments()
    {
        $children = $this->children;
        if ($children) {
            //缓存变量便于还原
            $tmp = $this->row;
            $this->sequence ++;

            //在子评论之前输出
            echo $this->_singleCommentOptions->before;

            foreach ($children as $child) {
                $this->row = $child;
                $this->threadedCommentsCallback();
                $this->row = $tmp;
            }

            //在子评论之后输出
            echo $this->_singleCommentOptions->after;

            $this->sequence --;
        }
    }
    
    /**
     * 列出评论
     * 
     * @access private
     * @param mixed $singleCommentOptions 单个评论自定义选项
     * @return void
     */
    public function listComments($singleCommentOptions = NULL)
    {
        //初始化一些变量
        $this->_singleCommentOptions = Typecho_Config::factory($singleCommentOptions);
        $this->_singleCommentOptions->setDefault(array(
            'before'        =>  '<ol class="comment-list">',
            'after'         =>  '</ol>',
            'beforeAuthor'  =>  '',
            'afterAuthor'   =>  '',
            'beforeDate'    =>  '',
            'afterDate'     =>  '',
            'dateFormat'    =>  $this->options->commentDateFormat,
            'replyWord'     =>  '回复',
            'commentStatus' =>  '评论正等待审核!',
            'avatarSize'    =>  32,
            'defaultAvatar' =>  NULL
        ));
        $this->pluginHandle()->trigger($plugged)->listComments($this->_singleCommentOptions, $this);

        if (!$plugged) {
            if ($this->have()) { 
                echo $this->_singleCommentOptions->before;
            
                while ($this->next()) {
                    $this->threadedCommentsCallback();
                }
            
                echo $this->_singleCommentOptions->after;
            }
        }
    }
    
    /**
     * 重载alt函数,以适应多级评论
     * 
     * @access public
     * @return void
     */
    public function alt(...$args)
    {
        $args = func_get_args();
        $num = func_num_args();
        
        $sequence = $this->levels <= 0 ? $this->sequence : $this->order;
        
        $split = $sequence % $num;
        echo $args[(0 == $split ? $num : $split) -1];
    }

    /**
     * 根据深度余数输出
     *
     * @access public
     * @param string $param 需要输出的值
     * @return void
     */
    public function levelsAlt()
    {
        $args = func_get_args();
        $num = func_num_args();
        $split = $this->levels % $num;
        echo $args[(0 == $split ? $num : $split) -1];
    }
    
    /**
     * 获取评论者客户端图标信息（浏览器、操作系统、IP 归属地）
     * 返回小图标 HTML，用于显示在评论 meta 区域
     *
     * @access private
     * @return string HTML 片段
     */
    private function getCommentClientIcons()
    {
        $html = '';

        if (!class_exists('ParseAgent') || !class_exists('VOID_IpDb')) {
            return $html;
        }

        // 操作系统图标
        $os = ParseAgent::getOs($this->agent);
        if (!empty($os) && $os !== 'Unknown') {
            $osIcon = self::getOsIconSvg($os);
            $html .= '<span class="comment-icon comment-icon-os" title="' . htmlspecialchars($os, ENT_QUOTES, 'UTF-8') . '">' . $osIcon . '</span>';
        }

        // 浏览器图标
        $browserInfo = self::detectBrowserSimple($this->agent);
        if (!empty($browserInfo)) {
            $browserIcon = $browserInfo['icon'];
            $browserName = $browserInfo['name'];
            $html .= '<span class="comment-icon comment-icon-browser" title="' . htmlspecialchars($browserName, ENT_QUOTES, 'UTF-8') . '">' . $browserIcon . '</span>';
        }

        // IP 归属地
        if (!empty($this->ip)) {
            $location = VOID_IpDb::locate($this->ip);
            if (!empty($location) && $location !== 'unknown') {
                $flagIcon = self::getLocationFlagSvg($location);
                $html .= '<span class="comment-icon comment-icon-location" title="' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '">' . $flagIcon . '</span>';
            }
        }

        return $html;
    }

    /**
     * 简化版浏览器检测，返回名称和 SVG 图标
     */
    private static function detectBrowserSimple($agent)
    {
        $agent = trim((string)$agent);
        if ($agent === '') return null;

        $browsers = [
            ['Edge', '/(?:Edg|Edge|EdgA|EdgiOS)\/([0-9.]+)/i'],
            ['Firefox', '/(?:Firefox|FxiOS)\/([0-9.]+)/i'],
            ['Chrome', '/(?:CriOS|HeadlessChrome|Chrome)\/([0-9.]+)/i'],
            ['Safari', '/Version\/([0-9.]+).*Safari/i'],
            ['Opera', '/(?:OPR|Opera)\/([0-9.]+)/i'],
            ['Vivaldi', '/Vivaldi\/([0-9.]+)/i'],
            ['WeChat', '/MicroMessenger\/([0-9.]+)/i'],
            ['QQ Browser', '/(?:QQBrowser|MQQBrowser)\/([0-9.]+)/i'],
            ['UC Browser', '/(?:UCBrowser|UCWEB)\/?([0-9.]*)/i'],
            ['Baidu', '/BIDUBrowser\/([0-9.]+)/i'],
            ['Sogou', '/(?:MetaSr|SE\s2\.X|SogouMobileBrowser)/i'],
            ['Maxthon', '/Maxthon(?:\/| )([0-9.]+)/i'],
            ['Samsung', '/SamsungBrowser\/([0-9.]+)/i'],
            ['Huawei', '/(?:HuaweiBrowser|HUAWEI Browser)\/([0-9.]+)/i'],
            ['MIUI', '/MiuiBrowser\/([0-9.]+)/i'],
            ['360', '/(?:360SE|360EE|QihooBrowser|QHBrowser)/i'],
        ];

        foreach ($browsers as $b) {
            if (preg_match($b[1], $agent)) {
                return ['name' => $b[0], 'icon' => self::getBrowserIconSvg($b[0])];
            }
        }

        return ['name' => 'Browser', 'icon' => self::getBrowserIconSvg('Unknown')];
    }

    /**
     * 获取操作系统 SVG 图标（16x16 简洁风格）
     */
    private static function getOsIconSvg($os)
    {
        $osLower = strtolower($os);
        $color = '#888';

        // Windows
        if (strpos($osLower, 'windows') !== false) {
            $color = '#0078D4';
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><path fill="' . $color . '" d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/></svg>';
        }
        // macOS / iOS
        if (strpos($osLower, 'macos') !== false || strpos($osLower, 'mac os') !== false || strpos($osLower, 'ios') !== false || strpos($osLower, 'ipados') !== false) {
            $color = '#333';
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><path fill="' . $color . '" d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>';
        }
        // Android
        if (strpos($osLower, 'android') !== false) {
            $color = '#78C257';
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><path fill="' . $color . '" d="M17.523 15.341a.96.96 0 0 0-.953.958c0 .529.427.958.953.958a.96.96 0 0 0 .954-.958.96.96 0 0 0-.954-.958zm-11.046 0a.96.96 0 0 0-.954.958c0 .529.427.958.954.958a.96.96 0 0 0 .953-.958.96.96 0 0 0-.953-.958zm11.4-5.772 1.997-3.466a.416.416 0 0 0-.152-.567.416.416 0 0 0-.566.152l-2.024 3.513A12.26 12.26 0 0 0 12 8.07c-1.862 0-3.618.406-5.132 1.131L4.844 5.688a.416.416 0 0 0-.566-.152.416.416 0 0 0-.152.567l1.997 3.466C2.688 11.667.463 15.473.463 19.745h23.074c0-4.272-2.225-8.078-5.66-10.176z"/></svg>';
        }
        // Linux / Ubuntu / Debian / Fedora / CentOS / Arch
        if (strpos($osLower, 'linux') !== false || strpos($osLower, 'ubuntu') !== false || strpos($osLower, 'debian') !== false || strpos($osLower, 'fedora') !== false || strpos($osLower, 'centos') !== false || strpos($osLower, 'arch') !== false) {
            $color = '#FCC624';
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><path fill="' . $color . '" d="M12.504 0c-.155 0-.315.008-.48.021-4.226.333-3.105 4.807-3.17 6.298-.076 1.092-.3 1.953-1.05 3.02-.885 1.051-2.127 2.75-2.716 4.521-.278.832-.41 1.684-.287 2.489a.424.424 0 0 0-.11.135c-.26.268-.45.6-.663.839-.199.199-.485.267-.797.4-.313.136-.658.269-.864.68-.09.189-.136.394-.132.602 0 .199.027.4.055.536.058.399.116.728.04.97-.249.68-.28 1.145-.106 1.484.174.334.535.47.94.601.81.2 1.91.135 2.774.6.926.466 1.866.67 2.616.47.526-.116.97-.464 1.208-.946.587-.003 1.23-.269 2.26-.334.699-.058 1.574.267 2.577.2.025.134.063.198.114.333l.003.003c.391.778 1.113 1.368 1.884 1.43.199.018.397.005.589-.04.18-.04.345-.115.49-.21.334-.26.563-.57.743-.84.09-.14.18-.29.24-.45.09-.19.14-.4.14-.62 0-.19-.03-.37-.07-.54-.08-.34-.22-.64-.34-.89-.04-.08-.07-.16-.1-.23.14-.04.27-.13.38-.24.26-.29.36-.72.21-1.06-.12-.26-.36-.45-.63-.53-.14-.04-.29-.05-.44-.03a.92.92 0 0 0-.37.12c-.11.08-.21.18-.29.31-.12.19-.21.42-.27.65-.03.12-.05.24-.06.36-.13-.11-.27-.21-.42-.29-.35-.2-.75-.3-1.16-.3-.21 0-.42.03-.62.09-.2.06-.38.15-.54.27-.16.12-.29.27-.39.44-.1.17-.16.37-.18.57-.01.14 0 .28.03.42-.26.09-.5.24-.7.44-.36.36-.56.86-.56 1.38 0 .14.02.28.05.42-.19-.11-.4-.19-.62-.24a1.93 1.93 0 0 0-.72-.02c-.23.04-.44.13-.62.26-.18.13-.33.3-.44.49-.11.19-.17.41-.17.63 0 .21.04.41.12.6.08.18.19.35.33.48.14.14.3.24.48.31.18.07.37.11.56.11.17 0 .34-.03.5-.08.16-.05.31-.13.44-.23.13-.1.23-.22.31-.36.08-.14.13-.29.15-.45.01-.13 0-.26-.03-.39.15-.06.29-.15.41-.26.12-.11.21-.24.27-.39.06-.15.08-.31.06-.47-.02-.16-.07-.31-.15-.45.23-.14.43-.33.57-.55.14-.22.22-.48.22-.74 0-.26-.07-.51-.2-.73-.13-.22-.31-.4-.53-.53.1-.17.16-.36.16-.56 0-.24-.07-.47-.2-.67-.13-.2-.31-.37-.52-.48-.21-.11-.45-.17-.69-.17-.17 0-.34.03-.5.09a1.3 1.3 0 0 0-.42.26c-.12.11-.21.25-.27.41-.06.16-.08.33-.06.5.02.17.08.33.17.47.09.14.21.26.36.35-.09.16-.15.34-.17.53a1.36 1.36 0 0 0 .33 1.07c-.14.14-.25.31-.32.5a1.36 1.36 0 0 0 .14 1.18z"/></svg>';
        }
        // HarmonyOS / OpenHarmony
        if (strpos($osLower, 'harmony') !== false || strpos($osLower, 'openharmony') !== false) {
            $color = '#0A59F7';
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="none" stroke="' . $color . '" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="' . $color . '"/></svg>';
        }
        // ChromeOS
        if (strpos($osLower, 'chromeos') !== false || strpos($osLower, 'chrome os') !== false || strpos($osLower, 'cros') !== false) {
            $color = '#1AA260';
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="none" stroke="' . $color . '" stroke-width="2"/><circle cx="12" cy="12" r="3.5" fill="' . $color . '"/></svg>';
        }
        // 通用
        return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><rect x="2" y="3" width="20" height="14" rx="2" fill="none" stroke="#888" stroke-width="1.5"/><path d="M8 21h8M12 17v4" stroke="#888" stroke-width="1.5" stroke-linecap="round"/></svg>';
    }

    /**
     * 获取浏览器 SVG 图标（16x16 简洁风格）
     */
    private static function getBrowserIconSvg($browser)
    {
        $map = [
            'Chrome' => ['#4285F4', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#fff"/><path fill="#EA4335" d="M12 8a4 4 0 0 1 3.8 2.8h3.1A8 8 0 0 0 8.3 6.3l2.5 4.3A4 4 0 0 1 12 8z"/><path fill="#34A853" d="M15.8 10.8A4 4 0 0 1 12 16l-2.5 4.3A8 8 0 0 0 20 12a8 8 0 0 0-.9-3.7h-3.3z"/><path fill="#FBBC05" d="M5.7 9.3A8 8 0 0 0 4 12c0 2.7 1.4 5.1 3.5 6.5l2.5-4.3A4 4 0 0 1 8.2 11l-2.5-1.7z"/><circle cx="12" cy="12" r="3.5" fill="#4285F4"/></svg>'],
            'Firefox' => ['#FF7139', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#FF7139"/><circle cx="12" cy="12" r="6" fill="#FFBD4F"/><circle cx="14" cy="10" r="3" fill="#FF7139"/></svg>'],
            'Safari' => ['#006CFF', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#fff"/><circle cx="12" cy="12" r="8" fill="none" stroke="#006CFF" stroke-width="1"/><line x1="12" y1="4" x2="12" y2="20" stroke="#006CFF" stroke-width="0.8"/><line x1="4" y1="12" x2="20" y2="12" stroke="#006CFF" stroke-width="0.8"/><circle cx="12" cy="12" r="2" fill="#FF3B30"/></svg>'],
            'Edge' => ['#0078D7', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#0078D7"/><path fill="#fff" d="M8 14.5c.5 1.5 2 2.5 3.5 2.5 2 0 3.5-1.5 3.5-3.5 0-1-.5-2-1.5-2.5.5.8.5 1.8 0 2.5-.5.8-1.5 1.2-2.5 1.2-1.5 0-2.8-1-3-2.5z"/><path fill="#fff" d="M14 9.5c-1-1-2.5-1.5-4-1.2C7.5 8.8 6 11 6 13.5c0 1.5.5 2.8 1.5 4 1-1 1.5-2.5 1.5-4 0-2 1-3.5 2.5-4 .5-.2 1.5 0 2.5 0z" opacity="0.6"/></svg>'],
            'Opera' => ['#FF1B2D', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#FF1B2D"/><ellipse cx="12" cy="12" rx="5" ry="8" fill="#fff"/></svg>'],
            'WeChat' => ['#07C160', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#07C160"/><circle cx="8.5" cy="10" r="1" fill="#fff"/><circle cx="15.5" cy="10" r="1" fill="#fff"/><path d="M9 14c1 1 3 1.5 4.5 1s3-1.5 3.5-2" stroke="#fff" stroke-width="1" fill="none" stroke-linecap="round"/></svg>'],
            'Vivaldi' => ['#EF3939', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#EF3939"/><path fill="#fff" d="M7 8l10 8-3 2z"/></svg>'],
            'QQ Browser' => ['#12B7F5', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#12B7F5"/><circle cx="12" cy="11" r="5" fill="none" stroke="#fff" stroke-width="1.5"/><circle cx="12" cy="11" r="1.5" fill="#fff"/></svg>'],
            'Sogou' => ['#FF5936', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#FF5936"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="10" font-weight="bold">搜</text></svg>'],
            'Baidu' => ['#2932E1', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#2932E1"/><circle cx="9" cy="11" r="2" fill="#fff"/><circle cx="15" cy="11" r="2" fill="#fff"/><circle cx="12" cy="15" r="1.5" fill="#fff"/></svg>'],
            'Maxthon' => ['#26B1F2', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#26B1F2"/><path fill="#fff" d="M8 8h8v8H8z" opacity="0.5"/></svg>'],
            '360' => ['#00B92A', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#00B92A"/><path fill="#fff" d="M12 6l2 6-2 6-2-6z"/></svg>'],
            'UC Browser' => ['#FF7F29', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#FF7F29"/><ellipse cx="12" cy="12" rx="5" ry="7" fill="#fff"/></svg>'],
            'Samsung' => ['#1428A0', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#1428A0"/><rect x="7" y="7" width="10" height="10" rx="2" fill="#fff"/></svg>'],
            'Huawei' => ['#CE0E2D', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#CE0E2D"/><path fill="#fff" d="M8 10c0-1 1-2 2-2s2 1 2 2v4c0 1-1 2-2 2s-2-1-2-2v-4z"/></svg>'],
            'MIUI' => ['#FF6900', '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="#FF6900"/><text x="12" y="16" text-anchor="middle" fill="#fff" font-size="9" font-weight="bold">M</text></svg>'],
        ];

        if (isset($map[$browser])) {
            return $map[$browser][1];
        }
        // 通用浏览器图标
        return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="none" stroke="#888" stroke-width="1.5"/><ellipse cx="12" cy="12" rx="5" ry="9" fill="none" stroke="#888" stroke-width="1"/><line x1="3" y1="9" x2="21" y2="9" stroke="#888" stroke-width="0.8"/><line x1="3" y1="15" x2="21" y2="15" stroke="#888" stroke-width="0.8"/></svg>';
    }

    /**
     * 获取归属地小图标（国旗 emoji 或定位图标）
     */
    private static function getLocationFlagSvg($location)
    {
        // 简单判断：如果包含中文则显示定位图标
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $location)) {
            return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><path fill="#888" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>';
        }
        // 非中文地区用地球图标
        return '<svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align:-2px;"><circle cx="12" cy="12" r="10" fill="none" stroke="#888" stroke-width="1.5"/><ellipse cx="12" cy="12" rx="4" ry="10" fill="none" stroke="#888" stroke-width="0.8"/><line x1="2" y1="8" x2="22" y2="8" stroke="#888" stroke-width="0.6"/><line x1="2" y1="16" x2="22" y2="16" stroke="#888" stroke-width="0.6"/></svg>';
    }

    /**
     * 获取头像 URL（使用 Cravatar 镜像，国内可访问）
     */
    private function getAvatarUrl($size = 64)
    {
        $mail = trim((string)$this->mail);
        if (empty($mail)) {
            return 'https://cravatar.cn/avatar/?d=mp&s=' . $size;
        }
        $hash = md5(strtolower($mail));
        return 'https://cravatar.cn/avatar/' . $hash . '?s=' . $size . '&d=mp';
    }

    /**
     * 评论回复链接
     * 
     * @access public
     * @param string $word 回复链接文字
     * @return void
     */
    public function reply($word = '')
    {
        // 对齐 Typecho 1.3：达到顶层时不再显示回复入口
        if ($this->options->commentsThreaded && !$this->isTopLevel && $this->parameter->allowComment) {
            $word = empty($word) ? '回复' : $word;
            $cancelWord = '取消回复';
            $this->pluginHandle()->trigger($plugged)->reply($word, $this);
            
            if (!$plugged) {
                echo '<a no-pjax href="' . substr($this->permalink, 0, - strlen($this->theId) - 1) . '?replyTo=' . $this->coid .
                    '#' . $this->parameter->respondId . '" rel="nofollow" data-comment-id="' . $this->theId .
                    '" data-comment-coid="' . $this->coid .
                    '" data-reply-word="' . htmlspecialchars((string)$word, ENT_QUOTES, 'UTF-8') .
                    '" data-cancel-word="' . htmlspecialchars($cancelWord, ENT_QUOTES, 'UTF-8') .
                    '" data-reply-state="idle" aria-pressed="false" onclick="return AjaxComment.handleReplyClick(\'' .
                    $this->theId . '\', ' . $this->coid . ', this);">' . $word . '</a>';
            }
        }
    }
    
    /**
     * 取消评论回复链接
     * 
     * @access public
     * @param string $word 取消回复链接文字
     * @return void
     */
    public function cancelReply($word = '')
    {
        if ($this->options->commentsThreaded) {
            $word = empty($word) ? '取消回复' : $word;
            $this->pluginHandle()->trigger($plugged)->cancelReply($word, $this);
            
            if (!$plugged) {
                // 兼容 Typecho 1.3：改为 get() 读取，避免使用已弃用的 request magic 属性
                $replyId = $this->request->filter('int')->get('replyTo');
                echo '<a id="cancel-comment-reply-link" href="' . (string)$this->getParentContentField('permalink', '') . '#' . $this->parameter->respondId .
                '" rel="nofollow"' . ($replyId ? '' : ' style="display:none"') . ' onclick="return AjaxComment.cancelActiveReply();">' . $word . '</a>';
            }
        }
    }
}
