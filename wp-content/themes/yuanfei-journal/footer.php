<?php if (!defined('ABSPATH')) { exit; } ?>
</main>
<footer class="site-footer">
    <div class="container footer-inner">
        <div><div class="footer-brand"><span class="brand-dot"></span><?php bloginfo('name'); ?></div><div class="footer-copy"><?php echo esc_html(get_bloginfo('description') ?: '以文字记录思考'); ?></div></div>
        <div class="footer-links"><span>© <?php echo esc_html(wp_date('Y')); ?></span><a href="<?php echo esc_url(admin_url('edit.php')); ?>">文章管理</a><a href="<?php echo esc_url(get_bloginfo('rss2_url')); ?>">RSS</a></div>
    </div>
</footer>
</div>
<div class="search-overlay" id="search-overlay" aria-hidden="true">
    <div class="search-dialog" role="dialog" aria-modal="true" aria-labelledby="search-title">
        <div class="search-dialog-head"><h2 id="search-title">搜索文章</h2><button class="icon-button js-search-close" type="button" aria-label="关闭搜索"><?php echo journal_icon('close'); ?></button></div>
        <?php get_search_form(); ?>
    </div>
</div>
<?php wp_footer(); ?>
</body></html>
