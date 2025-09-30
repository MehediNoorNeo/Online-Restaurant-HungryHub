function openModal(action, area = null) {
    const modal = document.getElementById('areaModal');
    const form = document.getElementById('areaForm');
    const title = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const formId = document.getElementById('formId');
    const areaNameInput = document.getElementById('area_name');
    if (action === 'add') {
        title.textContent = 'Add New Area';
        formAction.value = 'add';
        formId.value = '';
        areaNameInput.value = '';
        form.reset();
    } else if (action === 'edit' && area) {
        title.textContent = 'Edit Area';
        formAction.value = 'edit';
        formId.value = area.id;
        areaNameInput.value = area.area_name;
    }
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    setTimeout(() => { areaNameInput.focus(); }, 100);
}

function closeModal() {
    document.getElementById('areaModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function deleteArea(id, areaName) {
    const modal = document.getElementById('deleteModal');
    const areaToDelete = document.getElementById('areaToDelete');
    const deleteId = document.getElementById('deleteId');
    areaToDelete.textContent = areaName;
    deleteId.value = id;
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Outside click closers
window.onclick = function(event) {
    const areaModal = document.getElementById('areaModal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === areaModal) { closeModal(); }
    if (event.target === deleteModal) { closeDeleteModal(); }
}

// Form validation
document.getElementById('areaForm').addEventListener('submit', function(e) {
    const areaName = document.getElementById('area_name').value.trim();
    if (!areaName) { e.preventDefault(); showNotification('Please enter an area name', 'error'); return; }
    if (areaName.length < 2) { e.preventDefault(); showNotification('Area name must be at least 2 characters long', 'error'); return; }
});

// Notification helpers
function showNotification(message, type = 'info', duration = 4000) {
    const existing = document.querySelectorAll('.notification');
    existing.forEach(n => n.remove());
    const n = document.createElement('div');
    n.className = `notification notification-${type}`;
    const icons = {success:'fa-check-circle', error:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle'};
    const cfg = {success:['#f0fff4','#22543d','#48bb78'], error:['#fff5f5','#742a2a','#f56565'], warning:['#fffbf0','#744210','#ed8936'], info:['#ebf8ff','#2a4365','#3182ce']};
    const [bg,text,border] = cfg[type] || cfg.info;
    n.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon"><i class="fas ${icons[type] || icons.info}"></i></div>
            <div class="notification-message"><div class="notification-title">${type.charAt(0).toUpperCase()+type.slice(1)}</div><div class="notification-text">${message}</div></div>
            <button class="notification-close" onclick="closeNotification(this)"><i class="fas fa-times"></i></button>
        </div>
        <div class="notification-progress"></div>`;
    n.style.setProperty('--bg-color', bg);
    n.style.setProperty('--text-color', text);
    n.style.setProperty('--border-color', border);
    document.body.appendChild(n);
    setTimeout(()=> n.classList.add('notification-show'), 10);
    setTimeout(()=> closeNotification(n.querySelector('.notification-close')), duration);
}
function closeNotification(closeBtn) {
    const n = closeBtn.closest('.notification');
    if (!n) return; n.classList.add('notification-hide'); setTimeout(()=> n.remove(), 300);
}

// Auto-hide messages
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(m => { setTimeout(()=>{ m.style.opacity='0'; setTimeout(()=> m.remove(), 300); }, 5000); });
});


