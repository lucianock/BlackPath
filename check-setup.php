<?php

echo "🔍 BlackPath Setup Checker\n";
echo "==========================\n\n";

// 1. Verificar archivos críticos
echo "1. Verificando archivos críticos...\n";
$criticalFiles = [
    '.env' => 'Archivo de configuración',
    'storage/app/domains/domain-names.txt' => 'Lista de dominios',
    'docker-compose.yml' => 'Configuración Docker',
    'app/Services/ScannerService.php' => 'Servicio de escaneo'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✅ $description: $file\n";
    } else {
        echo "   ❌ $description: $file (FALTANTE)\n";
    }
}

// 2. Verificar configuración de entorno
echo "\n2. Verificando configuración de entorno...\n";
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    $requiredEnvVars = [
        'APP_KEY' => 'Clave de aplicación',
        'QUEUE_CONNECTION' => 'Configuración de cola',
        'CACHE_DRIVER' => 'Driver de cache',
        'SESSION_DRIVER' => 'Driver de sesión'
    ];
    
    foreach ($requiredEnvVars as $var => $description) {
        if (strpos($envContent, $var) !== false) {
            echo "   ✅ $description: $var configurado\n";
        } else {
            echo "   ❌ $description: $var (FALTANTE)\n";
        }
    }
} else {
    echo "   ❌ Archivo .env no encontrado\n";
}

// 3. Verificar base de datos
echo "\n3. Verificando base de datos...\n";
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $tables = ['jobs', 'failed_jobs', 'scans', 'scan_results'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if ($stmt->fetch()) {
            echo "   ✅ Tabla: $table\n";
        } else {
            echo "   ❌ Tabla: $table (FALTANTE)\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ Error de base de datos: " . $e->getMessage() . "\n";
}

// 4. Verificar Docker
echo "\n4. Verificando Docker...\n";
$dockerOutput = shell_exec('docker ps 2>&1');
if (strpos($dockerOutput, 'url-scanner-tools') !== false) {
    echo "   ✅ Contenedor url-scanner-tools está ejecutándose\n";
} else {
    echo "   ❌ Contenedor url-scanner-tools no está ejecutándose\n";
    echo "   💡 Ejecuta: docker-compose up -d\n";
}

// 5. Verificar herramientas
echo "\n5. Verificando herramientas de escaneo...\n";
$tools = ['whatweb', 'nmap', 'gobuster'];
foreach ($tools as $tool) {
    $output = shell_exec("docker exec url-scanner-tools which $tool 2>&1");
    if (strpos($output, $tool) !== false) {
        echo "   ✅ $tool instalado\n";
    } else {
        echo "   ❌ $tool no encontrado\n";
    }
}

// 6. Verificar permisos
echo "\n6. Verificando permisos...\n";
$directories = [
    'storage/logs' => 'Logs',
    'storage/framework/cache' => 'Cache',
    'storage/framework/sessions' => 'Sesiones',
    'storage/app/domains' => 'Dominios'
];

foreach ($directories as $dir => $description) {
    if (is_dir($dir) && is_writable($dir)) {
        echo "   ✅ $description: $dir (escribible)\n";
    } else {
        echo "   ❌ $description: $dir (no escribible)\n";
    }
}

echo "\n🎯 Resumen de verificación completado!\n";
echo "Si ves ❌, ejecuta los comandos sugeridos para solucionar los problemas.\n"; 