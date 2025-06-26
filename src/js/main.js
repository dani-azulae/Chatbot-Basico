import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './components/App.vue';
import axios from 'axios';

// Importar vistas
import LoginRegister from './views/LoginRegister.vue';
import ChatView from './views/ChatView.vue';
import DocumentsView from './views/DocumentsView.vue';
import ProfileView from './views/ProfileView.vue';

// Configuración global para axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios = axios;

// Verificar si existe un token de autenticación almacenado
const token = localStorage.getItem('user_token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Definir rutas
const routes = [
  { path: '/', component: LoginRegister },
  { path: '/chat', component: ChatView, meta: { requiresAuth: true } },
  { path: '/documentos', component: DocumentsView, meta: { requiresAuth: true } },
  { path: '/perfil', component: ProfileView, meta: { requiresAuth: true } }
];

// Crear instancia del router
const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navegación guard para rutas protegidas
router.beforeEach((to, from, next) => {
  // Verificar si la ruta requiere autenticación
  if (to.matched.some(record => record.meta.requiresAuth)) {
    // Comprobar si el usuario está autenticado (verificar en localStorage o sessionStorage)
    const isAuthenticated = !!localStorage.getItem('user_token');
    
    if (!isAuthenticated) {
      // Si no está autenticado, redirigir a la página de login
      next('/');
    } else {
      // Si está autenticado, permitir la navegación
      next();
    }
  } else {
    // Si la ruta no requiere autenticación, permitir la navegación
    next();
  }
});

// Crear aplicación Vue 3
const app = createApp(App);

// Usar router
app.use(router);

// Configurar axios como propiedad global
app.config.globalProperties.$axios = axios;

// Montar la aplicación
app.mount('#app');
