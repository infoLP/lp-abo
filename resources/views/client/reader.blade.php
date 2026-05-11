<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $issue->magazine->name }} — N°{{ $issue->issue_number }}</title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { background:#1a1a2e; font-family:system-ui,sans-serif; }
    #toolbar {
        position:fixed; top:0; left:0; right:0; z-index:100;
        background:#16213e; display:flex; align-items:center; gap:8px;
        padding:8px 16px; box-shadow:0 2px 8px rgba(0,0,0,0.5); flex-wrap:wrap;
    }
    #toolbar .title {
        color:#e2e8f0; font-weight:600; font-size:14px;
        flex:1; min-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    #toolbar button {
        background:#0f3460; border:none; color:#e2e8f0;
        padding:6px 10px; border-radius:6px; cursor:pointer; font-size:13px; transition:background .2s;
    }
    #toolbar button:hover { background:#533483; }
    #toolbar button:disabled { background:#2d3748; color:#718096; cursor:not-allowed; }
    #toolbar input[type="number"] {
        width:55px; padding:5px 8px; border-radius:6px;
        border:1px solid #4a5568; background:#2d3748; color:#e2e8f0;
        font-size:13px; text-align:center;
    }
    #toolbar input[type="text"] {
        padding:5px 10px; border-radius:6px;
        border:1px solid #4a5568; background:#2d3748; color:#e2e8f0;
        font-size:13px; width:180px;
    }
    #toolbar .sep { width:1px; height:28px; background:#4a5568; margin:0 4px; }
    #page-info { color:#a0aec0; font-size:13px; white-space:nowrap; }
    #search-results { color:#68d391; font-size:12px; white-space:nowrap; }
    #viewer-container {
        margin-top:62px; display:flex; flex-direction:column; align-items:center;
        padding:24px 16px; gap:16px; min-height:calc(100vh - 62px);
    }
    .page-wrapper { position:relative; box-shadow:0 4px 20px rgba(0,0,0,0.6); }
    .page-wrapper canvas { display:block; }
    .highlight-layer { position:absolute; top:0; left:0; pointer-events:none; }
    .search-highlight {
        position:absolute; background:rgba(255,213,0,0.4);
        border:1px solid rgba(255,213,0,0.8); border-radius:2px;
    }
    .search-highlight.current { background:rgba(255,100,0,0.5); border-color:rgba(255,100,0,0.9); }
    #loader {
        position:fixed; inset:0; background:#1a1a2e;
        display:flex; flex-direction:column; align-items:center; justify-content:center;
        z-index:200; gap:16px;
    }
    #loader-bar-bg { width:300px; height:6px; background:#2d3748; border-radius:3px; overflow:hidden; }
    #loader-bar { height:100%; background:#2563eb; width:0%; transition:width .3s; border-radius:3px; }
    #loader p { color:#a0aec0; font-size:14px; }
</style>
</head>
<body>

<div id="loader">
    <div id="loader-bar-bg"><div id="loader-bar"></div></div>
    <p id="loader-text">Chargement du document…</p>
</div>

<div id="toolbar">
    <button onclick="history.back()" title="Retour">← Retour</button>
    <div class="sep"></div>
    <span class="title">{{ $issue->magazine->name }} — N°{{ $issue->issue_number }} — {{ $issue->title }}</span>
    <div class="sep"></div>
    <button id="btn-prev" disabled>◀</button>
    <input type="number" id="page-input" min="1" value="1">
    <span id="page-info">/ —</span>
    <button id="btn-next" disabled>▶</button>
    <div class="sep"></div>
    <button id="btn-zoom-out">−</button>
    <button id="btn-zoom-in">+</button>
    <button id="btn-zoom-fit">⊡ Ajuster</button>
    <div class="sep"></div>
    <input type="text" id="search-input" placeholder="Rechercher…">
    <button id="btn-search-prev" disabled>↑</button>
    <button id="btn-search-next" disabled>↓</button>
    <span id="search-results"></span>
</div>

<div id="viewer-container"></div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

const PDF_URL   = "{{ route('client.issue.stream', $issue) }}";
const container = document.getElementById('viewer-container');
const loader    = document.getElementById('loader');
const loaderBar = document.getElementById('loader-bar');
const loaderTxt = document.getElementById('loader-text');

let pdfDoc = null, currentPage = 1, scale = 1.0, pageCount = 0;
let searchMatches = [], searchCurrent = -1, searchQuery = '';

const loadingTask = pdfjsLib.getDocument({ url: PDF_URL, withCredentials: true });
loadingTask.onProgress = (p) => {
    if (p.total) {
        const pct = Math.round((p.loaded / p.total) * 100);
        loaderBar.style.width = pct + '%';
        loaderTxt.textContent = `Chargement… ${pct}%`;
    }
};
loadingTask.promise.then(pdf => {
    pdfDoc = pdf; pageCount = pdf.numPages;
    document.getElementById('page-info').textContent = `/ ${pageCount}`;
    document.getElementById('page-input').max = pageCount;
    loaderBar.style.width = '100%';
    loaderTxt.textContent = 'Rendu en cours…';
    return pdf.getPage(1).then(page => {
        const vp = page.getViewport({ scale: 1 });
        scale = Math.min((window.innerWidth - 48) / vp.width, 1.5);
        return renderAllPages();
    });
}).then(() => {
    loader.style.display = 'none';
    updateNavButtons(); scrollToPage(1);
    setTimeout(observePages, 1000);
}).catch(err => {
    loaderTxt.textContent = 'Erreur : ' + err.message;
    loaderBar.style.background = '#fc8181';
});

function renderAllPages() {
    const promises = [];
    for (let i = 1; i <= pageCount; i++) promises.push(renderPage(i));
    return Promise.all(promises);
}
function renderPage(num) {
    return pdfDoc.getPage(num).then(page => {
        const vp = page.getViewport({ scale });
        const wrapper = document.createElement('div');
        wrapper.className = 'page-wrapper'; wrapper.id = `page-${num}`;
        wrapper.style.width = Math.floor(vp.width) + 'px';
        wrapper.style.height = Math.floor(vp.height) + 'px';
        const canvas = document.createElement('canvas');
        canvas.width = Math.floor(vp.width); canvas.height = Math.floor(vp.height);
        wrapper.appendChild(canvas);
        const hlLayer = document.createElement('div');
        hlLayer.className = 'highlight-layer';
        hlLayer.style.width = canvas.width + 'px'; hlLayer.style.height = canvas.height + 'px';
        wrapper.appendChild(hlLayer);
        container.appendChild(wrapper);
        return page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise
            .then(() => page.getTextContent())
            .then(tc => {
                wrapper._textContent = tc; wrapper._viewport = vp; wrapper._pageNum = num;
                loaderBar.style.width = Math.round((container.querySelectorAll('.page-wrapper').length / pageCount) * 100) + '%';
            });
    });
}

function scrollToPage(num) {
    const el = document.getElementById(`page-${num}`);
    if (el) el.scrollIntoView({ behavior:'smooth', block:'start' });
    currentPage = num;
    document.getElementById('page-input').value = num;
    updateNavButtons();
}
function updateNavButtons() {
    document.getElementById('btn-prev').disabled = currentPage <= 1;
    document.getElementById('btn-next').disabled = currentPage >= pageCount;
}
document.getElementById('btn-prev').addEventListener('click', () => { if (currentPage > 1) scrollToPage(currentPage - 1); });
document.getElementById('btn-next').addEventListener('click', () => { if (currentPage < pageCount) scrollToPage(currentPage + 1); });
document.getElementById('page-input').addEventListener('change', (e) => {
    const v = parseInt(e.target.value);
    if (v >= 1 && v <= pageCount) scrollToPage(v);
});
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && entry.intersectionRatio > 0.4) {
            const num = parseInt(entry.target.id.replace('page-', ''));
            currentPage = num;
            document.getElementById('page-input').value = num;
            updateNavButtons();
        }
    });
}, { threshold: 0.4 });
const observePages = () => document.querySelectorAll('.page-wrapper').forEach(el => observer.observe(el));

async function rerender() {
    container.innerHTML = ''; searchMatches = []; searchCurrent = -1;
    document.getElementById('search-results').textContent = '';
    await renderAllPages(); observePages();
    if (searchQuery) await doSearch(searchQuery);
    scrollToPage(currentPage);
}
document.getElementById('btn-zoom-in').addEventListener('click', () => { if (scale < 3.0) { scale = Math.min(scale + 0.25, 3.0); rerender(); } });
document.getElementById('btn-zoom-out').addEventListener('click', () => { if (scale > 0.5) { scale = Math.max(scale - 0.25, 0.5); rerender(); } });
document.getElementById('btn-zoom-fit').addEventListener('click', () => {
    pdfDoc.getPage(1).then(page => {
        scale = Math.min((window.innerWidth - 48) / page.getViewport({ scale:1 }).width, 1.5);
        rerender();
    });
});

async function doSearch(query) {
    searchMatches = []; searchCurrent = -1; clearHighlights();
    if (!query || query.length < 2) {
        document.getElementById('search-results').textContent = '';
        updateSearchButtons(); return;
    }
    const q = query.toLowerCase();
    document.querySelectorAll('.page-wrapper').forEach(wrapper => {
        if (!wrapper._textContent) return;
        wrapper._textContent.items.forEach(item => {
            if (item.str.toLowerCase().includes(q)) {
                const tx = item.transform, vp = wrapper._viewport;
                searchMatches.push({
                    page: wrapper._pageNum, wrapper,
                    x: tx[4] * scale,
                    y: vp.height - tx[5] * scale - (item.height || 10) * scale,
                    w: item.width * scale, h: (item.height || 10) * scale,
                });
            }
        });
    });
    searchMatches.forEach((m, idx) => drawHighlight(m, idx));
    const total = searchMatches.length;
    document.getElementById('search-results').textContent = total > 0 ? `${total} résultat(s)` : 'Aucun résultat';
    updateSearchButtons();
    if (total > 0) { searchCurrent = 0; goToMatch(0); }
}
function drawHighlight(m, idx) {
    const hl = document.createElement('div');
    hl.className = 'search-highlight' + (idx === searchCurrent ? ' current' : '');
    hl.dataset.idx = idx;
    hl.style.cssText = `left:${m.x}px;top:${m.y}px;width:${m.w}px;height:${m.h}px`;
    m.wrapper.querySelector('.highlight-layer').appendChild(hl);
}
function clearHighlights() { document.querySelectorAll('.highlight-layer').forEach(l => l.innerHTML = ''); }
function goToMatch(idx) {
    if (idx < 0 || idx >= searchMatches.length) return;
    searchCurrent = idx;
    document.querySelectorAll('.search-highlight').forEach(el => {
        el.classList.toggle('current', parseInt(el.dataset.idx) === idx);
    });
    scrollToPage(searchMatches[idx].page);
    document.getElementById('search-results').textContent = `${idx + 1} / ${searchMatches.length}`;
    updateSearchButtons();
}
function updateSearchButtons() {
    const has = searchMatches.length > 0;
    document.getElementById('btn-search-prev').disabled = !has || searchCurrent <= 0;
    document.getElementById('btn-search-next').disabled = !has || searchCurrent >= searchMatches.length - 1;
}
let searchTimer = null;
document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimer);
    searchQuery = e.target.value.trim();
    searchTimer = setTimeout(() => doSearch(searchQuery), 400);
});
document.getElementById('btn-search-prev').addEventListener('click', () => { if (searchCurrent > 0) goToMatch(searchCurrent - 1); });
document.getElementById('btn-search-next').addEventListener('click', () => { if (searchCurrent < searchMatches.length - 1) goToMatch(searchCurrent + 1); });
document.getElementById('search-input').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') e.shiftKey
        ? document.getElementById('btn-search-prev').click()
        : document.getElementById('btn-search-next').click();
});
</script>
</body>
</html>
