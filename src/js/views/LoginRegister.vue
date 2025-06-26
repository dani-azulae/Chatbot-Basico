<template>
  <div class="auth-container">
    <div class="auth-tabs">
      <button 
        :class="['tab-button', { active: activeTab === 'login' }]" 
        @click="activeTab = 'login'">
        Iniciar sesión
      </button>
      <button 
        :class="['tab-button', { active: activeTab === 'register' }]" 
        @click="activeTab = 'register'">
        Registrarse
      </button>
    </div>
    
    <div v-if="activeTab === 'login'" class="auth-form">
      <h2>Iniciar sesión</h2>
      <div class="form-group">
        <label for="login-username">Usuario o Email</label>
        <input 
          type="text" 
          id="login-username" 
          v-model="loginForm.username" 
          placeholder="Usuario o Email">
      </div>
      <div class="form-group">
        <label for="login-password">Contraseña</label>
        <input 
          type="password" 
          id="login-password" 
          v-model="loginForm.password" 
          placeholder="Contraseña">
      </div>
      <button class="btn btn-primary" @click="login" :disabled="isLoading">
        {{ isLoading ? 'Cargando...' : 'Iniciar sesión' }}
      </button>
      <p v-if="error" class="error-message">{{ error }}</p>
    </div>
    
    <div v-if="activeTab === 'register'" class="auth-form">
      <h2>Registrarse</h2>
      <div class="form-group">
        <label for="register-username">Nombre de usuario</label>
        <input 
          type="text" 
          id="register-username" 
          v-model="registerForm.username" 
          placeholder="Nombre de usuario">
      </div>
      <div class="form-group">
        <label for="register-email">Email</label>
        <input 
          type="email" 
          id="register-email" 
          v-model="registerForm.email" 
          placeholder="Email">
      </div>
      <div class="form-group">
        <label for="register-password">Contraseña</label>
        <input 
          type="password" 
          id="register-password" 
          v-model="registerForm.password" 
          placeholder="Contraseña">
      </div>
      <div class="form-group">
        <label for="register-confirm-password">Confirmar contraseña</label>
        <input 
          type="password" 
          id="register-confirm-password" 
          v-model="registerForm.confirmPassword" 
          placeholder="Confirmar contraseña">
      </div>
      <button class="btn btn-primary" @click="register" :disabled="isLoading">
        {{ isLoading ? 'Cargando...' : 'Registrarse' }}
      </button>
      <p v-if="error" class="error-message">{{ error }}</p>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue';
import axios from 'axios';

export default {
  emits: ['login-success'],
  setup(props, { emit }) {
    const activeTab = ref('login');
    const isLoading = ref(false);
    const error = ref(null);
    
    const loginForm = reactive({
      username: '',
      password: ''
    });
    
    const registerForm = reactive({
      username: '',
      email: '',
      password: '',
      confirmPassword: ''
    });
    
    const login = () => {
      if (!loginForm.username || !loginForm.password) {
        error.value = 'Por favor complete todos los campos';
        return;
      }
      
      isLoading.value = true;
      error.value = null;
      
      axios.post('/api/users/login', {
        username: loginForm.username,
        password: loginForm.password
      })
      .then(response => {
        if (response.data.success) {
          // Guardar token en localStorage
          localStorage.setItem('user_token', response.data.token);
          
          // Configurar el token para las siguientes solicitudes
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
          
          emit('login-success', response.data.user);
        }
      })
      .catch(error => {
        error.value = error.response?.data?.error || 'Error al iniciar sesión';
      })
      .finally(() => {
        isLoading.value = false;
      });
    };
    
    const register = () => {
      if (!registerForm.username || !registerForm.email || 
          !registerForm.password || !registerForm.confirmPassword) {
        error.value = 'Por favor complete todos los campos';
        return;
      }
      
      if (registerForm.password !== registerForm.confirmPassword) {
        error.value = 'Las contraseñas no coinciden';
        return;
      }
      
      isLoading.value = true;
      error.value = null;
      
      axios.post('/api/users/register', {
        username: registerForm.username,
        email: registerForm.email,
        password: registerForm.password
      })
      .then(response => {
        if (response.data.success) {
          // Guardar token en localStorage
          localStorage.setItem('user_token', response.data.token);
          
          // Configurar el token para las siguientes solicitudes
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
          
          emit('login-success', {
            id: response.data.user_id,
            username: response.data.username
          });
        }
      })
      .catch(err => {
        error.value = err.response?.data?.error || 'Error al registrarse';
      })
      .finally(() => {
        isLoading.value = false;
      });
    };
    
    return {
      activeTab,
      loginForm,
      registerForm,
      isLoading,
      error,
      login,
      register
    };
  }
};
</script>

<style scoped>
/* Los estilos específicos de este componente se pueden añadir aquí si es necesario */
</style>
