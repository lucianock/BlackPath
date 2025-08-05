<?php

echo "🐳 BlackPath Docker Checker\n";
echo "==========================\n\n";

// 1. Verificar que Docker esté ejecutándose
echo "1. Verificando Docker...\n";
$dockerOutput = shell_exec('docker ps 2>&1');
if (strpos($dockerOutput, 'url-scanner-tools') !== false) {
    echo "   ✅ Contenedor url-scanner-tools está ejecutándose\n";
} else {
    echo "   ❌ Contenedor url-scanner-tools no está ejecutándose\n";
    echo "   💡 Ejecuta: docker-compose up -d\n";
    exit(1);
}

// 2. Verificar herramientas básicas
echo "\n2. Verificando herramientas básicas...\n";
$tools = [
    'nmap' => 'nmap --version',
    'whatweb' => 'whatweb --version',
    'gobuster' => 'gobuster version'
];

foreach ($tools as $tool => $command) {
    $output = shell_exec("docker exec url-scanner-tools $command 2>&1");
    if (strpos($output, 'error') === false && !empty(trim($output))) {
        echo "   ✅ $tool instalado y funcionando\n";
    } else {
        echo "   ❌ $tool no funciona correctamente\n";
        echo "   💡 Error: " . trim($output) . "\n";
    }
}

// 3. Verificar wordlists
echo "\n3. Verificando wordlists...\n";
$wordlists = [
    'common.txt' => '/app/wordlists/common.txt',
    'medium.txt' => '/app/wordlists/medium.txt',
    'full.txt' => '/app/wordlists/full.txt'
];

foreach ($wordlists as $name => $path) {
    $output = shell_exec("docker exec url-scanner-tools ls -la $path 2>&1");
    if (strpos($output, 'No such file') === false && !empty(trim($output))) {
        $size = shell_exec("docker exec url-scanner-tools wc -l $path 2>&1");
        echo "   ✅ $name existe (" . trim($size) . " líneas)\n";
    } else {
        echo "   ❌ $name no existe o está vacío\n";
    }
}

// 4. Probar escaneo básico
echo "\n4. Probando escaneo básico...\n";
$testOutput = shell_exec("docker exec url-scanner-tools whatweb --color=never --log-json=- google.com 2>&1");
if (strpos($testOutput, 'error') === false && !empty(trim($testOutput))) {
    echo "   ✅ WhatWeb funciona correctamente\n";
} else {
    echo "   ❌ WhatWeb no funciona\n";
    echo "   💡 Error: " . trim($testOutput) . "\n";
}

// 5. Verificar permisos
echo "\n5. Verificando permisos...\n";
$permissions = [
    '/app/wordlists' => 'Directorio wordlists',
    '/app/results' => 'Directorio results',
    '/usr/local/bin/gobuster' => 'Gobuster ejecutable'
];

foreach ($permissions as $path => $description) {
    $output = shell_exec("docker exec url-scanner-tools ls -la $path 2>&1");
    if (strpos($output, 'No such file') === false) {
        echo "   ✅ $description: $path\n";
    } else {
        echo "   ❌ $description: $path (no existe)\n";
    }
}

echo "\n🎯 Verificación de Docker completada!\n";
echo "Si ves ❌, reconstruye el contenedor: docker-compose build --no-cache\n"; 