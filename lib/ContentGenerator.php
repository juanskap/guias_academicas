<?php
require_once __DIR__ . '/OllamaClient.php';

class ContentGenerator {
    private $ollama;

    public function __construct(OllamaClient $client) {
        $this->ollama = $client;
    }

    public function generateSection($type, $section, $materia, $unidad, $titulo, $referencias = [], $guiaContent = []) {
        $ctx = "Materia: $materia\nUnidad: $unidad\nTema: $titulo";

        // Build guia context for anexo sections (resumido para no saturar)
        $guiaCtx = '';
        if ($type === 'anexo' && !empty($guiaContent)) {
            $guiaCtx = "\n\nReferencia de la Guía: Objetivo: " . ($guiaContent['objetivo_general'] ?? '') . "\n";
            if ($section === 'descripcion') $guiaCtx .= "Procedimiento: " . ($guiaContent['procedimiento'] ?? '') . "\n";
            if ($section === 'recomendaciones') $guiaCtx .= "Evaluación: " . ($guiaContent['evaluacion'] ?? '') . "\n";
            if ($section === 'conclusiones') $guiaCtx .= "Objetivos: " . ($guiaContent['objetivos_especificos'] ?? '') . "\n";
            if ($section === 'introduccion') $guiaCtx .= "Fundamentos: " . ($guiaContent['fundamentos'] ?? '') . "\n";
        }

        $system = "Eres un asistente académico experto en normas APA 7ª edición. Usa tono formal. IMPORTANTE: NO uses asteriscos, negritas, cursivas, ni ningún formato markdown. Escribe solo texto plano. No uses etiquetas ni prefijos como 'Respuesta:'. Responde ÚNICAMENTE el contenido solicitado.";

        $fuentesTexto = '';
        if (!empty($referencias)) {
            $fuentesTexto = "\n\nFuentes académicas REALES (OpenAlex) que DEBES usar:\n";
            foreach ($referencias as $i => $ref) {
                $fuentesTexto .= ($i + 1) . ". " . ($ref['autores'] ?? '') . " (" . ($ref['anio'] ?? '') . "). " . ($ref['titulo'] ?? '') . ". " . ($ref['fuente'] ?? '');
                if (!empty($ref['doi'])) $fuentesTexto .= " https://doi.org/" . $ref['doi'];
                $fuentesTexto .= "\n";
            }
            $fuentesTexto .= "\nIncorpora estas fuentes en el formato correcto. Si no hay fuentes, genera referencias relacionadas al tema.";
        }

        $prompts = [
            'guia' => [
                'fundamentos' => "{$ctx}\n\nRedacta 3 párrafos como marco teórico sobre \"$titulo\". Cada párrafo: un concepto con su cita APA (Apellido, año).{$fuentesTexto}",
                'objetivo_general' => "{$ctx}\n\nRedacta UN objetivo general para una práctica sobre \"$titulo\". empezando con verbo en infinitivo.",
                'objetivos_especificos' => "{$ctx}\n\nRedacta 3 objetivos específicos numerados para \"$titulo\". Cada uno empezando con verbo en infinitivo.",
                'preparacion_previa' => "{$ctx}\n\nEnumera SOLO 4 conocimientos previos para realizar la práctica sobre \"$titulo\". Sin referencias, solo lista conceptos y habilidades técnicas.",
                'procedimiento' => "{$ctx}\n\nProcedimiento detallado con el siguiente formato EXACTO:\n\nDuración: X horas\n\nActividad 1. [Nombre de la actividad]\n\nPaso 1. [Nombre del paso]\n[Explicación detallada de cómo realizar este paso, con acciones concretas enumeradas]\n[Acción específica 1]\n[Acción específica 2]\n[Acción específica 3]\n\nPaso 2. [Nombre del paso]\n[Explicación detallada]\n[Acción 1]\n[Acción 2]\n[Acción 3]\n\nActividad 2. [Nombre de la actividad]\n\nPaso 1. [Nombre del paso]\n[Explicación detallada]\n[Acción 1]\n[Acción 2]\n\n... y así sucesivamente.\n\n(3-4 actividades, cada actividad con 2-4 pasos. Cada paso debe tener una explicación clara de CÓMO se realiza, no solo el título. Incluye un ejercicio guiado completo)\n\nMateriales y equipos\nObligatorios: Laboratorio, Internet, Proyector, Pizarra, Marcadores, E-books.\nAdicionales: [solo los específicos para \"$titulo\"]",
                'evaluacion' => "{$ctx}\n\nLista criterios de evaluación para \"$titulo\": comprensión (30%), aplicación (40%), resultados (20%), equipo (10%). Máximo 1 línea cada uno.",
            ],
            'anexo' => [
                'introduccion' => "{$ctx}{$guiaCtx}\n\nEscribe 2 párrafos cortos de introducción del Anexo, basada en la Guía de Prácticas. Contexto y aplicación profesional.",
                'descripcion' => "{$ctx}{$guiaCtx}\n\nDescribe en 3 párrafos el desarrollo de la práctica, basado en el procedimiento de la Guía. Actividades y ejecución.",
                'metodologia' => "{$ctx}{$guiaCtx}\n\nDescribe en 2 párrafos la metodología usada, basada en los fundamentos de la Guía. Enfoque, técnicas, procedimiento.",
                'resultados' => "{$ctx}{$guiaCtx}\n\nDescribe en 2 párrafos los resultados, relacionados con los objetivos de la Guía. Qué aprendió y competencias desarrolladas.",
                'conclusiones' => "{$ctx}{$guiaCtx}\n\n3 conclusiones numeradas del Anexo basadas en los objetivos y contenido de la Guía. Máximo 2 líneas c/u.",
                'recomendaciones' => "{$ctx}{$guiaCtx}\n\n3 recomendaciones numeradas para mejorar, basadas en la evaluación de la Guía. Máximo 2 líneas c/u.",
                'bibliografia' => "{$ctx}{$fuentesTexto}\n\nGenera 3 referencias APA 7ª edición RECIENTES (máximo 3-4 años de antigüedad) sobre \"$titulo\". Prioriza fuentes actuales. Formato: Apellido, Inicial. (Año). Título. Fuente.",
            ]
        ];

        $prompt = $prompts[$type][$section] ?? "{$ctx}\n\nGenera contenido académico sobre \"$titulo\".";
        $raw = $this->ollama->generate($prompt, $system);
        return $this->cleanContent($raw);
    }

    public function getAllGuiaContent($materia, $unidad, $titulo) {
        $sections = ['fundamentos','objetivo_general','objetivos_especificos','preparacion_previa','procedimiento','evaluacion'];
        $data = [
            'titulo' => $titulo, 'asignatura' => $materia,
            'carrera' => 'Tecnología Superior en Desarrollo de Software',
            'nivel' => 'IV', 'docente' => 'Ing. Diana Ramírez Garófalo',
            'nro_practica' => '1', 'horas' => '3'
        ];
        foreach ($sections as $s) {
            $data[$s] = $this->generateSection('guia', $s, $materia, $unidad, $titulo);
        }
        return $data;
    }

    public function getAllAnexoContent($materia, $unidad, $titulo, $guiaContent = []) {
        $sections = ['introduccion','descripcion','metodologia','resultados','conclusiones','recomendaciones','bibliografia'];
        $data = [
            'titulo' => $titulo, 'asignatura' => $materia,
            'carrera' => 'Tecnología Superior en Desarrollo de Software'
        ];
        foreach ($sections as $s) {
            $data[$s] = $this->generateSection('anexo', $s, $materia, $unidad, $titulo, [], $guiaContent);
        }
        return $data;
    }

    private function cleanContent($text) {
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/__(.+?)__/', '$1', $text);
        $text = preg_replace('/~~(.+?)~~/', '$1', $text);
        $text = preg_replace('/`(.+?)`/', '$1', $text);
        $text = preg_replace('/^#+\s*/m', '', $text);
        $text = preg_replace('/^>\s*/m', '', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);
        return $text;
    }
}
