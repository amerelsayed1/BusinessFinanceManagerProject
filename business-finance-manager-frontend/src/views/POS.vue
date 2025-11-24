<template>
  <div class="pos">
    <h1>Point of Sale</h1>

    <div class="pos-container">
      <!-- Product Selection -->
      <div class="products-section">
        <div class="search-box">
          <input
              v-model="searchQuery"
              type="text"
              placeholder="Search products..."
              class="search-input"
          />
        </div>

        <div class="products-grid">
          <div
              v-for="product in filteredProducts"
              :key="product.id"
              @click="addToCart(product)"
              class="product-card"
              :class="{ 'out-of-stock': product.current_stock <= 0 }"
          >
            <div class="product-name">{{ product.name }}</div>
            <div class="product-sku">{{ product.sku }}</div>
            <div class="product-price">{{ formatCurrency(product.selling_price) }}</div>
            <div class="product-stock">
              Stock: {{ product.current_stock }}
            </div>
          </div>
        </div>

        <div v-if="filteredProducts.length === 0" class="empty-state">
          No products found
        </div>
      </div>

      <!-- Cart Section -->
      <div class="cart-section">
        <h2>Cart</h2>

        <div class="cart-items">
          <div v-for="(item, index) in cart" :key="index" class="cart-item">
            <div class="item-details">
              <div class="item-name">{{ item.product.name }}</div>
              <div class="item-price">{{ formatCurrency(item.unit_price) }}</div>
            </div>
            <div class="item-controls">
              <button @click="decreaseQuantity(index)" class="btn-qty">-</button>
              <input
                  v-model.number="item.quantity"
                  type="number"
                  min="1"
                  :max="item.product.current_stock"
                  class="qty-input"
              />
              <button @click="increaseQuantity(index)" class="btn-qty">+</button>
              <button @click="removeFromCart(index)" class="btn-remove">×</button>
            </div>
            <div class="item-total">
              {{ formatCurrency(item.quantity * item.unit_price) }}
            </div>
          </div>

          <div v-if="cart.length === 0" class="empty-cart">
            Cart is empty
          </div>
        </div>

        <div class="cart-summary">
          <div class="summary-row">
            <span>Total:</span>
            <span class="total-amount">{{ formatCurrency(totalAmount) }}</span>
          </div>
        </div>

        <div class="checkout-form">
          <div class="form-group">
            <label>Account</label>
            <select v-model="checkoutForm.account_id" required>
              <option value="">Select account</option>
              <option v-for="account in accounts" :key="account.id" :value="account.id">
                {{ account.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Payment Method</label>
            <select v-model="checkoutForm.payment_method" required>
              <option value="Cash">Cash</option>
              <option value="Card">Card</option>
              <option value="Wallet">Wallet</option>
            </select>
          </div>

          <div class="form-group">
            <label>Note (Optional)</label>
            <textarea v-model="checkoutForm.note" rows="2"></textarea>
          </div>

          <button
              @click="checkout"
              class="btn btn-checkout"
              :disabled="cart.length === 0 || !checkoutForm.account_id || submitting"
          >
            {{ submitting ? 'Processing...' : `Checkout ${formatCurrency(totalAmount)}` }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api';

export default {
  name: 'POS',
  data() {
    return {
      products: [],
      accounts: [],
      cart: [],
      searchQuery: '',
      checkoutForm: {
        account_id: '',
        payment_method: 'Cash',
        note: '',
      },
      submitting: false,
      currency: 'EGP',
    };
  },
  computed: {
    filteredProducts() {
      if (!this.searchQuery) return this.products;

      const query = this.searchQuery.toLowerCase();
      return this.products.filter(p =>
          p.name.toLowerCase().includes(query) ||
          p.sku.toLowerCase().includes(query)
      );
    },
    totalAmount() {
      return this.cart.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
    },
  },
  mounted() {
    this.loadProducts();
    this.loadAccounts();
    this.loadUserProfile();
  },
  methods: {
    async loadProducts() {
      try {
        const response = await api.get('/products', {
          params: { is_active: 1 }
        });
        this.products = response.data.filter(p => p.current_stock > 0);
      } catch (error) {
        console.error('Failed to load products:', error);
      }
    },
    async loadAccounts() {
      try {
        const response = await api.get('/accounts');
        this.accounts = response.data;
      } catch (error) {
        console.error('Failed to load accounts:', error);
      }
    },
    async loadUserProfile() {
      try {
        const response = await api.get('/me');
        this.currency = response.data.default_currency || 'EGP';
      } catch (error) {
        console.error('Failed to load user profile:', error);
      }
    },
    addToCart(product) {
      if (product.current_stock <= 0) {
        alert('Product is out of stock');
        return;
      }

      const existingItem = this.cart.find(item => item.product.id === product.id);

      if (existingItem) {
        if (existingItem.quantity < product.current_stock) {
          existingItem.quantity++;
        } else {
          alert('Cannot add more than available stock');
        }
      } else {
        this.cart.push({
          product: product,
          quantity: 1,
          unit_price: product.selling_price,
        });
      }
    },
    increaseQuantity(index) {
      const item = this.cart[index];
      if (item.quantity < item.product.current_stock) {
        item.quantity++;
      } else {
        alert('Cannot exceed available stock');
      }
    },
    decreaseQuantity(index) {
      const item = this.cart[index];
      if (item.quantity > 1) {
        item.quantity--;
      }
    },
    removeFromCart(index) {
      this.cart.splice(index, 1);
    },
    async checkout() {
      if (this.cart.length === 0) {
        alert('Cart is empty');
        return;
      }

      if (!this.checkoutForm.account_id) {
        alert('Please select an account');
        return;
      }

      this.submitting = true;
      try {
        const orderData = {
          account_id: this.checkoutForm.account_id,
          date: new Date().toISOString().split('T')[0],
          payment_method: this.checkoutForm.payment_method,
          note: this.checkoutForm.note,
          items: this.cart.map(item => ({
            product_id: item.product.id,
            quantity: item.quantity,
            unit_price: item.unit_price,
          })),
        };

        await api.post('/pos-orders', orderData);

        alert('Order completed successfully!');
        this.cart = [];
        this.checkoutForm.note = '';
        this.loadProducts(); // Refresh product stock
        this.loadAccounts(); // Refresh account balances
      } catch (error) {
        alert(error.response?.data?.error || 'Failed to complete order');
      } finally {
        this.submitting = false;
      }
    },
    formatCurrency(amount) {
      return `${parseFloat(amount || 0).toFixed(2)} ${this.currency}`;
    },
  },
};
</script>

<style scoped>
.pos {
  padding: 20px;
}

.pos-container {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.products-section,
.cart-section {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.search-box {
  margin-bottom: 20px;
}

.search-input {
  width: 100%;
  padding: 12px;
  border: 2px solid #e9ecef;
  border-radius: 6px;
  font-size: 16px;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 15px;
  max-height: 600px;
  overflow-y: auto;
}

.product-card {
  padding: 15px;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.product-card:hover:not(.out-of-stock) {
  border-color: #007bff;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.product-card.out-of-stock {
  opacity: 0.5;
  cursor: not-allowed;
}

.product-name {
  font-weight: 600;
  margin-bottom: 5px;
}

.product-sku {
  font-size: 12px;
  color: #6c757d;
  margin-bottom: 8px;
}

.product-price {
  font-size: 18px;
  font-weight: 700;
  color: #28a745;
  margin-bottom: 5px;
}

.product-stock {
  font-size: 12px;
  color: #6c757d;
}

.cart-items {
  min-height: 300px;
  margin-bottom: 20px;
}

.cart-item {
  display: flex;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #e9ecef;
  gap: 10px;
}

.item-details {
  flex: 1;
}

.item-name {
  font-weight: 600;
  margin-bottom: 4px;
}

.item-price {
  font-size: 14px;
  color: #6c757d;
}

.item-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}

.btn-qty {
  width: 30px;
  height: 30px;
  border: 1px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 4px;
  font-size: 18px;
}

.qty-input {
  width: 50px;
  padding: 4px;
  text-align: center;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.btn-remove {
  width: 30px;
  height: 30px;
  border: 1px solid #dc3545;
  background: #dc3545;
  color: white;
  cursor: pointer;
  border-radius: 4px;
  font-size: 20px;
}

.item-total {
  font-weight: 700;
  min-width: 100px;
  text-align: right;
}

.empty-cart {
  text-align: center;
  padding: 40px;
  color: #6c757d;
}

.cart-summary {
  padding: 15px;
  background: #f8f9fa;
  border-radius: 6px;
  margin-bottom: 20px;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  font-size: 18px;
  font-weight: 600;
}

.total-amount {
  color: #28a745;
  font-size: 24px;
}

.checkout-form .form-group {
  margin-bottom: 15px;
}

.checkout-form label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.checkout-form select,
.checkout-form textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.btn-checkout {
  width: 100%;
  padding: 15px;
  background: #28a745;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
}

.btn-checkout:hover:not(:disabled) {
  background: #218838;
}

.btn-checkout:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #6c757d;
}

@media (max-width: 1024px) {
  .pos-container {
    grid-template-columns: 1fr;
  }
}
</style>