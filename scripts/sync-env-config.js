const fs = require('fs');
const path = require('path');
const dotenv = require('dotenv');

// Ruta al archivo .env
const envPath = path.resolve(__dirname, '..', '.env');
// Ruta al archivo config.js de destino
const configPath = path.resolve(__dirname, '..', 'src', 'js', 'config.js');

// Cargar variables de entorno
const env = dotenv.config({ path: envPath }).parsed || {};

// Crear objeto de configuración
const config = {
  websocket: {
    port: parseInt(env.WS_PORT) || 8080
  },
  api: {
    baseUrl: '/api'
  }
};

// Generar contenido del archivo config.js
const configContent = `// config.js - AUTOGENERADO, NO EDITAR MANUALMENTE
// Este archivo se genera automáticamente a partir de .env
// Ejecuta "npm run sync-config" para actualizarlo

export default ${JSON.stringify(config, null, 2)};
`;

// Escribir archivo config.js
fs.writeFileSync(configPath, configContent);

console.log('Configuración sincronizada con éxito desde .env');
