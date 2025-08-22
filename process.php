<?php
// Recebe uploads em ordem (FormData files[]), salva em ./arquivos com prefixo numericado e chama pdf-union.php
set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'arquivos';
$resultDir = __DIR__ . DIRECTORY_SEPARATOR . 'resultado';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
if (!is_dir($resultDir)) mkdir($resultDir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Método inválido';
    exit;
}

if (empty($_FILES['files'])) {
    echo 'Nenhum arquivo recebido';
    exit;
}

$files = $_FILES['files'];

// Limpeza de arquivos antigos no diretório de upload para evitar conflitos (opcional)
// Aqui não removemos tudo para ser conservador; apenas continuamos

$saved = [];

// $_FILES preserves the order of appended files in FormData
for ($i = 0; $i < count($files['name']); $i++) {
    $origName = $files['name'][$i];
    $tmp = $files['tmp_name'][$i];
    $err = $files['error'][$i];
    if ($err !== UPLOAD_ERR_OK) continue;

    // sanitize filename
    $safe = preg_replace('/[^A-Za-z0-9_\-\. ]+/', '_', $origName);
    // remove existing leading numeric prefixes like "1-" or "1-5 -" to avoid double-prefixing
    $safe = preg_replace('/^[0-9]+(?:-[0-9]+)?[\s-]*/', '', $safe);
    $index = $i + 1; // 1-based index to prefix
    $targetName = $index . '-' . $safe;
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $targetName;
    if (!move_uploaded_file($tmp, $targetPath)) {
        echo "Falha ao mover arquivo: $origName";
        // attempt continue
    } else {
        $saved[] = $targetPath;
    }
}

if (empty($saved)) {
    echo 'Nenhum arquivo válido foi salvo.';
    exit;
}

// Chama o script de união via include (sem exec). Adiciona diagnósticos visíveis na resposta
$output = [];
$rc = 0;
$phpExecutable = PHP_BINARY; // usa o mesmo PHP do ambiente
$unionScriptPath = __DIR__ . DIRECTORY_SEPARATOR . 'pdf-union.php';

// Diagnostics: collect environment and file info to help debug unexpected shell execution
// Prepare diagnostics (only shown when DEBUG env var is truthy)
$diag = [];
$diag[] = 'php_sapi_name: ' . php_sapi_name();
$diag[] = 'PHP_BINARY: ' . (defined('PHP_BINARY') ? PHP_BINARY : 'undefined');
$diag[] = 'disable_functions: ' . ini_get('disable_functions');
if (file_exists($unionScriptPath)) {
    $stat = @stat($unionScriptPath);
    if ($stat !== false) {
        $diag[] = 'union_script_exists: yes';
        $diag[] = 'union_script_perms: ' . substr(sprintf('%o', $stat['mode']), -4);
        $diag[] = 'union_script_uid: ' . $stat['uid'] . ' gid: ' . $stat['gid'];
        $diag[] = 'union_script_executable: ' . (is_executable($unionScriptPath) ? 'yes' : 'no');
        // read first non-empty lines to detect unexpected shebang or content
        $firstLines = @file($unionScriptPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($firstLines !== false) {
            $lines = array_slice($firstLines, 0, 6);
            foreach ($lines as $n => $l) {
                $diag[] = 'file_line_' . ($n + 1) . ': ' . $l;
            }
        }
    } else {
        $diag[] = 'union_script_exists: yes (stat failed)';
    }
} else {
    $diag[] = 'union_script_exists: no';
}

// Show uploads/result dir status
$diag[] = 'upload_dir: ' . $uploadDir . ' exists: ' . (is_dir($uploadDir) ? 'yes' : 'no');
$diag[] = 'result_dir: ' . $resultDir . ' exists: ' . (is_dir($resultDir) ? 'yes' : 'no');
$diag[] = 'saved_count: ' . count($saved);

// Append diag to output so it's visible in the web response for quick debugging
$debugOn = filter_var(getenv('DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);
if ($debugOn) {
    $output[] = implode("\n", $diag);
}

// Executa o processo de união por include + função exportada (sem exec())
if (file_exists($unionScriptPath)) {
    ob_start();
    // include dentro do mesmo processo PHP (mais seguro e previsível em ambientes restritos)
    require_once $unionScriptPath;
    $buf = ob_get_clean();
    if (function_exists('pdf_union_run')) {
        // chame a função e capture o retorno
        try {
            $ret = pdf_union_run();
            if ($buf) $output[] = $buf;
            if ($ret) $output[] = $ret;
            $rc = 0;
        } catch (Throwable $e) {
            if ($buf) $output[] = $buf;
            $output[] = 'Exception during pdf_union_run(): ' . $e->getMessage();
            $rc = 1;
        }
    } else {
        if ($buf) $output[] = $buf;
        $output[] = 'Erro: função pdf_union_run() não encontrada no script de união.';
        $rc = 1;
    }
} else {
    $output[] = 'Erro: script de união não encontrado em: ' . $unionScriptPath;
    $rc = 1;
}

$outHtml = '<h2>Processamento concluído</h2>';
$outHtml .= '<pre>' . htmlspecialchars(implode("\n", $output)) . '</pre>';

// Procura pelo último arquivo gerado na pasta resultado usando prefix final_unificado_
$filesRes = glob($resultDir . DIRECTORY_SEPARATOR . 'final_unificado_*.pdf');
usort($filesRes, function($a, $b){ return filemtime($b) - filemtime($a); });

if (!empty($filesRes)) {
    $latest = basename($filesRes[0]);
    $outHtml .= '<p><a href="download.php?file=' . urlencode($latest) . '">Baixar PDF unificado: ' . htmlspecialchars($latest) . '</a></p>';
} else {
    $outHtml .= '<p>Nenhum arquivo resultante encontrado em resultado/</p>';
}

// Opcional: mostrar arquivos enviados
$outHtml .= '<h3>Arquivos enviados (ordenados)</h3><ul>';
foreach ($saved as $p) {
    $outHtml .= '<li>' . htmlspecialchars(basename($p)) . '</li>';
}
$outHtml .= '</ul>';

// botão/form para recomeçar o processo do zero (limpa uploads/resultados e volta para index)
$outHtml .= '<form method="post" action="cleanup.php" style="margin-top:16px;"><button type="submit">Novo Processo</button></form>';

echo $outHtml;
