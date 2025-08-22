<?php
// Limpa arquivos em arquivos/ e resultado/ e redireciona para index.php
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'arquivos';
$resultDir = __DIR__ . DIRECTORY_SEPARATOR . 'resultado';

// Remove files matching prefix pattern (N-name) in arquivos
if (is_dir($uploadDir)) {
    foreach (glob($uploadDir . DIRECTORY_SEPARATOR . '*-*') as $f) {
        if (is_file($f)) @unlink($f);
    }
}

// Remove generated results
if (is_dir($resultDir)) {
    foreach (glob($resultDir . DIRECTORY_SEPARATOR . '*') as $f) {
        if (is_file($f)) @unlink($f);
    }
}

header('Location: index.php');
exit;
