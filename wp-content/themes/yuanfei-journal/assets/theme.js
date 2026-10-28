(function(){
  const root=document.documentElement;
  const themeButton=document.querySelector('.js-theme-toggle');
  const menuButton=document.querySelector('.js-menu-toggle');
  const menu=document.getElementById('mobile-nav');
  const search=document.getElementById('search-overlay');
  function updateThemeIcon(){if(themeButton){themeButton.setAttribute('aria-label',root.dataset.theme==='dark'?'切换浅色模式':'切换深色模式')}}
  updateThemeIcon();
  themeButton?.addEventListener('click',function(){const next=root.dataset.theme==='dark'?'light':'dark';root.dataset.theme=next;try{localStorage.setItem('journal-theme',next)}catch(e){}updateThemeIcon()});
  menuButton?.addEventListener('click',function(){const open=!menu.classList.contains('is-open');menu.classList.toggle('is-open',open);menuButton.setAttribute('aria-expanded',String(open));menuButton.setAttribute('aria-label',open?'关闭菜单':'打开菜单')});
  function closeSearch(){search.classList.remove('is-open');search.setAttribute('aria-hidden','true');document.body.style.overflow=''}
  document.querySelectorAll('.js-search-open').forEach(button=>button.addEventListener('click',function(){search.classList.add('is-open');search.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';search.querySelector('input')?.focus()}));
  document.querySelector('.js-search-close')?.addEventListener('click',closeSearch);
  search?.addEventListener('click',function(event){if(event.target===search)closeSearch()});
  document.addEventListener('keydown',function(event){if(event.key==='Escape'){closeSearch();menu?.classList.remove('is-open');menuButton?.setAttribute('aria-expanded','false')}});
})();
