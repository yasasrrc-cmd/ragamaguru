// Products page JavaScript

function openModal(action) {
    const modal = document.getElementById('product-modal');
    const form = document.getElementById('product-form');
    const title = document.getElementById('modal-title');
    
    form.reset();
    document.getElementById('form-action').value = action;
    
    if (action === 'add') {
        title.textContent = 'Add Product';
        document.getElementById('product-id').value = '';
    }
    
    modal.style.display = 'flex';
}

function editProduct(product) {
    const modal = document.getElementById('product-modal');
    const title = document.getElementById('modal-title');
    
    title.textContent = 'Edit Product';
    document.getElementById('form-action').value = 'edit';
    document.getElementById('product-id').value = product.id;
    document.getElementById('barcode').value = product.barcode;
    document.getElementById('name').value = product.name;
    document.getElementById('category_id').value = product.category_id || '';
    document.getElementById('cost').value = product.cost;
    document.getElementById('price').value = product.price;
    document.getElementById('stock').value = product.stock;
    document.getElementById('min_stock').value = product.min_stock;
    
    modal.style.display = 'flex';
}

function closeProductModal() {
    document.getElementById('product-modal').style.display = 'none';
}

function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('product-modal');
    if (event.target === modal) {
        closeProductModal();
    }
}