# Chat IA con N8N

Este proyecto implementa un sistema de chat inteligente que se conecta con una instancia local de N8N para procesar los mensajes y generar respuestas. La aplicación permite a los usuarios tener conversaciones con un asistente IA, subir documentación para mejorar el conocimiento del agente y gestionar sus perfiles.

## Características

- Chat en tiempo real con WebSockets
- Integración con N8N
- Gestión de usuarios (registro, inicio de sesión, perfil)
- Subida y gestión de documentación para mejorar el conocimiento del agente
- Historial de chats
- Interfaz intuitiva y responsive con Vue 3
- Estructura moderna con Composition API

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Node.js 16 o superior
- NPM 7 o superior
- Composer
- Instancia local de N8N

## Instalación

### Instalación multiplataforma

1. Clone el repositorio:
   ```
   git clone <url-del-repositorio>
   cd proyecto
   ```

2. Ejecute el script de instalación multiplataforma con Node.js (recomendado):
   ```
   npm run install:project
   ```

   > ⚠️ **Nota**: Los scripts multiplataforma escritos en Node.js funcionan tanto en Windows como en Linux/Mac.

3. Configure el archivo `.env` con sus credenciales:
   ```
   # Database configuration
   DB_HOST=localhost
   DB_NAME=chat_ia
   DB_USER=root
   DB_PASS=password
   
   # N8N configuration
   N8N_ENDPOINT=http://localhost:5678/webhook/
   
   # WebSocket configuration
   WS_PORT=8080
   ```

4. Importe la base de datos:
   ```
   mysql -u root -p < src/config/setup.sql
   ```

5. Configure su servidor web (Apache, Nginx) para que apunte a la carpeta `public/`.

6. Inicie el servidor WebSocket usando el script multiplataforma:
   ```
   npm run start:websocket
   ```

## Desarrollo

- Para compilar los assets en modo desarrollo con watch:
  ```
  npm run dev
  ```

- Para iniciar un servidor de desarrollo:
  ```
  npm run serve
  ```

## Estructura del proyecto

```
proyecto/
├── public/             # Directorio público accesible por la web
│   ├── assets/         # Recursos compilados (CSS, JS)
│   │   ├── css/        # Estilos CSS
│   │   └── js/         # JavaScript compilado por Webpack
│   └── index.php       # Punto de entrada de la aplicación
├── scripts/            # Scripts multiplataforma para instalación y ejecución
│   ├── install.js      # Script de instalación multiplataforma
│   └── start-websocket.js # Script para iniciar WebSocket
├── src/
│   ├── api.php         # Manejador de API REST
│   ├── config/         # Configuración de la aplicación
│   ├── controllers/    # Controladores para las APIs
│   ├── js/             # Código fuente JavaScript/Vue.js
│   │   ├── components/ # Componentes Vue reutilizables
│   │   ├── views/      # Vistas principales Vue
│   │   └── main.js     # Punto de entrada JS con Vue Router
│   ├── models/         # Modelos de datos
│   └── websocket/      # Servidor WebSocket
├── .env                # Variables de entorno
├── babel.config.js     # Configuración de Babel
├── composer.json       # Dependencias PHP
├── package.json        # Dependencias JavaScript y scripts npm
└── webpack.config.js   # Configuración de Webpack
```

## Nota sobre la arquitectura frontend

Este proyecto utiliza un enfoque moderno de Vue 3 y la Composition API:

1. Los archivos `.vue` en `src/js/components` y `src/js/views` contienen los componentes y vistas de la aplicación.
2. Se usa la Composition API (`setup()`, `ref()`, `reactive()`, etc.) para una mejor organización del código y reutilización de lógica.
3. El archivo `main.js` inicializa Vue 3 y Vue Router 4 para gestionar la navegación.
4. Webpack compila todos estos archivos en un único bundle (`app.js`) que se sirve desde `public/assets/js/`.
5. La navegación entre vistas se realiza mediante Vue Router sin recargar la página.

## Conexión con N8N

La aplicación se conecta a una instancia local de N8N mediante una API webhook:
1. Instala y configura N8N en tu entorno
2. Crea un webhook en N8N para recibir los mensajes del chat
3. Configura el workflow para procesar los mensajes y devolver respuestas
4. Actualiza la URL del webhook en el archivo .env

## Autenticación

El sistema utiliza autenticación basada en tokens JWT. Los tokens se almacenan en localStorage para persistencia entre sesiones.

## Base de Datos

La base de datos MySQL incluye las siguientes tablas:

- `users`: Almacena los usuarios del sistema
- `documents`: Almacena los documentos subidos para mejorar el conocimiento del agente
- `chat_sessions`: Almacena las sesiones de chat
- `chat_messages`: Almacena los mensajes de cada sesión

## WebSockets

La implementación de WebSockets utiliza la biblioteca Ratchet para PHP y proporciona comunicación en tiempo real para las conversaciones del chat.

## Tecnologías utilizadas

- **Frontend**: Vue 3, Vue Router 4, Composition API, Axios, WebSockets
- **Backend**: PHP, MySQL, Ratchet (WebSockets)
- **Herramientas de desarrollo**: Webpack 5, Babel, Node.js
- **Integración**: N8N para procesamiento de mensajes

## Contribuir

Para contribuir al proyecto:

1. Haz un fork del repositorio
2. Crea una rama para tu nueva característica
3. Envía un pull request

## Licencia

Este proyecto está bajo la licencia MIT.
