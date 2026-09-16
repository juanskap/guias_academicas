<?php

namespace App\Models;

use App\Core\Model;

/**
 * Configuración general editable desde el panel (tabla configuraciones).
 * Almacena pares clave → valor.
 */
class Configuracion extends Model
{
    protected string $table = 'configuraciones';

    /** Devuelve el valor de una clave con un valor por defecto si no existe */
    public function get(string $clave, string $default = ''): string
    {
        $fila = $this->firstWhere('clave', $clave);
        return $fila ? (string) ($fila['valor'] ?? '') : $default;
    }

    /** Devuelve el valor como entero */
    public function getInt(string $clave, int $default = 0): int
    {
        return (int) $this->get($clave, (string) $default);
    }

    /** Crea o actualiza el valor de una clave */

    public function set(string $clave, string $valor, ?string $descripcion = null): void
    {
        $fila = $this->firstWhere('clave', $clave);
        $datos = ['valor' => $valor];
        if ($descripcion !== null) {
            $datos['descripcion'] = $descripcion;
        }
        if ($fila) {
            $this->update((int) $fila['id'], $datos);
        } else {
            $datos['clave'] = $clave;
            $this->create($datos);
        }
    }
}