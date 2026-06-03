(function(){
    var KEY='svaps_dark';
    function on(){ return localStorage.getItem(KEY)==='1'; }
    // Apply to <html> IMMEDIATELY — before any paint
    document.documentElement.classList.toggle('dark-mode', on());
    document.addEventListener('DOMContentLoaded', function(){
        document.body.classList.toggle('dark-mode', on());
        syncBtn(on());
        var btn=document.getElementById('darkModeToggle');
        if(btn) btn.addEventListener('click', function(){
            var now=!on();
            localStorage.setItem(KEY, now?'1':'0');
            document.documentElement.classList.toggle('dark-mode',now);
            document.body.classList.toggle('dark-mode',now);
            syncBtn(now);
        });
    });
    window.addEventListener('storage',function(e){
        if(e.key!==KEY)return;
        var now=e.newValue==='1';
        document.documentElement.classList.toggle('dark-mode',now);
        document.body&&document.body.classList.toggle('dark-mode',now);
        syncBtn(now);
    });
    function syncBtn(now){
        var i=document.getElementById('darkModeIcon');
        if(!i)return;
        i.innerHTML=now
            ?'<path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"/>'
            :'<path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>';
    }
})();