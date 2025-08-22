<?php
// Stream the generated PDF and delete it after sending
$resultDir = __DIR__ . DIRECTORY_SEPARATOR . 'resultado';

if (empty($_GET['file'])) {
    http_response_code(400);
    echo 'Arquivo não especificado.';
    exit;
}

$file = basename($_GET['file']);
$path = $resultDir . DIRECTORY_SEPARATOR . $file;

if (!file_exists($path)) {
    http_response_code(404);
    echo 'Arquivo não encontrado.';
    exit;
}

// Send correct headers
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $file . '"');

$fp = fopen($path, 'rb');
while (!feof($fp)) {
    echo fread($fp, 8192);
    flush();
}
fclose($fp);

// Delete the file after sending
unlink($path);

// Also attempt to clean uploaded files (prefix-d files) in ./arquivos to keep workspace tidy
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'arquivos';
foreach (glob($uploadDir . DIRECTORY_SEPARATOR . '*-*') as $f) {
    // only remove files that look like they were prefixed by our process (N-name)
    if (is_file($f)) @unlink($f);
}

exit;
