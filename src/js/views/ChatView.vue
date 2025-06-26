<template>
  <div class="chat-container">
    <div class="chat-sidebar">
      <h3>Chats</h3>
      <button class="btn btn-sm" @click="createNewSession">+ Nuevo Chat</button>
      
      <ul class="chat-list">
        <li 
          v-for="session in chatSessions" 
          :key="session.id"
          :class="{ active: currentSession && currentSession.id === session.id }"
          @click="selectSession(session)">
          <span>{{ session.title }}</span>
          <button class="btn-icon" @click.stop="deleteSession(session.id)">
            <i class="icon-trash">🗑️</i>
          </button>
        </li>
      </ul>
    </div>
    
    <div class="chat-main">
      <div v-if="!currentSession" class="chat-placeholder">
        <h3>Selecciona un chat o crea uno nuevo</h3>
        <button class="btn btn-primary" @click="createNewSession">Nuevo Chat</button>
      </div>
      
      <div v-else class="chat-content">
        <div class="chat-header">
          <h2>{{ currentSession.title }}</h2>
          <button class="btn-icon" @click="editingTitle = true" v-if="!editingTitle">
            <i class="icon-edit">✏️</i>
          </button>
          <div class="edit-title" v-if="editingTitle">
            <input 
              type="text" 
              v-model="newTitle" 
              @keyup.enter="updateSessionTitle"
              @blur="editingTitle = false">
            <button class="btn-sm" @click="updateSessionTitle">Guardar</button>
          </div>
        </div>
        
        <div class="chat-messages" ref="messagesContainer">
          <div 
            v-for="(message, index) in messages" 
            :key="message.clientId || message.id || index"
            :class="['message', message.is_bot ? 'bot' : 'user']">
            <div class="message-content">{{ message.message }}</div>
            <div class="message-time">{{ formatTimestamp(message.created_at) }}</div>
          </div>
          <div v-if="isTyping" class="message bot">
            <div class="message-content typing">
              <span class="dot"></span>
              <span class="dot"></span>
              <span class="dot"></span>
            </div>
          </div>
        </div>
        
        <div class="chat-input">
          <textarea 
            v-model="newMessage" 
            placeholder="Escribe un mensaje..." 
            @keyup.enter.prevent="sendMessage"
            :disabled="isTyping">
          </textarea>
          <button 
            class="btn btn-primary" 
            @click="sendMessage" 
            :disabled="isTyping || !newMessage.trim()">
            Enviar
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import config from '../config.js';

export default {
  props: {
    user: Object
  },
  setup(props) {
    const chatSessions = ref([]);
    const currentSession = ref(null);
    const messages = ref([]);
    const newMessage = ref('');
    const isTyping = ref(false);
    const websocket = ref(null);
    const editingTitle = ref(false);
    const newTitle = ref('');
    const messagesContainer = ref(null);
    const router = useRouter();
    
    onMounted(() => {
      loadSessions();
      initWebSocket();
    });
    
    onUnmounted(() => {
      // Close WebSocket connection
      if (websocket.value) {
        websocket.value.close();
      }
    });    
    
    const authenticateWebSocket = () => {
      if (!websocket.value || websocket.value.readyState !== WebSocket.OPEN) {
        console.log('No se puede autenticar WebSocket: conexión no abierta');
        return false;
      }
      
      if (!props.user) {
        console.log('No se puede autenticar WebSocket: faltan datos de usuario');
        return false;
      }
      
      if (!currentSession.value) {
        console.log('No se puede autenticar WebSocket: no hay sesión activa');
        return false;
      }
      
      console.log('Enviando autenticación al WebSocket...', {
        userId: props.user.id,
        sessionId: currentSession.value.id
      });
      
      websocket.value.send(JSON.stringify({
        type: 'auth',
        user_id: props.user.id,
        session_id: currentSession.value.id
      }));
      
      // La confirmación real se hará cuando recibamos auth_success
      return true;
    };
    
    // Variable para rastrear si estamos autenticados
    const isWebSocketAuthenticated = ref(false);
    
    const initWebSocket = () => {
      // Get WebSocket URL from configuration
      const wsHost = window.location.hostname;
      const wsPort = config.websocket.port; // Use the port from config
      
      // Reset authentication state
      isWebSocketAuthenticated.value = false;
      
      // Create WebSocket connection
      websocket.value = new WebSocket(`ws://${wsHost}:${wsPort}`);
      
      websocket.value.onopen = () => {
        console.log('WebSocket conectado');
        
        // Always try to authenticate when connection opens
        // Intentar autenticar inmediatamente si tenemos una sesión actual
        if (currentSession.value) {
          console.log('Intentando autenticar WebSocket al conectar con sesión existente');
          setTimeout(() => authenticateWebSocket(), 100); // Pequeña pausa para asegurar que el WebSocket esté listo
        } else {
          console.log('No hay sesión activa, no se puede autenticar WebSocket aún');
        }
      };
        websocket.value.onmessage = (event) => {
        let data;
        
        try {
          data = JSON.parse(event.data);
          console.log('WebSocket message received:', data);
        } catch (e) {
          console.error('Error al parsear mensaje WebSocket:', e, event.data);
          return;
        }
        
        switch (data.type) {
          case 'error':
            console.error('Error de WebSocket:', data.message);
            
            // Si el error es de autenticación, intentamos autenticar de nuevo
            if (data.message === 'Not authenticated') {
              console.log('Reintentando autenticación después de error...');
              // Intentamos varias veces con un backoff exponencial
              setTimeout(() => authenticateWebSocket(), 500);
              setTimeout(() => authenticateWebSocket(), 1500);
              setTimeout(() => authenticateWebSocket(), 3500);
            }
            break;
            
          case 'auth_success':
            console.log('Autenticación exitosa en WebSocket');
            isWebSocketAuthenticated.value = true;
            break;
          case 'user_message':
            // Check if this is our own message using clientId (más confiable)
            if (data.clientId) {
              const isDuplicate = messages.value.some(m => 
                m.clientId === data.clientId && m.fromLocal === true
              );
              
              if (isDuplicate) {
                console.log('Mensaje duplicado detectado por clientId:', data.clientId);
                break;
              }
            } else {
              // Fallback: comparación por contenido si no hay clientId
              const possibleDuplicate = messages.value.some(m => 
                !m.is_bot && 
                m.message === data.message && 
                m.user_id === data.user_id &&
                m.fromLocal === true
              );
              
              if (possibleDuplicate) {
                console.log('Posible mensaje duplicado detectado por contenido');
                break;
              }
            }
            
            // Si no es duplicado, añadirlo
            messages.value.push({
              id: data.id || null,
              user_id: data.user_id,
              message: data.message,
              is_bot: 0, 
              clientId: data.clientId || null,
              created_at: data.timestamp
            });
            scrollToBottom();
            break;
            
          case 'ai_message':
            isTyping.value = false;
            messages.value.push({
              message: data.message,
              is_bot: 1,
              created_at: data.timestamp
            });
            scrollToBottom();
            break;
            
          case 'stream_chunk':
            if (!isTyping.value) break;
            
            // If it's the first chunk, add a new message
            if (messages.value.length === 0 || 
              !messages.value[messages.value.length - 1].streaming) {
              messages.value.push({
                message: data.chunk,
                is_bot: 1,
                streaming: true,
                created_at: new Date().toISOString()
              });
            } else {
              // Otherwise append to the last message
              messages.value[messages.value.length - 1].message += data.chunk;
            }
            
            if (data.is_final) {
              messages.value[messages.value.length - 1].streaming = false;
              isTyping.value = false;
            }
            
            scrollToBottom();
            break;
        }
      };
      
      websocket.value.onclose = () => {
        console.log('WebSocket desconectado');
        // Try to reconnect after a delay
        setTimeout(() => {
          initWebSocket();
        }, 5000);
      };
      
      websocket.value.onerror = (error) => {
        console.error('Error de WebSocket:', error);
      };
    };
    
    const loadSessions = () => {
      axios.get('/api/chats')
        .then(response => {
          if (response.data.success) {
            chatSessions.value = response.data.sessions;
            
            // If there are sessions, select the most recent one
            if (chatSessions.value.length > 0) {
              selectSession(chatSessions.value[0]);
            }
          }
        })
        .catch(error => {
          console.error('Error al cargar las sesiones:', error);
        });
    };
    
    const selectSession = (session) => {
      currentSession.value = session;
      newTitle.value = session.title;
      messages.value = [];
      
      // Load messages for this session
      axios.get(`/api/chats/${session.id}/messages`)
        .then(response => {
          if (response.data.success) {
            messages.value = response.data.messages;
            scrollToBottom();
          }
        })
        .catch(error => {
          console.error('Error al cargar los mensajes:', error);
        });
        
      // Update WebSocket session
      if (websocket.value && websocket.value.readyState === WebSocket.OPEN) {
        // Reset authentication state when switching sessions
        isWebSocketAuthenticated.value = false;
        
        console.log('Cambiando de sesión en WebSocket:', {
          sessionId: session.id,
          sessionTitle: session.title
        });
        
        // First re-authenticate with the new session
        const authSuccess = authenticateWebSocket();
        
        if (authSuccess) {
          // Then send the session switch command
          websocket.value.send(JSON.stringify({
            type: 'switch_session',
            session_id: session.id
          }));
        } else {
          console.warn('No se pudo autenticar al cambiar de sesión. Intentando reconectar WebSocket...');
          // Intentar reconectar el WebSocket si la autenticación falló
          if (websocket.value) {
            websocket.value.close();
          }
          setTimeout(() => initWebSocket(), 500);
        }
      } else {
        console.warn('WebSocket no disponible al cambiar de sesión. Intentando reconectar...');
        // Si el WebSocket no está disponible, intentar reconectarlo
        initWebSocket();
      }
    };
    
    const createNewSession = () => {
      axios.post('/api/chats', { title: 'New Chat' })
        .then(response => {
          if (response.data.success) {
            const newSession = {
              id: response.data.session_id,
              title: response.data.title,
              user_id: props.user.id,
              created_at: new Date().toISOString(),
              updated_at: new Date().toISOString()
            };
            
            chatSessions.value.unshift(newSession);
            selectSession(newSession);
          }
        })
        .catch(error => {
          console.error('Error al crear una nueva sesión:', error);
        });
    };
    
    const updateSessionTitle = () => {
      if (!newTitle.value.trim() || newTitle.value === currentSession.value.title) {
        editingTitle.value = false;
        return;
      }
      
      axios.put(`/api/chats/${currentSession.value.id}`, { title: newTitle.value })
        .then(response => {
          if (response.data.success) {
            // Update session title locally
            currentSession.value.title = newTitle.value;
            
            // Update in sessions list
            const index = chatSessions.value.findIndex(s => s.id === currentSession.value.id);
            if (index !== -1) {
              chatSessions.value[index].title = newTitle.value;
            }
          }
        })
        .catch(error => {
          console.error('Error al actualizar el título:', error);
        })
        .finally(() => {
          editingTitle.value = false;
        });
    };
    
    const deleteSession = (sessionId) => {
      if (!confirm('¿Estás seguro de que deseas eliminar este chat?')) {
        return;
      }
      
      axios.delete(`/api/chats/${sessionId}`)
        .then(response => {
          if (response.data.success) {
            // Remove session from list
            chatSessions.value = chatSessions.value.filter(s => s.id !== sessionId);
            
            // If the current session was deleted, clear it
            if (currentSession.value && currentSession.value.id === sessionId) {
              currentSession.value = null;
              messages.value = [];
              
              // Reset authentication state since our session is gone
              isWebSocketAuthenticated.value = false;
            }
            
            // If there are other sessions, select the first one
            if (chatSessions.value.length > 0 && !currentSession.value) {
              selectSession(chatSessions.value[0]);
              // selectSession will call authenticateWebSocket
            }
          }
        })
        .catch(error => {
          console.error('Error al eliminar la sesión:', error);
        });
    };
      const sendMessage = () => {
      if (!newMessage.value.trim() || isTyping.value) return;
      
      // Create a new session if none is selected
      if (!currentSession.value) {
        createNewSession();
        // We need to wait for session creation
        setTimeout(() => sendMessage(), 500);
        return;
      }
      
      const message = newMessage.value.trim();
      newMessage.value = '';
      
      // Generate a client-side ID for this message
      const clientId = `local_${Date.now()}`;
      
      // Add message to UI immediately
      messages.value.push({
        user_id: props.user.id,
        message: message,
        is_bot: 0,
        created_at: new Date().toISOString(),
        clientId: clientId, // Marca para identificar mensajes enviados localmente
        fromLocal: true     // Flag para marcar que este mensaje se originó localmente
      });
        // Indicate typing
      isTyping.value = true;
      scrollToBottom();
      
      console.log('Current messages array:', messages.value);
        // Send via WebSocket
      if (websocket.value && websocket.value.readyState === WebSocket.OPEN) {
        // Intentar autenticarnos primero si es necesario y luego enviar el mensaje
        const isAuthenticated = authenticateWebSocket();
        
        console.log('Enviando mensaje mediante WebSocket:', {
          sessionId: currentSession.value.id,
          messagePreview: message.substring(0, 30) + (message.length > 30 ? '...' : ''),
          clientId: clientId,
          isAuthenticated: isAuthenticated
        });
        
        // Siempre enviar el mensaje (el servidor debe manejar el rechazo si no estamos autenticados)
        websocket.value.send(JSON.stringify({
          type: 'message',
          user_id: props.user.id,
          session_id: currentSession.value.id,
          message: message,
          clientId: clientId // Enviar el ID del cliente para mejor identificación
        }));
      } else {
        // WebSocket is not connected, try to reconnect
        initWebSocket();
        // And use a fallback HTTP request
        axios.post('/api/messages', {
          session_id: currentSession.value.id,
          message: message
        })
        .then(response => {
          if (response.data.success) {
            isTyping.value = false;
            messages.value.push({
              message: response.data.ai_response,
              is_bot: 1,
              created_at: new Date().toISOString()
            });
            scrollToBottom();
          }
        })
        .catch(error => {
          console.error('Error al enviar mensaje:', error);
          isTyping.value = false;
        });
      }
    };
    
    const scrollToBottom = () => {
      nextTick(() => {
        if (messagesContainer.value) {
          messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
      });
    };
    
    const formatTimestamp = (timestamp) => {
      const date = new Date(timestamp);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    return {
      chatSessions,
      currentSession,
      messages,
      newMessage,
      isTyping,
      editingTitle,
      newTitle,
      messagesContainer,
      isWebSocketAuthenticated,
      loadSessions,
      selectSession,
      createNewSession,
      updateSessionTitle,
      deleteSession,
      sendMessage,
      scrollToBottom,
      formatTimestamp
    };
  }
};
</script>

<style scoped>
/* Los estilos específicos de este componente se pueden añadir aquí si es necesario */
</style>
