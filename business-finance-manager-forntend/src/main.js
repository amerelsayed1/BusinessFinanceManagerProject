// src/main.js (Main Vue app entry)
import Vue from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';
import './services/api.js'; // Import axios configuration

Vue.config.productionTip = false;

new Vue({
    router,
    store,
    render: (h) => h(App),
}).$mount('#app');
