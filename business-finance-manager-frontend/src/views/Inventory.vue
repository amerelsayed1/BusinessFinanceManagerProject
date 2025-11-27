<template>
  <div class="inventory">
    <div class="header">
      <div>
        <h1>Inventory Management</h1>
        <p class="subtitle">Manage products, categories, and stock from one page.</p>
      </div>
    </div>

    <div class="grid">
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
          <select v-model="categoryFilter" class="filter-select">
            <option value="">All Categories</option>
            <option v-for="cat in categoryOptions" :key="cat" :value="cat">{{ cat }}</option>
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
                <span class="stock-badge" :class="getStockClass(product.current_stock)">
                  {{ product.current_stock }}
                </span>
              </td>
              <td>
                <span class="status-badge" :class="product.is_active ? 'active' : 'inactive'">
                  {{ product.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="actions">
                <button @click="selectProduct(product)" class="btn btn-sm btn-light">View</button>
                <button @click="editProduct(product)" class="btn btn-sm btn-secondary">Edit</button>
                <button @click="prepareStockAdjust(product)" class="btn btn-sm btn-info">Adjust Stock</button>
                <button @click="deleteProduct(product.id)" class="btn btn-sm btn-danger">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="filteredProducts.length === 0" class="empty-state">
          No products found
        </div>
      </div>

      <div class="side-panel">
        <div class="card">
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
                <select v-model="productForm.category">
                  <option value="">Uncategorized</option>
                  <option v-for="cat in categoryOptions" :key="cat" :value="cat">{{ cat }}</option>
                </select>
                <div class="inline-add">
                  <input
                    v-model="newCategoryName"
                    type="text"
                    placeholder="New category"
                  />
                  <button type="button" class="btn btn-xs" @click="addCategory">Add</button>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label>Description (optional)</label>
              <textarea
                v-model="productForm.description"
                rows="2"
                placeholder="Short description for the product"
              ></textarea>
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
              <div class="flex justify-between items-center">
                <label class="mb-1">Variants &amp; Stock</label>
                <button type="button" class="btn btn-xs" @click="addVariantRow">Add Variant</button>
              </div>
              <div v-if="variants.length === 0" class="text-sm text-gray-500 mb-2">No variants yet.</div>
              <div v-for="(variant, index) in variants" :key="index" class="variant-row">
                <input
                  v-model="variant.name"
                  type="text"
                  placeholder="Variant name"
                />
                <input
                  v-model.number="variant.stock"
                  type="number"
                  min="0"
                  placeholder="Available stock"
                />
                <button type="button" class="btn btn-xs btn-danger" @click="removeVariantRow(index)">
                  Remove
                </button>
              </div>
              <p class="text-xs text-gray-600 mt-2">
                Total variant stock: {{ variantStockTotal }}
              </p>
            </div>

          <div class="form-group">
            <label>Product Image</label>
            <input
                type="file"
                accept="image/*"
                @change="onImageSelected"
            />
            <div v-if="imagePreview || productForm.existing_image" class="image-preview">
              <p class="preview-label">Preview</p>
              <img :src="imagePreview || productForm.existing_image" alt="Product preview" />
            </div>
          </div>

          <div class="form-group">
            <label>
              <input v-model="productForm.is_active" type="checkbox" />
              Active
            </label>
          </div>

            <div class="form-actions">
              <button type="submit" class="btn btn-primary" :disabled="submitting">
                {{ submitting ? 'Saving...' : 'Save' }}
              </button>
              <button type="button" class="btn btn-info" @click="fillRandomProduct">Random product</button>
              <button type="button" class="btn btn-secondary" @click="resetForm">Reset</button>
            </div>
          </form>
        </div>

        <div class="card">
          <h3>Product Details</h3>
          <div v-if="selectedProduct" class="product-details">
            <p><strong>Name:</strong> {{ selectedProduct.name }}</p>
            <p><strong>SKU:</strong> {{ selectedProduct.sku }}</p>
            <p><strong>Category:</strong> {{ selectedProduct.category || 'Uncategorized' }}</p>
            <p><strong>Cost:</strong> {{ formatCurrency(selectedProduct.cost_price) }}</p>
            <p><strong>Price:</strong> {{ formatCurrency(selectedProduct.selling_price) }}</p>
            <p><strong>Stock:</strong> {{ selectedProduct.current_stock }}</p>
            <p><strong>Status:</strong> {{ selectedProduct.is_active ? 'Active' : 'Inactive' }}</p>

            <div class="stock-form">
              <h4>Adjust Stock</h4>
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
                  <textarea v-model="stockForm.note" rows="2"></textarea>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-info" :disabled="submitting">
                    {{ submitting ? 'Adjusting...' : 'Adjust Stock' }}
                  </button>
                  <button type="button" class="btn btn-secondary" @click="resetStockForm">Clear</button>
                </div>
              </form>
            </div>
          </div>
          <div v-else class="empty-state">
            Select a product to see its details
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api'
import productCategoryService from '../services/productCategoryService'

export default {
  name: 'Inventory',
  data() {
    return {
      products: [],
      categories: [],
      searchQuery: '',
      activeFilter: '',
      categoryFilter: '',
      editingProduct: null,
      selectedProduct: null,
      submitting: false,
      productForm: {
        name: '',
        sku: '',
        category: '',
        description: '',
        cost_price: null,
        selling_price: null,
        current_stock: 0,
        is_active: true,
        image: null,
        existing_image: null,
      },
      variants: [],
      newCategoryName: '',
      stockForm: {
        quantity: null,
        note: '',
      },
      currency: 'EGP',
      imagePreview: null,
      showProductModal: false,
    };
  },
  computed: {
    filteredProducts() {
      let filtered = this.products

      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(
          (p) =>
            p.name.toLowerCase().includes(query) ||
            p.sku.toLowerCase().includes(query) ||
            (p.category && p.category.toLowerCase().includes(query)),
        )
      }

      if (this.activeFilter !== '') {
        filtered = filtered.filter(
          (p) => p.is_active === Boolean(Number(this.activeFilter)),
        )
      }

      if (this.categoryFilter) {
        filtered = filtered.filter((p) => p.category === this.categoryFilter)
      }

      return filtered
    },
    categoryOptions() {
      const names = new Set([...(this.categories || []).map((c) => c.name)])
      this.products
        .filter((p) => p.category)
        .forEach((p) => names.add(p.category))
      return Array.from(names).sort((a, b) => a.localeCompare(b))
    },
    variantStockTotal() {
      return this.variants.reduce((sum, variant) => sum + Number(variant.stock || 0), 0)
    },
  },
  mounted() {
    this.loadProducts()
    this.loadCategories()
    this.loadUserProfile()
  },
  methods: {
    async loadProducts() {
      try {
        const response = await api.get('/products')
        this.products = response.data
      } catch (error) {
        console.error('Failed to load products:', error)
      }
    },
    async loadCategories() {
      try {
        const response = await productCategoryService.getAll()
        this.categories = response.data
      } catch (error) {
        console.error('Failed to load categories:', error)
      }
    },
    async loadUserProfile() {
      try {
        const response = await api.get('/me')
        this.currency = response.data.default_currency || 'EGP'
      } catch (error) {
        console.error('Failed to load user profile:', error)
      }
    },
    selectProduct(product) {
      this.selectedProduct = product
      this.stockForm = {
        quantity: null,
        note: '',
      }
    },
    editProduct(product) {
      this.editingProduct = product
      this.productForm = {
        name: product.name,
        sku: product.sku,
        category: product.category,
        description: product.description || '',
        cost_price: product.cost_price,
        selling_price: product.selling_price,
        is_active: product.is_active,
        image: null,
        existing_image: product.image || product.image_url || product.image_path || null,
      };
      this.variants = product.current_stock
        ? [{ name: 'Default', stock: Number(product.current_stock) }]
        : []
      this.imagePreview = this.productForm.existing_image;
      this.showProductModal = true;
    },
    async saveProduct() {
      this.submitting = true
      try {
        const formData = new FormData();
        formData.append('name', this.productForm.name);
        formData.append('sku', this.productForm.sku);
        formData.append('category', this.productForm.category || '');
        formData.append('description', this.productForm.description || '');
        formData.append('cost_price', this.productForm.cost_price ?? 0);
        formData.append('selling_price', this.productForm.selling_price ?? 0);
        formData.append('is_active', this.productForm.is_active ? 1 : 0);

        const variantStock = this.variants.reduce(
          (sum, variant) => sum + Number(variant.stock || 0),
          0,
        )

        const stockToSave = this.variants.length > 0
          ? variantStock
          : this.productForm.current_stock ?? 0

        if (!this.editingProduct) {
          formData.append('current_stock', stockToSave);
        }

        if (this.productForm.image) {
          formData.append('image', this.productForm.image);
        }

        const config = {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        };

        if (this.editingProduct) {
          formData.append('_method', 'PUT');
          await api.post(`/products/${this.editingProduct.id}`, formData, config);
        } else {
          await api.post('/products', formData, config);
        }
        await this.loadProducts()
        await this.loadCategories()
        this.resetForm()
        alert('Product saved successfully')
      } catch (error) {
        alert(error.response?.data?.error || 'Failed to save product')
      } finally {
        this.submitting = false
      }
    },
    onImageSelected(event) {
      const file = event.target.files?.[0];
      this.productForm.image = file || null;
      this.imagePreview = file ? URL.createObjectURL(file) : this.productForm.existing_image;
    },
    async deleteProduct(id) {
      if (!confirm('Are you sure you want to delete this product?')) return

      try {
        await api.delete(`/products/${id}`)
        await this.loadProducts()
        alert('Product deleted successfully')
        if (this.selectedProduct && this.selectedProduct.id === id) {
          this.selectedProduct = null
        }
      } catch (error) {
        alert('Failed to delete product')
      }
    },
    prepareStockAdjust(product) {
      this.selectProduct(product)
    },
    async adjustStock() {
      if (!this.selectedProduct) {
        alert('Please select a product first')
        return
      }

      if (!this.stockForm.quantity || this.stockForm.quantity === 0) {
        alert('Please enter a valid quantity')
        return
      }

      this.submitting = true
      try {
        await api.post(
          `/products/${this.selectedProduct.id}/adjust-stock`,
          this.stockForm,
        )
        await this.loadProducts()
        const updated = this.products.find(
          (p) => p.id === this.selectedProduct.id,
        )
        if (updated) this.selectedProduct = updated
        this.resetStockForm()
        alert('Stock adjusted successfully')
      } catch (error) {
        alert('Failed to adjust stock')
      } finally {
        this.submitting = false
      }
    },
    resetForm() {
      this.editingProduct = null
      this.productForm = {
        name: '',
        sku: '',
        category: '',
        description: '',
        cost_price: null,
        selling_price: null,
        current_stock: 0,
        is_active: true,
        image: null,
        existing_image: null,
      };
      this.variants = []
      this.imagePreview = null;
    },
    addVariantRow() {
      this.variants.push({ name: '', stock: 0 })
    },
    removeVariantRow(index) {
      this.variants.splice(index, 1)
    },
    fillRandomProduct() {
      const randomId = Math.floor(Math.random() * 10000)
      this.productForm.name = `Sample Product ${randomId}`
      this.productForm.sku = `SKU-${randomId}`
      this.productForm.category = this.categoryOptions[0] || ''
      this.productForm.description = 'Autogenerated product for quick testing.'
      this.productForm.cost_price = Number((Math.random() * 50 + 10).toFixed(2))
      this.productForm.selling_price = Number(
        (this.productForm.cost_price + Math.random() * 50).toFixed(2),
      )
      this.productForm.current_stock = Math.floor(Math.random() * 50) + 1
      this.variants = [
        { name: 'Standard', stock: Math.max(1, Math.floor(this.productForm.current_stock / 2)) },
        { name: 'Premium', stock: Math.max(0, this.productForm.current_stock - 5) },
      ]
    },
    resetStockForm() {
      this.stockForm = {
        quantity: null,
        note: '',
      }
    },
    async addCategory() {
      const name = this.newCategoryName.trim()
      if (!name) return

      try {
        await productCategoryService.create(name)
        await this.loadCategories()
        this.productForm.category = name
        this.newCategoryName = ''
      } catch (error) {
        alert('Failed to add category')
      }
    },
    getStockClass(stock) {
      if (stock === 0) return 'out-of-stock'
      if (stock < 10) return 'low-stock'
      return 'in-stock'
    },
    formatCurrency(amount) {
      return `${parseFloat(amount || 0).toFixed(2)} ${this.currency}`
    },
  },
}
</script>

<style scoped>
.inventory {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.subtitle {
  color: #6c757d;
  margin-top: 4px;
}

.grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  margin-bottom: 20px;
}

.filters {
  display: flex;
  gap: 10px;
  margin-bottom: 15px;
}

.search-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.products-table {
  width: 100%;
  border-collapse: collapse;
}

.products-table th,
.products-table td {
  padding: 10px 8px;
  text-align: left;
  border-bottom: 1px solid #f1f1f1;
}

.products-table th {
  background: #f8f9fa;
  font-weight: 600;
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
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

.side-panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-group {
  margin-bottom: 12px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input[type='text'],
.form-group input[type='number'],
.form-group select,
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

.image-preview {
  margin-top: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 10px;
  background: #f8fafc;
}

.image-preview img {
  max-width: 100%;
  max-height: 200px;
  display: block;
  object-fit: contain;
}

.preview-label {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 6px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.checkbox {
  display: flex;
  align-items: center;
}

.btn {
  padding: 8px 14px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-xs {
  padding: 6px 10px;
  margin-left: 6px;
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

.btn-light {
  background: #f1f3f5;
  color: #333;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-sm {
  padding: 6px 10px;
  font-size: 12px;
}

.inline-add {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
}

.product-details p {
  margin-bottom: 6px;
}

.stock-form {
  margin-top: 12px;
  padding-top: 10px;
  border-top: 1px solid #eee;
}

.empty-state {
  text-align: center;
  color: #6c757d;
  padding: 16px 0;
}

@media (max-width: 1024px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>
