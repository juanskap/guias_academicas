<?php
require_once __DIR__ . '/../vendor/autoload.php';
if (!defined('PROJECT_ROOT')) define('PROJECT_ROOT', __DIR__ . '/..');

class DocumentGenerator {

public function generateGuia($templateSrc, $outputPath, $data, $materia, $unidad, $titulo) {
    $templatePath = PROJECT_ROOT . '/templates/guia_base.docx';
    if (!file_exists($templatePath)) copy($templateSrc, $templatePath);
    copy($templatePath, $outputPath);

    $zip = new ZipArchive();
    if ($zip->open($outputPath) !== true) throw new Exception("Cannot open guia docx");

    $content = $zip->getFromName('word/document.xml');
    $dom = new DOMDocument();
    $dom->loadXML($content, LIBXML_NOBLANKS);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $this->replaceSectionContent($dom, $xpath, $data, 'Carrera:', 'carrera');
    $this->replaceSectionContent($dom, $xpath, $data, 'Asignatura:', 'asignatura');
    $this->replaceSectionContent($dom, $xpath, $data, 'Título de la práctica:', 'titulo');
    $this->replaceSectionContent($dom, $xpath, $data, 'Fundamentos del desarrollo', 'fundamentos');
    $this->replaceSectionContent($dom, $xpath, $data, 'Objetivo General', 'objetivo_general');
    $this->replaceSectionContent($dom, $xpath, $data, 'Objetivos específicos', 'objetivos_especificos');
    $this->replaceSectionContent($dom, $xpath, $data, 'Preparación previa', 'preparacion_previa');
    $this->replaceSectionContent($dom, $xpath, $data, 'Procedimiento a emplear', 'procedimiento');
    $this->replaceSectionContent($dom, $xpath, $data, 'Evaluación del aprendizaje', 'evaluacion');

    $zip->addFromString('word/document.xml', $dom->saveXML());
    $zip->close();
}

public function generateAnexo($templateSrc, $outputPath, $data, $materia, $unidad, $titulo) {
    $templatePath = PROJECT_ROOT . '/templates/anexo_base.docx';
    if (!file_exists($templatePath)) copy($templateSrc, $templatePath);
    copy($templatePath, $outputPath);
    $data['unidad'] = $unidad;

    $zip = new ZipArchive();
    if ($zip->open($outputPath) !== true) throw new Exception("Cannot open anexo docx");

    $content = $zip->getFromName('word/document.xml');
    $dom = new DOMDocument();
    $dom->loadXML($content, LIBXML_NOBLANKS);
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

    $this->fillAnexoTable($dom, $xpath, $data);
    $this->replaceSectionContent($dom, $xpath, $data, 'Introducción', 'introduccion');
    $this->replaceSectionContent($dom, $xpath, $data, 'Objetivo de la práctica', 'objetivo_general');
    $this->replaceSectionContent($dom, $xpath, $data, 'Descripción del desarrollo', 'descripcion');
    $this->replaceSectionContent($dom, $xpath, $data, 'Metodología', 'metodologia');
    $this->replaceSectionContent($dom, $xpath, $data, 'Resultados obtenidos', 'resultados');
    $this->replaceSectionContent($dom, $xpath, $data, 'Conclusiones', 'conclusiones');
    $this->replaceSectionContent($dom, $xpath, $data, 'Recomendaciones', 'recomendaciones');
    $this->replaceSectionContent($dom, $xpath, $data, 'Bibliografía', 'bibliografia');

    $zip->addFromString('word/document.xml', $dom->saveXML());
    $zip->close();
}

private function replaceSectionContent($dom, $xpath, $data, $headerText, $dataKey) {
    // Use paragraph-level text matching, handles split w:t nodes and encoding
    $headerNodes = $xpath->query("//w:p[contains(., '$headerText')]");
    if ($headerNodes->length === 0) return;

    $headerNode = $headerNodes->item(0);
    $nextNode = $headerNode->nextSibling;

    while ($nextNode && $this->isBlankParagraph($dom, $nextNode)) {
        $nextNode = $nextNode->nextSibling;
    }

    if (!$nextNode) return;

    $content = $data[$dataKey] ?? '';
    if (trim($content) === '') return;

    $contentLines = explode("\n", $content);
    $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    $first = true;
    $current = $nextNode;
    foreach ($contentLines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if ($first) {
            $this->replaceParagraphText($dom, $current, $line);
            $toRemove = $current->nextSibling;
            while ($toRemove && $this->isDotsOrDashesParagraph($dom, $toRemove)) {
                $next = $toRemove->nextSibling;
                $toRemove->parentNode->removeChild($toRemove);
                $toRemove = $next;
            }
            $first = false;
        } else {
            $newP = $dom->createElementNS($w, 'w:p');
            $newR = $dom->createElementNS($w, 'w:r');
            $newT = $dom->createElementNS($w, 'w:t', htmlspecialchars($line, ENT_XML1, 'UTF-8'));
            $newT->setAttribute('xml:space', 'preserve');
            $newR->appendChild($newT);
            $newP->appendChild($newR);
            $current->parentNode->insertBefore($newP, $current->nextSibling);
            $current = $newP;
        }
    }
}

private function replaceParagraphText($dom, $pNode, $newText) {
    if (!$pNode || $pNode->localName !== 'p') return;
    $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $runs = $pNode->getElementsByTagNameNS($w, 'r');
    if ($runs->length > 0) {
        $run = $runs->item(0);
        $texts = $run->getElementsByTagNameNS($w, 't');
        if ($texts->length > 0) {
            $texts->item(0)->textContent = htmlspecialchars($newText, ENT_XML1, 'UTF-8');
            for ($i = $texts->length - 1; $i > 0; $i--) {
                $texts->item($i)->parentNode->removeChild($texts->item($i));
            }
        } else {
            $t = $dom->createElementNS($w, 'w:t', htmlspecialchars($newText, ENT_XML1, 'UTF-8'));
            $t->setAttribute('xml:space', 'preserve');
            $run->appendChild($t);
        }
        for ($i = $runs->length - 1; $i > 0; $i--) {
            $runs->item($i)->parentNode->removeChild($runs->item($i));
        }
    } else {
        $run = $dom->createElementNS($w, 'w:r');
        $t = $dom->createElementNS($w, 'w:t', htmlspecialchars($newText, ENT_XML1, 'UTF-8'));
        $t->setAttribute('xml:space', 'preserve');
        $run->appendChild($t);
        $pNode->appendChild($run);
    }
}

private function isBlankParagraph($dom, $node) {
    if ($node->nodeType !== XML_ELEMENT_NODE || $node->localName !== 'p') return true;
    $text = '';
    foreach ($node->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $t) {
        $text .= $t->textContent;
    }
    return trim($text) === '';
}

private function isDotsOrDashesParagraph($dom, $node) {
    if ($node->nodeType !== XML_ELEMENT_NODE || $node->localName !== 'p') return false;
    $text = '';
    foreach ($node->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 't') as $t) {
        $text .= $t->textContent;
    }
    $trimmed = trim($text);
    if ($trimmed === '') return false;
    return preg_match('/^[\.\,\-\—\…\s]+$/', $trimmed) === 1;
}

private function fillAnexoTable($dom, $xpath, $data) {
    $w = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $mappings = [
        'Carrera:' => $data['carrera'] ?? 'Tecnología Superior en Desarrollo de Software',
        'Asignatura:' => $data['asignatura'] ?? '',
        'Nivel acad' => $data['nivel'] ?? 'IV',
        'Docente:' => $data['docente'] ?? 'Ing. Diana Ramírez Garófalo',
        'Título de la pr' => $data['titulo'] ?? '',
        'No. de pr' => ($data['nro_practica'] ?? '1'),
        'de horas:' => ($data['horas'] ?? '3'),
    ];

    foreach ($mappings as $search => $replacement) {
        // Search within paragraph text (concatenated) instead of individual w:t
        $cells = $xpath->query("//w:tc[.//w:p[contains(., '$search')]]");
        if ($cells->length === 0) continue;

        $tc = $cells->item(0);
        $nextCell = $xpath->query("following-sibling::w:tc", $tc);
        if ($nextCell->length === 0) continue;

        $targetCell = $nextCell->item(0);
        $runs = $targetCell->getElementsByTagNameNS($w, 'r');
        if ($runs->length > 0) {
            $run = $runs->item(0);
            $texts = $run->getElementsByTagNameNS($w, 't');
            if ($texts->length > 0) {
                $texts->item(0)->textContent = $replacement;
                for ($i = $texts->length - 1; $i > 0; $i--) {
                    $texts->item($i)->parentNode->removeChild($texts->item($i));
                }
            } else {
                $t = $dom->createElementNS($w, 'w:t', $replacement);
                $t->setAttribute('xml:space', 'preserve');
                $run->appendChild($t);
            }
            for ($i = $runs->length - 1; $i > 0; $i--) {
                $runs->item($i)->parentNode->removeChild($runs->item($i));
            }
        } else {
            $p = $targetCell->getElementsByTagNameNS($w, 'p');
            $run = $dom->createElementNS($w, 'w:r');
            $t = $dom->createElementNS($w, 'w:t', $replacement);
            $t->setAttribute('xml:space', 'preserve');
            $run->appendChild($t);
            if ($p->length > 0) {
                $p->item(0)->appendChild($run);
            } else {
                $p = $dom->createElementNS($w, 'w:p');
                $p->appendChild($run);
                $targetCell->appendChild($p);
            }
        }
    }
}

}
