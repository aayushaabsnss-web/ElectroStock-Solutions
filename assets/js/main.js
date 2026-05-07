// ElectroStock Solutions — main.js
// Modal open/close
function openModal(id) { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }
document.querySelectorAll('[data-modal]').forEach(btn =>
  btn.addEventListener('click', () => openModal(btn.dataset.modal)));
document.querySelectorAll('.modal-overlay').forEach(overlay =>
  overlay.addEventListener('click', e => { if(e.target===overlay) overlay.classList.remove('open'); }));

// Auto-dismiss alerts after 4s
document.querySelectorAll('.alert').forEach(el =>
  setTimeout(() => el.style.opacity='0', 4000));

// Confirm deletes
document.querySelectorAll('[data-confirm]').forEach(el =>
  el.addEventListener('click', e => { if(!confirm(el.dataset.confirm)) e.preventDefault(); }));

// Order total calculator (orders/add.php)
function calcTotal() {
  let t = 0;
  document.querySelectorAll('.oi-row').forEach(row => {
    const qty = parseInt(row.querySelector('.oi-qty')?.value || 0);
    const price = parseFloat(row.querySelector('.oi-price')?.dataset.price || 0);
    t += qty * price;
  });
  const el = document.getElementById('order-total');
  if (el) el.textContent = '$' + t.toFixed(2);
}
document.querySelectorAll('.oi-qty').forEach(i => i.addEventListener('input', calcTotal));
