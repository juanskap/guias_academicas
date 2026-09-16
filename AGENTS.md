# AGENTS.md — Proyecto SIGEP (guia_academica)

Sistema de Gestión y Seguimiento de Proyectos Académicos (Titulación, Vinculación, PIS).

## Entorno

- **Ruta del proyecto**: `C:\xampp\htdocs\guia_academica`
- **Stack**: PHP 8.3 (XAMPP) + MySQL + MVC propio SIN framework + Tailwind (CDN). (Se descartó el spec original de Node.js/React/PostgreSQL por decisión del usuario.)
- **Servidores**: Apache puerto 80, MySQL puerto 3306 (XAMPP). Usuario BD: `root`, sin contraseña. BD: `sigep`.
- **PHP CLI**: `C:\xampp\php\php.exe`
- **URL**: `http://localhost/guia_academica` (redirige a `public/`) o `http://localhost/guia_academica/public`
- **Sistema**: Windows. En PowerShell usar `npm.cmd` (npm.ps1 está bloqueado por Execution Policy).

## Credenciales seed

- Admin: `admin@sigep.edu.ec` / `Admin123`
- Estudiante de prueba: `maria@sigep.edu.ec` / `NuevaClave123` (María José Pérez Sánchez, código 2025-0001; contraseña original `Estudiante123` se cambió al probar la recuperación)
- Docente/tutor: `juan@sigep.edu.ec` / `Docente123` (Juan Carlos Rodríguez Mora)
- Estudiante extra: `prueba@sigep.edu.ec` / `Istel2026+` (probó la clave por defecto)
- **Contraseña por defecto al crear usuarios desde el panel**: `Istel2026+` (constante `DEFAULT_PASSWORD` en `config/config.php`). El formulario de creación ya no pide contraseña.
- Roles seed: `admin`, `estudiante`, `docente`
- Tipos de proyecto seed: Titulación (9 etapas), Vinculación (7), PIS (6)

## Recuperación de contraseña (funcional)

- Ruta `auth/olvide` (GET form + POST envía token), `auth/restablecer/{token}`, `auth/cambiar`.
- Tabla `password_resets` (token único, expira en 30 min, `usado`).
- Flujo estándar: se valida el correo, si existe se genera el enlace. En **modo local** (`MAIL_ENABLED=false` en config) el enlace se muestra en pantalla (flash `demo`) y se intenta `mail()`; en producción activar SMTP real vía `enviar_correo()` helper.
- **Correo real configurado (funcional)**: SMTP iCloud (`smtp.mail.me.com:587`, STARTTLS) con `juanc.cr178@icloud.com` y contraseña específica de app (constantes `MAIL_HOST/MAIL_PORT/MAIL_USER/MAIL_PASS/MAIL_FROM/MAIL_FROM_NAME` en `config/config.php`). `MAIL_ENABLED=true`. Probado enviando correos reales (llegaron OK). Cliente SMTP propio en `app/Core/Mailer.php` (sin PHPMailer).
- Link "¿Olvidaste tu contraseña?" en el login.

## Estructura

```
guia_academica/
├── .htaccess              # redirige a public/
├── public/
│   ├── index.php          # front controller (index.php?url=...)
│   ├── .htaccess
│   └── assets/{css,js}
├── config/config.php      # BASE_URL, credenciales BD, rutas uploads, MAX_FILE_SIZE
├── app/
│   ├── Core/              # Router, Database(PDO), Model, Auth, Request(CSRF), View, Controller, bootstrap.php
│   ├── Controllers/       # Auth, Dashboard, Usuario, TipoProyecto, Proyecto
│   ├── Middlewares/       # AuthMiddleware (requireLogin, requireRole)
│   ├── Models/            # Usuario, Estudiante, Docente, TipoProyecto, Etapa, Proyecto
│   ├── Views/             # layouts/main, auth/login, dashboard, usuarios, tipos-proyecto, proyectos, errors
│   └── Helpers/helpers.php# e, url, asset, flash, old, estado_badge, dias_restantes, format_date
├── database/
│   ├── schema.sql         # 17 tablas MySQL (InnoDB, utf8mb4)
│   └── install.php        # crea BD, ejecuta schema y siembra datos (roles, admin, tipos/etapas)
└── storage/               # uploads (con .htaccess de bloqueo)
```

## Rutas (Router)

- URL: `/controlador/metodo/param...`; aliases: `usuarios`→UsuarioController, `tipos-proyecto`→TipoProyectoController, `mis-proyectos`/`proyectos`→ProyectoController, `documentos`→DocumentoController, `plan`→PlanController.
- Métodos kebab-case se convierten a camelCase (`guardar-tutor`→`guardarTutor`).
- `Controller` base exige login por defecto; `AuthController` lo sobrescribe. Control de acceso por rol con `AuthMiddleware::requireRole`.

Rutas existentes:
- `login`, `logout`, `dashboard`
- `usuarios` (admin): index, nuevo, crear, editar, estado
- `tipos-proyecto` (admin): index, nuevo, crear, editar, estado, etapas (crear/renombrar/eliminar/mover etapa)
- `proyectos`/`mis-proyectos` (estudiante ve los suyos; admin ve todos): index, nuevo, crear, ver, asignar (admin), guardar-tutor (admin)
- `documentos`: subir-form, subir, ver, descargar, previsualizar, observar, responder, aprobar-observacion, aprobar
- `plan` (admin o tutor gestionan; estudiante dueño solo lee): index/{proyecto}, actividad-guardar, plazo-guardar, actividad-estado, plazo-estado, actividad-eliminar, plazo-eliminar
- `calendario` (admin o tutor gestionan; lectura según rol): index, guardar, estado, eliminar. Select compuesto `proyecto_etapa` = "proyectoId:etapaId" (0 = sin etapa)
- `notificaciones`: index, marcar-leida/{id}, marcar-todas

## Base de datos (17 tablas)

roles, usuarios, estudiantes, docentes, tipos_proyecto, etapas, proyectos, asignaciones, documentos, observaciones, respuestas_observaciones, actividades, plazos, calendario, notificaciones, historial_acciones, configuraciones.

Notas clave:
- `proyectos.estado` enum: borrador, enviado, en_revision, con_observaciones, en_correccion, reenviado, aprobado, finalizado, vencido. Código: `PRJ-AÑO-XXXX`.
- `documentos.tipo`: 'trabajo' (solo se guarda la versión actual) / 'final' (permanente al aprobar). Estado: enviado, en_revision, con_observaciones, en_correccion, aprobado, final.
- `observaciones` con texto seleccionado y posiciones; `respuestas_observaciones` (respuesta/correccion).

## Instalación / reinicio de BD

1. Iniciar Apache y MySQL en el panel de XAMPP.
2. Borrar BD `sigep` y ejecutar con `C:\xampp\php\php.exe database\install.php` desde la raíz del proyecto para recrear schema + seed.

## Pruebas manuales (verificadas)

- Fase 1+2 ✅: login/logout con CSRF, dashboard por rol, 403 por rol incorrecto, gestión de usuarios admin (crear/editar/activar/desactivar).
- Fase 3 (parcial) ✅: tipos de proyecto y etapas CRUD con orden; proyectos: index por rol, creación por estudiante, detalle, asignación de tutor (admin).
- **PENDIENTE de re-verificar**: flujo de creación de proyecto tras corregir `etapasConEstado` en `Proyecto.php` (se cambió la columna inexistente `documento_id` por `etapa_id`; el join se hace desde `documentos` filtrando tipo 'trabajo'/'final'). La prueba quedó interrumpida porque MySQL se detuvo a mitad.

## Siguiente fase pendiente

**Fase 4 — Documentos (DEMO implementada y verificada)**: 
- ✅ Subida/reemplazo de documento de trabajo por etapa (versión++, solo se conserva la actual). Rutas: `documentos/subir-form/{proyecto}/{etapa}` (GET form), `documentos/subir` (POST), `documentos/ver/{id}`, `documentos/descargar/{id}`.
- ✅ Observaciones del tutor con texto seleccionado + hilo de respuestas/correcciones (`documentos/observar`, `documentos/responder`, `documentos/aprobar-observacion/{id}`). Al observar: doc y proyecto pasan a `con_observaciones`; respuesta del estudiante → `en_correccion`.
- ✅ Aprobación de etapa (`documentos/aprobar/{id}`): copia el archivo a `storage/uploads/finales`, crea documento `final` permanente, recalcula avance (% etapas finales), avanza `etapa_actual_id`, proyecto `en_revision` o `aprobado` al 100%.
- Archivos en `storage/uploads/{documentos,finales}` servidos vía controlador (bloqueados por .htaccess). Límites: MAX_FILE_SIZE 25 MB por defecto (ajustable por admin en `configuracion`), ALLOWED_EXTENSIONS pdf/doc/docx/txt/odt.

**Fase 5 — Actividades y Plazos (DEMO implementada y verificada)**:
- Modelos `Actividad` y `Plazo` (con `porProyecto()` y `marcarVencidos()`); controlador `PlanController`; vista `proyectos/plan.php`; acceso compartido via `Proyecto::puedeVer()` (refactor de `ProyectoController::canAccess`).
- Gestión (crear actividad/plazo, cambiar estado, eliminar): **admin o tutor del proyecto**. Lectura: admin, tutor y estudiante dueño. Estudiante ajeno → 403 (GET y POST verificados).
- Estados: actividades `pendiente/en_curso/completada/vencida` (botones Iniciar/Completar); plazos `activo/completado/vencido`. Al cargar la vista se auto-marcan como vencidos los que superaron `fecha_limite` (verificado).
- Demo creada en PRJ-2026-0003: actividad "Elaborar perfil del proyecto" (completada) y plazo "Entrega del perfil final" (completado); una actividad con fecha pasada muestra el estado vencida.
- Enlace "Plan de actividades" en `proyectos/ver.php`.

**Fase 6 — Calendario (DEMO implementada y verificada)**:
- Modelo `Calendario` (`visiblesPorRol`, `proximos`, `marcarVencidos`), controlador `CalendarioController`, vista `calendario/index.php`.
- Agenda por rol: admin ve todos los proyectos; docente sus tutorías; estudiante sus proyectos. Vista en dos secciones (Próximos / Historial). Eventos con proyecto + etapa opcional (tipo entrega/revision/correccion/limite/otro).
- Gestión (crear/completar/eliminar): admin o tutor. Estudiante dueño solo lee; ajeno no ve nada y el POST → 403. Auto-marca vencido al cargar la vista.
- Dashboard: panel "Próximos eventos" (5) para todos los roles (`DashboardController::index` + vista).
- Demo en BD: eventos del PRJ-2026-0003 ("Entrega del documento de la etapa 2" completado, "Revisión general del proyecto" pendiente, uno vencido para mostrar el estado).

**Fase 7 — Notificaciones (implementada y verificada)**:
- Modelo `Notificacion` (`porUsuario`, `noLeidas`, `crear`, `marcarLeida`, `marcarTodasLeidas`, `adminIds`); controlador `NotificacionController`; vista `notificaciones/index.php`; helper global `notificar()`.
- Campanita 🔔 en el header (layout `main.php`) con contador de no leídas y dropdown con las últimas 5; enlace en el menú lateral. Marcar leída / marcar todas (scoped al usuario).
- Avisos automáticos (hooks): nuevo proyecto → admins; subida de documento → tutor (o admins si no hay tutor); observación → estudiante; respuesta (estudiante→tutor, tutor→estudiante); aprobación de etapa/observación → estudiante; actividad/plazo/evento → estudiante+tutor. Helper `Proyecto::involucrados()` (estudiante/tutor usuario id, nombres, emails).
- Verificado end-to-end: evento→tutor+estudiante, subida de documento→tutor, observación→estudiante, marcar leída/todas.

**Fases pendientes**: panel del docente con pendientes (dashboard del tutor con observaciones y documentos por revisar), historial visible en UI (`historial_acciones`).

## Reglas de desarrollo (del prompt maestro)

1. Analizar antes de codear; implementar por fases.
2. No crear botones/funciones falsas; todo lo visible debe funcionar.
3. Seguir el patrón MVC existente (Controlador→Modelo→Vista con layout, CSRF en POST).

## Ollama / opencode (configuración local)

- Config global: `C:\Users\LENOVO\.config\opencode\opencode.json`
- Modelo por defecto: `ollama/qwen3:4b` (recomendado para este hardware: i3-1215U, 7.7 GB RAM, sin GPU dedicada). `llama3.2` también disponible.
- Servidor Ollama: `http://localhost:11434` (endpoint compatible OpenAI `/v1`).
- Cambiar modelo por sesión con `/models`.
