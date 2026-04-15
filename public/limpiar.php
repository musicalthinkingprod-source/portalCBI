<?php
// Script temporal para limpiar caché de Laravel
// ELIMINAR después de usar

$base = __DIR__ . '/..';
$cache_dir = $base . '/bootstrap/cache';
$storage_cache = $base . '/storage/framework';

$eliminados = [];
$errores = [];

// Limpiar bootstrap/cache
foreach (glob($cache_dir . '/*.php') as $file) {
    if (unlink($file)) {
        $eliminados[] = basename($file);
    } else {
        $errores[] = basename($file);
    }
}

// Limpiar storage/framework/cache
foreach (glob($storage_cache . '/cache/data/*/*') as $file) {
    if (is_file($file)) unlink($file);
}

// Limpiar storage/framework/views
foreach (glob($storage_cache . '/views/*.php') as $file) {
    if (unlink($file)) {
        $eliminados[] = 'views/' . basename($file);
    }
}

echo "<pre>";
echo "=== Cache limpiado ===\n\n";
echo "Eliminados (" . count($eliminados) . "):\n";
foreach ($eliminados as $f) echo "  - $f\n";

if ($errores) {
    echo "\nErrores:\n";
    foreach ($errores as $f) echo "  - $f\n";
}

echo "\nListo. Ahora elimina este archivo del servidor.";
echo "</pre>";
