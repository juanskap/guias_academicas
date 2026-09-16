<?php

namespace App\Models;

use App\Core\Model;

/**
 * Anotaciones (resaltados amarillos) sobre un documento, ligadas opcionalmente
 * a una observación. Guardan rectángulos normalizados (0..1) por página para
 * poder repintarlos en la vista previa independientemente del zoom.
 */
class Anotacion extends Model
{
    protected string $table = 'anotaciones';

    /** Resaltados de un documento (con rects decodificados) */
    public function porDocumento(int $documentoId): array
    {
        $rows = $this->query(
            "SELECT id, observacion_id, usuario_id, pagina, rects, texto, color
             FROM anotaciones WHERE documento_id = ? ORDER BY pagina, id",
            [$documentoId]
        );

        $out = [];
        foreach ($rows as $r) {
            $rects = json_decode((string) $r['rects'], true);
            if (!is_array($rects) || !$rects) {
                continue;
            }
            $out[] = [
                'id' => (int) $r['id'],
                'observacion_id' => $r['observacion_id'] !== null ? (int) $r['observacion_id'] : null,
                'usuario_id' => (int) $r['usuario_id'],
                'pagina' => (int) $r['pagina'],
                'rects' => $rects,
                'texto' => $r['texto'],
                'color' => $r['color'],
            ];
        }
        return $out;
    }

    /** Guarda un resaltado (una página con sus rectángulos) */
    public function guardar(int $documentoId, ?int $observacionId, int $usuarioId, int $pagina, array $rects, ?string $texto, string $color = 'amarillo'): int
    {
        return $this->create([
            'documento_id' => $documentoId,
            'observacion_id' => $observacionId,
            'usuario_id' => $usuarioId,
            'pagina' => $pagina,
            'rects' => json_encode($rects),
            'texto' => $texto,
            'color' => $color,
        ]);
    }
}
