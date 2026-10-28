<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="journal-search">搜索文章</label>
    <input id="journal-search" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="输入关键词搜索文章…" required>
    <button class="button-primary" type="submit">搜索</button>
</form>
