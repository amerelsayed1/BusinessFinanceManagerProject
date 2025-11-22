<template>
  <div class="monthly-sales">
    <div class="header">
      <h1>Monthly Sales</h1>
      <button @click="showModal = true" class="btn btn-primary">
        Add Monthly Sales
      </button>
    </div>

    <!-- Sales Table -->
    <div class="card">
      <table class="sales-table">
        <thead>
        <tr>
          <th>Month/Year</th>
          <th>Total Sales</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="sale in sales" :key="sale.id">
          <td>{{ sale.date }}</td>
          <td>{{ formatCurrency(sale.total_sales) }}</td>
          <td>
            <button
                @click="editSale(sale)"
                class="btn btn-sm btn-secondary"
            >
              Edit
            </button>
            <button
                @click="deleteSale(sale.id)"
                class="btn btn-sm btn-danger"
            >
              Delete
            </button>
          </td>
        </tr>
        </tbody>
      </table>

      <div v-if="sales.length === 0" class="empty-state">
        No sales data found. Add your first monthly sales record!
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="modal">
      <div class="modal-content">
        <h3>{{ editingId ? 'Edit' : 'Add' }} Monthly Sales</h3>
        <form @submit.prevent="saveSale">
          <div class="form-row">
            <div class="form-group">
              <label>Month</label>
              <select v-model="form.month" required>
                <option value="">Select month</option>
                <option v-for="m in 12" :key="m" :value="m">
                  {{ getMonthName(m) }}
                </option>
              </select>
            </div>

            <div class="form-group">
              <label>Year</label>
              <input
                  v-model.number="form.year"
                  type="number"
                  min="2000"
                  max="2100"
                  required
              />
            </div>
          </div>

          <div class="form-group">
            <label>Total Sales</label>
            <input
                v-model.number="form.total_sales"
                type="number"
                step="0.01"
                min="0"
                required
            />
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Saving...' : 'Save' }}
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
  name: 'MonthlySales',
  data() {
    return {
      sales: [],
      showModal: false,
      editingId: null,
      submitting: false,
      form: {
        month: '',
        year: new Date().getFullYear(),
        total_sales: null,
      },
      currency: 'EGP',
    };
  },
  mounted() {
    this.loadSales();
    this.loadUserProfile();
  },
  methods: {
    async loadSales() {
      try {
        const response = await axios.get('/api/monthly-sales');
        this.sales = response.data;
      } catch (error) {
        console.error('Failed to load sales:', error);
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
    editSale(sale) {
      this.editingId = sale.id;
      this.form = {
        month: sale.month,
        year: sale.year,
        total_sales: sale.total_sales,
      };
      this.showModal = true;
    },
    async saveSale() {
      this.submitting = true;
      try {
        if (this.editingId) {
          await axios.put(`/api/monthly-sales/${this.editingId}`, this.form);
        } else {
          await axios.post('/api/monthly-sales', this.form);
        }
        this.loadSales();
        this.closeModal();
        alert('Sales record saved successfully');
      } catch (error) {
        alert(error.response?.data?.error || 'Failed to save sales record');
      } finally {
        this.submitting = false;
      }
    },
    async deleteSale(id) {
      if (!confirm('Are you sure you want to delete this sales record?')) {
        return;
      }

      try {
        await axios.delete(`/api/monthly-sales/${id}`);
        this.loadSales();
        alert('Sales record deleted successfully');
      } catch (error) {
        alert('Failed to delete sales record');
      }
    },
    closeModal() {
      this.showModal = false;
      this.editingId = null;
      this.form = {
        month: '',
        year: new Date().getFullYear(),
        total_sales: null,
      };
    },
    getMonthName(month) {
      const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ];
      return months[month - 1];
    },
    formatCurrency(amount) {
      return `${parseFloat(amount).toFixed(2)} ${this.currency}`;
    },
  },
};
</script>

<style scoped>
.monthly-sales {
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

.sales-table {
  width: 100%;
  border-collapse: collapse;
}

.sales-table th,
.sales-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.sales-table th {
  background: #f8f9fa;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #6c757d;
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
  min-width: 500px;
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

.form-group input,
.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
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
</style>