<?php if (!defined('ABSPATH')) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>try{var t=localStorage.getItem('journal-theme');if(t==='dark'||(!t&&matchMedia('(prefers-color-scheme: dark)').matches))document.documentElement.dataset.theme='dark'}catch(e){}</script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site-shell">
<a class="screen-reader-text" href="#content">跳至正文</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>，返回首页"><span class="brand-dot"></span><span class="brand-name"><?php bloginfo('name'); ?></span></a>
        <nav class="desktop-nav" aria-label="主导航">
            <?php foreach (journal_nav_items() as $item) : ?>
                <a class="nav-link<?php echo $item['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"><?php echo journal_icon($item['icon']); ?><span><?php echo esc_html($item['label']); ?></span></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <span class="nav-separator" aria-hidden="true"></span>
            <button class="icon-button js-search-open" type="button" aria-label="搜索文章"><?php echo journal_icon('search'); ?></button>
            <button class="icon-button js-theme-toggle" type="button" aria-label="切换深色模式"><?php echo journal_icon('moon'); ?></button>
            <a class="write-link" href="<?php echo esc_url(journal_admin_url()); ?>" title="进入后台新增文章"><?php echo journal_icon('write'); ?><span>写文章</span></a>
            <button class="icon-button mobile-menu-button js-menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="打开菜单"><?php echo journal_icon('menu'); ?></button>
        </div>
    </div>
</header>
<div class="mobile-panel" id="mobile-nav">
    <nav aria-label="移动端导航">
        <?php foreach (journal_nav_items() as $item) : ?>
            <a class="nav-link<?php echo $item['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"><?php echo journal_icon($item['icon']); ?><span><?php echo esc_html($item['label']); ?></span></a>
        <?php endforeach; ?>
        <a class="write-link" href="<?php echo esc_url(journal_admin_url()); ?>"><?php echo journal_icon('write'); ?><span>写文章</span></a>
    </nav>
</div>
<main id="content" class="site-main">
