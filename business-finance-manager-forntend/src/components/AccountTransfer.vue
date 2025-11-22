<template>
  <div class="transfers">
    <div class="header">
      <h1>Account Transfers</h1>
      <button @click="showModal = true" class="btn btn-primary">
        New Transfer
      </button>
    </div>

    <!-- Transfers List -->
    <div class="card">
      <table class="transfers-table">
        <thead>
        <tr>
          <th>Date</th>
          <th>From Account</th>
          <th>To Account</th>
          <th>Amount</th>
          <th>Note</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="transfer in transfers" :key="transfer.id">
          <td>{{ formatDate(transfer.date) }}</td>
          <td>{{ transfer.from_account?.name || 'N/A' }}</td>
          <td>{{ transfer.to_account?.name || 'N/A' }}</td>
          <td>{{ formatCurrency(transfer.amount) }}</td>
          <td>{{ transfer.note || '-' }}</td>
          <td>
            <button
                @click="deleteTransfer(transfer.id)"
                class="btn btn-sm btn-danger"
            >
              Delete
            </button>
          </td>
        </tr>
        </tbody>
      </table>

      <div v-if="transfers.length === 0" class="empty-state">
        No transfers found. Create your first transfer!
      </div>
    </div>

    <!-- Transfer Modal -->
    <div v-if="showModal" class="modal">
      <div class="modal-content">
        <h3>New Transfer</h3>
        <form @submit.prevent="createTransfer">
          <div class="form-group">
            <label>From Account</label>
            <select v-model="form.from_account_id" required>
              <option value="">Select account</option>
              <option
                  v-for="account in accounts"
                  :key="account.id"
                  :value="account.id"
                  :disabled="account.id === form.to_account_id"
              >
                {{ account.name }} ({{ formatCurrency(account.balance) }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>To Account</label>
            <select v-model="form.to_account_id" required>
              <option value="">Select account</option>
              <option
                  v-for="account in accounts"
                  :key="account.id"
                  :value="account.id"
                  :disabled="account.id === form.from_account_id"
              >
                {{ account.name }} ({{ formatCurrency(account.balance) }})
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Amount</label>
            <input
                v-model.number="form.amount"
                type="number"
                step="0.01"
                min="0.01"
                required
            />
          </div>

          <div class="form-group">
            <label>Date</label>
            <input v-model="form.date" type="date" required />
          </div>

          <div class="form-group">
            <label>Note (Optional)</label>
            <textarea v-model="form.note" rows="3"></textarea>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="submitting">
              {{ submitting ? 'Creating...' : 'Create Transfer' }}
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
  name: 'AccountTransfers',
  data() {
    return {
      transfers: [],
      accounts: [],
      showModal: false,
      submitting: false,
      form: {
        from_account_id: '',
        to_account_id: '',
        amount: null,
        date: new Date().toISOString().split('T')[0],
        note: '',
      },
      currency: 'EGP',
    };
  },
  mounted() {
    this.loadTransfers();
    this.loadAccounts();
    this.loadUserProfile();
  },
  methods: {
    async loadTransfers() {
      try {
        const response = await axios.get('/api/transfers');
        this.transfers = response.data;
      } catch (error) {
        console.error('Failed to load transfers:', error);
      }
    },
    async loadAccounts() {
      try {
        const response = await axios.get('/api/accounts');
        this.accounts = response.data;
      } catch (error) {
        console.error('Failed to load accounts:', error);
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
    async createTransfer() {
      if (this.form.from_account_id === this.form.to_account_id) {
        alert('Source and destination accounts must be different');
        return;
      }

      this.submitting = true;
      try {
        await axios.post('/api/transfers', this.form);
        this.loadTransfers();
        this.loadAccounts(); // Refresh account balances
        this.closeModal();
        alert('Transfer created successfully');
      } catch (error) {
        alert(error.response?.data?.error || 'Failed to create transfer');
      } finally {
        this.submitting = false;
      }
    },
    async deleteTransfer(id) {
      if (!confirm('Are you sure you want to delete this transfer? This will reverse the account balances.')) {
        return;
      }

      try {
        await axios.delete(`/api/transfers/${id}`);
        this.loadTransfers();
        this.loadAccounts();
        alert('Transfer deleted successfully');
      } catch (error) {
        alert('Failed to delete transfer');
      }
    },
    closeModal() {
      this.showModal = false;
      this.form = {
        from_account_id: '',
        to_account_id: '',
        amount: null,
        date: new Date().toISOString().split('T')[0],
        note: '',
      };
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    formatCurrency(amount) {
      return `${parseFloat(amount).toFixed(2)} ${this.currency}`;
    },
  },
};
</script>

<style scoped>
.transfers {
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

.transfers-table {
  width: 100%;
  border-collapse: collapse;
}

.transfers-table th,
.transfers-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

.transfers-table th {
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
  max-height: 90vh;
  overflow-y: auto;
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
.form-group select,
.form-group textarea {
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
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>