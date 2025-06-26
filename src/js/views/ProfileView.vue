<template>
  <div class="profile-container">
    <h2>Mi Perfil</h2>
    
    <div class="profile-info">
      <div class="form-group">
        <label for="username">Nombre de usuario</label>
        <input 
          type="text" 
          id="username" 
          v-model="profile.username" 
          disabled
        >
      </div>
      
      <div class="form-group">
        <label for="email">Email</label>
        <input 
          type="email" 
          id="email" 
          v-model="profile.email"
        >
      </div>
      
      <div class="form-group">
        <label for="new-password">Nueva contraseña</label>
        <input 
          type="password" 
          id="new-password" 
          v-model="profile.newPassword" 
          placeholder="Dejar en blanco para mantener la actual"
        >
      </div>
      
      <div class="form-group">
        <label for="confirm-password">Confirmar contraseña</label>
        <input 
          type="password" 
          id="confirm-password" 
          v-model="profile.confirmPassword"
          placeholder="Confirmar nueva contraseña"
        >
      </div>
      
      <div v-if="error" class="error-message">{{ error }}</div>
      <div v-if="success" class="success-message">{{ success }}</div>
      
      <div class="form-actions">
        <button class="btn btn-primary" @click="updateProfile" :disabled="isLoading">
          {{ isLoading ? 'Guardando...' : 'Guardar cambios' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

export default {
  props: {
    user: Object
  },
  emits: ['profile-updated'],
  setup(props, { emit }) {
    const profile = reactive({
      username: '',
      email: '',
      newPassword: '',
      confirmPassword: ''
    });
    
    const isLoading = ref(false);
    const error = ref(null);
    const success = ref(null);
    
    onMounted(() => {
      // Initialize with user data
      if (props.user) {
        profile.username = props.user.username;
        profile.email = props.user.email || '';
      }
    });
    
    const updateProfile = () => {
      // Validate new password
      if (profile.newPassword && profile.newPassword !== profile.confirmPassword) {
        error.value = 'Las contraseñas no coinciden';
        return;
      }
      
      isLoading.value = true;
      error.value = null;
      success.value = null;
      
      const updateData = {
        email: profile.email
      };
      
      if (profile.newPassword) {
        updateData.password = profile.newPassword;
      }
      
      axios.put('/api/users/profile', updateData)
        .then(response => {
          if (response.data.success) {
            success.value = 'Perfil actualizado correctamente';
            profile.newPassword = '';
            profile.confirmPassword = '';
            
            // Emit event to update user data in parent
            emit('profile-updated', {
              email: profile.email
            });
          }
        })
        .catch(err => {
          error.value = err.response?.data?.error || 'Error al actualizar el perfil';
        })
        .finally(() => {
          isLoading.value = false;
        });
    };
    
    return {
      profile,
      isLoading,
      error,
      success,
      updateProfile
    };
  }
};
</script>

<style scoped>
/* Los estilos específicos de este componente se pueden añadir aquí si es necesario */
</style>
