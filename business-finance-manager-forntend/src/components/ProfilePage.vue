<template>
  <div class="profile-settings">
    <h1>Profile & Settings</h1>

    <!-- Profile Section -->
    <div class="card">
      <h2>Business Profile</h2>
      <form @submit.prevent="updateProfile">
        <div class="form-group">
          <label>Name</label>
          <input v-model="profile.name" type="text" required />
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="profile.email" type="email" disabled />
        </div>

        <div class="form-group">
          <label>Business Name</label>
          <input v-model="profile.business_name" type="text" />
        </div>

        <div class="form-group">
          <label>Default Currency</label>
          <select v-model="profile.default_currency">
            <option value="EGP">EGP</option>
            <option value="USD">USD</option>
            <option value="EUR">EUR</option>
            <option value="GBP">GBP</option>
          </select>
        </div>

        <div class="form-group">
          <label>Business Logo</label>
          <div v-if="profile.business_logo" class="logo-preview">
            <img :src="getLogoUrl(profile.business_logo)" alt="Logo" />
          </div>
          <input type="file" @change="handleLogoUpload" accept="image/*" />
        </div>

        <button type="submit" class="btn btn-primary" :disabled="saving">
          {{ saving ? 'Saving...' : 'Save Profile' }}
        </button>
      </form>
    </div>

    <!-- Expense Categories Section -->
    <div class="card">
      <h2>Expense Categories</h2>

      <div class="category-list">
        <div v-for="category in categories" :key="category.id" class="category-item">
          <span class="category-name">
            {{ category.name }}
            <span v-if="category.is_default" class="badge">Default</span>
          </span>
          <div class="category-actions">
            <button @click="editCategory(category)" class="btn btn-sm btn-secondary">
              Edit
            </button>
            <button
                v-if="!category.is_default"
                @click="deleteCategory(category.id)"
                class="btn btn-sm btn-danger"
            >
              Delete
            </button>
          </div>
        </div>
      </div>

      <button @click="showCategoryModal = true" class="btn btn-success mt-3">
        Add Category
      </button>
    </div>

    <!-- Category Modal -->
    <div v-if="showCategoryModal" class="modal">
      <div class="modal-content">
        <h3>{{ editingCategory ? 'Edit' : 'Add' }} Category</h3>
        <form @submit.prevent="saveCategory">
          <div class="form-group">
            <label>Category Name</label>
            <input v-model="categoryForm.name" type="text" required />
          </div>
          <div class="modal-actions">
            <button type="button" @click="closeCategoryModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary">
              Save
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
  name: 'ProfileSettings',
  data() {
    return {
      profile: {
        name: '',
        email: '',
        business_name: '',
        business_logo: '',
        default_currency: 'EGP',
      },
      categories: [],
      showCategoryModal: false,
      editingCategory: null,
      categoryForm: {
        name: '',
      },
      saving: false,
    };
  },
  mounted() {
    this.loadProfile();
    this.loadCategories();
  },
  methods: {
    async loadProfile() {
      try {
        const response = await axios.get('/api/me');
        this.profile = response.data;
      } catch (error) {
        console.error('Failed to load profile:', error);
      }
    },
    async updateProfile() {
      this.saving = true;
      try {
        await axios.put('/api/profile', this.profile);
        alert('Profile updated successfully');
      } catch (error) {
        alert('Failed to update profile');
      } finally {
        this.saving = false;
      }
    },
    async handleLogoUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('logo', file);

      try {
        const response = await axios.post('/api/profile/logo', formData);
        this.profile.business_logo = response.data.logo_url;
        alert('Logo uploaded successfully');
      } catch (error) {
        alert('Failed to upload logo');
      }
    },
    getLogoUrl(path) {
      return `${process.env.VUE_APP_API_URL}/storage/${path}`;
    },
    async loadCategories() {
      try {
        const response = await axios.get('/api/expense-categories');
        this.categories = response.data;
      } catch (error) {
        console.error('Failed to load categories:', error);
      }
    },
    editCategory(category) {
      this.editingCategory = category;
      this.categoryForm.name = category.name;
      this.showCategoryModal = true;
    },
    async saveCategory() {
      try {
        if (this.editingCategory) {
          await axios.put(`/api/expense-categories/${this.editingCategory.id}`, this.categoryForm);
        } else {
          await axios.post('/api/expense-categories', this.categoryForm);
        }
        this.loadCategories();
        this.closeCategoryModal();
      } catch (error) {
        alert('Failed to save category');
      }
    },
    async deleteCategory(id) {
      if (!confirm('Are you sure you want to delete this category?')) return;

      try {
        await axios.delete(`/api/expense-categories/${id}`);
        this.loadCategories();
      } catch (error) {
        alert(error.response?.data?.error || 'Failed to delete category');
      }
    },
    closeCategoryModal() {
      this.showCategoryModal = false;
      this.editingCategory = null;
      this.categoryForm.name = '';
    },
  },
};
</script>

<style scoped>
.profile-settings {
  padding: 20px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.logo-preview img {
  max-width: 200px;
  margin-bottom: 10px;
}

.category-list {
  margin-bottom: 15px;
}

.category-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.badge {
  background: #007bff;
  color: white;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  margin-left: 10px;
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
  padding: 20px;
  border-radius: 8px;
  min-width: 400px;
}

.modal-actions {
  display: flex;
  gap: 10px;
  margin-top: 15px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 14px;
}

.mt-3 {
  margin-top: 15px;
}
</style>