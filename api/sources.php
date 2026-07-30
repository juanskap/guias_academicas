<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$query = $_GET['q'] ?? '';
if (empty($query)) {
    echo json_encode(['error' => 'No se proporcionó término de búsqueda']);
    exit;
}

$limite = min((int)($_GET['limite'] ?? 10), 25);
$resultados = [];

// OpenAlex
$url = 'https://api.openalex.org/works?search=' . urlencode($query) . '&per_page=' . $limite;
$context = stream_context_create([
    'http' => ['method' => 'GET', 'header' => "User-Agent: DocGenGuia/1.0\r\n", 'timeout' => 10]
]);
$response = @file_get_contents($url, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if (isset($data['results'])) {
        foreach ($data['results'] as $work) {
            $autores = [];
            if (isset($work['authorships'])) {
                foreach ($work['authorships'] as $auth) {
                    if (isset($auth['author']['display_name'])) {
                        $autores[] = $auth['author']['display_name'];
                    }
                }
            }
            $resultados[] = [
                'titulo' => $work['title'] ?? 'Sin título',
                'autores' => implode(', ', $autores),
                'anio' => substr($work['publication_year'] ?? '', 0, 4),
                'fuente' => $work['primary_location']['source']['display_name'] ?? $work['primary_location']['landing_page_url'] ?? '',
                'doi' => $work['doi'] ?? '',
                'url' => $work['primary_location']['landing_page_url'] ?? '',
            ];
        }
    }
}

// Fallback Crossref
if (empty($resultados)) {
    $url = 'https://api.crossref.org/works?query=' . urlencode($query) . '&rows=' . $limite;
    $context = stream_context_create([
        'http' => ['method' => 'GET', 'header' => "User-Agent: DocGenGuia/1.0 (mailto:docgen@example.com)\r\n", 'timeout' => 10]
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (isset($data['message']['items'])) {
            foreach ($data['message']['items'] as $item) {
                $autores = [];
                if (isset($item['author'])) {
                    foreach ($item['author'] as $auth) {
                        if (isset($auth['given'], $auth['family'])) {
                            $autores[] = $auth['family'] . ', ' . $auth['given'];
                        }
                    }
                }
                $resultados[] = [
                    'titulo' => $item['title'][0] ?? 'Sin título',
                    'autores' => implode(', ', $autores),
                    'anio' => substr($item['published-print']['date-parts'][0][0] ?? $item['issued']['date-parts'][0][0] ?? '', 0, 4),
                    'fuente' => $item['container-title'][0] ?? '',
                    'doi' => $item['DOI'] ?? '',
                    'url' => $item['URL'] ?? ''
                ];
            }
        }
    }
}

echo json_encode($resultados);
