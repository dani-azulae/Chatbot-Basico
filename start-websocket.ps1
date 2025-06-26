# Script para iniciar el servidor WebSocket

# Cargar las variables de entorno
$envFile = "$PSScriptRoot\.env"
if (Test-Path $envFile) {
    Get-Content $envFile | ForEach-Object {
        if ($_ -match "^\s*([^#][^=]+)=(.*)$") {
            $key = $matches[1].Trim()
            $value = $matches[2].Trim()
            [Environment]::SetEnvironmentVariable($key, $value)
        }
    }
}

# Iniciar el servidor WebSocket
Write-Host "Iniciando servidor WebSocket..."
php "$PSScriptRoot\src\websocket\server.php"
