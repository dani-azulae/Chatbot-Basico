<template>
  <div class="documents-container">
    <h2>Documentación</h2>
    <p class="documents-info">
      Sube documentos para mejorar el conocimiento del agente IA.
      Formatos soportados: .txt, .pdf
    </p>
    
    <div class="documents-actions">
      <div class="search-container">
        <input 
          type="text" 
          v-model="searchQuery" 
          placeholder="Buscar documentos..." 
          @keyup.enter="searchDocuments"
        >
        <button class="btn" @click="searchDocuments">Buscar</button>
      </div>
      
      <div class="upload-container">
        <input 
          type="file" 
          ref="fileInput" 
          accept=".txt,.pdf"
          style="display: none"
          @change="onFileSelected"
        >
        <button class="btn btn-primary" @click="fileInput.click()">
          Subir Documento
        </button>
      </div>
    </div>
    
    <div v-if="isUploading" class="upload-form">
      <h3>Subir Documento</h3>
      <div class="form-group">
        <label for="document-title">Título</label>
        <input 
          type="text" 
          id="document-title" 
          v-model="uploadForm.title" 
          placeholder="Título del documento"
        >
      </div>
      <div class="form-group">
        <label>Archivo</label>
        <div class="file-info">{{ uploadForm.file.name }}</div>
      </div>
      <div class="form-actions">
        <button class="btn" @click="cancelUpload">Cancelar</button>
        <button class="btn btn-primary" @click="uploadDocument" :disabled="isLoading">
          {{ isLoading ? 'Subiendo...' : 'Subir' }}
        </button>
      </div>
    </div>
    
    <div v-if="error" class="error-message">{{ error }}</div>
    
    <div class="documents-list">
      <div v-if="documents.length === 0 && !isLoading" class="no-documents">
        No se encontraron documentos.
      </div>
      
      <table v-else class="documents-table">
        <thead>
          <tr>
            <th>Título</th>
            <th>Fecha</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="document in documents" :key="document.id">
            <td>{{ document.title }}</td>
            <td>{{ formatDate(document.created_at) }}</td>
            <td class="actions">
              <button class="btn-icon" @click="viewDocument(document)">
                <i class="icon-view">👁️</i>
              </button>
              <button class="btn-icon" @click="deleteDocument(document.id)">
                <i class="icon-trash">🗑️</i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <div v-if="viewingDocument" class="document-modal">
      <div class="document-modal-content">
        <div class="document-modal-header">
          <h3>{{ viewingDocument.title }}</h3>
          <button class="btn-close" @click="viewingDocument = null">&times;</button>
        </div>
        <div class="document-modal-body">
          <div v-if="viewingDocument.content" class="document-content">
            {{ viewingDocument.content }}
          </div>
          <div v-else class="document-content">
            <a :href="viewingDocument.file_path" target="_blank">Abrir documento</a>
          </div>
        </div>
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
  setup() {
    const documents = ref([]);
    const searchQuery = ref('');
    const isLoading = ref(false);
    const error = ref(null);
    const isUploading = ref(false);
    const uploadForm = reactive({
      title: '',
      file: null
    });
    const viewingDocument = ref(null);
    const fileInput = ref(null);
    
    onMounted(() => {
      loadDocuments();
    });

    const loadDocuments = () => {
      isLoading.value = true;
      
      axios.get('/api/documents')
        .then(response => {
          if (response.data.success) {
            documents.value = response.data.documents;
          }
        })
        .catch(error => {
          error.value = error.response?.data?.error || 'Error al cargar los documentos';
        })
        .finally(() => {
          isLoading.value = false;
        });
    };

    const searchDocuments = () => {
      if (!searchQuery.value.trim()) {
        loadDocuments();
        return;
      }
      
      isLoading.value = true;
      
      axios.get(`/api/documents/search?q=${encodeURIComponent(searchQuery.value)}`)
        .then(response => {
          if (response.data.success) {
            documents.value = response.data.documents;
          }
        })
        .catch(err => {
          error.value = err.response?.data?.error || 'Error al buscar documentos';
        })
        .finally(() => {
          isLoading.value = false;
        });
    };

    const onFileSelected = (event) => {
      uploadForm.file = event.target.files[0];
      
      if (uploadForm.file) {
        // Default title to filename without extension
        const filename = uploadForm.file.name;
        uploadForm.title = filename.substring(0, filename.lastIndexOf('.')) || filename;
        
        isUploading.value = true;
      }
    };

    const cancelUpload = () => {
      isUploading.value = false;
      uploadForm.title = '';
      uploadForm.file = null;
      if (fileInput.value) {
        fileInput.value.value = '';
      }
    };

    const uploadDocument = () => {
      if (!uploadForm.title.trim() || !uploadForm.file) {
        error.value = 'Por favor complete todos los campos';
        return;
      }
      
      isLoading.value = true;
      error.value = null;
      
      const formData = new FormData();
      formData.append('title', uploadForm.title);
      formData.append('document', uploadForm.file);
      
      axios.post('/api/documents', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      .then(response => {
        if (response.data.success) {
          // Add new document to the list
          loadDocuments();
          cancelUpload();
        }
      })
      .catch(err => {
        error.value = err.response?.data?.error || 'Error al subir el documento';
      })
      .finally(() => {
        isLoading.value = false;
      });
    };

    const viewDocument = (document) => {
      // First check if we already have the content
      if (document.content) {
        viewingDocument.value = document;
        return;
      }
      
      // Otherwise, fetch the document
      axios.get(`/api/documents/${document.id}`)
        .then(response => {
          if (response.data.success) {
            viewingDocument.value = response.data.document;
          }
        })
        .catch(err => {
          error.value = err.response?.data?.error || 'Error al cargar el documento';
        });
    };

    const deleteDocument = (id) => {
      if (!confirm('¿Estás seguro de que deseas eliminar este documento?')) {
        return;
      }
      
      axios.delete(`/api/documents/${id}`)
        .then(response => {
          if (response.data.success) {
            // Remove document from list
            documents.value = documents.value.filter(d => d.id !== id);
            
            // If viewing this document, close the viewer
            if (viewingDocument.value && viewingDocument.value.id === id) {
              viewingDocument.value = null;
            }
          }
        })
        .catch(err => {
          error.value = err.response?.data?.error || 'Error al eliminar el documento';
        });
    };

    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    };
    
    return {
      documents,
      searchQuery,
      isLoading,
      error,
      isUploading,
      uploadForm,
      viewingDocument,
      fileInput,
      loadDocuments,
      searchDocuments,
      onFileSelected,
      cancelUpload,
      uploadDocument,
      viewDocument,
      deleteDocument,
      formatDate
    };
  }
};
</script>

<style scoped>
/* Los estilos específicos de este componente se pueden añadir aquí si es necesario */
</style>
