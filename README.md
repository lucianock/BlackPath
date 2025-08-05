# BlackPath - Web Security Scanner

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-red?style=for-the-badge&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.1+-blue?style=for-the-badge&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/Docker-Enabled-green?style=for-the-badge&logo=docker" alt="Docker">
</p>

## 🎯 Descripción

BlackPath es una herramienta de escaneo de seguridad web desarrollada en Laravel que permite realizar análisis completos de sitios web utilizando herramientas de reconocimiento como **WhatWeb**, **Nmap** y **Gobuster**. La aplicación proporciona una interfaz web intuitiva para ejecutar escaneos y visualizar los resultados en tiempo real.

## ✨ Características

- **🔍 Escaneo de Tecnologías**: Análisis de tecnologías web con WhatWeb
- **🌐 Escaneo de Puertos**: Detección de puertos abiertos con Nmap
- **📁 Escaneo de Directorios**: Búsqueda de directorios y archivos con Gobuster
- **⚡ Procesamiento en Tiempo Real**: Actualizaciones en vivo del progreso del escaneo
- **📊 Interfaz Web Moderna**: UI intuitiva con barra de progreso y estados
- **🐳 Contenedores Docker**: Entorno aislado y reproducible
- **💾 Almacenamiento Temporal**: Resultados guardados en cache (1 hora de expiración)
- **📄 Exportación PDF**: Generación de reportes en formato PDF

## 🛠️ Herramientas Integradas

- **WhatWeb**: Análisis de tecnologías web y frameworks
- **Nmap**: Escaneo de puertos y servicios
- **Gobuster**: Búsqueda de directorios y archivos
- **Wordlists**: Múltiples listas de palabras (common, medium, full)

## 🚀 Instalación

### Prerrequisitos

- Docker y Docker Compose
- PHP 8.1 o superior
- Composer

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/lucianock/BlackPath
cd BlackPath
```

2. **Instalar dependencias de PHP**
```bash
composer install
```

3. **Configurar variables de entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Construir y ejecutar contenedores Docker**
```bash
docker-compose build --no-cache
docker-compose up -d
```

5. **Ejecutar migraciones (OBLIGATORIO)**
```bash
php artisan migrate
```

6. **Configurar archivos necesarios**
```bash
php artisan blackpath:setup-files
```

7. **Iniciar el worker de jobs (IMPORTANTE)**
```bash
php artisan queue:work --daemon
```

8. **Iniciar BlackPath!**
```bash
php artisan serve
```

### ⚠️ Pasos Críticos

**IMPORTANTE**: Si los escaneos no funcionan, verifica que hayas ejecutado:

1. **Migraciones** (para la tabla de jobs):
```bash
php artisan migrate
```

2. **Worker de jobs** (para procesar escaneos):
```bash
php artisan queue:work --daemon
```

3. **Verificar contenedor Docker**:
```bash
docker ps
docker exec url-scanner-tools whatweb --version
```

### ✅ Verificación Rápida

Para confirmar que todo está funcionando correctamente:

```bash
# 1. Verificar que Docker esté ejecutándose
docker ps

# 2. Verificar que las herramientas estén instaladas
docker exec url-scanner-tools whatweb --version
docker exec url-scanner-tools nmap --version
docker exec url-scanner-tools gobuster version

# 3. Verificar que los archivos necesarios existan
php artisan blackpath:setup-files

# 4. Verificar que el worker esté funcionando
php artisan queue:work --once --verbose

# 5. Verificar el contenedor Docker completamente
php check-docker.php

# 6. Iniciar la aplicación
php artisan serve
```

Si todos los comandos anteriores funcionan sin errores, BlackPath está listo para usar.

## 🎮 Uso

### Iniciar un Escaneo

1. Abre tu navegador y ve a `http://localhost`
2. Ingresa el dominio que quieres escanear
3. Selecciona la wordlist (common, medium, full)
4. Haz clic en "Iniciar Escaneo"

### Tipos de Wordlists

- **Common**: ~4,000 palabras (~2-3 minutos)
- **Medium**: ~14,000 palabras (~4-5 minutos)  
- **Full**: ~100,000 palabras (~15-20 minutos)

### Monitorear Progreso

- La página se actualiza automáticamente
- Barra de progreso en tiempo real
- Estados detallados de cada herramienta
- Redirección automática al completar

## 📁 Estructura del Proyecto

```
blackpath/
├── app/
│   ├── Http/Controllers/    # Controladores de la aplicación
│   ├── Jobs/               # Jobs para procesamiento en segundo plano
│   ├── Services/           # Servicios de escaneo
│   └── Models/             # Modelos de datos
├── resources/views/        # Vistas Blade
├── docker/                 # Configuración de Docker
├── storage/scanner/        # Logs y resultados temporales
└── docker-compose.yml      # Configuración de contenedores
```

## 🔧 Configuración

### Variables de Entorno

```env
APP_NAME=BlackPath
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 🔧 Configuración de Jobs

El proyecto usa jobs en cola para procesar escaneos. Asegúrate de que en tu `.env` tengas:

```env
QUEUE_CONNECTION=database
```

Y que hayas ejecutado las migraciones para crear la tabla `jobs`.

### 📁 Archivos Necesarios

El proyecto requiere algunos archivos que no están en Git por seguridad:

- **`storage/app/domains/domain-names.txt`**: Lista de dominios para el generador aleatorio
- **`storage/app/wordlists/`**: Directorios para wordlists (se descargan automáticamente)
- **`storage/logs/`**: Directorio para logs de Laravel
- **`storage/framework/`**: Directorios para cache, sesiones y vistas

El script `setup-files.php` crea automáticamente estos archivos y directorios.

### Docker

El proyecto incluye un contenedor Docker con todas las herramientas necesarias:
- Ubuntu 22.04
- WhatWeb 0.5.5
- Nmap
- Gobuster 3.7.0
- Wordlists de SecLists

## 📊 Resultados

Los escaneos generan:

- **Análisis de Tecnologías**: Frameworks, servidores, tecnologías detectadas
- **Puertos Abiertos**: Servicios y puertos disponibles
- **Directorios Encontrados**: Rutas y archivos descubiertos
- **Reporte PDF**: Exportación completa de resultados

## 🔍 Logs y Debugging

### Ver Logs de Laravel
```bash
tail -f storage/logs/laravel.log
```

### Ver Logs de Docker
```bash
docker-compose logs -f
```

### Ver Jobs en Cola
```bash
php artisan queue:work --verbose
```

### Ver Jobs Fallidos
```bash
php artisan queue:failed
```

## 🛡️ Seguridad

- **Uso Ético**: Solo escanea sitios que poseas o tengas autorización
- **Rate Limiting**: Implementado para prevenir abuso
- **Validación de Entrada**: Sanitización de dominios
- **Contenedores Aislados**: Herramientas ejecutadas en Docker

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## ⚠️ Disclaimer

Esta herramienta está diseñada únicamente para propósitos educativos y de testing en entornos controlados. Los usuarios son responsables de cumplir con todas las leyes y regulaciones aplicables al usar esta herramienta.

## 🆘 Soporte

Si encuentras algún problema:

1. Revisa los logs en `storage/logs/laravel.log`
2. Verifica que Docker esté funcionando: `docker ps`
3. Revisa el estado de los contenedores: `docker-compose logs`
4. Abre un issue en GitHub con detalles del error

---

**Desarrollado con ❤️ usando Laravel y Docker**

### 🔧 Troubleshooting Común

**Problema**: "We're waiting for this website to finish loading"
- **Solución**: Ejecuta `php artisan queue:work --daemon`

**Problema**: "WhatWeb failed"
- **Solución**: Verifica que Docker esté ejecutándose: `docker-compose up -d`

**Problema**: "Failed to fetch random domain"
- **Solución**: Ejecuta `php artisan blackpath:setup-files`

**Problema**: Los escaneos no inician
- **Solución**: Ejecuta las migraciones: `php artisan migrate`

**Problema**: Errores durante la construcción de Docker (líneas rojas)
- **Solución**: 
```bash
# Detener y eliminar contenedores
docker-compose down

# Limpiar cache de Docker
docker system prune -f

# Reconstruir sin cache
docker-compose build --no-cache

# Verificar la construcción
php check-docker.php
```

**Problema**: Herramientas no funcionan en el contenedor
- **Solución**: 
```bash
# Verificar el estado del contenedor
docker logs url-scanner-tools

# Reconstruir completamente
docker-compose down
docker-compose build --no-cache --pull
docker-compose up -d
```
