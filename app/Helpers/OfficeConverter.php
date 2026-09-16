<?php

namespace App\Helpers;

/**
 * Convierte documentos Office (docx/odt/doc/rtf) a PDF usando LibreOffice
 * instalado en el servidor. Se genera una vez y se guarda en una caché
 * para no reconvertir en cada visita a la vista previa.
 */
class OfficeConverter
{
    private static ?string $binario = null;
    private static ?string $perfil = null;

    /** Busca soffice.exe en rutas habituales (solo la primera vez) */
    private static function binario(): ?string
    {
        if (self::$binario !== null) {
            return self::$binario;
        }

        $candidatos = [
            'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
            'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            'C:\\LibreOffice\\program\\soffice.exe',
        ];

        foreach ($candidatos as $c) {
            if (is_file($c)) {
                return self::$binario = $c;
            }
        }

        // Buscar en el PATH
        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where soffice 2>NUL' : 'which soffice';
        $salida = [];
        exec($cmd, $salida);
        if (!empty($salida[0]) && is_file($salida[0])) {
            return self::$binario = trim($salida[0]);
        }

        return self::$binario = '';
    }

    /** Perfil propio de LibreOffice para evitar colisiones con otras instancias */
    private static function perfil(): string
    {
        if (self::$perfil === null) {
            $dir = UPLOAD_PATH . '/libreoffice_profile';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $normalizado = str_replace('\\', '/', $dir);
            self::$perfil = 'file:///' . ltrim($normalizado, '/');
        }
        return self::$perfil;
    }

    /** Carpeta de caché de PDFs generados */
    private static function cacheDir(): string
    {
        $dir = UPLOAD_PATH . '/vistaprevia';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    /**
     * Convierte el archivo dado a PDF y devuelve la ruta del PDF resultante.
     * Si ya existe una conversión válida (según fecha/mt de la fuente) usa la caché.
     *
     * @return string|null Ruta absoluta del PDF, o null si falla.
     */
    public static function toPdf(string $src): ?string
    {
        $bin = self::binario();
        if ($bin === '' || !is_file($src)) {
            return null;
        }

        $mt = @filemtime($src);
        $hash = md5($src . $mt);
        $destino = self::cacheDir() . '/' . $hash . '.pdf';

        if (is_file($destino) && filesize($destino) > 0) {
            return $destino;
        }

        // Carpeta temporal (soffice escribe el PDF con el mismo nombre)
        $temporalTrabajo = temp_dir('soffice_' . bin2hex(random_bytes(4)));
        if (!is_dir($temporalTrabajo)) {
            @mkdir($temporalTrabajo, 0777, true);
        }

        $cmd = '"' . $bin . '"';
        $cmd .= ' --headless --norestore --nolockcheck';
        $cmd .= ' -env:UserInstallation=' . self::perfil();
        $cmd .= ' --convert-to pdf --outdir "' . $temporalTrabajo . '"';
        $cmd .= ' "' . $src . '"';

        $salida = [];
        $codigo = 0;
        @exec($cmd . ' 2>&1', $salida, $codigo);

        $pdfTemp = $temporalTrabajo . '/' . basename($src, '.' . pathinfo($src, PATHINFO_EXTENSION)) . '.pdf';

        if (is_file($pdfTemp)) {
            if (!@rename($pdfTemp, $destino)) {
                @copy($pdfTemp, $destino);
            }
        }

        // Limpieza de la carpeta temporal
        @unlink($pdfTemp);
        @rmdir($temporalTrabajo);

        return (is_file($destino) && filesize($destino) > 0) ? $destino : null;
    }
}