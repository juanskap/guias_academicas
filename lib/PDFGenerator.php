<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generarPDF($contenido, $outputPath) {
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new Dompdf($options);

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
    $html .= '<style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12pt; line-height: 1.5; color: #333; margin: 40px; }
        h1 { font-size: 16pt; text-align: center; margin-bottom: 20px; color: #1a1a1a; }
        h2 { font-size: 13pt; color: #2563eb; margin-top: 18px; margin-bottom: 6px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
        .data-field { font-size: 11pt; margin: 3px 0; }
        .data-field strong { color: #475569; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 10pt; }
        table td { border: 1px solid #cbd5e1; padding: 6px 10px; }
        table td:first-child { font-weight: 600; background: #f8fafc; width: 25%; }
        p { margin: 4px 0; }
        .firma-table td { text-align: center; padding: 20px 10px; }
    </style></head><body>';

    $html .= $contenido;
    $html .= '</body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    file_put_contents($outputPath, $dompdf->output());
    return true;
}

function contenidoGuiaToHTML($data) {
    $h = '<h1>GUÍA DE PRÁCTICAS EN EL ENTORNO ACADÉMICO</h1>';
    $h .= '<h2>Datos Informativos</h2>';
    $h .= '<div class="data-field"><strong>Carrera:</strong> ' . htmlspecialchars($data['carrera'] ?? 'Tecnología Superior en Desarrollo de Software') . '</div>';
    $h .= '<div class="data-field"><strong>Asignatura:</strong> ' . htmlspecialchars($data['asignatura'] ?? '') . '</div>';
    $h .= '<div class="data-field"><strong>Título de la práctica:</strong> ' . htmlspecialchars($data['titulo'] ?? '') . '</div>';
    $h .= '<div class="data-field"><strong>Nivel Académico:</strong> IV</div>';
    $h .= '<div class="data-field"><strong>Docente:</strong> Ing. Diana Ramírez Garófalo</div>';
    $h .= '<div class="data-field"><strong>Nro. de práctica:</strong> 1 &bull; <strong>Tiempo:</strong> 3 horas</div>';

    $h .= '<h2>Fundamentos del desarrollo</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['fundamentos'] ?? '')) . '</p>';

    $h .= '<h2>Objetivo General</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['objetivo_general'] ?? '')) . '</p>';

    $h .= '<h2>Objetivos específicos</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['objetivos_especificos'] ?? '')) . '</p>';

    $h .= '<h2>Preparación previa</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['preparacion_previa'] ?? '')) . '</p>';

    $h .= '<h2>Procedimiento</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['procedimiento'] ?? '')) . '</p>';

    $h .= '<h2>Materiales y equipos</h2>';
    $h .= '<p>Laboratorio tecnológico, Internet, Proyector, Pizarra, Marcadores, E-books</p>';

    $h .= '<h2>Normas de seguridad</h2>';
    $h .= '<p>Siempre que se trabaje en el laboratorio, se debe tener en cuenta las siguientes normas de seguridad:</p>';
    $h .= '<p>• Está estrictamente prohibido comer o beber en el laboratorio<br>';
    $h .= '• Uso adecuado de sillas y mesas<br>';
    $h .= '• Manejo adecuado de equipos y accesorios del laboratorio<br>';
    $h .= '• Guardar y respaldar datos<br>';
    $h .= '• Conocer las rutas de evacuación<br>';
    $h .= '• Uso responsable de internet y redes<br>';
    $h .= '• No obstruir pasillos y salidas de emergencia</p>';

    $h .= '<h2>Evaluación del aprendizaje</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['evaluacion'] ?? '')) . '</p>';

    $h .= '<h2>Firmas de responsabilidad</h2>';
    $h .= '<table class="firma-table"><tr>';
    $h .= '<td><strong>Elaborado por:</strong><br>Ing. Diana Ramírez Garófalo<br>Docente</td>';
    $h .= '<td><strong>Revisado por:</strong><br>Lcda. Diana Alegría Camino<br>Responsable de Prácticas</td>';
    $h .= '<td><strong>Aprobado por:</strong><br>Ing. Maribel Fierro Montero<br>Coordinación de Carrera</td>';
    $h .= '</tr></table>';

    return $h;
}

function contenidoAnexoToHTML($data) {
    $h = '<h1>Informe de las prácticas de experimentación y aplicación de los aprendizajes</h1>';

    $h .= '<h2>Datos Informativos</h2>';
    $h .= '<table><tr><td>Carrera</td><td>' . htmlspecialchars($data['carrera'] ?? '') . '</td></tr>';
    $h .= '<tr><td>Asignatura</td><td>' . htmlspecialchars($data['asignatura'] ?? '') . '</td></tr>';
    $h .= '<tr><td>Nivel académico</td><td>IV</td></tr>';
    $h .= '<tr><td>Docente</td><td>Ing. Diana Ramírez Garófalo</td></tr>';
    $h .= '<tr><td>Título de la práctica</td><td>' . htmlspecialchars($data['titulo'] ?? '') . '</td></tr>';
    $h .= '<tr><td>No. de práctica / horas</td><td>1 / 3</td></tr></table>';

    $h .= '<h2>Introducción</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['introduccion'] ?? '')) . '</p>';

    $h .= '<h2>Objetivo de la práctica</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['objetivo_general'] ?? '')) . '</p>';

    $h .= '<h2>Descripción del desarrollo</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['descripcion'] ?? '')) . '</p>';

    $h .= '<h2>Metodología</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['metodologia'] ?? '')) . '</p>';

    $h .= '<h2>Resultados obtenidos</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['resultados'] ?? '')) . '</p>';

    $h .= '<h2>Conclusiones</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['conclusiones'] ?? '')) . '</p>';

    $h .= '<h2>Recomendaciones</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['recomendaciones'] ?? '')) . '</p>';

    $h .= '<h2>Bibliografía</h2>';
    $h .= '<p>' . nl2br(htmlspecialchars($data['bibliografia'] ?? '')) . '</p>';

    return $h;
}
