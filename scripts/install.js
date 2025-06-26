/**
 * Script de instalación multiplataforma
 */
const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// Función para ejecutar comandos y mostrar su salida
function runCommand(command, message) {
  console.log(`\n${message || command}...`);
  try {
    execSync(command, { stdio: 'inherit' });
    return true;
  } catch (error) {
    console.error(`Error al ejecutar: ${command}`);
    console.error(error.message);
    return false;
  }
}

// Banner
console.log('=== Chat IA con N8N - Instalación ===\n');

// Instalar dependencias de Composer
runCommand('composer install', 'Instalando dependencias de PHP');

// Instalar dependencias de Node.js
runCommand('npm install', 'Instalando dependencias de Node.js');

// Compilar assets
runCommand('npm run build', 'Compilando assets');

// Crear carpeta de uploads si no existe
const uploadsDir = path.join(__dirname, '..', 'uploads');
if (!fs.existsSync(uploadsDir)) {
  console.log('\nCreando carpeta de uploads...');
  fs.mkdirSync(uploadsDir);
  
  // En Linux/Mac, establecer permisos
  if (process.platform !== 'win32') {
    runCommand(`chmod 755 ${uploadsDir}`, 'Estableciendo permisos para uploads');
  }
}

// Mensaje final
console.log('\n=== Instalación completada ===\n');
console.log('Para configurar el proyecto:');
console.log('1. Edite el archivo .env con sus datos de conexión');
console.log('2. Importe la base de datos con el archivo src/config/setup.sql');
console.log('3. Configure su servidor web para que apunte a la carpeta public/');
console.log('4. Inicie el servidor WebSocket con: npm run start:websocket');
console.log('\nPara desarrollo:');
console.log('- npm run dev (para compilar assets en modo desarrollo y watch)');
console.log('- npm run serve (para iniciar un servidor de desarrollo)');
