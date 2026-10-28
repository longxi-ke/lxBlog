<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article class="container inner-page"><div class="content-narrow"><header class="page-heading"><h1><?php the_title(); ?></h1><p><?php echo is_page('about') ? '关于这个博客与写下它的人' : (is_page('links') ? '值得分享的站点和朋友' : ''); ?></p></header><div class="content-panel"><div class="entry-content"><?php the_content(); ?></div><?php if (current_user_can('edit_post', get_the_ID())) : ?><div class="post-footer"><span></span><a href="<?php echo esc_url(get_edit_post_link()); ?>">编辑此页 ↗</a></div><?php endif; ?></div></div></article>
<?php endwhile; ?>
<?php get_footer(); ?>
