<?php
// Script temporal para extraer vendor.zip
// ELIMINAR después de usar

$zip_path = __DIR__ . '/../vendor.zip';
$extract_to = __DIR__ . '/..';

if (!file_exists($zip_path)) {
    die('ERROR: No se encuentra vendor.zip en la raíz del proyecto.');
}

if (!class_exists('ZipArchive')) {
    die('ERROR: ZipArchive no está disponible en este servidor.');
}

$zip = new ZipArchive();
if ($zip->open($zip_path) !== true) {
    die('ERROR: No se pudo abrir vendor.zip.');
}

echo "Extrayendo " . $zip->numFiles . " archivos...<br>";
flush();

$zip->extractTo($extract_to);
$zip->close();

echo "Extraccion completada.<br>";
echo "Eliminando zip...<br>";
unlink($zip_path);
echo "Listo. Ahora elimina este archivo (extraer.php) del servidor.";
