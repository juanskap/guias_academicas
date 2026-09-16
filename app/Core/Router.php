<?php

namespace App\Core;

/**
 * Router: enruta la URL a un controlador y método.
 *
 * Formato de URL: /controlador/metodo/param1/param2
 */
class Router
{
    private string $controller = 'DashboardController';
    private string $method = 'index';
    private array $params = [];

    /** Mapa de alias de URL → controlador */
    private array $aliases = [
        'usuarios'        => 'UsuarioController',
        'tipos-proyecto'  => 'TipoProyectoController',
        'configuracion'   => 'ConfiguracionController',
        'consolidado'     => 'ConsolidadoController',
        'mis-proyectos'   => 'ProyectoController',
        'proyectos'       => 'ProyectoController',
        'documentos'      => 'DocumentoController',
        'plan'            => 'PlanController',
        'calendario'      => 'CalendarioController',
        'notificaciones'  => 'NotificacionController',
    ];

    public function dispatch(string $url): void
    {
        $segments = array_values(array_filter(explode('/', trim($url, '/')), fn ($s) => $s !== ''));

        if (!empty($segments[0])) {
            $key = strtolower($segments[0]);
            $this->controller = $this->aliases[$key] ?? (ucfirst($key) . 'Controller');
        }

        $controllerClass = 'App\\Controllers\\' . $this->controller;

        if (!class_exists($controllerClass)) {
            $this->notFound();
            return;
        }

        $controller = new $controllerClass();

        if (isset($segments[1])) {
            $method = $this->toCamelCase($segments[1]);
            if (method_exists($controller, $method)) {
                $this->method = $method;
                $this->params = array_slice($segments, 2);
            } else {
                $this->params = array_slice($segments, 1);
            }
        }

        call_user_func_array([$controller, $this->method], $this->params);
    }

    /** Convierte kebab-case a camelCase: 'guardar-tutor' → 'guardarTutor' */
    private function toCamelCase(string $value): string
    {
        $parts = explode('-', strtolower($value));
        $camel = $parts[0];
        foreach (array_slice($parts, 1) as $part) {
            $camel .= ucfirst($part);
        }
        return $camel;
    }

    private function notFound(): void
    {
        http_response_code(404);
        require VIEW_PATH . '/errors/404.php';
    }
}
