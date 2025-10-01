// Toast notification system
(function () {
  function closeNotification(btn) {
    const el = btn.closest ? btn.closest(".notification") : btn;
    if (el) {
      el.classList.add("notification-hide");
      setTimeout(() => el.remove(), 300);
    }
  }

  function showNotification(message, type = "info", duration = 4000) {
    const existing = document.querySelectorAll(".notification");
    existing.forEach((n) => n.remove());

    const n = document.createElement("div");
    n.className = `notification notification-${type}`;

    const icons = {
      success: "fa-check-circle",
      error: "fa-exclamation-circle",
      warning: "fa-exclamation-triangle",
      info: "fa-info-circle",
    };

    const cfg = {
      success: ["#f0fff4", "#22543d", "#48bb78"],
      error: ["#fff5f5", "#742a2a", "#f56565"],
      warning: ["#fffbf0", "#744210", "#ed8936"],
      info: ["#ebf8ff", "#2a4365", "#3182ce"],
    };
    const [bg, text, border] = cfg[type] || cfg.info;

    n.innerHTML = `<div class="notification-content"><div class="notification-icon"><i class="fas ${
      icons[type] || icons.info
    }"></i></div><div class="notification-message"><div class="notification-title">${
      type.charAt(0).toUpperCase() + type.slice(1)
    }</div><div class="notification-text">${message}</div></div><button class="notification-close" aria-label="Close"><i class="fas fa-times"></i></button></div><div class="notification-progress"></div>`;

    n.style.setProperty("--bg-color", bg);
    n.style.setProperty("--text-color", text);
    n.style.setProperty("--border-color", border);

    // set duration for CSS progress animation
    n.style.setProperty("--notif-duration", `${duration}ms`);

    document.body.appendChild(n);
    setTimeout(() => n.classList.add("notification-show"), 10);

    const closeBtn = n.querySelector(".notification-close");
    closeBtn.addEventListener("click", () => closeNotification(closeBtn));
    setTimeout(() => closeNotification(closeBtn), duration);
  }

  // Backward compatibility: expose showToast using the new system
  window.showToast = function (type, title, message) {
    const msg = [title, message].filter(Boolean).join("\n");
    showNotification(msg || "", type || "info");
  };

  document.addEventListener("DOMContentLoaded", function () {
    if (window.__flashMessage && window.__flashMessage.text) {
      showNotification(
        window.__flashMessage.text,
        window.__flashMessage.type || "info"
      );
    }
  });
})();

function openEditModal(user) {
  const modal = document.getElementById("editModal");
  document.getElementById("editUserId").value = user.id;
  document.getElementById("editName").value = user.name;
  document.getElementById("editEmail").value = user.email;
  document.getElementById("editPhone").value = user.phone || "";
  document.getElementById("editAddress").value = user.address || "";
  document.getElementById("editStatus").value = user.status;
  modal.style.display = "block";
  modal.scrollTop = 0;
  document.body.style.overflow = "hidden";
}

function closeModal() {
  const modal = document.getElementById("editModal");
  modal.style.display = "none";
  document.body.style.overflow = "auto";
}

let pendingDeleteId = null;
function openDeleteModal(id, name) {
  pendingDeleteId = id;
  const modal = document.getElementById("deleteModal");
  const text = document.getElementById("deleteModalText");
  text.textContent = `Are you sure you want to delete user "${name}"? This action cannot be undone.`;
  modal.style.display = "block";
  document.body.style.overflow = "hidden";
}
function closeDeleteModal() {
  const modal = document.getElementById("deleteModal");
  modal.style.display = "none";
  document.body.style.overflow = "auto";
  pendingDeleteId = null;
}
function confirmDelete() {
  if (!pendingDeleteId) {
    closeDeleteModal();
    return;
  }
  const form = document.createElement("form");
  form.method = "POST";
  form.innerHTML = `
        <input type="hidden" name="action" value="delete_user">
        <input type="hidden" name="user_id" value="${pendingDeleteId}">
    `;
  document.body.appendChild(form);
  form.submit();
}

// Close modal when clicking outside
window.onclick = function (event) {
  const modal = document.getElementById("editModal");
  if (event.target === modal) {
    closeModal();
  }
  const deleteModal = document.getElementById("deleteModal");
  if (event.target === deleteModal) {
    closeDeleteModal();
  }
};
