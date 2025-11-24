<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-card">
        <div class="logo-section">
          <h1>Business Finance Manager</h1>
          <p>Manage your business finances efficiently</p>
        </div>

        <form @submit.prevent="handleLogin" class="login-form">
          <h2>Login</h2>

          <div v-if="error" class="alert alert-error">
            {{ error }}
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
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
            <label for="password">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
            />
          </div>

          <div class="form-group remember-me">
            <label>
              <input v-model="rememberMe" type="checkbox" />
              Remember me
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-block" :disabled="loading">
            {{ loading ? 'Logging in...' : 'Login' }}
          </button>

          <div class="form-footer">
            <p>
              Don't have an account?
              <router-link to="/register" class="link">Register here</router-link>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { mapActions } from 'vuex';

export default {
  name: 'Login',
  data() {
    return {
      form: {
        email: '',
        password: '',
      },
      rememberMe: false,
      loading: false,
      error: null,
    };
  },
  methods: {
    ...mapActions('auth', ['login']),

    async handleLogin() {
      this.loading = true;
      this.error = null;

      try {
        await this.login(this.form);

        // Redirect to dashboard after successful login
        this.$router.push('/');
      } catch (error) {
        console.error('Login error:', error);
        this.error = error.response?.data?.error || 'Invalid email or password';
      } finally {
        this.loading = false;
      }
    },
  },
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.login-container {
  width: 100%;
  max-width: 450px;
}

.login-card {
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

.login-form {
  padding: 40px 30px;
}

.login-form h2 {
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

.form-group input[type="email"],
.form-group input[type="password"] {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e1e8ed;
  border-radius: 8px;
  font-size: 15px;
  transition: all 0.3s;
}

.form-group input[type="email"]:focus,
.form-group input[type="password"]:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.remember-me {
  margin-bottom: 25px;
}

.remember-me label {
  display: flex;
  align-items: center;
  cursor: pointer;
  font-weight: 400;
  font-size: 14px;
}

.remember-me input[type="checkbox"] {
  margin-right: 8px;
  width: 18px;
  height: 18px;
  cursor: pointer;
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

@media (max-width: 480px) {
  .login-card {
    border-radius: 0;
  }

  .logo-section {
    padding: 30px 20px;
  }

  .logo-section h1 {
    font-size: 24px;
  }

  .login-form {
    padding: 30px 20px;
  }
}
</style>