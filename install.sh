#!/bin/bash

# Script de instalación

# Obtener la ruta del directorio del script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

echo "=== Chat IA con N8N - Instalación ==="
echo ""

# Instalar dependencias de Composer
echo "Instalando dependencias de PHP..."
composer install

# Instalar dependencias de Node.js
echo "Instalando dependencias de Node.js..."
npm install

# Compilar assets
echo "Compilando assets..."
npm run build

# Crear carpeta de uploads si no existe
if [ ! -d "$SCRIPT_DIR/uploads" ]; then
    echo "Creando carpeta de uploads..."
    mkdir -p "$SCRIPT_DIR/uploads"
    chmod 755 "$SCRIPT_DIR/uploads"
fi

echo ""
echo "=== Instalación completada ==="
echo ""
echo "Para configurar el proyecto:"
echo "1. Edite el archivo .env con sus datos de conexión"
echo "2. Importe la base de datos con el archivo src/config/setup.sql"
echo "3. Configure su servidor web para que apunte a la carpeta public/"
echo "4. Inicie el servidor WebSocket con: bash start-websocket.sh"
echo ""
echo "Para desarrollo:"
echo "- npm run dev (para compilar assets en modo desarrollo y watch)"
echo "- npm run serve (para iniciar un servidor de desarrollo)"
