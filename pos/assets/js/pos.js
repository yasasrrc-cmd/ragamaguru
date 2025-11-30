// POS JavaScript functionality

let cart = [];
let saleCompleted = false;
// POS JavaScript functionality

let cart = [];
let saleCompleted = false;

// Search products
const searchInput = document.getElementById('product-search');
const productList = document.getElementById('product-list');
let searchTimeout;

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value.trim();
    
    if (query.length < 2) {
        productList.innerHTML = '<p class="text-muted">Start typing to search products...</p>';
        return;
    }
    
    searchTimeout = setTimeout(() => {
        fetch(`pos.php?search=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(products => {
                if (products.length === 0) {
                    productList.innerHTML = '<p class="text-muted">No products found</p>';
                    return;
                }
                
                productList.innerHTML = '';
                products.forEach(product => {
                    const card = document.createElement('div');
                    card.className = 'product-card';
                    card.innerHTML = `
                        <div class="product-icon">📦</div>
                        <div class="product-name">${escapeHtml(product.name)}</div>
                        <div class="product-price">${formatCurrency(product.price)}</div>
                        <div class="product-stock">Stock: ${product.stock}</div>
                    `;
                    card.addEventListener('click', () => addToCart(product));
                    productList.appendChild(card);
                });
            })
            .catch(error => {
                console.error('Error:', error);
                productList.innerHTML = '<p class="text-muted">Error loading products</p>';
            });
    }, 300);
});

// Add product to cart
function addToCart(product) {
    if (product.stock <= 0) {
        showToast('Product out of stock', 'danger');
        return;
    }
    
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity >= product.stock) {
            showToast('Cannot add more than available stock', 'warning');
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            quantity: 1,
            stock: product.stock
        });
    }
    
    updateCart();
    showToast('Product added to cart', 'success');
}

// Update cart display
function updateCart() {
    const cartItems = document.getElementById('cart-items');
    const cartSubtotal = document.getElementById('cart-subtotal');
    const cartTotal = document.getElementById('cart-total');
    
    if (cart.length === 0) {
        cartItems.innerHTML = '<div class="empty-cart"><p>🛒 Cart is empty</p></div>';
        cartSubtotal.textContent = '$0.00';
        cartTotal.textContent = '$0.00';
        document.getElementById('amount-paid').value = '';
        document.getElementById('change-amount').value = '';
        return;
    }
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <div class="cart-item">
                <div class="cart-item-info">
                    <h4>${escapeHtml(item.name)}</h4>
                    <div class="cart-item-price">${formatCurrency(item.price)} x ${item.quantity} = ${formatCurrency(subtotal)}</div>
                </div>
                <div class="cart-item-actions">
                    <button class="qty-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                    <input type="number" class="qty-input" value="${item.quantity}" min="1" max="${item.stock}" onchange="setQuantity(${item.id}, this.value)">
                    <button class="qty-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                    <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">✕</button>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    cartSubtotal.textContent = formatCurrency(total);
    cartTotal.textContent = formatCurrency(total);
    
    // Auto-fill amount paid
    const amountPaid = document.getElementById('amount-paid');
    if (!amountPaid.value) {
        amountPaid.value = total.toFixed(2);
        calculateChange();
    }
}

// Update quantity
function updateQuantity(productId, change) {
    const item = cart.find(i => i.id === productId);
    if (!item) return;
    
    const newQuantity = item.quantity + change;
    
    if (newQuantity <= 0) {
        removeFromCart(productId);
        return;
    }
    
    if (newQuantity > item.stock) {
        showToast('Cannot exceed available stock', 'warning');
        return;
    }
    
    item.quantity = newQuantity;
    updateCart();
}

// Set quantity directly
function setQuantity(productId, quantity) {
    quantity = parseInt(quantity);
    if (isNaN(quantity) || quantity < 1) {
        updateCart();
        return;
    }
    
    const item = cart.find(i => i.id === productId);
    if (!item) return;
    
    if (quantity > item.stock) {
        showToast('Cannot exceed available stock', 'warning');
        updateCart();
        return;
    }
    
    item.quantity = quantity;
    updateCart();
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCart();
    showToast('Product removed from cart', 'info');
}

// Clear cart
document.getElementById('clear-cart').addEventListener('click', function() {
    if (cart.length === 0) return;
    
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCart();
        showToast('Cart cleared', 'info');
    }
});

// Calculate change
const amountPaidInput = document.getElementById('amount-paid');
const changeAmountInput = document.getElementById('change-amount');

amountPaidInput.addEventListener('input', calculateChange);

function calculateChange() {
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const paid = parseFloat(amountPaidInput.value) || 0;
    const change = paid - total;
    
    changeAmountInput.value = change >= 0 ? formatCurrency(change) : '$0.00';
}

// Process sale
document.getElementById('process-sale').addEventListener('click', function() {
    if (cart.length === 0) {
        showToast('Cart is empty', 'warning');
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const amountPaid = parseFloat(document.getElementById('amount-paid').value) || 0;
    
    if (amountPaid < total) {
        showToast('Insufficient payment amount', 'danger');
        return;
    }
    
    const paymentMethod = document.getElementById('payment-method').value;
    
    // Show loading
    this.disabled = true;
    this.textContent = 'Processing...';
    
    // Send to server
    const formData = new FormData();
    formData.append('action', 'process_sale');
    formData.append('cart', JSON.stringify(cart));
    formData.append('payment_method', paymentMethod);
    formData.append('amount_paid', amountPaid);
    
    fetch('pos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success modal
            document.getElementById('modal-invoice').textContent = data.invoice_no;
            document.getElementById('modal-total').textContent = data.total.toFixed(2);
            document.getElementById('modal-paid').textContent = data.paid.toFixed(2);
            document.getElementById('modal-change').textContent = data.change.toFixed(2);
            
            const modal = document.getElementById('success-modal');
            modal.classList.add('active');
            
            saleCompleted = true;
            window.lastSaleId = data.sale_id;
            
            // Clear cart
            cart = [];
            updateCart();
            searchInput.value = '';
            productList.innerHTML = '<p class="text-muted">Start typing to search products...</p>';
            
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error processing sale', 'danger');
    })
    .finally(() => {
        this.disabled = false;
        this.textContent = 'Complete Sale';
    });
});

// Close modal
function closeModal() {
    document.getElementById('success-modal').classList.remove('active');
}

// Print invoice
function printInvoice() {
    if (window.lastSaleId) {
        window.open(`invoice.php?id=${window.lastSaleId}`, '_blank');
    }
}

// Format currency
function formatCurrency(amount) {
    return 'Rs ' + parseFloat(amount).toFixed(2);
}

// Utility function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    searchInput.focus();
});