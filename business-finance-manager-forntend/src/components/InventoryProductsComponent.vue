<template>
  <div class="inventory">
    <div class="header">
      <h1>Inventory Management</h1>
      <button @click="showProductModal = true" class="btn btn-primary">
        Add Product
      </button>
    </div>

    <!-- Products Table -->
    <div class="card">
      <div class="filters">
        <input
            v-model="searchQuery"
            type="text"
            placeholder="Search products..."
            class="search-input"
        />
        <select v-model="activeFilter" class="filter-select">
          <option value="">All Products</option>
          <option value="1">Active Only</option>
          <option value="0">Inactive Only</option>
        </select>
      </div>

      <table class="products-table">
        <thead>
        <tr>
          <th>Name</th>
          <th>SKU</th>
          <th>Category</th>
          <th>Cost Price</th>
          <th>Selling Price</th>
          <th>Stock</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="product in filteredProducts" :key="product.id">
          <td>{{ product.name }}</td>
          <td>{{ product.sku }}</td>
          <td>{{ product.category || '-' }}</td>
          <td>{{ formatCurrency(product.cost_price) }}</td>
          <td>{{ formatCurrency(product.selling_price) }}</td>
          <td>
              <span
                  class="stock-badge"
                  :class="getStockClass(product.current_stock)"
              >
                {{ product.current_stock }}
              </span>
          </td>
          <td>
              <span
                  class="status-badge"
                  :class="product.is_active ? 'active' : 'inactive'"
              >
                {{ product.is_active ? 'Active' : 'Inactive' }}
              </span>
          </td>
          <td>
            <button @click="editProduct(product)" class="btn btn-sm btn-secondary">
              Edit
            </button>
            <button @click="showStockModal(product)" class="btn btn-sm btn-info">
              Adjust Stock
            </button>
            <button @click="deleteProduct(product.id)" class="btn btn-sm btn-danger">
              Delete
            </button>
          </td>
        </tr>
        </tbody>
      </table>

      <div v-if="filteredProducts.length === 0" class="empty-state">
        No products found
      </div>
    </div>

    <!-- Product Modal -->
    <div v-if="showProductModal" class="modal">
      <div class="modal-content">
        <h3>{{ editingProduct ? 'Edit' : 'Add' }} Product</h3>
        <form @submit.prevent="saveProduct">
          <div class="form-group">
            <label>Name *</label>
            <input v-model="productForm.name" type="text" required />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>SKU *</label>
              <input v-model="productForm.sku" type="text" required />
            </div>

            <div class="form-group">
              <label>Category</label>
              <input v-model="productForm.category" type="text" />
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Cost Price *</label>
              <input v-model.number="productForm.cost_price" type="number" step="0.01" required />
            </div>

            <div class="form-group">
              <label>Selling Price *</label>
              <input v-model.number="productForm.selling_price" type="number" step="0.01" required />
            </div>
          </div>

          <div class="form-row" v-if="!editingProduct">
            <div class="form-group">
              <label>Initial Stock</label>
              <input v-model.number="productForm.current_stock" type="number" min="0" />
            </div>
          </div>

          <div class="form-group">
            <label>
              <input v-model="productForm.is_active" type="checkbox" />
              Active
            </label>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeProductModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div v-if="showStockAdjustModal" class="modal">
      <div class="modal-content">
        <h3>Adjust Stock: {{ currentProduct?.name }}</h3>
        <p>Current Stock: <strong>{{ currentProduct?.current_stock }}</strong></p>

        <form @submit.prevent="adjustStock">
          <div class="form-group">
            <label>Adjustment Quantity</label>
            <input
                v-model.number="stockForm.quantity"
                type="number"
                required
                placeholder="Positive to add, negative to remove"
            />
            <small>Enter a positive number to add stock, negative to remove</small>
          </div>

          <div class="form-group">
            <label>Note</label>
            <textarea v-model="stockForm.note" rows="3"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeStockModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Adjusting...' : 'Adjust Stock' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Inventory',
  data() {
    return {
      products: [],
      searchQuery: '',
      activeFilter: '',
      showProductModal: false,
      showStockAdjustModal: false,
      editingProduct: null,
      currentProduct: null,
      submitting: false,
      productForm: {
        name: '',
        sku: '',
        category: '',
        cost_price: null,
        selling_price: null,
        current_stock: 0,
        is_active: true,
      },
      stockForm: {
        quantity: null,
        note: '',
      },
      currency: 'EGP',
    };
  },
  computed: {
    filteredProducts() {
      let filtered = this.products;

      // Filter by search query
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase();
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(query) ||
            p.sku.toLowerCase().includes(query) ||
            (p.category && p.category.toLowerCase().includes(query))
        );
      }

      // Filter by active status
      if (this.activeFilter !== '') {
        filtered = filtered.filter(p => p.is_active === Boolean(Number(this.activeFilter)));
      }

      return filtered;
    },
  },
  mounted() {
    this.loadProducts();
    this.loadUserProfile();
  },
  methods: {
    async loadProducts() {
      try {
        const response = await axios.get('/api/products');
        this.products = response.data;
      } catch (error) {
        console.error('Failed to load products:', error);
      }
    },
    async loadUserProfile() {
      try {
        const response = await axios.get('/api/me');
        this.currency = response.data.default_currency || 'EGP';
      } catch (error) {
        console.error('Failed to load user profile:', error);
      }
    },
    editProduct(product) {
      this.editingProduct = product;
      this.productForm = {
        name: product.name,
        sku: product.sku,
        category: product.category,
        cost_price: product.cost_price,
        selling_price: product.selling_price,
        is_active: product.is_active,
      };
      this.showProductModal = true;
    },
    async saveProduct() {
      this.submitting = true;
      try {
        if (this.editingProduct) {
          await axios.put(`/api/products/${this.editingProduct.id}`, this.productForm);
        } else {
          await axios.post('/api/products', this.productForm);
        }
        this.loadProducts();
        this.closeProductModal();
        alert('Product saved successfully');
      } catch (error) {
        alert(error.response?.data?.error || 'Failed to save product');
      } finally {
        this.submitting = false;
      }
    },
    async deleteProduct(id) {
      if (!confirm('Are you sure you want to delete this product?')) return;

      try {
        await axios.delete(`/api/products/${id}`);
        this.loadProducts();
        alert('Product deleted successfully');
      } catch (error) {
        alert('Failed to delete product');
      }
    },
    showStockModal(product) {
      this.currentProduct = product;
      this.showStockAdjustModal = true;
    },
    async adjustStock() {
      if (!this.stockForm.quantity || this.stockForm.quantity === 0) {
        alert('Please enter a valid quantity');
        return;
      }

      this.submitting = true;
      try {
        await axios.post(`/api/products/${this.currentProduct.id}/adjust-stock`, this.stockForm);
        this.loadProducts();
        this.closeStockModal();
        alert('Stock adjusted successfully');
      } catch (error) {
        alert('Failed to adjust stock');
      } finally {
        this.submitting = false;
      }
    },
    closeProductModal() {
      this.showProductModal = false;
      this.editingProduct = null;
      this.productForm = {
        name: '',
        sku: '',
        category: '',
        cost_price: null,
        selling_price: null,
        current_stock: 0,
        is_active: true,
      };
    },
    closeStockModal() {
      this.showStockAdjustModal = false;
      this.currentProduct = null;
      this.stockForm = {
        quantity: null,
        note: '',
      };
    },
    getStockClass(stock) {
      if (stock === 0) return 'out-of-stock';
      if (stock < 10) return 'low-stock';
      return 'in-stock';
    },
    formatCurrency(amount) {
      return `${parseFloat(amount || 0).toFixed(2)} ${this.currency}`;
    },
  },
};
</script>

<style scoped>
.inventory {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filters {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.search-input,
.filter-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.search-input {
  flex: 1;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
}

.products-table th,
.products-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.products-table th {
  background: #f8f9fa;
  font-weight: 600;
}

.stock-badge,
.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.stock-badge.in-stock {
  background: #d4edda;
  color: #155724;
}

.stock-badge.low-stock {
  background: #fff3cd;
  color: #856404;
}

.stock-badge.out-of-stock {
  background: #f8d7da;
  color: #721c24;
}

.status-badge.active {
  background: #d4edda;
  color: #155724;
}

.status-badge.inactive {
  background: #f8d7da;
  color: #721c24;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 30px;
  border-radius: 8px;
  min-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 15px;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-group small {
  display: block;
  margin-top: 4px;
  color: #6c757d;
  font-size: 12px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-info {
  background: #17a2b8;
  color: white;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 14px;
  margin-right: 5px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #6c757d;
}
</style>