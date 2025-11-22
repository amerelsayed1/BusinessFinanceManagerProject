<template>
  <div class="dashboard">
    <h1>Dashboard</h1>

    <div class="dashboard-cards">
      <!-- Total Balance Card -->
      <div class="card">
        <div class="card-header">
          <h3>Total Balance</h3>
          <button @click="$router.push('/accounts')" class="btn btn-sm">
            Manage
          </button>
        </div>
        <div class="card-value">
          {{ formatCurrency(dashboardData.total_balance) }}
        </div>
      </div>

      <!-- Total Expenses Card -->
      <div class="card">
        <div class="card-header">
          <h3>Total Expenses</h3>
          <span class="card-subtitle">This Month</span>
        </div>
        <div class="card-value">
          {{ formatCurrency(dashboardData.total_expenses) }}
        </div>
      </div>

      <!-- Pending Invoices Card -->
      <div class="card">
        <div class="card-header">
          <h3>Pending Invoices</h3>
          <span class="card-subtitle">This Month</span>
        </div>
        <div class="card-value">
          {{ formatCurrency(dashboardData.pending_invoices) }}
        </div>
      </div>

      <!-- Paid Invoices Card -->
      <div class="card">
        <div class="card-header">
          <h3>Paid Invoices</h3>
          <span class="card-subtitle">This Month</span>
        </div>
        <div class="card-value">
          {{ formatCurrency(dashboardData.paid_invoices) }}
        </div>
      </div>

      <!-- ROI Card -->
      <div class="card card-roi">
        <div class="card-header">
          <h3>Return on Ad Spend (ROI)</h3>
          <span class="card-subtitle">{{ dashboardData.roi?.date }}</span>
        </div>
        <div class="roi-content">
          <div class="roi-percentage">
            <span class="roi-value">{{ dashboardData.roi?.roi_percent || 0 }}%</span>
          </div>
          <div class="roi-details">
            <div class="roi-detail-item">
              <span class="label">Profit:</span>
              <span class="value">{{ formatCurrency(dashboardData.roi?.profit) }}</span>
            </div>
            <div class="roi-detail-item">
              <span class="label">Total Sales:</span>
              <span class="value">{{ formatCurrency(dashboardData.roi?.total_sales) }}</span>
            </div>
            <div class="roi-detail-item">
              <span class="label">Ads Expenses:</span>
              <span class="value">{{ formatCurrency(dashboardData.roi?.ads_expenses) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <h2>Quick Actions</h2>
      <div class="action-buttons">
        <button @click="$router.push('/expenses')" class="btn btn-primary">
          Add Expense
        </button>
        <button @click="$router.push('/invoices')" class="btn btn-primary">
          Create Invoice
        </button>
        <button @click="$router.push('/transfers')" class="btn btn-primary">
          Transfer Money
        </button>
        <button @click="$router.push('/pos')" class="btn btn-success">
          New Sale (POS)
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Dashboard',
  data() {
    return {
      dashboardData: {
        total_balance: 0,
        total_expenses: 0,
        pending_invoices: 0,
        paid_invoices: 0,
        roi: {
          date: '',
          roi_percent: 0,
          profit: 0,
          total_sales: 0,
          ads_expenses: 0,
        },
        currency: 'EGP',
      },
    };
  },
  mounted() {
    this.loadDashboard();
  },
  methods: {
    async loadDashboard() {
      try {
        const response = await axios.get('/api/dashboard');
        this.dashboardData = response.data;
      } catch (error) {
        console.error('Failed to load dashboard:', error);
      }
    },
    formatCurrency(amount) {
      const value = parseFloat(amount || 0).toFixed(2);
      return `${value} ${this.dashboardData.currency}`;
    },
  },
};
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.dashboard-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}

.card-header h3 {
  margin: 0;
  font-size: 16px;
  color: #6c757d;
}

.card-subtitle {
  font-size: 12px;
  color: #adb5bd;
}

.card-value {
  font-size: 32px;
  font-weight: 700;
  color: #212529;
}

.card-roi {
  grid-column: span 2;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.card-roi .card-header h3,
.card-roi .card-subtitle {
  color: white;
}

.roi-content {
  display: flex;
  align-items: center;
  gap: 30px;
}

.roi-percentage {
  flex-shrink: 0;
}

.roi-value {
  font-size: 48px;
  font-weight: 700;
}

.roi-details {
  flex: 1;
}

.roi-detail-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid rgba(255,255,255,0.2);
}

.roi-detail-item:last-child {
  border-bottom: none;
}

.roi-detail-item .label {
  font-weight: 500;
  opacity: 0.9;
}

.roi-detail-item .value {
  font-weight: 600;
}

.quick-actions {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.quick-actions h2 {
  margin-bottom: 15px;
}

.action-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-success {
  background: #28a745;
  color: white;
}

.btn-sm {
  padding: 4px 12px;
  font-size: 14px;
  background: rgba(255,255,255,0.2);
  color: white;
  border: 1px solid rgba(255,255,255,0.3);
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
  .card-roi {
    grid-column: span 1;
  }

  .roi-content {
    flex-direction: column;
    gap: 15px;
  }
}
</style>