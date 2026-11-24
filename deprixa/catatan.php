<?php
// Catatan dan tugas: papan Kanban pribadi dan papan perusahaan.
require_once __DIR__ . '/report-lib.php';
require_once __DIR__ . '/catatan-lib.php';
rp_boot(false);
kb_ensure_tables();

$scope = (($_GET['b'] ?? '') === 'company') ? 'company' : 'personal';
rp_start('Catatan dan tugas', 'th-large', 'Masuk sebagai ' . ($_SESSION['user_name'] ?? ''));
?>
<div class="kb-top">
    <div class="kb-tabs" role="tablist">
        <button type="button" class="kb-tab" data-scope="personal">Catatan saya</button>
        <button type="button" class="kb-tab" data-scope="company">Catatan perusahaan</button>
    </div>
    <div class="kb-actions">
        <button type="button" class="kb-btn" id="kb-archive-btn">Arsip <span id="kb-archive-n" class="kb-badge">0</span></button>
        <button type="button" class="kb-btn kb-btn-main" id="kb-add-col">+ Kolom</button>
    </div>
</div>
<p class="kb-hint" id="kb-hint"></p>
<p class="kb-hint" id="kb-used"></p>
<div id="kb-board" class="kb-board"></div>
<div id="kb-archive" class="kb-archive" style="display:none"></div>

<!-- Kartu -->
<div class="kb-modal" id="kb-card-modal" style="display:none">
    <div class="kb-modal-box">
        <h4 id="kb-card-title-h">Kartu</h4>
        <label>Judul</label>
        <input type="text" id="kc-title" maxlength="200" placeholder="Apa yang perlu dicatat atau dikerjakan?">
        <label>Catatan</label>
        <textarea id="kc-notes" rows="5" maxlength="4000" placeholder="Detail, langkah, atau link (opsional)"></textarea>
        <div class="kb-row">
            <div><label>Status (kolom)</label><select id="kc-col"></select></div>
            <div><label>Prioritas</label><select id="kc-pr"><option value="rendah">Rendah</option><option value="sedang" selected>Sedang</option><option value="tinggi">Tinggi</option></select></div>
        </div>
        <div class="kb-row">
            <div><label>Tenggat</label><input type="date" id="kc-due"></div>
            <div id="kc-assignee-wrap"><label>Penanggung jawab</label><select id="kc-assignee"></select></div>
        </div>
        <label>Lampiran <span class="kb-sub">(maks 5 file, 5 MB per file)</span></label>
        <div id="kc-files" class="kb-files"></div>
        <input type="file" id="kc-file" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.xlsx,.docx,.csv,.txt,image/*">
        <div class="kb-modal-btns">
            <button type="button" class="kb-btn kb-danger" id="kc-del">Hapus</button>
            <span class="kb-spacer"></span>
            <button type="button" class="kb-btn" id="kc-cancel">Batal</button>
            <button type="button" class="kb-btn kb-btn-main" id="kc-save">Simpan</button>
        </div>
    </div>
</div>

<!-- Kolom -->
<div class="kb-modal" id="kb-col-modal" style="display:none">
    <div class="kb-modal-box kb-small">
        <h4 id="kcol-h">Kolom</h4>
        <label>Nama kolom</label>
        <input type="text" id="kcol-name" maxlength="60" placeholder="Contoh: Menunggu persetujuan">
        <label>Warna</label>
        <div class="kb-swatches" id="kcol-colors"></div>
        <label class="kb-check"><input type="checkbox" id="kcol-done"> Kolom ini berarti <b>selesai</b> (kartunya masuk arsip tiap Senin)</label>
        <div class="kb-modal-btns">
            <button type="button" class="kb-btn kb-danger" id="kcol-del">Hapus kolom</button>
            <span class="kb-spacer"></span>
            <button type="button" class="kb-btn" id="kcol-cancel">Batal</button>
            <button type="button" class="kb-btn kb-btn-main" id="kcol-save">Simpan</button>
        </div>
    </div>
</div>
<?php
$js = <<<'JS'
var KB = { scope: 'personal', cols: [], cards: [], people: [], me: '', archived: 0, view: 'board', editCard: null, pending: [], used: 0, keep: 90, editCol: null, color: '#8a949e' };
var COLORS = ['#8a949e', '#3b82f6', '#f59e0b', '#22a06b', '#e11d27', '#8b5cf6'];
function fmtSize(b) { return b >= 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
var PR = { rendah: 'Rendah', sedang: 'Sedang', tinggi: 'Tinggi' };
function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
function api(action, data, ok) {
    var p = new URLSearchParams(); p.append('action', action); p.append('scope', KB.scope);
    Object.keys(data || {}).forEach(function (k) {
        var v = data[k];
        if (Array.isArray(v)) { v.forEach(function (x) { p.append(k + '[]', x); }); } else if (v !== undefined && v !== null) { p.append(k, v); }
    });
    fetch('catatan-api.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: p.toString() })
        .then(function (r) { return r.json(); })
        .then(function (res) { if (res && res.success) { if (ok) { ok(res); } } else { rpNotify({ icon: 'error', title: 'Gagal', text: res ? res.message : 'Tidak ada respons' }); load(); } })
        .catch(function () { rpNotify({ icon: 'error', title: 'Gagal menghubungi server' }); });
}
function dueInfo(c, done) {
    if (!c.due_date) { return ''; }
    var d = new Date(c.due_date + 'T00:00:00'), t = new Date(); t.setHours(0, 0, 0, 0);
    var late = !done && d < t;
    var txt = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    return '<span class="kb-chip' + (late ? ' kb-late' : '') + '">' + (late ? 'Lewat ' : '') + esc(txt) + '</span>';
}
function render() {
    var hint = KB.scope === 'personal' ? 'Papan ini hanya bisa dilihat akunmu.' : 'Papan bersama: semua admin dan pegawai bisa melihat dan mengubahnya.';
    $('#kb-hint').text(hint + ' Kartu di kolom "selesai" otomatis masuk arsip tiap Senin.');
    $('.kb-tab').each(function () { $(this).toggleClass('on', $(this).data('scope') === KB.scope); });
    $('#kb-archive-n').text(KB.archived);
    $('#kb-used').text('Lampiran terpakai: ' + fmtSize(KB.used) + '. Lampiran kartu arsip dihapus otomatis setelah ' + KB.keep + ' hari (kartunya tetap ada).');
    var html = '';
    KB.cols.forEach(function (col, i) {
        var cards = KB.cards.filter(function (c) { return c.column_id === col.id; });
        html += '<section class="kb-col" data-col="' + col.id + '"><header style="border-top-color:' + esc(col.color) + '">' +
            '<b>' + esc(col.name) + '</b>' + (col.is_done ? '<span class="kb-done-mark" title="Kolom selesai">&#10003;</span>' : '') +
            '<span class="kb-count">' + cards.length + '</span>' +
            '<span class="kb-col-tools"><button type="button" class="kb-ic" data-act="left" title="Geser kiri"' + (i === 0 ? ' disabled' : '') + '>&#9664;</button>' +
            '<button type="button" class="kb-ic" data-act="right" title="Geser kanan"' + (i === KB.cols.length - 1 ? ' disabled' : '') + '>&#9654;</button>' +
            '<button type="button" class="kb-ic" data-act="edit" title="Ubah kolom">&#9998;</button></span></header>' +
            '<div class="kb-list" data-col="' + col.id + '">';
        cards.forEach(function (c) {
            html += '<article class="kb-card kb-p-' + esc(c.priority) + '" draggable="true" data-id="' + c.id + '">' +
                '<div class="kb-card-t">' + esc(c.title) + '</div>' +
                (c.notes ? '<div class="kb-card-n">' + esc(c.notes) + '</div>' : '') +
                '<div class="kb-meta"><span class="kb-chip kb-pr-' + esc(c.priority) + '">' + esc(PR[c.priority] || '') + '</span>' + dueInfo(c, !!col.is_done) +
                (c.assignee ? '<span class="kb-chip kb-who">' + esc(c.assignee) + '</span>' : '') + ((c.files && c.files.length) ? '<span class="kb-chip">&#128206; ' + c.files.length + '</span>' : '') + '</div></article>';
        });
        html += '</div><button type="button" class="kb-add" data-col="' + col.id + '">+ Tambah kartu</button></section>';
    });
    if (!KB.cols.length) { html = '<p class="kb-empty">Belum ada kolom.</p>'; }
    $('#kb-board').html(html);
}
function load() {
    api('board', {}, function (res) {
        KB.cols = res.columns; KB.cards = res.cards; KB.archived = res.archived; KB.used = res.used || 0; KB.keep = res.keep_days || 90; KB.people = res.people || []; KB.me = res.me || '';
        if (KB.view === 'board') { render(); } else { loadArchive(); $('#kb-archive-n').text(KB.archived); }
    });
}
function showView(v) {
    KB.view = v;
    $('#kb-board').toggle(v === 'board'); $('#kb-archive').toggle(v === 'archive');
    $('#kb-add-col').toggle(v === 'board');
    $('#kb-archive-btn').toggleClass('on', v === 'archive').contents().first().replaceWith(v === 'archive' ? 'Kembali ke papan ' : 'Arsip ');
    if (v === 'archive') { loadArchive(); } else { render(); }
}
function weekLabel(dstr) {
    var d = new Date(String(dstr).replace(' ', 'T')); var day = (d.getDay() + 6) % 7; d.setDate(d.getDate() - day);
    var e = new Date(d); e.setDate(e.getDate() + 6);
    var f = function (x) { return x.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }); };
    return 'Minggu ' + f(d) + ' – ' + f(e) + ' ' + e.getFullYear();
}
function loadArchive() {
    api('archive_list', {}, function (res) {
        var groups = {}, order = [];
        res.cards.forEach(function (c) { var k = weekLabel(c.finished_at || c.archived_at); if (!groups[k]) { groups[k] = []; order.push(k); } groups[k].push(c); });
        var html = '';
        if (!order.length) { html = '<p class="kb-empty">Belum ada kartu di arsip.</p>'; }
        order.forEach(function (k) {
            html += '<h5 class="kb-week">' + esc(k) + ' <span class="kb-count">' + groups[k].length + '</span></h5><div class="kb-arch-list">';
            groups[k].forEach(function (c) {
                html += '<div class="kb-arch-item"><div><div class="kb-card-t">' + esc(c.title) + '</div>' + (c.notes ? '<div class="kb-card-n">' + esc(c.notes) + '</div>' : '') +
                    '<div class="kb-meta">' + (c.assignee ? '<span class="kb-chip kb-who">' + esc(c.assignee) + '</span>' : '') + '<span class="kb-chip">Selesai ' + esc((c.finished_at || '').substr(0, 10)) + '</span></div></div>' +
                    '<button type="button" class="kb-btn" data-restore="' + c.id + '">Kembalikan</button></div>';
            });
            html += '</div>';
        });
        $('#kb-archive').html(html);
    });
}
/* ---- kartu ---- */
function openCard(card, colId) {
    KB.editCard = card || null;
    $('#kb-card-title-h').text(card ? 'Ubah kartu' : 'Kartu baru');
    $('#kc-title').val(card ? card.title : ''); $('#kc-notes').val(card ? card.notes : '');
    $('#kc-col').html(KB.cols.map(function (c) { return '<option value="' + c.id + '">' + esc(c.name) + '</option>'; }).join('')).val(card ? card.column_id : colId);
    $('#kc-pr').val(card ? card.priority : 'sedang'); $('#kc-due').val(card && card.due_date ? card.due_date : '');
    var isCo = KB.scope === 'company';
    $('#kc-assignee-wrap').toggle(isCo);
    if (isCo) {
        var opts = '<option value="">(belum ditentukan)</option>' + KB.people.map(function (p) { return '<option>' + esc(p) + '</option>'; }).join('');
        $('#kc-assignee').html(opts).val(card ? card.assignee : '');
    }
    KB.pending = []; $('#kc-file').val(''); drawFiles();
    $('#kc-del').toggle(!!card);
    $('#kb-card-modal').css('display', 'flex'); setTimeout(function () { $('#kc-title').focus(); }, 30);
}
$('#kb-board').on('click', '.kb-card', function () { var id = +$(this).data('id'); openCard(KB.cards.filter(function (c) { return c.id === id; })[0]); });
$('#kb-board').on('click', '.kb-add', function () { openCard(null, +$(this).data('col')); });
$('#kc-cancel').on('click', function () { $('#kb-card-modal').hide(); });
$('#kc-save').on('click', function () {
    var t = $.trim($('#kc-title').val()); if (!t) { $('#kc-title').focus(); return; }
    api('card_save', { id: KB.editCard ? KB.editCard.id : 0, title: t, notes: $('#kc-notes').val(), column_id: $('#kc-col').val(), priority: $('#kc-pr').val(), due_date: $('#kc-due').val(), assignee: KB.scope === 'company' ? $('#kc-assignee').val() : '' },
        function (res) {
            var id = res.id; $('#kc-save').prop('disabled', true).text('Menyimpan...');
            uploadAll(id, KB.pending.slice(), function () { $('#kc-save').prop('disabled', false).text('Simpan'); KB.pending = []; $('#kb-card-modal').hide(); load(); });
        });
});
/* ---- lampiran ---- */
function drawFiles() {
    var h = '', old = KB.editCard && KB.editCard.files ? KB.editCard.files : [];
    old.forEach(function (f) {
        h += '<div class="kb-file">' + (f.image ? '<a href="catatan-file.php?id=' + f.id + '&v=1" target="_blank"><img src="catatan-file.php?id=' + f.id + '&v=1" alt=""></a>' : '<span class="kb-file-ic">&#128196;</span>') +
            '<a class="kb-file-n" href="catatan-file.php?id=' + f.id + '">' + esc(f.name) + '</a><span class="kb-sub">' + fmtSize(f.size) + '</span>' +
            '<button type="button" class="kb-ic" data-fdel="' + f.id + '" title="Hapus lampiran">&#10005;</button></div>';
    });
    KB.pending.forEach(function (f, i) {
        h += '<div class="kb-file kb-new"><span class="kb-file-ic">&#128206;</span><span class="kb-file-n">' + esc(f.name) + '</span><span class="kb-sub">' + fmtSize(f.size) + ' &middot; belum diunggah</span>' +
            '<button type="button" class="kb-ic" data-pdel="' + i + '" title="Batalkan">&#10005;</button></div>';
    });
    $('#kc-files').html(h);
}
// Foto besar dikecilkan dulu di browser supaya hemat ruang server.
function shrink(file, cb) {
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 400 * 1024) { cb(file); return; }
    var img = new Image(), url = URL.createObjectURL(file);
    img.onload = function () {
        var m = 1600, w = img.width, h = img.height, k = Math.min(1, m / Math.max(w, h));
        var cv = document.createElement('canvas'); cv.width = Math.round(w * k); cv.height = Math.round(h * k);
        var cx = cv.getContext('2d'); cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height); cx.drawImage(img, 0, 0, cv.width, cv.height);
        URL.revokeObjectURL(url);
        cv.toBlob(function (b) { if (b && b.size < file.size) { cb(new File([b], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' })); } else { cb(file); } }, 'image/jpeg', 0.82);
    };
    img.onerror = function () { URL.revokeObjectURL(url); cb(file); };
    img.src = url;
}
$('#kc-file').on('change', function () {
    var list = Array.prototype.slice.call(this.files), self = this, left = list.length;
    var have = (KB.editCard && KB.editCard.files ? KB.editCard.files.length : 0) + KB.pending.length;
    list.forEach(function (f) {
        if (have >= 5) { left--; rpNotify({ icon: 'warning', title: 'Maksimal 5 lampiran per kartu' }); return; }
        have++;
        shrink(f, function (g) {
            if (g.size > 5 * 1024 * 1024) { rpNotify({ icon: 'warning', title: g.name + ' lebih dari 5 MB' }); } else { KB.pending.push(g); }
            if (--left <= 0) { drawFiles(); }
        });
    });
    self.value = '';
});
$('#kc-files').on('click', '[data-pdel]', function () { KB.pending.splice(+$(this).data('pdel'), 1); drawFiles(); });
$('#kc-files').on('click', '[data-fdel]', function () {
    var id = +$(this).data('fdel');
    api('file_delete', { id: id }, function () { if (KB.editCard) { KB.editCard.files = KB.editCard.files.filter(function (f) { return f.id !== id; }); } drawFiles(); load(); });
});
function uploadAll(cardId, files, done) {
    if (!files.length) { done(); return; }
    var f = files.shift(), fd = new FormData();
    fd.append('action', 'file_upload'); fd.append('scope', KB.scope); fd.append('card_id', cardId); fd.append('file', f, f.name);
    fetch('catatan-api.php', { method: 'POST', credentials: 'same-origin', body: fd }).then(function (r) { return r.json(); }).then(function (res) {
        if (!res.success) { rpNotify({ icon: 'error', title: 'Lampiran gagal: ' + f.name, text: res.message }); }
        uploadAll(cardId, files, done);
    }).catch(function () { rpNotify({ icon: 'error', title: 'Lampiran gagal: ' + f.name }); uploadAll(cardId, files, done); });
}
$('#kc-del').on('click', function () {
    if (!KB.editCard) { return; }
    rpNotify({ icon: 'warning', title: 'Hapus kartu ini?', text: 'Kartu dihapus permanen.', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(function (r) {
        if (r.isConfirmed) { api('card_delete', { id: KB.editCard.id }, function () { $('#kb-card-modal').hide(); load(); }); }
    });
});
/* ---- kolom ---- */
function openCol(col) {
    KB.editCol = col || null; KB.color = col ? col.color : COLORS[0];
    $('#kcol-h').text(col ? 'Ubah kolom' : 'Kolom baru');
    $('#kcol-name').val(col ? col.name : ''); $('#kcol-done').prop('checked', col ? !!col.is_done : false);
    $('#kcol-del').toggle(!!col); drawSwatches();
    $('#kb-col-modal').css('display', 'flex'); setTimeout(function () { $('#kcol-name').focus(); }, 30);
}
function drawSwatches() {
    $('#kcol-colors').html(COLORS.map(function (c) { return '<button type="button" class="kb-sw' + (c === KB.color ? ' on' : '') + '" data-c="' + c + '" style="background:' + c + '"></button>'; }).join(''));
}
$('#kcol-colors').on('click', '.kb-sw', function () { KB.color = $(this).data('c'); drawSwatches(); });
$('#kb-add-col').on('click', function () { openCol(null); });
$('#kcol-cancel').on('click', function () { $('#kb-col-modal').hide(); });
$('#kcol-save').on('click', function () {
    var n = $.trim($('#kcol-name').val()); if (!n) { $('#kcol-name').focus(); return; }
    api('col_save', { id: KB.editCol ? KB.editCol.id : 0, name: n, color: KB.color, is_done: $('#kcol-done').is(':checked') ? 1 : 0 }, function () { $('#kb-col-modal').hide(); load(); });
});
$('#kcol-del').on('click', function () {
    if (!KB.editCol) { return; }
    rpNotify({ icon: 'warning', title: 'Hapus kolom "' + KB.editCol.name + '"?', text: 'Kolom harus kosong dulu.', showCancelButton: true, confirmButtonText: 'Hapus', cancelButtonText: 'Batal' }).then(function (r) {
        if (r.isConfirmed) { api('col_delete', { id: KB.editCol.id }, function () { $('#kb-col-modal').hide(); load(); }); }
    });
});
$('#kb-board').on('click', '.kb-ic', function (e) {
    e.stopPropagation();
    var id = +$(this).closest('.kb-col').data('col'), act = $(this).data('act');
    var idx = KB.cols.findIndex(function (c) { return c.id === id; });
    if (act === 'edit') { openCol(KB.cols[idx]); return; }
    var j = act === 'left' ? idx - 1 : idx + 1; if (j < 0 || j >= KB.cols.length) { return; }
    var ids = KB.cols.map(function (c) { return c.id; }); var t = ids[idx]; ids[idx] = ids[j]; ids[j] = t;
    api('col_order', { order: ids }, load);
});
$('.kb-modal').on('mousedown', function (e) { if (e.target === this) { $(this).hide(); } });
$(document).on('keydown', function (e) { if (e.key === 'Escape') { $('.kb-modal').hide(); } });
/* ---- tarik dan lepas ---- */
var dragEl = null;
$('#kb-board').on('dragstart', '.kb-card', function (e) { dragEl = this; this.classList.add('kb-drag'); try { e.originalEvent.dataTransfer.setData('text/plain', this.getAttribute('data-id')); e.originalEvent.dataTransfer.effectAllowed = 'move'; } catch (x) {} });
$('#kb-board').on('dragend', '.kb-card', function () { this.classList.remove('kb-drag'); $('.kb-list').removeClass('kb-over'); dragEl = null; });
$('#kb-board').on('dragover', '.kb-list', function (e) {
    if (!dragEl) { return; }
    e.preventDefault(); $('.kb-list').removeClass('kb-over'); this.classList.add('kb-over');
    var y = e.originalEvent.clientY, after = null, best = -Infinity;
    $(this).children('.kb-card').not(dragEl).each(function () { var b = this.getBoundingClientRect(), off = y - b.top - b.height / 2; if (off < 0 && off > best) { best = off; after = this; } });
    if (after) { this.insertBefore(dragEl, after); } else { this.appendChild(dragEl); }
});
$('#kb-board').on('drop', '.kb-list', function (e) {
    if (!dragEl) { return; }
    e.preventDefault();
    var colId = +this.getAttribute('data-col'), id = +dragEl.getAttribute('data-id');
    var order = $(this).children('.kb-card').map(function () { return +this.getAttribute('data-id'); }).get();
    api('card_move', { id: id, column_id: colId, order: order }, load);
});
/* ---- tab dan arsip ---- */
$('.kb-tab').on('click', function () { KB.scope = $(this).data('scope'); history.replaceState(null, '', KB.scope === 'company' ? '?b=company' : 'catatan.php'); showView('board'); load(); });
$('#kb-archive-btn').on('click', function () { showView(KB.view === 'archive' ? 'board' : 'archive'); });
$('#kb-archive').on('click', '[data-restore]', function () { api('card_restore', { id: $(this).data('restore') }, function () { load(); }); });
KB.scope = '__SCOPE__';
load();
JS;
rp_end(str_replace('__SCOPE__', $scope, $js));
