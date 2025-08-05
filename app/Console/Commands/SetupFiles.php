<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupFiles extends Command
{
    protected $signature = 'blackpath:setup-files';
    protected $description = 'Setup required files and directories for BlackPath';

    public function handle()
    {
        $this->info('🔧 BlackPath File Setup');
        $this->info('======================');
        $this->newLine();

        // 1. Crear directorio de dominios si no existe
        $domainsDir = storage_path('app/domains');
        if (!is_dir($domainsDir)) {
            mkdir($domainsDir, 0755, true);
            $this->info('✅ Creado directorio: ' . $domainsDir);
        } else {
            $this->info('✅ Directorio ya existe: ' . $domainsDir);
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
            $this->info('✅ Creado archivo de dominios: ' . $domainsFile);
        } else {
            $this->info('✅ Archivo de dominios ya existe: ' . $domainsFile);
        }

        // 3. Crear directorio de wordlists si no existe
        $wordlistsDir = storage_path('app/wordlists');
        if (!is_dir($wordlistsDir)) {
            mkdir($wordlistsDir, 0755, true);
            $this->info('✅ Creado directorio: ' . $wordlistsDir);
        } else {
            $this->info('✅ Directorio ya existe: ' . $wordlistsDir);
        }

        // 4. Crear directorio de logs si no existe
        $logsDir = storage_path('logs');
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
            $this->info('✅ Creado directorio: ' . $logsDir);
        } else {
            $this->info('✅ Directorio ya existe: ' . $logsDir);
        }

        // 5. Crear directorio de cache si no existe
        $cacheDir = storage_path('framework/cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
            $this->info('✅ Creado directorio: ' . $cacheDir);
        } else {
            $this->info('✅ Directorio ya existe: ' . $cacheDir);
        }

        // 6. Crear directorio de sesiones si no existe
        $sessionsDir = storage_path('framework/sessions');
        if (!is_dir($sessionsDir)) {
            mkdir($sessionsDir, 0755, true);
            $this->info('✅ Creado directorio: ' . $sessionsDir);
        } else {
            $this->info('✅ Directorio ya existe: ' . $sessionsDir);
        }

        // 7. Crear directorio de vistas si no existe
        $viewsDir = storage_path('framework/views');
        if (!is_dir($viewsDir)) {
            mkdir($viewsDir, 0755, true);
            $this->info('✅ Creado directorio: ' . $viewsDir);
        } else {
            $this->info('✅ Directorio ya existe: ' . $viewsDir);
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

        $this->newLine();
        $this->info('🔍 Verificando permisos de escritura...');
        foreach ($writableDirs as $dir) {
            if (is_writable($dir)) {
                $this->info('   ✅ ' . $dir . ' (escribible)');
            } else {
                $this->error('   ❌ ' . $dir . ' (no escribible)');
                $this->line('   💡 Ejecuta: chmod 755 ' . $dir);
            }
        }

        $this->newLine();
        $this->info('🎯 Setup de archivos completado!');
        $this->info('Ahora ejecuta: php artisan serve');
    }
} 