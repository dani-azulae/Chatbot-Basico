# Script de instalación

Write-Host "=== Chat IA con N8N - Instalación ==="
Write-Host ""

Write-Host "Instalando dependencias de Composer..."
composer install

Write-Host "Instalando dependencias de Node.js..."
npm install

Write-Host "Compilando assets..."
npm run build

# Crear carpeta de uploads si no existe
if (-not (Test-Path "$PSScriptRoot\uploads")) {
    Write-Host "Creando carpeta de uploads..."
    New-Item -ItemType Directory -Path "$PSScriptRoot\uploads"
}

Write-Host ""
Write-Host "=== Instalación completada ==="
Write-Host ""
Write-Host "Para configurar el proyecto:"
Write-Host "1. Editar el archivo .env con tus datos de conexión"
Write-Host "2. Importar la base de datos con el archivo src/config/setup.sql"
Write-Host "3. Configurar tu servidor web para que apunte a la carpeta public/"
Write-Host "4. Iniciar el servidor WebSocket con: .\start-websocket.ps1"
Write-Host ""
Write-Host "Para desarrollo:"
Write-Host "- npm run dev (para compilar assets en modo desarrollo y watch)"
Write-Host "- npm run serve (para iniciar un servidor de desarrollo)"
