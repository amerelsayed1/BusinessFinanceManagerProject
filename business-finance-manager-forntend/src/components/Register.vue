<template>
  <div class="register-page">
    <div class="register-container">
      <div class="register-card">
        <div class="logo-section">
          <h1>Business Finance Manager</h1>
          <p>Start managing your business today</p>
        </div>

        <form @submit.prevent="handleRegister" class="register-form">
          <h2>Create Account</h2>

          <div v-if="error" class="alert alert-error">
            {{ error }}
          </div>

          <div class="form-group">
            <label for="name">Full Name *</label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Enter your full name"
                required
                autocomplete="name"
            />
          </div>

          <div class="form-group">
            <label for="email">Email Address *</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="Enter your email"
                required
                autocomplete="email"
            />
          </div>

          <div class="form-group">
            <label for="business_name">Business Name</label>
            <input
                id="business_name"
                v-model="form.business_name"
                type="text"
                placeholder="Enter your business name (optional)"
                autocomplete="organization"
            />
          </div>

          <div class="form-group">
            <label for="default_currency">Default Currency *</label>
            <select id="default_currency" v-model="form.default_currency" required>
              <option value="EGP">EGP - Egyptian Pound</option>
              <option value="USD">USD - US Dollar</option>
              <option value="EUR">EUR - Euro</option>
              <option value="GBP">GBP - British Pound</option>
              <option value="SAR">SAR - Saudi Riyal</option>
              <option value="AED">AED - UAE Dirham</option>
            </select>
          </div>

          <div class="form-group">
            <label for="password">Password *</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Create a password (min 6 characters)"
                required
                autocomplete="new-password"
                minlength="6"
            />
          </div>

          <div class="form-group">
            <label for="password_confirmation">Confirm Password *</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                placeholder="Confirm your password"
                required
                autocomplete="new-password"
                minlength="6"
            />
          </div>

          <div class="form-group terms">
            <label>
              <input v-model="acceptedTerms" type="checkbox" required />
              I agree to the Terms of Service and Privacy Policy
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
            {{ loading ? 'Creating Account...' : 'Create Account' }}
          </button>

          <div class="form-footer">
            <p>
              Already have an account?
              <router-link to="/login" class="link">Login here</router-link>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'Register',
  data() {
    return {
      form: {
        name: '',
        email: '',
        business_name: '',
        default_currency: 'EGP',
        password: '',
        password_confirmation: '',
      },
      acceptedTerms: false,
      loading: false,
      error: null,
    };
  },
  methods: {
    async handleRegister() {
      // Validate password match
      if (this.form.password !== this.form.password_confirmation) {
        this.error = 'Passwords do not match';
        return;
      }

      // Validate terms acceptance
      if (!this.acceptedTerms) {
        this.error = 'Please accept the Terms of Service';
        return;
      }

      this.loading = true;
      this.error = null;

      try {
        const response = await axios.post('/api/register', this.form);

        // Store token and user data
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));

        // Update Vuex store
        this.$store.commit('auth/SET_TOKEN', response.data.token);
        this.$store.commit('auth/SET_USER', response.data.user);

        // Show success message
        alert('Account created successfully! Welcome to Business Finance Manager.');

        // Redirect to dashboard
        this.$router.push('/');
      } catch (error) {
        console.error('Registration error:', error);

        // Handle validation errors
        if (error.response?.data?.errors) {
          const errors = error.response.data.errors;
          this.error = Object.values(errors).flat().join(', ');
        } else if (error.response?.data?.error) {
          this.error = error.response.data.error;
        } else {
          this.error = 'Registration failed. Please try again.';
        }
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.register-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.register-container {
  width: 100%;
  max-width: 500px;
}

.register-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  overflow: hidden;
}

.logo-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 40px 30px;
  text-align: center;
}

.logo-section h1 {
  margin: 0 0 10px 0;
  font-size: 28px;
  font-weight: 700;
}

.logo-section p {
  margin: 0;
  font-size: 16px;
  opacity: 0.9;
}

.register-form {
  padding: 40px 30px;
  max-height: calc(100vh - 240px);
  overflow-y: auto;
}

.register-form h2 {
  margin: 0 0 30px 0;
  font-size: 24px;
  color: #2c3e50;
  text-align: center;
}

.alert {
  padding: 12px 16px;
  border-radius: 6px;
  margin-bottom: 20px;
  font-size: 14px;
}

.alert-error {
  background: #fee;
  color: #c33;
  border: 1px solid #fcc;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #2c3e50;
  font-size: 14px;
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"],
.form-group select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e1e8ed;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group select {
  cursor: pointer;
  background: white;
}

.terms {
  margin-bottom: 25px;
}

.terms label {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
  font-weight: 400;
  font-size: 13px;
  line-height: 1.5;
}

.terms input[type="checkbox"] {
  margin-right: 8px;
  margin-top: 2px;
  width: 18px;
  height: 18px;
  cursor: pointer;
  flex-shrink: 0;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-block {
  width: 100%;
}

.form-footer {
  margin-top: 25px;
  text-align: center;
}

.form-footer p {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}

.link {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.link:hover {
  text-decoration: underline;
}

/* Custom scrollbar for form */
.register-form::-webkit-scrollbar {
  width: 6px;
}

.register-form::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.register-form::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 10px;
}

.register-form::-webkit-scrollbar-thumb:hover {
  background: #555;
}

@media (max-width: 480px) {
  .register-card {
    border-radius: 0;
  }

  .logo-section {
    padding: 30px 20px;
  }

  .logo-section h1 {
    font-size: 24px;
  }

  .register-form {
    padding: 30px 20px;
  }
}
</style>