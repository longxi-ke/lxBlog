<?php
/** Article card. */
$palette = array(
    array('#ffedd5', '#fef3c7', '#f97316', '#f59e0b'),
    array('#dbeafe', '#e0f2fe', '#3b82f6', '#06b6d4'),
    array('#fce7f3', '#fef3c7', '#ec4899', '#f97316'),
    array('#dcfce7', '#ccfbf1', '#10b981', '#14b8a6'),
    array('#ede9fe', '#fce7f3', '#8b5cf6', '#ec4899'),
);
$colors = $palette[get_the_ID() % count($palette)];
$tags = get_the_tags();
?>
<article <?php post_class('article-card'); ?>>
    <a class="card-link" href="<?php the_permalink(); ?>">
        <div class="card-media">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('medium_large', array('loading' => 'lazy')); ?>
            <?php else : ?>
                <div class="card-art" style="--art-a:<?php echo esc_attr($colors[0]); ?>;--art-b:<?php echo esc_attr($colors[1]); ?>;--art-main:<?php echo esc_attr($colors[2]); ?>;--art-alt:<?php echo esc_attr($colors[3]); ?>">
                    <span class="card-letter"><?php echo esc_html(function_exists('mb_substr') ? mb_substr(get_the_title(), 0, 1, 'UTF-8') : substr(get_the_title(), 0, 1)); ?></span>
                    <span class="card-lines" aria-hidden="true"><i></i><i></i><i></i></span>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="card-meta"><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date('Y-m-d')); ?></time><span class="meta-dot"></span><span><?php echo esc_html(journal_read_minutes(get_the_ID())); ?> min</span></div>
            <h3 class="card-title"><?php the_title(); ?></h3>
            <p class="card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 36, '…')); ?></p>
            <?php if ($tags) : ?><div class="card-tags"><?php foreach (array_slice($tags, 0, 3) as $tag) : ?><span class="tag-pill"><?php echo esc_html($tag->name); ?></span><?php endforeach; ?></div><?php endif; ?>
        </div>
    </a>
</article>
