<?php
/**
 * head.php
 * 
 * <head>
 * 
 * @author      熊猫小A
 * @version     2019-01-15 0.1
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$setting = $GLOBALS['VOIDSetting']; 

if (isset($_POST['void_action'])) {
    if ($_POST['void_action'] == 'getLoginAction') {
        if ($this->request->isPost()) {
            echo $this->options->loginAction;
        }
        exit;
    }
}
?>
<!DOCTYPE HTML>
<html lang="zh-CN">
    <head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="HandheldFriendly" content="true">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php 
    $banner = '';
    $description = '';
    if($this->is('post') || $this->is('page')){
        if($this->fields->banner != '')
            $banner=$this->fields->banner;
        if($this->fields->excerpt != '')
            $description = $this->fields->excerpt;
    }else{
        $description = Helper::options()->description;
    }
    ?>
    <title><?php Contents::title($this); ?></title>
    <meta name="author" content="<?php $this->author(); ?>" />
    <meta name="description" content="<?php if($description != '') echo $description; else $this->excerpt(50); ?>" />
    <meta property="og:title" content="<?php Contents::title($this); ?>" />
    <meta property="og:description" content="<?php if($description != '') echo $description; else $this->excerpt(50); ?>" />
    <meta property="og:site_name" content="<?php Contents::title($this); ?>" />
    <meta property="og:type" content="<?php if($this->is('post') || $this->is('page')) echo 'article'; else echo 'website'; ?>" />
    <meta property="og:url" content="<?php $this->permalink(); ?>" />
    <meta property="og:image" content="<?php echo $banner; ?>" />
    <meta property="article:published_time" content="<?php echo date('c', $this->created); ?>" />
    <meta property="article:modified_time" content="<?php echo date('c', $this->modified); ?>" />
    <meta name="twitter:title" content="<?php Contents::title($this); ?>" />
    <meta name="twitter:description" content="<?php if($description != '') echo $description; else $this->excerpt(50); ?>" />
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:site" content="@<?php echo $setting['twitterId']; ?>" />
    <meta name="twitter:creator" content="@<?php echo $setting['twitterId']; ?>" />
    <meta name="twitter:image" content="<?php echo $banner; ?>" />
    <?php $this->header('commentReply=&description=&social=0'); ?>

    <!--CSS-->
    <link rel="stylesheet" href="<?php Utils::indexTheme('/assets/bundle.css');?>">
    <link rel="stylesheet" href="<?php Utils::indexTheme('/assets/VOID.css');?>">

    <!--JS-->
    <script src="<?php Utils::indexTheme('/assets/bundle-header.js'); ?>"></script>
    <?php
    // 安全注入前端配置：通过 JSON 输出，避免字符串拼接导致的 XSS 风险
    // Utils::index / getBuildTime 为 echo 函数，用输出缓冲捕获其返回值
    $voidCapture = function ($fn) {
        ob_start();
        $fn();
        return ob_get_clean();
    };
    $voidConfig = array(
        'PJAX'                    => (bool)$setting['pjax'],
        'searchBase'              => $voidCapture(function () { Utils::index('/search/'); }),
        'home'                    => $voidCapture(function () { Utils::index('/'); }),
        'buildTime'               => $voidCapture(function () { Utils::getBuildTime(); }),
        'enableMath'              => (bool)$setting['enableMath'],
        'lazyload'                => (bool)$setting['lazyload'],
        'colorScheme'             => (int)$setting['colorScheme'],
        'headerMode'              => (int)$setting['headerMode'],
        'followSystemColorScheme' => (bool)$setting['followSystemColorScheme'],
        'browserLevelLoadingLazy' => (bool)$setting['browserLevelLoadingLazy'],
        'VOIDPlugin'              => (bool)$setting['VOIDPlugin'],
        'votePath'                => $voidCapture(function () { Utils::index('/action/void?'); }),
        'lightBg'                 => '',
        'darkBg'                  => '',
        'lineNumbers'             => (bool)$setting['lineNumbers'],
        'darkModeTime'            => array(
            'start' => (float)$setting['darkModeTime']['start'],
            'end'   => (float)$setting['darkModeTime']['end'],
        ),
        'horizontalBg'            => !empty($setting['siteBg']),
        'verticalBg'              => !empty($setting['siteBgVertical']),
        'indexStyle'              => (int)$setting['indexStyle'],
        'version'                 => (int)$GLOBALS['VOIDVersion'],
        'isDev'                   => true,
    );
    ?>
    <script type="application/json" id="void-config"><?php echo json_encode($voidConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <script src="<?php Utils::indexTheme('/assets/header.js'); ?>"></script>
    
    <?php echo $setting['head']; ?>
    <style>
        <?php if(!empty($setting['desktopBannerHeight'])): ?>
        @media screen and (min-width: 768px){
            main>.lazy-wrap{min-height: <?php echo $setting['desktopBannerHeight']; ?>vh;}
        }
        <?php endif; ?>

        <?php if(!empty($setting['mobileBannerHeight'])): ?>
        @media screen and (max-width: 768px){
            main>.lazy-wrap{min-height: <?php echo $setting['mobileBannerHeight']; ?>vh;}
        }
        <?php endif; ?>
    </style>

    <?php if (array_key_exists('src', $setting['brandFont']) && !empty($setting['brandFont']['src'])): ?>
    <style>
    @font-face {
        font-family: "BrandFont";
        src: url("<?php echo $setting['brandFont']['src']; ?>");
        font-display: swap;
    }
    .brand {
        font-family: BrandFont, sans-serif;
        font-style: <?php echo $setting['brandFont']['style']; ?>!important;
        font-weight: <?php echo $setting['brandFont']['weight']; ?>!important;
    }
    </style>
    <?php endif; ?>

    <link href="https://fonts.googleapis.cn/css?family=Open+Sans:300,400,700&display=swap" rel="stylesheet">
    <?php if(Utils::isSerif($setting)): ?>
        <link id="stylesheet_noto" href="https://fonts.googleapis.cn/css?family=Noto+Serif+SC:300,400,700&display=swap&subset=chinese-simplified" rel="stylesheet">
    <?php endif; ?>

    <?php if($setting['useFiraCodeFont']): ?>
        <link href="https://fonts.googleapis.cn/css?family=Fira+Code&display=swap" rel="stylesheet">
        <style>.yue code, .yue tt {font-family: "Fira Code", Menlo, Monaco, Consolas, "Courier New", monospace}</style>
    <?php endif; ?>

    </head>
