    /* WP TableWise settings — pure vanilla JS, no jQuery dependencies */
    (function(){
        'use strict';

        var presets = window.wptwAdminSettings.presets || {};
        var defClrs = window.wptwAdminSettings.defaults || {};
        var defaultLayout = window.wptwAdminSettings.defaultLayout || 'manuscript';
        var layoutPresetColors = {
            manuscript: {
                default: {
                    color_bg:'#0f172a', color_border:'#243044', color_header_bg:'#0b1120',
                    color_label:'#d97706', color_rt:'#94a3b8', color_rt_bar:'#d97706', color_rt_bar_bg:'#243044',
                    color_toggle_bg:'#f8fafc', color_toggle_fg:'#0f172a', color_toggle_border:'#f8fafc',
                    color_link:'#cbd5e1', color_link_hover:'#ffffff', color_active_bar:'#d97706',
                    color_active_bg:'#1e293b', color_number:'#d97706', color_back_top_bg:'#f8fafc', color_back_top_fg:'#0f172a'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#e5e7eb', color_header_bg:'#f8fafc',
                    color_label:'#9a3412', color_rt:'#64748b', color_rt_bar:'#d97706', color_rt_bar_bg:'#e5e7eb',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#334155', color_link_hover:'#0f172a', color_active_bar:'#d97706',
                    color_active_bg:'#fff7ed', color_number:'#d97706', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#0f172a', color_border:'#243044', color_header_bg:'#0b1120',
                    color_label:'#d97706', color_rt:'#94a3b8', color_rt_bar:'#d97706', color_rt_bar_bg:'#243044',
                    color_toggle_bg:'#f8fafc', color_toggle_fg:'#0f172a', color_toggle_border:'#f8fafc',
                    color_link:'#cbd5e1', color_link_hover:'#ffffff', color_active_bar:'#d97706',
                    color_active_bg:'#1e293b', color_number:'#d97706', color_back_top_bg:'#f8fafc', color_back_top_fg:'#0f172a'
                }
            },
            editorial: {
                default: {
                    color_bg:'#ffffff', color_border:'#d1d5db', color_header_bg:'#f8fafc',
                    color_label:'#111827', color_rt:'#64748b', color_rt_bar:'#111827', color_rt_bar_bg:'#e5e7eb',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#374151', color_link_hover:'#111827', color_active_bar:'#111827',
                    color_active_bg:'#f3f4f6', color_number:'#94a3b8', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#e5e7eb', color_header_bg:'#f9fafb',
                    color_label:'#111827', color_rt:'#6b7280', color_rt_bar:'#111827', color_rt_bar_bg:'#e5e7eb',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#374151', color_link_hover:'#111827', color_active_bar:'#111827',
                    color_active_bg:'#f3f4f6', color_number:'#9ca3af', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#111827', color_border:'#374151', color_header_bg:'#030712',
                    color_label:'#f9fafb', color_rt:'#9ca3af', color_rt_bar:'#f9fafb', color_rt_bar_bg:'#374151',
                    color_toggle_bg:'#f9fafb', color_toggle_fg:'#111827', color_toggle_border:'#f9fafb',
                    color_link:'#e5e7eb', color_link_hover:'#ffffff', color_active_bar:'#f9fafb',
                    color_active_bg:'#1f2937', color_number:'#9ca3af', color_back_top_bg:'#f9fafb', color_back_top_fg:'#111827'
                }
            },
            brutalist: {
                default: {
                    color_bg:'#18181b', color_border:'#0a0a0a', color_header_bg:'#050505',
                    color_label:'#f8fafc', color_rt:'#a1a1aa', color_rt_bar:'#f8fafc', color_rt_bar_bg:'#3f3f46',
                    color_toggle_bg:'#f8fafc', color_toggle_fg:'#0a0a0a', color_toggle_border:'#f8fafc',
                    color_link:'#e4e4e7', color_link_hover:'#ffffff', color_active_bar:'#f8fafc',
                    color_active_bg:'#27272a', color_number:'#a1a1aa', color_back_top_bg:'#f8fafc', color_back_top_fg:'#0a0a0a'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#111111', color_header_bg:'#f4f4f5',
                    color_label:'#111111', color_rt:'#52525b', color_rt_bar:'#111111', color_rt_bar_bg:'#d4d4d8',
                    color_toggle_bg:'#111111', color_toggle_fg:'#ffffff', color_toggle_border:'#111111',
                    color_link:'#27272a', color_link_hover:'#000000', color_active_bar:'#111111',
                    color_active_bg:'#f4f4f5', color_number:'#71717a', color_back_top_bg:'#111111', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#09090b', color_border:'#000000', color_header_bg:'#000000',
                    color_label:'#ffffff', color_rt:'#a1a1aa', color_rt_bar:'#ffffff', color_rt_bar_bg:'#27272a',
                    color_toggle_bg:'#ffffff', color_toggle_fg:'#000000', color_toggle_border:'#ffffff',
                    color_link:'#f4f4f5', color_link_hover:'#ffffff', color_active_bar:'#ffffff',
                    color_active_bg:'#27272a', color_number:'#d4d4d8', color_back_top_bg:'#ffffff', color_back_top_fg:'#000000'
                }
            },
            default: {
                default: {
                    color_bg:'#ffffff', color_border:'#e8e8e8', color_header_bg:'#fafafa',
                    color_label:'#666666', color_rt:'#737373', color_rt_bar:'#111111', color_rt_bar_bg:'#e8e8e8',
                    color_toggle_bg:'#111111', color_toggle_fg:'#ffffff', color_toggle_border:'#111111',
                    color_link:'#333333', color_link_hover:'#000000', color_active_bar:'#111111',
                    color_active_bg:'#f4f4f4', color_number:'#737373', color_back_top_bg:'#111111', color_back_top_fg:'#ffffff'
                },
                light: {
                    color_bg:'#ffffff', color_border:'#e5e7eb', color_header_bg:'#f3f4f6',
                    color_label:'#4b5563', color_rt:'#6b7280', color_rt_bar:'#111827', color_rt_bar_bg:'#d1d5db',
                    color_toggle_bg:'#111827', color_toggle_fg:'#ffffff', color_toggle_border:'#111827',
                    color_link:'#374151', color_link_hover:'#111827', color_active_bar:'#111827',
                    color_active_bg:'#f9fafb', color_number:'#6b7280', color_back_top_bg:'#111827', color_back_top_fg:'#ffffff'
                },
                dark: {
                    color_bg:'#1a1a1a', color_border:'#3a3a3a', color_header_bg:'#111111',
                    color_label:'#e5e5e5', color_rt:'#a3a3a3', color_rt_bar:'#e5e5e5', color_rt_bar_bg:'#3a3a3a',
                    color_toggle_bg:'#e5e5e5', color_toggle_fg:'#111111', color_toggle_border:'#e5e5e5',
                    color_link:'#d4d4d4', color_link_hover:'#ffffff', color_active_bar:'#e5e5e5',
                    color_active_bg:'#2a2a2a', color_number:'#a3a3a3', color_back_top_bg:'#e5e5e5', color_back_top_fg:'#111111'
                }
            }
        };
        presets['__reset'] = defClrs;
        var currentPreset = 'default';
        var lastPreset = 'default';
        var savedPreset = 'default';

        /* ── TABS ── */
        var tabs   = document.querySelectorAll('.wptw-tab');
        var panels = document.querySelectorAll('.wptw-panel');
        function activateTab(id){
            tabs.forEach(function(t){ t.setAttribute('aria-selected','false'); });
            panels.forEach(function(p){ p.classList.remove('is-active'); });
            var tab = document.querySelector('.wptw-tab[data-tab="'+id+'"]');
            var pnl = document.querySelector('.wptw-panel[data-panel="'+id+'"]');
            if(tab) tab.setAttribute('aria-selected','true');
            if(pnl) pnl.classList.add('is-active');
            try{ localStorage.setItem('wptw_tab',id); }catch(e){}
        }
        tabs.forEach(function(t){
            t.addEventListener('click', function(){ activateTab(this.dataset.tab); });
        });
        document.querySelectorAll('[data-jump-tab]').forEach(function(btn){
            btn.addEventListener('click', function(){ activateTab(this.dataset.jumpTab); });
        });
        var init; try{ init=localStorage.getItem('wptw_tab'); }catch(e){}
        activateTab(init||'visibility');

        /* ── Segmented radio — keep .on class in sync ── */
        document.querySelectorAll('.wptw-seg').forEach(function(seg){
            seg.querySelectorAll('input[type="radio"]').forEach(function(r){
                r.addEventListener('change', function(){
                    seg.querySelectorAll('.wptw-segopt').forEach(function(o){ o.classList.remove('on'); });
                    if(r.checked) r.closest('.wptw-segopt').classList.add('on');
                });
            });
        });

        /* ── Heading picker visual toggle ── */
        document.querySelectorAll('.wptw-hpick input').forEach(function(cb){
            cb.addEventListener('change', function(){
                cb.closest('.wptw-hpick').classList.toggle('on', cb.checked);
            });
        });

        /* ── Sliders synced to number inputs ── */
        document.querySelectorAll('.wptw-slsync').forEach(function(sl){
            var numId = sl.dataset.num;
            var out   = sl.nextElementSibling; // <output>
            sl.addEventListener('input', function(){
                if(out) out.textContent = sl.value;
                if(numId){ var n=document.getElementById(numId); if(n) n.value=sl.value; }
            });
        });

        /* ── Sticky offset slider ↔ number ── */
        var sRange = document.getElementById('wptw-sticky-range');
        var sOut   = document.getElementById('wptw-sticky-out');
        var sNum   = document.getElementById('wptw-sticky-num');
        if(sRange && sNum){
            sRange.addEventListener('input', function(){ sNum.value=sRange.value; if(sOut) sOut.textContent=sRange.value+'px'; });
            sNum.addEventListener('input', function(){ sRange.value=sNum.value; if(sOut) sOut.textContent=sNum.value+'px'; });
        }

        /* ── Sticky sub-field dim ── */
        var sToggle = document.getElementById('wptw-sticky-toggle');
        var sSub    = document.getElementById('wptw-sticky-sub');
        function dimSticky(){ if(sSub) sSub.style.opacity = (sToggle&&sToggle.checked)?'1':'0.4'; }
        if(sToggle){ sToggle.addEventListener('change', dimSticky); dimSticky(); }

        /* ── Native colour inputs: show hex text + sync ── */
        document.querySelectorAll('.wptw-color').forEach(function(inp){
            var hex = inp.nextElementSibling; // .wptw-chex
            inp.addEventListener('input', function(){ if(hex) hex.textContent = inp.value; });
        });

        /* ── Per-colour reset buttons ── */
        document.querySelectorAll('.wptw-creset').forEach(function(btn){
            btn.addEventListener('click', function(){
                var key = btn.dataset.key;
                var presetId = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                var preset = presetId ? resolvedPreset(presetId) : null;
                var def = preset && preset[key] ? preset[key] : btn.dataset.default;
                var inp = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                if(inp){
                    inp.value = def;
                    var hex = inp.nextElementSibling;
                    if(hex) hex.textContent = def;
                }
                if(typeof updatePreview === 'function') updatePreview();
            });
        });

        /* ── Colour presets ── */
        document.querySelectorAll('.wptw-pbtn').forEach(function(btn){
            btn.addEventListener('click', function(){
                if(btn.dataset.preset !== '__reset'){
                    currentPreset = btn.dataset.preset;
                    lastPreset = currentPreset;
                } else {
                    currentPreset = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                    lastPreset = currentPreset;
                }
                var p = resolvedPreset(btn.dataset.preset);
                if(!p) return;
                applyColors(p);
                if(typeof updatePreview === 'function') updatePreview();
            });
        });

        /* ── Font preview ── */
        var fontSel = document.getElementById('wptw-font-family');
        var fontPrv = document.getElementById('wptw-font-preview');
        function showFontPreview(font){
            if(!fontPrv) return;
            if(!font||font==='system'){ fontPrv.textContent=''; fontPrv.style.fontFamily=''; return; }
            fontPrv.style.fontFamily="'"+font+"',sans-serif";
            fontPrv.textContent='The quick brown fox - Aa Bb Cc 0123456789';
        }
        if(fontSel){ fontSel.addEventListener('change', function(){ showFontPreview(this.value); }); showFontPreview(fontSel.value); }

        /* Live preview */
        var form = document.getElementById('wptw-form');
        var preview = document.querySelector('.wptw-preview-toc');
        var previewCanvas = document.querySelector('.wptw-preview__canvas');
        var previewMode = document.getElementById('wptw-preview-toggle');
        var optionName = window.wptwAdminSettings.optionName || 'wptw_settings';
        var cssMap = {
            color_bg:'--wptw-bg', color_border:'--wptw-border', color_header_bg:'--wptw-head-bg',
            color_label:'--wptw-label-c', color_rt:'--wptw-rt-c', color_rt_bar:'--wptw-rtbar-fill',
            color_rt_bar_bg:'--wptw-rtbar-bg', color_toggle_bg:'--wptw-tog-bg', color_toggle_fg:'--wptw-tog-fg',
            color_toggle_border:'--wptw-tog-bdr', color_link:'--wptw-link', color_link_hover:'--wptw-link-hov',
            color_active_bar:'--wptw-bar', color_active_bg:'--wptw-act-bg', color_number:'--wptw-num-c'
        };
        function field(key){ return form ? form.querySelector('[name="'+optionName+'['+key+']"]:not([type="hidden"])') : null; }
        function checked(key){ var el = field(key); return !!(el && el.checked); }
        function value(key, fallback){
            var checkedRadio = form ? form.querySelector('[name="'+optionName+'['+key+']"][type="radio"]:checked') : null;
            if(checkedRadio) return checkedRadio.value;
            var el = field(key);
            return el ? el.value : fallback;
        }
        function activeLayout(){
            return value('toc_layout', defaultLayout || 'manuscript') || 'manuscript';
        }
        function resolvedPreset(presetId){
            var id = presetId === '__reset' ? (currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default') : presetId;
            var layout = activeLayout();
            var layoutPresets = layoutPresetColors[layout] || layoutPresetColors.default || {};
            if(id === 'default') return layoutPresets.default || presets.default || defClrs;
            if(id === 'dark' && layoutPresets.dark) return layoutPresets.dark;
            return presets[id] || layoutPresets.default || presets.default || defClrs;
        }
        function colorsMatch(colors){
            return Object.keys(cssMap).every(function(key){
                var el = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                return el && colors && colors[key] && el.value.toLowerCase() === colors[key].toLowerCase();
            });
        }
        function inferSavedPreset(){
            var ids = ['default','light','dark','ocean','forest','rose'];
            for(var i = 0; i < ids.length; i++){
                if(colorsMatch(resolvedPreset(ids[i]))) return ids[i];
            }
            return 'default';
        }
        function fontStack(font){
            if(!font) return 'inherit';
            if(font === 'system') return "system-ui,-apple-system,'Helvetica Neue',Arial,sans-serif";
            return "'" + font.replace(/'/g,'') + "',system-ui,sans-serif";
        }
        function applyColors(colors){
            Object.keys(colors || {}).forEach(function(key){
                var inp = document.querySelector('input.wptw-color[data-key="'+key+'"]');
                if(!inp) return;
                inp.value = colors[key];
                var hex = inp.nextElementSibling;
                if(hex) hex.textContent = colors[key];
            });
        }
        function rgb(hex){
            hex = String(hex || '').replace('#','').trim();
            if(hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
            if(!/^[0-9a-f]{6}$/i.test(hex)) hex = 'ffffff';
            return [parseInt(hex.slice(0,2),16), parseInt(hex.slice(2,4),16), parseInt(hex.slice(4,6),16)];
        }
        function lum(hex){
            return rgb(hex).map(function(ch){
                var v = ch / 255;
                return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
            }).reduce(function(sum, v, i){ return sum + v * [0.2126,0.7152,0.0722][i]; }, 0);
        }
        function contrast(a,b){
            var l1 = lum(a) + 0.05, l2 = lum(b) + 0.05;
            return Math.max(l1,l2) / Math.min(l1,l2);
        }
        function blend(fg,bg,amt){
            var f = rgb(fg), b = rgb(bg);
            return '#' + f.map(function(v,i){
                var n = Math.round((v * amt) + (b[i] * (1 - amt))).toString(16);
                return n.length === 1 ? '0' + n : n;
            }).join('');
        }
        function primaryOn(bg){ return lum(bg) < 0.5 ? '#ffffff' : '#0f172a'; }
        function secondaryOn(bg){ return blend(primaryOn(bg), bg, 0.66); }
        function normalizedColors(){
            var c = {};
            Object.keys(cssMap).forEach(function(key){ c[key] = value(key, ''); });
            var bg = c.color_bg || '#ffffff', head = c.color_header_bg || '#fafaf9';
            if(Math.abs(lum(bg) - lum(head)) < 0.06){
                head = lum(bg) < 0.5 ? blend('#ffffff', bg, 0.10) : blend('#0f172a', bg, 0.06);
                c.color_header_bg = head;
            }
            if(contrast(c.color_label, head) < 3) c.color_label = secondaryOn(head);
            if(contrast(c.color_rt, head) < 3) c.color_rt = secondaryOn(head);
            if(contrast(c.color_link, bg) < 4.5) c.color_link = primaryOn(bg);
            if(contrast(c.color_link_hover, bg) < 5) c.color_link_hover = primaryOn(bg);
            if(contrast(c.color_number, bg) < 2.3) c.color_number = blend(primaryOn(bg), bg, 0.48);
            if(Math.abs(lum(c.color_active_bg) - lum(bg)) < 0.04){
                c.color_active_bg = lum(bg) < 0.5 ? blend('#ffffff', bg, 0.09) : blend('#0f172a', bg, 0.05);
            }
            if(Math.min(contrast(c.color_active_bar, bg), contrast(c.color_active_bar, head)) < 2.4){
                c.color_active_bar = activeLayout() === 'brutalist'
                    ? (lum(bg) < 0.5 ? '#ffffff' : '#111827')
                    : (lum(bg) < 0.5 ? '#d97706' : '#111827');
            }
            if(contrast(c.color_rt_bar, bg) < 2.4){
                c.color_rt_bar = c.color_active_bar;
            }
            if(contrast(c.color_toggle_fg, c.color_toggle_bg) < 4.5) c.color_toggle_fg = primaryOn(c.color_toggle_bg);
            return c;
        }
        function updateLayoutCards(){
            document.querySelectorAll('.wptw-layout-card').forEach(function(card){
                var input = card.querySelector('input');
                card.classList.toggle('on', !!(input && input.checked));
            });
        }
        function updatePresetButtons(){
            document.querySelectorAll('.wptw-pbtn[data-preset]').forEach(function(btn){
                var previewId = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                btn.classList.toggle('is-saved-active', btn.dataset.preset !== '__reset' && btn.dataset.preset === savedPreset);
                btn.classList.toggle('is-preview', btn.dataset.preset !== '__reset' && btn.dataset.preset === previewId);
            });
        }
        var previewLayout = '';
        function previewToggle(){
            return '<button type="button" class="wptw-toc__toggle" aria-expanded="true"><span class="wptw-toc__tog-text">Hide</span><svg class="wptw-toc__tog-icon" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true"><path d="M2 4.5L6 8.5L10 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>';
        }
        function renderPreviewMarkup(layout){
            if(!preview || previewLayout === layout) return;
            previewLayout = layout;
            if(layout === 'manuscript'){
                preview.innerHTML =
                    '<div class="toc-manuscript-eyebrow"><span class="wptw-toc__label"></span><span class="toc-ms-actions"><span class="wptw-toc__rt">5 min read</span>'+previewToggle()+'</span></div>'+
                    '<div class="wptw-toc__body"><ol class="wptw-toc__list toc-manuscript-list" role="list">'+
                    '<li class="wptw-toc__item is-done toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">I</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">Preface</span></a></span></li>'+
                    '<li class="wptw-toc__item is-done toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">II</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">Origins and context</span></a><span class="toc-ms-sub"><a class="wptw-toc__link toc-ms-sub-link" href="#">The founding years</a><a class="wptw-toc__link toc-ms-sub-link" href="#">Key influences</a></span></span></li>'+
                    '<li class="wptw-toc__item is-active toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">III</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">A theory of everything</span></a><span class="toc-ms-sub"><a class="wptw-toc__link toc-ms-sub-link" href="#">Framework overview</a><a class="wptw-toc__link toc-ms-sub-link" href="#">Core propositions</a></span></span></li>'+
                    '<li class="wptw-toc__item toc-ms-item"><span class="toc-ms-node"><span class="toc-ms-node-inner"></span></span><span class="toc-ms-content"><span class="wptw-toc__num toc-ms-roman">IV</span><a class="wptw-toc__link toc-ms-main" href="#"><span class="wptw-toc__text toc-ms-title">Evidence and proof</span></a></span></li>'+
                    '</ol></div><div class="toc-ms-footer"><span class="toc-ms-footer-label">Progress</span><div class="wptw-toc__prog toc-ms-track" role="presentation"><div class="wptw-toc__prog-fill toc-ms-track-fill" style="width:42%"></div></div></div>';
            } else if(layout === 'brutalist'){
                preview.innerHTML =
                    '<div class="wptw-toc__head toc-brut-header"><div class="wptw-toc__head-left"><span class="wptw-toc__label toc-brut-title"></span><span class="wptw-toc__rt">5 min read</span></div><div class="wptw-toc__actions toc-brut-actions">'+previewToggle()+'</div></div>'+
                    '<div class="wptw-toc__body"><ol class="wptw-toc__list" role="list">'+
                    '<li class="wptw-toc__item is-done toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">1</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Introduction</span></a></span><span class="toc-brut-check"><svg width="8" height="6" viewBox="0 0 8 6" fill="none"><path d="M1 3l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></span></li>'+
                    '<li class="wptw-toc__item is-done toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">2</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Background and theory</span></a><span class="toc-brut-subs"><a class="wptw-toc__link toc-brut-sub-link" href="#">Historical context</a><a class="wptw-toc__link toc-brut-sub-link" href="#">Key frameworks</a></span></span><span class="toc-brut-check"><svg width="8" height="6" viewBox="0 0 8 6" fill="none"><path d="M1 3l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span></span></li>'+
                    '<li class="wptw-toc__item is-active toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">3</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Methodology</span></a><span class="toc-brut-subs"><a class="wptw-toc__link toc-brut-sub-link" href="#">Research design</a><a class="wptw-toc__link toc-brut-sub-link" href="#">Data collection</a></span><span class="toc-brut-pill">Reading now</span></span><span class="toc-brut-check"></span></span></li>'+
                    '<li class="wptw-toc__item toc-brut-item"><span class="toc-brut-row"><span class="toc-brut-step"><span class="wptw-toc__num toc-brut-num">4</span></span><span class="toc-brut-body"><a class="wptw-toc__link toc-brut-main" href="#"><span class="wptw-toc__text toc-brut-name">Results</span></a></span><span class="toc-brut-check"></span></span></li>'+
                    '</ol></div><div class="wptw-toc__prog toc-brut-progress" role="presentation"><div class="wptw-toc__prog-fill toc-brut-progress-fill" style="width:42%"></div></div>';
            } else if(layout === 'default') {
                preview.innerHTML =
                    '<div class="wptw-toc__head"><div class="wptw-toc__head-left"><span class="wptw-toc__label"></span><span class="wptw-toc__rt">5 min read</span></div>'+previewToggle()+'</div>'+
                    '<div class="wptw-toc__prog" role="presentation"><div class="wptw-toc__prog-fill" style="width:42%"></div></div>'+
                    '<div class="wptw-toc__body"><ol class="wptw-toc__list" role="list">'+
                    '<li class="wptw-toc__item is-done"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.</span><span class="wptw-toc__text">Getting started</span></a></li>'+
                    '<li class="wptw-toc__item is-done wptw-toc__item--sub wptw-toc__item--d3"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">1.1.</span><span class="wptw-toc__text">Setup checklist</span></a></li>'+
                    '<li class="wptw-toc__item is-active"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.</span><span class="wptw-toc__text">Design decisions</span></a></li>'+
                    '<li class="wptw-toc__item wptw-toc__item--sub wptw-toc__item--d3"><a class="wptw-toc__link" href="#"><span class="wptw-toc__num">2.1.</span><span class="wptw-toc__text">Responsive behavior</span></a></li>'+
                    '</ol></div>';
            } else {
                preview.innerHTML =
                    '<div class="wptw-toc__head toc-ed-header"><div class="wptw-toc__head-left toc-ed-header-left"><span class="toc-ed-icon"><svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M2 4h12M2 8h8M2 12h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span><span class="wptw-toc__label toc-ed-label"></span></div><div class="wptw-toc__actions toc-ed-actions"><span class="toc-ed-badge">4 sections</span><span class="wptw-toc__rt">5 min read</span>'+previewToggle()+'</div></div>'+
                    '<div class="wptw-toc__body toc-ed-body"><ol class="wptw-toc__list" role="list">'+
                    '<li class="wptw-toc__item is-done toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">&#10003;</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Overview</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">2 min</span></span></span></li>'+
                    '<li class="wptw-toc__item is-done toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">&#10003;</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Prerequisites</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">3 min</span></span></span></li>'+
                    '<li class="wptw-toc__item is-active toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">3</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Installation</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">5 min</span></span><span class="toc-ed-sub"><a class="wptw-toc__link toc-ed-sub-link" href="#">Package setup</a><a class="wptw-toc__link toc-ed-sub-link" href="#">Environment variables</a><a class="wptw-toc__link toc-ed-sub-link" href="#">Verify your install</a></span></span></li>'+
                    '<li class="wptw-toc__item toc-ed-item"><span class="toc-ed-gutter"><span class="toc-ed-dot">4</span></span><span class="toc-ed-row"><a class="wptw-toc__link toc-ed-main" href="#"><span class="wptw-toc__text toc-ed-title">Configuration</span></a><span class="toc-ed-meta"><span class="toc-ed-mins">4 min</span></span></span></li>'+
                    '</ol></div><div class="toc-ed-footer"><div class="wptw-toc__prog toc-ed-progress" role="presentation"><div class="wptw-toc__prog-fill toc-ed-progress-fill" style="width:42%"></div></div><span class="toc-ed-progress-label">42% done</span></div>';
            }
        }
        function syncPreviewState(){
            if(!preview) return;
            var items = Array.prototype.slice.call(preview.querySelectorAll('.wptw-toc__item'));
            var activeIndex = items.findIndex(function(item){ return item.classList.contains('is-active'); });
            items.forEach(function(item, idx){
                item.classList.toggle('is-done', activeIndex > -1 && idx < activeIndex);
            });
            if(preview.classList.contains('wptw-toc--layout-editorial')){
                preview.querySelectorAll('.toc-ed-dot').forEach(function(dot, idx){
                    dot.textContent = idx < activeIndex ? '\u2713' : String(idx + 1);
                });
            }
            var edLabel = preview.querySelector('.toc-ed-progress-label');
            if(edLabel) edLabel.textContent = '42% done';
        }
        function updatePreview(){
            if(!preview) return;
            var colors = normalizedColors();
            Object.keys(cssMap).forEach(function(key){ preview.style.setProperty(cssMap[key], colors[key] || value(key, '')); });
            preview.style.setProperty('--wptw-radius', value('border_radius', 4) + 'px');
            preview.style.setProperty('--wptw-label-sz', value('font_size_label', 10) + 'px');
            preview.style.setProperty('--wptw-label-ls', (parseInt(value('letter_spacing_label', 13), 10) / 100) + 'em');
            preview.style.setProperty('--wptw-label-tt', value('text_transform_label', 'uppercase'));
            preview.style.setProperty('--wptw-rt-sz', value('font_size_rt', 10) + 'px');
            preview.style.setProperty('--wptw-num-sz', value('font_size_num', 11) + 'px');
            preview.style.setProperty('--wptw-flink', value('font_size_link', 14) + 'px');
            preview.style.setProperty('--wptw-fsub', value('font_size_sub', 13) + 'px');
            preview.style.setProperty('--wptw-font', fontStack(value('font_family', 'system')));
            var title = preview.querySelector('.wptw-toc__label');
            if(title) title.textContent = value('toc_title', 'Contents') || 'Contents';
            var layout = value('toc_layout', defaultLayout || 'manuscript');
            preview.className = preview.className.replace(/\bwptw-toc--layout-[a-z0-9_-]+/g, '').trim();
            preview.classList.add('wptw-toc--layout-' + layout);
            renderPreviewMarkup(layout);
            syncPreviewState();
            title = preview.querySelector('.wptw-toc__label');
            if(title) title.textContent = value('toc_title', 'Contents') || 'Contents';
            updateLayoutCards();
            updatePresetButtons();
            preview.querySelectorAll('.wptw-toc__num').forEach(function(num){ num.style.display = checked('show_numbers') ? '' : 'none'; });
            var rt = preview.querySelector('.wptw-toc__rt');
            if(rt) rt.style.display = checked('reading_time') ? '' : 'none';
            preview.querySelectorAll('.wptw-toc__prog,.toc-ms-footer,.toc-ed-footer').forEach(function(prog){
                prog.style.display = checked('reading_progress') ? '' : 'none';
            });
            var isOpen = value('default_state', 'open') !== 'closed';
            var list = preview.querySelector('.wptw-toc__list');
            var toggle = preview.querySelector('.wptw-toc__toggle');
            var ttext = preview.querySelector('.wptw-toc__tog-text');
            if(list) list.style.display = isOpen ? '' : 'none';
            if(toggle) toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if(ttext) ttext.textContent = isOpen ? 'Hide' : 'Show';
        }
        if(form){
            form.addEventListener('input', function(e){
                if(e.target && e.target.classList && e.target.classList.contains('wptw-color')){
                    currentPreset = 'custom';
                }
                updatePreview();
            });
            form.addEventListener('change', function(e){
                if(e.target && e.target.name === optionName + '[toc_layout]'){
                    currentPreset = currentPreset && currentPreset !== 'custom' ? currentPreset : lastPreset || 'default';
                    lastPreset = currentPreset;
                    applyColors(resolvedPreset(currentPreset));
                } else if(e.target && e.target.classList && e.target.classList.contains('wptw-color')){
                    currentPreset = 'custom';
                }
                updatePreview();
            });
        }
        if(previewMode && previewCanvas){
            previewMode.addEventListener('click', function(){
                var mobile = previewCanvas.classList.toggle('is-mobile');
                previewMode.textContent = mobile ? 'Mobile' : 'Desktop';
            });
        }
        savedPreset = inferSavedPreset();
        currentPreset = savedPreset;
        lastPreset = savedPreset;
        updatePreview();

        /* ── Copy shortcode ── */
        document.querySelectorAll('.wptw-copybtn').forEach(function(btn){
            btn.addEventListener('click', function(){
                var t = btn.dataset.copy;
                if(navigator.clipboard) navigator.clipboard.writeText(t);
                btn.textContent='Copied!';
                setTimeout(function(){ btn.textContent='Copy'; }, 2000);
            });
        });

        /* ── Save flash ── */
        if (window.wptwAdminSettings.settingsUpdated) {
        var sv = document.getElementById('wptw-saved');
        if(sv){ sv.classList.add('on'); setTimeout(function(){ sv.classList.remove('on'); }, 3000); }
        }

    })();
