<?php
/** Theme setup and small presentation helpers. */

if (!defined('ABSPATH')) { exit; }

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
    add_editor_style('style.css');
    register_nav_menus(array('primary' => '顶部导航'));
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('yuanfei-journal', get_stylesheet_uri(), array(), '1.0.0');
    wp_enqueue_script('yuanfei-journal', get_template_directory_uri() . '/assets/theme.js', array(), '1.0.0', true);
});

add_filter('query_vars', function ($vars) { $vars[] = 'journal_view'; return $vars; });
add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query()) { return; }
    $view = isset($_GET['journal_view']) ? sanitize_key(wp_unslash($_GET['journal_view'])) : '';
    if (in_array($view, array('archive', 'tags'), true)) {
        $query->set('journal_view', $view);
    }
});

function journal_view() {
    $view = get_query_var('journal_view');
    return in_array($view, array('archive', 'tags'), true) ? $view : '';
}

function journal_nav_items() {
    $about = get_page_by_path('about');
    $links = get_page_by_path('links');
    return array(
        array('label' => 'Home', 'url' => home_url('/'), 'icon' => 'home', 'active' => is_home() && !is_search() && !journal_view()),
        array('label' => 'About Me', 'url' => $about ? get_permalink($about) : home_url('/?journal_view=about'), 'icon' => 'user', 'active' => is_page('about')),
        array('label' => 'Link', 'url' => $links ? get_permalink($links) : home_url('/?journal_view=links'), 'icon' => 'link', 'active' => is_page('links')),
        array('label' => 'Archive', 'url' => add_query_arg('journal_view', 'archive', home_url('/')), 'icon' => 'archive', 'active' => journal_view() === 'archive'),
        array('label' => 'Tags', 'url' => add_query_arg('journal_view', 'tags', home_url('/')), 'icon' => 'tags', 'active' => journal_view() === 'tags'),
    );
}

function journal_icon($name) {
    $paths = array(
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'user' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7v2"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>',
        'archive' => '<path d="M16 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h7"/>',
        'tags' => '<path d="m12 2 10 5-10 5L2 7l10-5zM2 12l10 5 10-5M2 17l10 5 10-5"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'write' => '<path d="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L10 16l-4 1 1-4 9.5-9.5z"/>',
    );
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
}

function journal_admin_url() {
    $destination = admin_url('post-new.php');
    return is_user_logged_in() ? $destination : wp_login_url($destination);
}

function journal_read_minutes($post_id) {
    $content = get_post_field('post_content', $post_id);
    $length = function_exists('mb_strlen') ? mb_strlen(wp_strip_all_tags($content), 'UTF-8') : strlen(wp_strip_all_tags($content));
    return max(1, (int) ceil($length / 400));
}

function journal_ensure_pages() {
    $pages = array(
        'about' => array('关于我', '<p>欢迎来到我的博客。这里记录阅读、思考与技术探索。</p><p>登录 WordPress 后台后，可以随时编辑这一页，写下你的个人介绍。</p>'),
        'links' => array('友情链接', '<p>这里可以整理值得关注的网站与朋友。登录 WordPress 后台后，编辑本页即可添加链接。</p>'),
    );
    foreach ($pages as $slug => $data) {
        if (!get_page_by_path($slug)) {
            wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $data[0], 'post_content' => $data[1]));
        }
    }
}
add_action('after_switch_theme', 'journal_ensure_pages');
