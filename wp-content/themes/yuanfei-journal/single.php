<?php get_header(); ?>
<?php while (have_posts()) : the_post(); ?>
<article class="container inner-page"><div class="content-narrow">
    <header class="page-heading"><h1><?php the_title(); ?></h1><div class="entry-meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y-m-d')); ?></time><span>·</span><span><?php echo esc_html(journal_read_minutes(get_the_ID())); ?> 分钟阅读</span><?php $categories = get_the_category(); if ($categories) : ?><span>·</span><span><?php echo esc_html($categories[0]->name); ?></span><?php endif; ?></div></header>
    <div class="content-panel"><?php if (has_post_thumbnail()) : the_post_thumbnail('large', array('class' => 'entry-cover')); endif; ?><div class="entry-content"><?php the_content(); wp_link_pages(); ?></div>
        <div class="post-footer"><a href="<?php echo esc_url(home_url('/')); ?>">← 返回首页</a><?php if (current_user_can('edit_post', get_the_ID())) : ?><a href="<?php echo esc_url(get_edit_post_link()); ?>">编辑这篇文章 ↗</a><?php endif; ?></div>
    </div>
</div></article>
<?php endwhile; ?>
<?php get_footer(); ?>
