<?php

namespace App\Helpers;

use App\Models\Proyecto;

/**
 * Genera el Documento Unificado del proyecto cuando todas las etapas están aprobadas.
 *
 * Produce DOS documentos, cada uno en PDF y Word (.docx):
 *  - Documento 1: Perfil (solo la etapa de Perfil / primera aprobada).
 *  - Documento 2: Resto del proyecto (etapas <= después del Perfil, en orden).
 *
 * Los PDF se generan uniendo mediante FPDI los PDF individuales de cada etapa
 * (convirtiéndolos primero con LibreOffice si no son PDF). Los .docx se generan
 * con el texto legible de cada documento.
 */
class Consolidado
{
    private const PERFIL_ETAPA = 1;

    /**
     * Genera los documentos consolidados para un proyecto si todas sus etapas
     * están aprobadas. Devuelve array con las rutas generadas o null si no aplica.
     */
    public function generar(int $proyectoId): ?array
    {
        $proyecto = (new Proyecto())->detail($proyectoId);
        if (!$proyecto) {
            return null;
        }

        $etapas = (new Proyecto())->etapasConEstado($proyectoId, (int) $proyecto['tipo_proyecto_id']);

        // El consolidado solo aplica cuando todas las etapas están aprobadas
        $todasAprobadas = true;
        foreach ($etapas as $et) {
            if ($et['estado_etapa'] !== 'aprobada') {
                $todasAprobadas = false;
                break;
            }
        }
        if (!$todasAprobadas) {
            return null;
        }

        $perfil = [];
        $resto = [];
        foreach ($etapas as $et) {
            if (!empty($et['final_id']) && $et['estado_etapa'] === 'aprobada') {
                $item = [
                    'etapa' => $et['nombre'],
                    'etapa_id' => (int) $et['id'],
                    'orden' => (int) $et['orden'],
                    'doc_id' => (int) $et['final_id'],
                    'nombre' => $et['final_nombre'] ?? 'Documento',
                    'ruta_final' => $et['final_ruta'] ?? '',
                ];
                if ((int) $et['orden'] <= self::PERFIL_ETAPA) {
                    $perfil[] = $item;
                } else {
                    $resto[] = $item;
                }
            }
        }

        if (!$perfil && !$resto) {
            return null;
        }

        $datos = [
            'proyecto' => $proyecto,
            'perfil' => $perfil,
            'resto' => $resto,
        ];

        $pdfPerfil = $perfil ? $this->pdfUnificado($perfil, $proyecto, 'PERFIL DEL PROYECTO') : null;
        $pdfResto = $resto ? $this->pdfUnificado($resto, $proyecto, 'PROYECTO DE TITULACIÓN') : null;

        $docxPerfil = $perfil ? $this->docxUnificado($perfil, $proyecto, 'PERFIL DEL PROYECTO') : null;
        $docxResto = $resto ? $this->docxUnificado($resto, $proyecto, 'PROYECTO DE TITULACIÓN') : null;

        $resultado = [
            'perfil_pdf' => $pdfPerfil,
            'perfil_docx' => $docxPerfil,
            'resto_pdf' => $pdfResto,
            'resto_docx' => $docxResto,
        ];

        // Registrar en historial
        $accion = 'Documento unificado';
        $descripcion = 'Se generaron los documentos consolidados (Perfil y Proyecto).';
        $usuarioId = \App\Core\Auth::id();
        if ($usuarioId === null) {
            $inv = (new Proyecto())->involucrados($proyectoId);
            $usuarioId = $inv['estudiante_usuario_id'] ?? null;
        }

        if ($usuarioId !== null) {
            \App\Core\Database::getConnection()
                ->prepare('INSERT INTO historial_acciones (usuario_id, accion, descripcion, proyecto_id) VALUES (?, ?, ?, ?)')
                ->execute([$usuarioId, $accion, $descripcion, $proyectoId]);
        }

        return $resultado;
    }

    /** Ruta absoluta de un archivo consolidado si existe (tipo: perfil|proyecto, formato: pdf|docx) */
    public function archivo(int $proyectoId, string $tipo, string $formato): ?string
    {
        $tipo = in_array($tipo, ['perfil', 'proyecto'], true) ? $tipo : null;
        $formato = in_array($formato, ['pdf', 'docx'], true) ? $formato : null;
        if ($tipo === null || $formato === null) {
            return null;
        }
        $ruta = UPLOAD_CONSOLIDADOS . '/' . $tipo . '_p' . $proyectoId . '.' . $formato;
        return is_file($ruta) ? $ruta : null;
    }

    /** ¿Ya se generaron los documentos consolidados del proyecto? */
    public function generados(int $proyectoId): bool
    {
        return $this->archivo($proyectoId, 'proyecto', 'pdf') !== null
            || $this->archivo($proyectoId, 'perfil', 'pdf') !== null;
    }

    /** Fecha de última generación (de los archivos existentes) */
    public function fechaGeneracion(int $proyectoId): ?string
    {
        $max = null;
        foreach (['perfil', 'proyecto'] as $tipo) {
            foreach (['pdf', 'docx'] as $formato) {
                $r = $this->archivo($proyectoId, $tipo, $formato);
                if ($r) {
                    $m = filemtime($r);
                    if ($max === null || $m > $max) {
                        $max = $m;
                    }
                }
            }
        }
        return $max ? date('Y-m-d H:i:s', $max) : null;
    }

    // ---------- PDF (FPDF + FPDI) ----------

    private function pdfUnificado(array $etapas, array $proyecto, string $tituloPortada): ?string
    {
        $dirSalida = UPLOAD_CONSOLIDADOS;
        if (!is_dir($dirSalida)) {
            @mkdir($dirSalida, 0777, true);
        }

        $prefijo = $tituloPortada === 'PERFIL DEL PROYECTO' ? 'perfil' : 'proyecto';
        $nombreSalida = $prefijo . '_p' . $proyecto['id'] . '.pdf';
        $rutaSalida = $dirSalida . '/' . $nombreSalida;

        try {
            $pdf = new \setasign\Fpdi\Fpdi('P', 'mm', 'A4');

            // Portada
            $pdf->AddPage();
            $pdf->SetFont('Times', 'B', 18);
            $pdf->SetY(60);
            $pdf->Cell(0, 12, mb_strtoupper((string) $tituloPortada, 'UTF-8'), 0, 1, 'C');
            $pdf->SetFont('Times', '', 13);
            $pdf->Ln(8);
            $pdf->Cell(0, 8, mb_strtoupper((string) ($proyecto['codigo'] ?? ''), 'UTF-8'), 0, 1, 'C');
            $pdf->Ln(6);
            $pdf->SetFont('Times', 'I', 12);
            $pdf->Cell(0, 8, ($proyecto['nombre'] ?? ''), 0, 1, 'C');

            // Índice de etapas
            $pdf->Ln(20);
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 10, 'CONTENIDO', 0, 1, 'C');
            $pdf->Ln(4);
            $pdf->SetFont('Times', '', 12);
            $num = 1;
            foreach ($etapas as $et) {
                $pdf->Cell(0, 7, "{$num}. {$et['etapa']}", 0, 1);
                $num++;
            }

            // Unir cada documento de etapa
            foreach ($etapas as $et) {
                $pdfTmp = $this->etapaComoPdf((int) $et['doc_id'], $et['ruta_final'], $proyecto);
                if ($pdfTmp === null) {
                    continue;
                }
                $paginas = $pdf->setSourceFile($pdfTmp);
                for ($i = 1; $i <= $paginas; $i++) {
                    $tpl = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);

                    // Escalar proporcionalmente para encajar en el ancho útil de A4 (210mm - márgenes)
                    $anchoUtil = 190;
                    $escala = $size['width'] > 0 ? $anchoUtil / $size['width'] : 1;
                    $ancho = $size['width'] * $escala;
                    $alto = $size['height'] * $escala;

                    $pdf->AddPage($alto > $ancho ? 'P' : 'L');
                    $pdf->useTemplate($tpl, ($pdf->GetPageWidth() - $ancho) / 2, 10, $ancho, $alto);
                }
                @unlink($pdfTmp);
            }

            $pdf->Output('F', $rutaSalida);
        } catch (\Throwable $e) {
            error_log('Consolidado PDF error: ' . $e->getMessage());
            return null;
        }

        return file_exists($rutaSalida) ? $rutaSalida : null;
    }

    /** Devuelve una ruta a un PDF individual de la etapa (convierte si hace falta) */
    private function etapaComoPdf(int $docId, string $rutaFinal, array $proyecto): ?string
    {
        $ruta = $this->resolverRuta($rutaFinal);
        if ($ruta === null) {
            return null;
        }

        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            return $ruta;
        }

        // Convertir a PDF con LibreOffice (txt/doc/docx/odt/rtf)
        $pdf = OfficeConverter::toPdf($ruta);
        if ($pdf && file_exists($pdf)) {
            return $pdf;
        }

        return null;
    }

    // ---------- Word (.docx) ----------

    private function docxUnificado(array $etapas, array $proyecto, string $tituloPortada): ?string
    {
        $dirSalida = UPLOAD_CONSOLIDADOS;
        if (!is_dir($dirSalida)) {
            @mkdir($dirSalida, 0777, true);
        }

        $prefijo = $tituloPortada === 'PERFIL DEL PROYECTO' ? 'perfil' : 'proyecto';
        $nombreSalida = $prefijo . '_p' . $proyecto['id'] . '.docx';
        $rutaSalida = $dirSalida . '/' . $nombreSalida;

        if (!class_exists('ZipArchive')) {
            return null;
        }

        try {
            $parrafos = [];
            $parrafos[] = ['TITLE', mb_strtoupper((string) $tituloPortada, 'UTF-8')];
            $parrafos[] = ['H1', 'Código: ' . ($proyecto['codigo'] ?? '')];
            $parrafos[] = ['P', ($proyecto['nombre'] ?? '')];
            $parrafos[] = ['SPACER', ''];

            foreach ($etapas as $et) {
                $texto = $this->textoEtapa((int) $et['doc_id'], $et['ruta_final']);
                $parrafos[] = ['H1', $et['etapa']];
                foreach (explode("\n", $texto) as $linea) {
                    $l = trim($linea);
                    if ($l !== '') {
                        $parrafos[] = ['P', $l];
                    }
                }
                $parrafos[] = ['SPACER', ''];
            }

            $xmlBody = '';
            foreach ($parrafos as [$tipo, $contenido]) {
                $xmlBody .= $this->parrafoXml($tipo, $contenido);
            }

            $contenido = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' .
                $xmlBody .
                '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1800"/></w:sectPr></w:body></w:document>';

            $zip = new \ZipArchive();
            if ($zip->open($rutaSalida, \ZipArchive::CREATE) !== true) {
                return null;
            }

            $zip->addFromString('[Content_Types].xml',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
                '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
                '<Default Extension="xml" ContentType="application/xml"/>' .
                '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>' .
                '</Types>');

            $zip->addFromString('_rels/.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>' .
                '</Relationships>');

            $zip->addFromString('word/_rels/document.xml.rels',
                '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>');

            $zip->addFromString('word/document.xml', $contenido);
            $zip->close();
        } catch (\Throwable $e) {
            return null;
        }

        return file_exists($rutaSalida) ? $rutaSalida : null;
    }

    private function parrafoXml(string $tipo, string $texto): string
    {
        $t = mb_strtoupper($texto, 'UTF-8');
        $xml = fn ($runs) => '<w:p>' . $runs . '</w:p>';
        $escape = fn ($s) => htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return match ($tipo) {
            'TITLE' => $xml('<w:r><w:rPr><w:b/><w:sz w:val="44"/></w:rPr><w:t>' . $escape($t) . '</w:t></w:r>'),
            'H1' => $xml('<w:r><w:rPr><w:b/><w:sz w:val="28"/></w:rPr><w:t>' . $escape($t) . '</w:t></w:r>'),
            'SPACER' => $xml(''),
            default => $xml('<w:r><w:t xml:space="preserve">' . $escape($texto) . '</w:t></w:r>'),
        };
    }

    /** Extrae texto legible de un documento final de etapa */
    private function textoEtapa(int $docId, string $rutaFinal): string
    {
        $ruta = $this->resolverRuta($rutaFinal);
        if (!$ruta) {
            return '';
        }
        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        if ($ext === 'txt') {
            return file_get_contents($ruta) ?: '';
        }
        if (in_array($ext, ['docx', 'odt'], true)) {
            return $this->textoOffice($ruta, $ext);
        }
        // PDF y doc/rtf no tienen texto simple; se incluye referencia
        return '[Documento ' . strtoupper($ext) . ': revisar adjunto como PDF en el consolidado]';
    }

    private function resolverRuta(string $rutaFinal): ?string
    {
        if ($rutaFinal === '') {
            return null;
        }
        if (str_contains($rutaFinal, '/') || str_contains($rutaFinal, '\\')) {
            return file_exists($rutaFinal) ? $rutaFinal : null;
        }
        foreach ([UPLOAD_FINALES, UPLOAD_DOCUMENTOS] as $dir) {
            $c = $dir . '/' . basename($rutaFinal);
            if (file_exists($c)) {
                return $c;
            }
        }
        return null;
    }

    private function textoOffice(string $ruta, string $ext): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }
        $zip = new \ZipArchive();
        if ($zip->open($ruta) !== true) {
            return '';
        }
        $archivoInterno = $ext === 'docx' ? 'word/document.xml' : 'content.xml';
        $xml = $zip->getFromName($archivoInterno);
        $zip->close();
        if ($xml === false) {
            return '';
        }

        $parrafos = [];
        if ($ext === 'docx') {
            preg_match_all('#<w:p(?:\s[^>]*)?>(.*?)</w:p>#s', $xml, $m);
            foreach ($m[1] ?? [] as $p) {
                preg_match_all('#<w:t(?:\s[^>]*)?>(.*?)</w:t>#s', $p, $tm);
                $parrafos[] = implode('', $tm[1] ?? []);
            }
        } else {
            preg_match_all('#<text:p(?:\s[^>]*)?>(.*?)</text:p>#s', $xml, $m);
            foreach ($m[1] ?? [] as $p) {
                $parrafos[] = strip_tags($p);
            }
        }
        return implode("\n", array_filter(array_map('trim', $parrafos)));
    }
}
