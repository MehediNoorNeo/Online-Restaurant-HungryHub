function openModal(action, category = null, item = null) {
    const modal = document.getElementById('foodModal');
    const form = document.getElementById('foodForm');
    const title = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const formId = document.getElementById('formId');
    if (action === 'add') {
        title.textContent = 'Add Food Item';
        formAction.value = 'add';
        formId.value = '';
        form.reset();
        if (category) { document.getElementById('category').value = category; }
        document.getElementById('image_url_radio').checked = true;
        toggleImageInput();
    } else if (action === 'edit' && item) {
        title.textContent = 'Edit Food Item';
        formAction.value = 'edit';
        formId.value = item.id;
        document.getElementById('name').value = item.name;
        document.getElementById('category').value = item.category;
        document.getElementById('price').value = item.price;
        document.getElementById('description').value = item.description;
        document.getElementById('image').value = item.image;
        document.getElementById('image_url_radio').checked = true;
        toggleImageInput();
    }
    modal.style.display = 'block';
    modal.scrollTop = 0;
    document.body.style.overflow = 'hidden';
}

function toggleImageInput() {
    const uploadRadio = document.getElementById('image_upload_radio');
    const urlRadio = document.getElementById('image_url_radio');
    const uploadSection = document.getElementById('upload_section');
    const urlSection = document.getElementById('url_section');
    if (uploadRadio.checked) { uploadSection.style.display = 'block'; urlSection.style.display = 'none'; }
    else { uploadSection.style.display = 'none'; urlSection.style.display = 'block'; }
}

function uploadImage() {
    const fileInput = document.getElementById('image_file');
    const category = document.getElementById('category').value;
    const progressDiv = document.getElementById('upload_progress');
    const imageUrlInput = document.getElementById('image');
    if (!fileInput.files[0]) { showNotification('Please select a file to upload', 'warning'); return; }
    if (!category) { showNotification('Please select a category first', 'warning'); return; }
    const formData = new FormData();
    formData.append('image', fileInput.files[0]);
    formData.append('category', category);
    progressDiv.style.display = 'block';
    fetch('upload_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        progressDiv.style.display = 'none';
        if (data.success) {
            imageUrlInput.value = data.file_path;
            document.getElementById('image_url_radio').checked = true; toggleImageInput();
            showNotification('Image uploaded successfully!', 'success');
        } else { showNotification('Upload failed: ' + data.message, 'error'); }
    })
    .catch(err => { progressDiv.style.display = 'none'; showNotification('Upload failed: ' + err.message, 'error'); });
}

function closeModal() { document.getElementById('foodModal').style.display = 'none'; document.body.style.overflow = 'auto'; }

function openCategoryModal() {
    const modal = document.getElementById('categoryModal');
    const form = document.getElementById('categoryForm');
    form.reset(); modal.style.display = 'block'; modal.scrollTop = 0; document.body.style.overflow = 'hidden';
}
function closeCategoryModal() { document.getElementById('categoryModal').style.display = 'none'; document.body.style.overflow = 'auto'; }

function openDeleteCategoryModal(categoryName) {
    const modal = document.getElementById('deleteCategoryModal');
    document.getElementById('categoryToDelete').textContent = categoryName;
    document.getElementById('categoryPageName').textContent = categoryName.toLowerCase().replace(/[^a-zA-Z0-9]/g, '');
    document.getElementById('deleteCategoryName').value = categoryName;
    const confirmText = document.getElementById('confirmText');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmText.value = ''; confirmText.classList.remove('valid','invalid'); confirmBtn.disabled = true;
    modal.style.display = 'block'; modal.scrollTop = 0; document.body.style.overflow = 'hidden';
}
function closeDeleteCategoryModal() { document.getElementById('deleteCategoryModal').style.display = 'none'; document.body.style.overflow = 'auto'; }

function deleteItem(id) {
    const confirmModal = document.createElement('div');
    confirmModal.className = 'modal'; confirmModal.style.display = 'block';
    confirmModal.innerHTML = `
        <div class="modal-content" style="max-width: 400px;">
            <h3>Delete Food Item</h3>
            <div class="delete-warning" style="margin-bottom: 1.5rem;">
                <div class="warning-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="warning-content"><h4>Confirm Deletion</h4><p>Are you sure you want to delete this food item? This action cannot be undone.</p></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteConfirm()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteItem(${id})">Delete Item</button>
            </div>
        </div>`;
    document.body.appendChild(confirmModal); document.body.style.overflow = 'hidden';
}
function closeDeleteConfirm() { const m = document.querySelector('.modal:last-of-type'); if (m) { m.remove(); document.body.style.overflow = 'auto'; } }
function confirmDeleteItem(id) { closeDeleteConfirm(); showNotification('Deleting food item...', 'info', 2000); const form = document.createElement('form'); form.method='POST'; form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`; document.body.appendChild(form); form.submit(); }

// Outside clicks
window.onclick = function(event) {
    const foodModal = document.getElementById('foodModal');
    const categoryModal = document.getElementById('categoryModal');
    const deleteCategoryModal = document.getElementById('deleteCategoryModal');
    if (event.target === foodModal) { closeModal(); }
    else if (event.target === categoryModal) { closeCategoryModal(); }
    else if (event.target === deleteCategoryModal) { closeDeleteCategoryModal(); }
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('image_upload_radio').addEventListener('change', toggleImageInput);
    document.getElementById('image_url_radio').addEventListener('change', toggleImageInput);
    document.getElementById('image_file').addEventListener('change', function(e){ const f = e.target.files[0]; if (f){ const label = document.querySelector('.file-label span'); if (label) label.textContent = f.name; }});
    document.getElementById('confirmText').addEventListener('input', function(e){ const btn = document.getElementById('confirmDeleteBtn'); const v = e.target.value.toLowerCase().trim(); e.target.classList.remove('valid','invalid'); if (v==='confirm'){ btn.disabled=false; e.target.classList.add('valid'); } else { btn.disabled=true; if(v.length>0) e.target.classList.add('invalid'); }});
    document.getElementById('foodForm').addEventListener('submit', function(e){ e.preventDefault(); submitFoodForm(); });
    setupFilterStateSaving();
    loadFilterState();
});

function filterFoodItems() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const categoryFilter = document.getElementById('categoryFilter').value;
    const minPrice = parseFloat(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseFloat(document.getElementById('maxPrice').value) || Infinity;
    const sortBy = document.getElementById('sortBy').value;
    const foodItems = document.querySelectorAll('.food-item');
    const categorySections = document.querySelectorAll('.category-section');
    let visibleItems = [];
    foodItems.forEach(item => {
        const name = item.getAttribute('data-name');
        const category = item.getAttribute('data-category');
        const price = parseFloat(item.getAttribute('data-price'));
        let showItem = true;
        if (searchTerm && !name.includes(searchTerm)) showItem = false;
        if (categoryFilter && category !== categoryFilter) showItem = false;
        if (price < minPrice || price > maxPrice) showItem = false;
        if (showItem) { item.style.display = 'block'; visibleItems.push({ element: item, name: item.querySelector('.food-name').textContent, category, price }); }
        else { item.style.display = 'none'; }
    });
    visibleItems.sort((a,b)=>{
        switch (sortBy) {
            case 'name-asc': return a.name.localeCompare(b.name);
            case 'name-desc': return b.name.localeCompare(a.name);
            case 'price-asc': return a.price - b.price;
            case 'price-desc': return b.price - a.price;
            case 'category-asc': return a.category.localeCompare(b.category);
            default: return 0;
        }
    });
    visibleItems.forEach(item => { const section = item.element.closest('.category-section'); const grid = section.querySelector('.food-grid'); grid.appendChild(item.element); });
    categorySections.forEach(section => { section.style.display = 'block'; });
    updateResultsCount(visibleItems.length);
}
function clearFilters(){ document.getElementById('searchInput').value=''; document.getElementById('categoryFilter').value=''; document.getElementById('minPrice').value=''; document.getElementById('maxPrice').value=''; document.getElementById('sortBy').value='name-asc'; document.querySelectorAll('.food-item').forEach(i=> i.style.display='block'); document.querySelectorAll('.category-section').forEach(s=> s.style.display='block'); updateResultsCount(document.querySelectorAll('.food-item').length); }
function updateResultsCount(count){ const d = document.getElementById('resultsCount'); if (d) d.textContent = `Showing ${count} food item(s)`; }

function submitFoodForm(){
    const form = document.getElementById('foodForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true; submitBtn.textContent = 'Saving...';
    fetch(window.location.href, { method:'POST', body: formData })
    .then(r=> r.text())
    .then(()=> { showNotification('Food item saved successfully!', 'success'); closeModal(); setTimeout(()=> location.reload(), 1500); })
    .catch(()=> { showNotification('Error saving food item. Please try again.', 'error'); })
    .finally(()=> { submitBtn.disabled=false; submitBtn.textContent=originalText; });
}

function showNotification(message, type = 'info', duration = 4000) {
    const existing = document.querySelectorAll('.notification'); existing.forEach(n=> n.remove());
    const n = document.createElement('div'); n.className = `notification notification-${type}`;
    const icons = {success:'fa-check-circle', error:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle'};
    const cfg = {success:['#f0fff4','#22543d','#48bb78'], error:['#fff5f5','#742a2a','#f56565'], warning:['#fffbf0','#744210','#ed8936'], info:['#ebf8ff','#2a4365','#3182ce']};
    const [bg,text,border] = cfg[type] || cfg.info;
    n.innerHTML = `<div class="notification-content"><div class="notification-icon"><i class="fas ${icons[type] || icons.info}"></i></div><div class="notification-message"><div class="notification-title">${type.charAt(0).toUpperCase()+type.slice(1)}</div><div class="notification-text">${message}</div></div><button class="notification-close" onclick="closeNotification(this)"><i class="fas fa-times"></i></button></div><div class="notification-progress"></div>`;
    n.style.setProperty('--bg-color', bg); n.style.setProperty('--text-color', text); n.style.setProperty('--border-color', border);
    document.body.appendChild(n); setTimeout(()=> n.classList.add('notification-show'), 10); setTimeout(()=> closeNotification(n.querySelector('.notification-close')), duration);
}
function closeNotification(btn){ const n = btn.closest('.notification'); if (n){ n.classList.add('notification-hide'); setTimeout(()=> n.remove(), 300); } }

function saveFilterState(){ const s = { search:document.getElementById('searchInput').value, category:document.getElementById('categoryFilter').value, minPrice:document.getElementById('minPrice').value, maxPrice:document.getElementById('maxPrice').value, sortBy:document.getElementById('sortBy').value }; localStorage.setItem('foodFilterState', JSON.stringify(s)); }
function loadFilterState(){ const saved = localStorage.getItem('foodFilterState'); if (!saved) return; const s = JSON.parse(saved); document.getElementById('searchInput').value = s.search || ''; document.getElementById('categoryFilter').value = s.category || ''; document.getElementById('minPrice').value = s.minPrice || ''; document.getElementById('maxPrice').value = s.maxPrice || ''; document.getElementById('sortBy').value = s.sortBy || 'name-asc'; filterFoodItems(); }
function setupFilterStateSaving(){ ['searchInput','categoryFilter','minPrice','maxPrice','sortBy'].forEach(id=>{ const el = document.getElementById(id); if (el){ el.addEventListener('change', saveFilterState); el.addEventListener('keyup', saveFilterState); }}); }


