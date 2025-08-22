<?php
// pdf-union.php - exposes pdf_union_run() for include or CLI use
require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

function convertImageToPdf($imagePath, $outputPdf) {
    $pdf = new \FPDF();
    $pdf->AddPage('P', 'A4');
    $imageInfo = getimagesize($imagePath);
    if (!$imageInfo) throw new Exception("Erro ao obter informações da imagem: $imagePath");
    $mime = $imageInfo['mime'];
    $tempFile = null;
    if ($mime !== 'image/jpeg') {
        $img = imagecreatefromstring(file_get_contents($imagePath));
        if (!$img) throw new Exception("Erro ao criar imagem a partir do arquivo: $imagePath");
        $tempFile = tempnam(sys_get_temp_dir(), 'conv') . '.jpg';
        imagejpeg($img, $tempFile);
        imagedestroy($img);
        $pdf->Image($tempFile, 0, 0, 210);
    } else {
        $pdf->Image($imagePath, 0, 0, 210);
    }
    $pdf->Output('F', $outputPdf);
    if ($tempFile && file_exists($tempFile)) @unlink($tempFile);
}

function extractNumeric($filePath) {
    $base = pathinfo($filePath, PATHINFO_FILENAME);
    return ctype_digit($base) ? (int)$base : null;
}

function pdf_union_run() {
    $input_folder = __DIR__ . DIRECTORY_SEPARATOR . 'arquivos';
    $output_folder = __DIR__ . DIRECTORY_SEPARATOR . 'resultado';
    if (!file_exists($output_folder)) mkdir($output_folder, 0777, true);

    $pdfs_originais = [];
    $pdfs_convertidos = [];

    $warnings = [];

    // small helper: basic PDF validation (header + presence of xref/startxref/%%EOF)
    $is_valid_pdf = function(string $path): bool {
        if (!is_readable($path)) return false;
        $fh = fopen($path, 'rb');
        if (!$fh) return false;
        $head = fread($fh, 1024);
        if ($head === false) { fclose($fh); return false; }
        if (strpos($head, '%PDF-') !== 0) { fclose($fh); return false; }
        // check for xref or startxref or EOF in whole file tail
        $stat = fstat($fh);
        $size = $stat['size'] ?? 0;
        $tailSize = min(2048, $size);
        if ($tailSize > 0) {
            fseek($fh, -$tailSize, SEEK_END);
            $tail = fread($fh, $tailSize);
        } else {
            $tail = '';
        }
        fclose($fh);
        if ($tail === false) $tail = '';
        if (stripos($tail, 'startxref') !== false || stripos($tail, 'xref') !== false || stripos($tail, '%%EOF') !== false) {
            return true;
        }
        return false;
    };

    $files = @scandir($input_folder);
    if ($files === false) return "Pasta de entrada não encontrada: $input_folder";

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $file_path = $input_folder . DIRECTORY_SEPARATOR . $file;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            if ($is_valid_pdf($file_path)) {
                $pdfs_originais[] = $file_path;
            } else {
                $warnings[] = "Ignorado (PDF inválido/corrompido): $file";
            }
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $outputPdf = $input_folder . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_FILENAME) . '.pdf';
            convertImageToPdf($file_path, $outputPdf);
            $pdfs_convertidos[] = $outputPdf;
        }
    }

    $todos_pdfs = array_merge($pdfs_originais, $pdfs_convertidos);
    if (empty($todos_pdfs)) return "Não existem arquivos para gerar pdf";

    $numeric_files = [];
    $non_numeric_files = [];
    foreach ($todos_pdfs as $pdf) {
        $num = extractNumeric($pdf);
        if ($num !== null) $numeric_files[$pdf] = $num; else $non_numeric_files[] = $pdf;
    }

    asort($numeric_files);
    $numeric_files_sorted = array_keys($numeric_files);
    sort($non_numeric_files);
    $todos_pdfs_sorted = array_merge($numeric_files_sorted, $non_numeric_files);

    $date_str = date("Ymd-Hi");
    $output_pdf_final = $output_folder . DIRECTORY_SEPARATOR . "final_unificado_" . $date_str . ".pdf";

    $pdf = new Fpdi();
    $processed = 0;
    foreach ($todos_pdfs_sorted as $file) {
        try {
            $pageCount = $pdf->setSourceFile($file);
        } catch (Exception $e) {
            $warnings[] = "Falha ao abrir '$file': " . $e->getMessage();
            continue;
        }
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplIdx = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($tplIdx);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplIdx);
            $processed++;
        }
    }

    if ($processed === 0) {
        $msg = "Nenhuma página válida encontrada para unir.";
        if (!empty($warnings)) $msg .= " Avisos: " . implode(' | ', $warnings);
        return $msg;
    }

    $pdf->Output('F', $output_pdf_final);
    $msg = "PDF unificado criado com sucesso em: " . $output_pdf_final;
    if (!empty($warnings)) $msg .= " (Avisos: " . implode(' | ', $warnings) . ")";
    return $msg;
}

if (php_sapi_name() === 'cli' && isset($_SERVER['argv'][0]) && realpath(__FILE__) === realpath($_SERVER['argv'][0])) {
    echo pdf_union_run();
}
