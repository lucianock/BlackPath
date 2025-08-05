<?php

echo "🔧 BlackPath File Setup\n";
echo "======================\n\n";

// 1. Crear directorio de dominios si no existe
$domainsDir = storage_path('app/domains');
if (!is_dir($domainsDir)) {
    mkdir($domainsDir, 0755, true);
    echo "✅ Creado directorio: $domainsDir\n";
} else {
    echo "✅ Directorio ya existe: $domainsDir\n";
}

// 2. Crear archivo de dominios si no existe
$domainsFile = $domainsDir . '/domain-names.txt';
if (!file_exists($domainsFile)) {
    $domains = [
        'testphp.vulnweb.com',
        'testasp.vulnweb.com',
        'testhtml5.vulnweb.com',
        'zero.webappsecurity.com',
        'demo.testfire.net',
        'juice-shop.herokuapp.com',
        'bwapp.hackthebox.eu',
        'bwapplive.appspot.com',
        'dvwa.co.uk',
        'xss-game.appspot.com',
        'public-firing-range.appspot.com',
        'google-gruyere.appspot.com',
        'hack.me',
        'phprdap.sourceforge.net/demo/',
        'www.webscantest.com',
        'www.wa-st.ru',
        'mutillidae.kwae.org',
        'nsa.gov.phishing.website',
        'pentestmonkey.net/test',
        'xss.rocks'
    ];
    
    file_put_contents($domainsFile, implode("\n", $domains));
    echo "✅ Creado archivo de dominios: $domainsFile\n";
} else {
    echo "✅ Archivo de dominios ya existe: $domainsFile\n";
}

// 3. Crear directorio de wordlists si no existe
$wordlistsDir = storage_path('app/wordlists');
if (!is_dir($wordlistsDir)) {
    mkdir($wordlistsDir, 0755, true);
    echo "✅ Creado directorio: $wordlistsDir\n";
} else {
    echo "✅ Directorio ya existe: $wordlistsDir\n";
}

// 4. Crear directorio de logs si no existe
$logsDir = storage_path('logs');
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0755, true);
    echo "✅ Creado directorio: $logsDir\n";
} else {
    echo "✅ Directorio ya existe: $logsDir\n";
}

// 5. Crear directorio de cache si no existe
$cacheDir = storage_path('framework/cache');
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
    echo "✅ Creado directorio: $cacheDir\n";
} else {
    echo "✅ Directorio ya existe: $cacheDir\n";
}

// 6. Crear directorio de sesiones si no existe
$sessionsDir = storage_path('framework/sessions');
if (!is_dir($sessionsDir)) {
    mkdir($sessionsDir, 0755, true);
    echo "✅ Creado directorio: $sessionsDir\n";
} else {
    echo "✅ Directorio ya existe: $sessionsDir\n";
}

// 7. Crear directorio de vistas si no existe
$viewsDir = storage_path('framework/views');
if (!is_dir($viewsDir)) {
    mkdir($viewsDir, 0755, true);
    echo "✅ Creado directorio: $viewsDir\n";
} else {
    echo "✅ Directorio ya existe: $viewsDir\n";
}

// 8. Verificar permisos de escritura
$writableDirs = [
    storage_path('logs'),
    storage_path('framework/cache'),
    storage_path('framework/sessions'),
    storage_path('framework/views'),
    storage_path('app/domains'),
    storage_path('app/wordlists')
];

echo "\n🔍 Verificando permisos de escritura...\n";
foreach ($writableDirs as $dir) {
    if (is_writable($dir)) {
        echo "   ✅ $dir (escribible)\n";
    } else {
        echo "   ❌ $dir (no escribible)\n";
        echo "   💡 Ejecuta: chmod 755 $dir\n";
    }
}

echo "\n🎯 Setup de archivos completado!\n";
echo "Ahora ejecuta: php artisan serve\n"; 