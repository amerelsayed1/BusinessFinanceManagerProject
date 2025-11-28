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
        <div
            v-for="category in categories"
            :key="category.id"
            class="category-item"
        >
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

    <!-- Category Modal (SalePro-style) -->
    <div
        v-if="showCategoryModal"
        class="modal"
        @click.self="closeCategoryModal"
    >
      <div class="modal-dialog">
        <!-- Header -->
        <div class="modal-header">
          <h3 class="modal-title">
            {{ editingCategory ? 'Edit Category' : 'Add Category' }}
          </h3>
          <button
              type="button"
              class="modal-close"
              @click="closeCategoryModal"
          >
            ×
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body">
          <p class="modal-note">
            The field labels marked with <span>*</span> are required input fields.
          </p>

          <form @submit.prevent="saveCategory">
            <div class="modal-grid">
              <!-- Left column (similar to name + parent in SalePro) -->
              <div class="modal-col">
                <div class="form-group">
                  <label>
                    Name <span class="required">*</span>
                  </label>
                  <input
                      v-model="categoryForm.name"
                      type="text"
                      required
                      placeholder="Type category name"
                  />
                </div>

                <!-- If later you support parent category, add it here -->
                <!--
                <div class="form-group">
                  <label>Parent Category</label>
                  <select>
                    <option>No parent</option>
                  </select>
                </div>
                -->
              </div>

              <div class="modal-col">
                <div class="form-group">
                  <label>Image</label>
                  <input type="file" />
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
              <button
                  type="button"
                  class="btn btn-secondary"
                  @click="closeCategoryModal"
              >
                Cancel
              </button>
              <button type="submit" class="btn btn-primary">
                {{ editingCategory ? 'Save Changes' : 'Submit' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../services/api';

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
        const response = await api.get('/me');
        this.profile = response.data;
      } catch (error) {
        console.error('Failed to load profile:', error);
      }
    },
    async updateProfile() {
      this.saving = true;
      try {
        await api.put('/profile', this.profile);
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
        const response = await api.post('/profile/logo', formData);
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
        const response = await api.get('/expense-categories');
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
          await api.put(
              `/expense-categories/${this.editingCategory.id}`,
              this.categoryForm,
          );
        } else {
          await api.post('/expense-categories', this.categoryForm);
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
        await api.delete(`/expense-categories/${id}`);
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

/* ==== Modal (SalePro-like) ==== */

.modal {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-dialog {
  background: #fff;
  border-radius: 10px;
  min-width: 520px;
  max-width: 780px;
  box-shadow: 0 15px 40px rgba(0,0,0,0.18);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #eee;
}

.modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.modal-close {
  border: none;
  background: transparent;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  color: #999;
}

.modal-close:hover {
  color: #555;
}

.modal-body {
  padding: 18px 20px 22px;
}

.modal-note {
  font-size: 13px;
  font-style: italic;
  color: #666;
  margin-bottom: 16px;
}

.modal-note span {
  color: #e55353;
}

.modal-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
}

@media (min-width: 768px) {
  .modal-grid {
    grid-template-columns: 1.5fr 1fr;
  }
}

.modal-col {
  /* placeholder for future right column fields */
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 24px;
}

.required {
  color: #e55353;
}

/* Buttons */

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-primary {
  background: #6c4ad9;
  color: white;
}

.btn-primary:hover {
  background: #593ac5;
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
