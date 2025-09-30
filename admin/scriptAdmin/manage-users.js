// Toast notification system
(function(){
    const TOAST_DURATION_MS = 4000;
    function createIcon(type){
        const svg = {
            success: '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 7L9 18L4 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            error: '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            warning: '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L15.71 3.86a2 2 0 00-3.42 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            info: '<svg class="icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M13 16h-1v-4h-1m1-4h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/></svg>'
        };
        return svg[type] || svg.info;
    }
    window.showToast = function(type, title, message){
        const container = document.getElementById('toasts');
        if(!container) return;
        const toast = document.createElement('div');
        toast.className = 'toast ' + (type || 'info');
        toast.innerHTML = '\n            <div class="toast-inner">\n                <div class="icon-wrap">'+ createIcon(type) +'</div>\n                <div>\n                    <div class="title">'+ (title || '') +'</div>\n                    <p class="message">'+ (message || '') +'</p>\n                </div>\n                <button class="close-btn" aria-label="Close">\n                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18M6 6l12 12" stroke="#2c3e50" stroke-width="2" stroke-linecap="round"/></svg>\n                </button>\n            </div>\n            <div class="progress"><span></span></div>\n        ';
        container.appendChild(toast);
        requestAnimationFrame(()=> toast.classList.add('show'));
        const progress = toast.querySelector('.progress > span');
        const start = Date.now();
        let remaining = TOAST_DURATION_MS;
        let rafId;
        function tick(){
            const elapsed = Date.now() - start;
            const t = Math.max(0, 1 - (elapsed / remaining));
            progress.style.transform = 'scaleX(' + t + ')';
            if(elapsed < remaining){ rafId = requestAnimationFrame(tick); }
        }
        rafId = requestAnimationFrame(tick);
        const close = ()=>{
            cancelAnimationFrame(rafId);
            toast.classList.remove('show');
            setTimeout(()=> toast.remove(), 300);
        };
        const timeoutId = setTimeout(close, remaining);
        toast.querySelector('.close-btn').addEventListener('click', ()=>{ clearTimeout(timeoutId); close(); });
        toast.addEventListener('mouseenter', ()=>{ clearTimeout(timeoutId); remaining = Math.max(0, remaining - (Date.now() - start)); });
        toast.addEventListener('mouseleave', ()=>{ setTimeout(close, remaining); });
    };
    document.addEventListener('DOMContentLoaded', function(){
        if (window.__flashMessage && window.__flashMessage.text) {
            showToast(
                window.__flashMessage.type || 'info',
                (window.__flashMessage.type || 'Info').toUpperCase(),
                window.__flashMessage.text
            );
        }
    });
})();

function openEditModal(user) {
    const modal = document.getElementById('editModal');
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editName').value = user.name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editPhone').value = user.phone || '';
    document.getElementById('editAddress').value = user.address || '';
    document.getElementById('editStatus').value = user.status;
    modal.style.display = 'block';
    modal.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('editModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

let pendingDeleteId = null;
function openDeleteModal(id, name) {
    pendingDeleteId = id;
    const modal = document.getElementById('deleteModal');
    const text = document.getElementById('deleteModalText');
    text.textContent = `Are you sure you want to delete user "${name}"? This action cannot be undone.`;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    pendingDeleteId = null;
}
function confirmDelete() {
    if (!pendingDeleteId) { closeDeleteModal(); return; }
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" value="${pendingDeleteId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) { closeModal(); }
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === deleteModal) { closeDeleteModal(); }
}


