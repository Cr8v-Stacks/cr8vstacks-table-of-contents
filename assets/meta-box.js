(function(){
    'use strict';

    document.addEventListener('DOMContentLoaded', function(){
        var cb = document.getElementById('wptw-meta-disable');
        var box = document.getElementById('wptw-meta-options');
        if (!cb || !box) return;

        cb.addEventListener('change', function(){
            box.classList.toggle('wptw-meta-dimmed', this.checked);
        });
    });
})();
