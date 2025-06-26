#!/bin/bash

# Script para iniciar el servidor WebSocket

# Obtener la ruta del directorio del script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

echo "Iniciando servidor WebSocket..."
php "$SCRIPT_DIR/src/websocket/server.php"
