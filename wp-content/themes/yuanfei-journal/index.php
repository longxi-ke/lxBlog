<?php get_header(); ?>
<?php $view = journal_view(); ?>
<?php if ($view === 'archive') : ?>
    <div class="container inner-page"><div class="page-heading"><h1>文章归档</h1><p>按时间回看每一篇记录</p></div><div class="content-narrow content-panel"><ul class="archive-list">
    <?php $archive_posts = get_posts(array('numberposts' => -1, 'post_status' => 'publish')); foreach ($archive_posts as $post) : setup_postdata($post); ?><li><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y-m-d')); ?></time><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li><?php endforeach; wp_reset_postdata(); ?>
    </ul><?php if (!$archive_posts) : ?><p>还没有文章，开始写下第一篇吧。</p><?php endif; ?></div></div>
<?php elseif ($view === 'tags') : ?>
    <div class="container inner-page"><div class="page-heading"><h1>标签</h1><p>按主题浏览文章</p></div><div class="content-narrow content-panel"><div class="tag-cloud-wrap">
    <?php $all_tags = get_tags(array('hide_empty' => true)); foreach ($all_tags as $tag) : ?><a href="<?php echo esc_url(get_tag_link($tag)); ?>"># <?php echo esc_html($tag->name); ?> <small><?php echo esc_html($tag->count); ?></small></a><?php endforeach; ?>
    </div><?php if (!$all_tags) : ?><p>文章添加标签后，会显示在这里。</p><?php endif; ?></div></div>
<?php else : ?>
    <?php if (is_home() && !is_paged() && !is_search()) : ?>
        <section class="hero"><div class="hero-grid" aria-hidden="true"></div><div class="hero-orb one" aria-hidden="true"></div><div class="hero-orb two" aria-hidden="true"></div><div class="hero-orb three" aria-hidden="true"></div><div class="container"><h1><?php bloginfo('name'); ?></h1><p><?php echo esc_html(get_bloginfo('description') ?: '阅读、思考、技术'); ?></p><div class="hero-mark"></div></div></section>
    <?php else : ?>
        <div class="container inner-page" style="padding-bottom:0"><div class="page-heading"><h1><?php echo is_search() ? '搜索结果' : (is_archive() ? get_the_archive_title() : '所有文章'); ?></h1><p><?php echo is_search() ? '关键词：' . esc_html(get_search_query()) : '阅读、思考、技术'; ?></p></div></div>
    <?php endif; ?>
    <section class="container articles-section"><div class="section-heading"><h2><span aria-hidden="true">🔖</span><?php echo is_search() ? '搜索结果' : (is_home() && !is_paged() ? '最新文章' : '文章列表'); ?></h2><span class="section-line"></span></div>
        <?php if (have_posts()) : ?><div class="article-grid"><?php while (have_posts()) : the_post(); get_template_part('template-parts/card'); endwhile; ?></div><div class="pagination"><?php echo paginate_links(array('prev_text' => '上一页', 'next_text' => '下一页')); ?></div>
        <?php else : ?><div class="empty-card"><h3><?php echo is_search() ? '没有找到相关文章' : '这里还没有文章'; ?></h3><p><?php echo is_search() ? '换个关键词再试试。' : '从第一篇文章开始，记录阅读与思考。'; ?></p><?php if (!is_search()) : ?><a class="button-primary" href="<?php echo esc_url(journal_admin_url()); ?>">新增文章</a><?php endif; ?></div><?php endif; ?>
    </section>
<?php endif; ?>
<?php get_footer(); ?>
