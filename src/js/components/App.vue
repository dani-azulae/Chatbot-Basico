<template>
  <div class="app-container">
    <header class="app-header">
      <h1>Chat IA con N8N</h1>
      <nav v-if="isLoggedIn">
        <ul>
          <li><router-link to="/chat">Chat</router-link></li>
          <li><router-link to="/documentos">Documentación</router-link></li>
          <li><router-link to="/perfil">Mi Perfil</router-link></li>
          <li><a href="#" @click.prevent="logout">Cerrar sesión</a></li>
        </ul>
      </nav>
    </header>
    
    <main class="app-content">
      <router-view :user="user" @login-success="onLoginSuccess" @profile-updated="onProfileUpdated"></router-view>
    </main>
    
    <footer class="app-footer">
      <p>&copy; {{ new Date().getFullYear() }} Chat IA con N8N. Todos los derechos reservados.</p>
    </footer>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  setup() {
    const isLoggedIn = ref(false);
    const user = ref(null);
    const router = useRouter();
    const route = useRoute();
    
    onMounted(() => {
      // Check if user is already logged in
      checkAuthStatus();
    });    const checkAuthStatus = () => {
      const token = localStorage.getItem('user_token');
      
      if (!token) {
        isLoggedIn.value = false;
        user.value = null;
        return;
      }
      
      axios.get('/api/users/profile')
        .then(response => {
          if (response.data.success) {
            isLoggedIn.value = true;
            user.value = response.data.user;
            
            // Si estamos en la ruta principal y ya estamos autenticados, redirigir a la página de chat
            if (route.path === '/') {
              router.push('/chat');
            }
          }
        })
        .catch(() => {
          // Si hay un error, es posible que el token ya no sea válido
          isLoggedIn.value = false;
          user.value = null;
          localStorage.removeItem('user_token');
        });
    };
    
    const onLoginSuccess = (userData) => {
      isLoggedIn.value = true;
      user.value = userData;
      router.push('/chat');
    };
    
    const logout = () => {
      axios.post('/api/users/logout')
        .then(() => {
          isLoggedIn.value = false;
          user.value = null;
          localStorage.removeItem('user_token');
          router.push('/');
        })
        .catch(error => {
          console.error('Error al cerrar sesión:', error);
        });
    };
    
    const onProfileUpdated = (userData) => {
      user.value = { ...user.value, ...userData };
    };
      // Retornar variables y funciones que se usarán en el template
    return {
      isLoggedIn,
      user,
      onLoginSuccess,
      logout,
      onProfileUpdated
    };
  }
};
</script>

<style>
/* Estilos globales se incluyen en el archivo CSS principal */
</style>
