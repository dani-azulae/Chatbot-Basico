/**
 * Script para iniciar el servidor WebSocket de manera multiplataforma
 */
const fs = require('fs');
const path = require('path');
const { execSync, spawn } = require('child_process');
const dotenv = require('dotenv');

// Cargar variables de entorno
const envPath = path.join(__dirname, '..', '.env');
if (fs.existsSync(envPath)) {
  console.log('Cargando variables de entorno desde .env...');
  dotenv.config({ path: envPath });
}

// Ruta al archivo del servidor WebSocket
const serverPath = path.join(__dirname, '..', 'src', 'websocket', 'server.php');

console.log('Iniciando servidor WebSocket...');

// Ejecutar el servidor PHP
const phpProcess = spawn('php', [serverPath], {
  stdio: 'inherit',
  env: process.env
});

// Manejar eventos del proceso
phpProcess.on('error', (err) => {
  console.error('Error al iniciar el servidor WebSocket:', err);
});

// Manejar cierre del proceso
phpProcess.on('close', (code) => {
  if (code !== 0) {
    console.log(`El servidor WebSocket se cerró con código: ${code}`);
  }
});

// Mostrar mensaje de ejecución
console.log(`\nServidor WebSocket en ejecución...`);
console.log(`Presione Ctrl+C para detener`);

// Manejar señal de interrupción (Ctrl+C)
process.on('SIGINT', () => {
  console.log('\nDeteniendo servidor WebSocket...');
  phpProcess.kill();
  process.exit();
});
