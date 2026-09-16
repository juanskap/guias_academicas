<?php

namespace App\Models;

use App\Core\Model;

class PeriodoAcademico extends Model
{
    protected string $table = 'periodos_academicos';

    /** Periodo activo actual (si existe) */
    public function activo(): ?array
    {
        return $this->firstWhere('activo', 1);
    }

    /** Periodos con total de proyectos asignados */
    public function allConProyectos(): array
    {
        return $this->query(
            "SELECT pa.*, COUNT(p.id) AS total_proyectos
             FROM periodos_academicos pa
             LEFT JOIN proyectos p ON p.periodo_id = pa.id
             GROUP BY pa.id
             ORDER BY pa.fecha_inicio DESC"
        );
    }
}