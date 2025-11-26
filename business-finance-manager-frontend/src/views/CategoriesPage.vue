<template>
  <div class="categories">
    <div class="header">
      <div>
        <h1>Product Categories</h1>
        <p class="subtitle">Organize your inventory with reusable categories.</p>
      </div>
      <div class="add">
        <input v-model="newCategory" type="text" placeholder="New category name" />
        <button class="btn btn-primary" @click="createCategory">Add</button>
      </div>
    </div>

    <div class="card">
      <table class="categories-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categories" :key="category.id">
            <td>
              <input
                v-if="editingId === category.id"
                v-model="editName"
                type="text"
                class="edit-input"
              />
              <span v-else>{{ category.name }}</span>
            </td>
            <td class="actions">
              <button
                v-if="editingId === category.id"
                class="btn btn-sm btn-primary"
                @click="saveEdit(category.id)"
              >
                Save
              </button>
              <button
                v-else
                class="btn btn-sm btn-light"
                @click="startEdit(category)"
              >
                Edit
              </button>
              <button class="btn btn-sm btn-danger" @click="deleteCategory(category.id)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="categories.length === 0" class="empty-state">No categories yet</div>
    </div>
  </div>
</template>

<script>
import productCategoryService from '../services/productCategoryService'

export default {
  name: 'CategoriesPage',
  data() {
    return {
      categories: [],
      newCategory: '',
      editingId: null,
      editName: '',
    }
  },
  mounted() {
    this.loadCategories()
  },
  methods: {
    async loadCategories() {
      try {
        const response = await productCategoryService.getAll()
        this.categories = response.data
      } catch (error) {
        console.error('Failed to load categories', error)
      }
    },
    async createCategory() {
      const name = this.newCategory.trim()
      if (!name) return
      try {
        await productCategoryService.create(name)
        this.newCategory = ''
        this.loadCategories()
      } catch (error) {
        alert('Failed to create category')
      }
    },
    startEdit(category) {
      this.editingId = category.id
      this.editName = category.name
    },
    async saveEdit(id) {
      const name = this.editName.trim()
      if (!name) return
      try {
        await productCategoryService.update(id, name)
        this.editingId = null
        this.editName = ''
        this.loadCategories()
      } catch (error) {
        alert('Failed to update category')
      }
    },
    async deleteCategory(id) {
      if (!confirm('Delete this category?')) return
      try {
        await productCategoryService.delete(id)
        this.loadCategories()
      } catch (error) {
        alert('Failed to delete category')
      }
    },
  },
}
</script>

<style scoped>
.categories {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.subtitle {
  color: #6c757d;
  margin-top: 4px;
}

.add {
  display: flex;
  gap: 8px;
}

.add input {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
}

.card {
  background: white;
  padding: 16px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.categories-table {
  width: 100%;
  border-collapse: collapse;
}

.categories-table th,
.categories-table td {
  padding: 10px 8px;
  border-bottom: 1px solid #f1f1f1;
  text-align: left;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-danger {
  background: #dc3545;
  color: white;
}

.btn-light {
  background: #f1f3f5;
  color: #333;
}

.btn-sm {
  padding: 6px 10px;
  font-size: 12px;
}

.edit-input {
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.empty-state {
  text-align: center;
  color: #6c757d;
  padding: 12px 0;
}
</style>
