# Análisis del módulo de constancias

Fecha de revisión: 15 de octubre de 2026, America/Mexico_City. Código base: `e51f957`.

El módulo permite consultar constancias recibidas, revisar su evidencia, aceptarlas o rechazarlas, actualizar los cursos del empleado en PAC y enviar una notificación. El flujo está implementado, pero existen problemas de integridad de calificaciones, exposición de campos internos, integración con altas de empleados y trazabilidad del correo. También hay riesgos de concurrencia y de conservación del historial que requieren atención.

**Alcance y método**

Se revisaron los cinco controladores, las cinco rutas, el componente Vue completo, la vista Blade, las dos clases de visibilidad, el servicio de notificaciones, el Mailable, su plantilla, las migraciones relacionadas y las dependencias relevantes de autenticación, auditoría, PAC y altas de empleados. Se contrastó el código con el esquema y datos agregados de la conexión PostgreSQL configurada.

Las consultas de diagnóstico se ejecutaron dentro de transacciones `READ ONLY`, con límite de tiempo por consulta y cierre mediante rollback. No se cambiaron constancias, cursos, usuarios, permisos ni catálogos. No se enviaron correos. Las comprobaciones de rechazo y aceptación que requerirían escrituras reales no se ejecutaron sobre los datos existentes.

Se distingue entre comportamiento demostrado, riesgos deducidos del código y decisiones de negocio que necesitan definición. Las cifras corresponden a la base configurada durante esta revisión; no se presume que sea el mismo entorno que producción. No se incluyen credenciales, direcciones de infraestructura, CURP, correos personales ni códigos de acceso.

## 1. Estado observado

| Indicador | Resultado |
|---|---:|
| Constancias totales | 1,418 |
| Pendientes, estatus 1 | 165 |
| Aceptadas, estatus 2 | 734 |
| Rechazadas, estatus 3 | 485 |
| Sin estatus | 34 |
| Sin puesto | 34 |
| Revisables en la consulta de un administrador global | 1,384 |
| Registros sin evidencia en ambos campos de enlace | 0 |
| Aceptadas sin `fecha_hora_envio` | 734 |
| Pendientes con `calificacion_n` nula y calificación anterior numérica disponible | 77 |
| De las anteriores, con calificación anterior distinta de 100 | 39 |
| Pendientes con curso que no existe en el catálogo por nombre normalizado | 1 |
| Pendientes cuya instancia no corresponde al ID o nombre del catálogo | 1 |
| Rechazadas sin motivo | 1 |
| Registros con `val_plantilla` nula | 1,418 |
| Grupos con `id_respuesta` duplicado actualmente | 0 |
| Grupos duplicados por puesto, CURP y curso en acciones de empleados | 0 |
| Aceptadas sin curso concluido correspondiente, comparando puesto, CURP y nombre | 0 |
| Revisables sin pareja de puesto y CURP en la plantilla | 0 |

Todas las constancias observadas tienen `anio = 2026`. No se observaron pendientes con calificación nueva fuera de 70–100 ni con fechas futuras respecto del 15/10/2026. Esto describe los datos actuales; el servidor no impone todas esas restricciones.

Los 734 valores nulos de `fecha_hora_envio` son una carencia de trazabilidad. Por sí solos no demuestran que hayan fallado 734 correos ni que el código actual haya procesado esos registros.

## 2. Archivos y responsabilidades

| Archivo | Función |
|---|---|
| `routes/app/constancias/web.php` | Rutas y acceso por autenticación y rol. |
| `app/Http/Controllers/Constancias/ViewConstanciasController.php` | Entrega la página Blade. |
| `resources/views/constancias/index.blade.php` | Contenedor de montaje de Vue. |
| `resources/js/app.js` | Monta el componente cuando existe `#blade_table_constancias`. |
| `resources/js/app/constancias/table.vue` | Filtros, tabla, paginación, detalle, rechazo y confirmación de duplicados. |
| `app/Http/Controllers/Constancias/TableConstanciasController.php` | Consulta, búsqueda, filtros, conteo y paginación. |
| `app/Http/Controllers/Constancias/DataConstanciasController.php` | Detalle de una constancia y auditoría de consulta. |
| `app/Http/Controllers/Constancias/ConstanciasFilterOptionsController.php` | Opciones de validación, entidad, nómina y CLUES. |
| `app/Http/Controllers/Constancias/UpdateEstatusConstanciasController.php` | Rechazo, aceptación, sincronización PAC y regla 1000011/1000001. |
| `app/Support/ConstanciaVisibilityByName.php` | Clase de visibilidad usada por el módulo actual. |
| `app/Support/ConstanciaVisibility.php` | Implementación alternativa sin referencias de uso encontradas en la aplicación. |
| `app/Services/Constancias/ConstanciaNotificationService.php` | Destinatario, copias, reintentos, envío y registro de resultado del correo. |
| `app/Mail/ConstanciaDecisionMail.php` | Asunto y variables del mensaje. |
| `resources/views/emails/constancias/decision.blade.php` | HTML del correo e historial de capacitación. |
| `app/Support/UserActionLogger.php` | Auditoría compatible con las columnas disponibles. |
| `app/Models/Pac/Helpers/GetTrimestreModel.php` | Determina trimestre por mes y día de conclusión. |
| `app/Http/Controllers/Empleado/SaveEmpleadoController.php` | Vincula registros de constancias y tokens durante el alta de un empleado. |
| `app/Http/Controllers/Pac/SavePacController.php` | Otro punto de modificación de los cursos que también toca constancias. |

El controlador de actualización tiene 1,468 líneas y el componente Vue 1,127. Una parte considerable del comportamiento y de las reglas se concentra en esos dos archivos.

## 3. Rutas y contratos

Todas las rutas usan `web`, `auth` y `role:admin_oc,supervisor_oc,revisor_est,supervisor_est`. Por el grupo `web` también reciben la protección CSRF estándar de Laravel. Axios envía cookies y cabeceras de CSRF.

| Método y ruta | Entrada principal | Respuesta o efecto |
|---|---|---|
| `GET /constancias` | Sesión autenticada. | Página de revisión. |
| `POST /constancias/table` | `limit`, `offset`, CURP, curso, año, estatus, validación y búsqueda; filtros administrativos. | `list`, `allRow`, `row`, `is_admin`. |
| `POST /constancias/data` | `id_respuesta`. | `status`, `data`; registra la consulta de detalle. |
| `POST /constancias/filter-options` | Entidad opcional. | Opciones e indicador de administrador. |
| `POST /constancias/estatus` | ID, `ACEPTAR` o `RECHAZAR`, motivo y banderas de duplicado. | Decisión, sincronización PAC y notificación; puede solicitar confirmación. |

La tabla usa cinco filas por defecto y permite hasta 100 por petición. El selector visual ofrece 5, 10, 15, 30 y 50. El valor `select` que envía Vue no interviene en la consulta del controlador.

En una duplicidad se responde HTTP 200 con `status: false`, `requires_confirmation: true` y `duplicate_concluido: true`. Vue conoce este contrato y abre SweetAlert2. Una decisión sobre una constancia ya procesada devuelve 409. Un registro fuera del alcance devuelve 404 en detalle o actualización.

## 4. Permisos y segmentación

| Rol | Acceso efectivo definido en el código |
|---|---|
| `admin_oc` | Acceso global; puede quedar limitado por `id_adscripcion_scope` si la columna y el valor existen. |
| `supervisor_oc` | Entidad y tipo de nómina asignados. |
| `revisor_est` | Entidad y tipo de nómina asignados. |
| `supervisor_est` | Entidad y tipo de nómina asignados. |
| Cualquier rol filtrado con nómina que contiene `HRAES` | Además debe coincidir la CLUES y el usuario debe tenerla asignada. |
| Usuario sin rol activo admitido o segmentación suficiente | Las consultas se cierran con una condición que no devuelve filas. |

La CLUES `DFIMB000014` se excluye para los roles no administradores. Un administrador limitado por adscripción tampoco obtiene acceso ilimitado a esa CLUES: continúa aplicándose su alcance de adscripción.

La clase activa compara texto normalizado: nombre de entidad y código de nómina de los catálogos contra los campos de la constancia. Normaliza mayúsculas y espacios en extremos; no resuelve diferencias de acentos, alias, abreviaturas o cambios de nombre de entidad.

El nombre de la persona proviene de la fila de plantilla con mayor `id_cat` por CURP normalizada. La selección no incluye el puesto de la constancia ni una condición de empleado activo. Esto necesita revisión si una CURP tiene varias plazas o registros históricos con información distinta.

No hay separación entre permiso de consulta y permiso de decidir: los cuatro roles autorizados pueden aceptar, rechazar y confirmar una actualización de curso duplicado dentro de su alcance. Tampoco hay doble aprobación.

La clase acepta el alias `ADMIN`, pero las rutas y el menú no lo incluyen. En la base revisada solo se encontraron los cuatro códigos indicados en las rutas; la inconsistencia del alias no tiene un usuario afectado demostrado.

En la base actual existen seis asignaciones de `admin_oc` y dos de `revisor_est`; no hay asignaciones de los otros dos roles. Se comprobó una consulta de tabla con una cuenta de cada rol existente, dentro de una transacción de solo lectura. Esto no equivale a una prueba de extremo a extremo de todas las combinaciones de permisos.

## 5. Flujo de consulta e interfaz

Al montar Vue se cargan primero las opciones y después la tabla. No se selecciona automáticamente el estatus pendiente: el listado inicial incluye todos los estatus revisables.

Los filtros generales son CURP, nombre del curso, año, estatus y validación. El administrador recibe entidad, nómina y CLUES. El servidor rechaza con 403 el uso de filtros administrativos por otro rol, aunque el cliente los oculte.

Las opciones se derivan de constancias visibles, no de un catálogo global. CLUES depende de entidad; no depende de nómina. Existe protección contra respuestas de CLUES que llegan fuera de orden mediante un consecutivo de petición. No se usa una protección equivalente en la tabla.

El detalle muestra persona, CURP, correo, año, curso, instancia, fechas, horas, calificación y enlace a la evidencia. Para un rechazo muestra el motivo y su fecha. Incluye un bloque de “Información Adicional de Mando” pendiente de definición.

La evidencia se abre en otra pestaña con `rel="noopener"`. Se prefiere `hipervinculo`; si está vacío se usa `subir_constancia`. No se genera un PDF, no se firma un documento ni se adjunta la evidencia al correo desde este módulo. Los textos sobre “generación” exceden lo que implementa este código.

Solo se muestran botones de decisión para `PENDIENTE`. El servidor vuelve a comprobar el estatus antes de escribir; la restricción no depende únicamente de Vue.

## 6. Reglas de aceptación

1. Valida la petición y obtiene el usuario.
2. Abre una transacción y bloquea la constancia visible con `FOR UPDATE`.
3. Exige estatus pendiente, CURP, nombre del curso y puesto.
4. Resuelve el curso por nombre exacto normalizado en `a1_cat_acciones`.
5. Toma un bloqueo asesor global `5001` y otro derivado de la CURP.
6. Considera duplicado un curso del mismo puesto, CURP e ID de acción que tenga `fecha_fin` no nula.
7. Si el curso ya está concluido, pide confirmación, salvo que se reciba una de las banderas de decisión. Confirmar permite actualizar; la otra opción marca la constancia como rechazada por duplicidad.
8. Resuelve horas, instancia, temática y trimestre.
9. Exige ambas fechas y que la final no sea anterior a la inicial. El año se toma de la fecha final.
10. Ajusta la calificación a 70–100, usando 100 como valor inicial si falta la nueva calificación.
11. Si el curso es `1000011`, anula el `1000001` pendiente y activo del mismo puesto y CURP.
12. Si ya hay filas de ese puesto, CURP y curso, las actualiza. Si no existen, inserta una nueva.
13. Marca la constancia como aceptada, actualiza el año y fechas de acción y, si existe la columna, asigna `fecha_hora_envio`.
14. Confirma la transacción; después intenta el correo y registra la auditoría de la decisión.

**Transformaciones de datos que aplica la aceptación**

| Dato destino en PAC | Regla actual |
|---|---|
| `id_finalidad` | Siempre 6. |
| `id_cat_estatus` | Busca `VIGENTE`; usa 1 como respaldo. |
| `eval_aprendizaje` | Siempre `true` si la columna existe. |
| `horas_progamadas` | Duración válida del catálogo; si falta, valor más frecuente existente de horas programadas, después horas reales, después horas de constancia. |
| `horas_real` | Con duración válida de catálogo, usa horas positivas de constancia hasta esa duración; vacías, inválidas, cero o superiores se sustituyen por catálogo. Sin catálogo usa horas positivas de constancia o nulo. |
| `calificacion` | Solo lee `calificacion_n`; si falta usa 100; limita valores al intervalo 70–100. |
| `id_instancia` | Busca ID o descripción en tablas públicas de instancia descubiertas por nombre. |
| `id_cat_tematica` | Texto de temática del curso → ID de `cat_tematica`. |
| `id_trimestre` | Coincidencia por mes y día con `cat_trimestres`, independiente del año base de sus fechas. |
| `observaciones` | IDs 1 y 2: `PAC 2025`; 1000001, 1000002 y 1000011: `OBLIGATORIO`; demás: `CURSO EXTRA`. Sustituye el valor previo. |
| `id_empl_accion`, al insertar | Máximo existente más uno. |
| `id_num_curso`, al insertar | Máximo del mismo puesto y CURP más uno. |

Estas reglas no constituyen verificación del contenido del PDF. La validación de evidencia depende de la revisión humana. Tampoco se exige `val_plantilla` aprobada ni se comprueba que el curso del catálogo esté vigente antes de aceptarlo.

## 7. Rechazo y duplicidad

El rechazo exige un motivo de hasta 2,000 caracteres. Bloquea la constancia, comprueba que siga pendiente y guarda estatus 3, fechas de acción, motivo y fecha de rechazo cuando esas columnas existen. No modifica los cursos PAC. Envía el correo después de confirmar la transacción.

La duplicidad utiliza únicamente puesto, CURP, ID de acción y existencia de fecha final. No toma en cuenta ejercicio, fecha del nuevo curso, evidencia, folio de una emisión anterior, envío de correo ni estado vigente/baja del curso previo.

Al confirmar el duplicado se actualiza la fila existente; no se crea automáticamente una nueva edición del curso. Si aparecieran varias filas con esa combinación, el `update` alcanzaría todas. Hoy no se observaron esas combinaciones duplicadas.

La regla especial de ciberseguridad afecta al mismo puesto y CURP: al aceptar 1000011, los registros pendientes del curso 1000001 con estatus nulo, 1 o 2 pasan a `NO VIGENTE`, con observación explícita. No se modifica un 1000001 que ya tenga fecha final. Esta operación participa en la transacción de aceptación y tiene un evento adicional de auditoría.

## 8. Datos y esquema

| Tabla | Uso principal |
|---|---|
| `public.tbl_constancias` | Solicitud recibida, evidencia, datos del curso y decisión. |
| `public.a2_acciones_capacitacion` | Identidad del empleado y alcance por adscripción mediante `num_cursos`. |
| `public.a2_acciones_empleados` | Cursos del empleado que se insertan o actualizan. |
| `public.a1_cat_acciones` | ID del curso, duración y temática. |
| `public.cat_instancias` | Instancia capacitadora; ID de tipo texto en la base actual. |
| `public.cat_tematica` | Catálogo de temática. |
| `public.cat_estatus` | Vigencia del curso PAC. |
| `public.cat_trimestres` | Trimestre por fecha de conclusión. |
| `administracion.users`, `roles`, `user_roles` | Usuario, roles activos y segmentación. |
| Catálogos administrativos de entidad, nómina y CLUES; `public.cat_clues_bi` | Resolución del alcance del usuario. |
| `log.log_eventos_usuario` | Auditoría de consultas, decisiones y correos. |
| `public.tbl_registros_token` | Vinculación lateral durante alta de empleados. |

En el esquema inspeccionado, `tbl_constancias.id_respuesta` admite nulos y no tiene una restricción de unicidad ni clave primaria. Sí existen claves primarias para `id_cat`, `id_accion` e `id_empl_accion` en sus tablas respectivas. En las tablas principales inspeccionadas no se encontraron claves foráneas que garanticen las relaciones usadas por el módulo.

`a2_acciones_empleados.id_empl_accion` no tiene generador por defecto. Su calificación tiene una restricción de 70–100. Esto explica la necesidad de validar ese intervalo, pero no justifica reemplazar una calificación ausente por una acreditación de 100.

`fecha_ini_accion` y `fecha_ultima_accion` son columnas `date` en la base actual: al asignar `CURRENT_TIMESTAMP` se conserva únicamente la fecha, no la hora. En cambio, `fecha_rechazo` y `fecha_hora_envio` sí permiten información de hora.

No hay disparadores propios en las dos tablas de constancias y acciones de empleados inspeccionadas que agreguen validaciones o sincronizaciones adicionales.

La aplicación depende explícitamente de PostgreSQL: usa esquemas, `ILIKE`, `DISTINCT ON`, conversiones `::`, bloqueos asesores y consultas a `information_schema`. La configuración de pruebas con SQLite en memoria no proporciona por sí sola un entorno equivalente para este módulo.

## 9. Hallazgos priorizados

Prioridad P1: atender primero por integridad de datos, exposición o bloqueo de una integración. P2: corregir en la siguiente iteración por comportamiento incorrecto o fiabilidad. P3: mantenimiento o mejora. La prioridad no implica que todos los escenarios potenciales ya hayan ocurrido.

### H01 — P1: se devuelve al navegador información interna de la constancia

**Evidencia:** `DataConstanciasController.php:67` selecciona `c.*`; `:159` devuelve el objeto completo. El esquema incluye `codigo_acceso` y `semilla`. Hay código de acceso no vacío en 1,417 registros y semilla no vacía en 1,418.

**Impacto:** cualquier rol que pueda consultar el detalle de un registro recibe campos que la pantalla no necesita. Ocultarlos en Vue no evita que aparezcan en la respuesta de red. No se probó si esos códigos permiten autenticarse o modificar información en un sistema externo; esa capacidad no se da por demostrada.

**Corrección propuesta:** respuesta con una lista explícita de campos permitidos; excluir códigos, semillas y metadatos de origen innecesarios. Verificar el contrato de respuesta con una prueba que impida reincorporarlos.

### H02 — P1: se pierde la calificación anterior y puede registrarse 100

**Evidencia:** `UpdateEstatusConstanciasController.php:477` inicializa 100 y solo consulta `calificacion_n`. `DataConstanciasController.php:141` conserva el valor anterior como `calificacion_original`, pero asigna la nueva columna al campo visual. Vue también prioriza la nueva calificación.

**Datos actuales:** 77 pendientes tienen nueva calificación nula y calificación anterior numérica disponible. En 39 de ellas la anterior es distinta de 100. Si se aceptan y las demás validaciones pasan, el código actual guardaría 100 en PAC en lugar de esa calificación anterior.

**Impacto adicional:** una nueva calificación inferior a 70 se eleva a 70; una superior a 100 se reduce a 100. El sistema transforma datos sin pedir corrección ni mostrar el resultado que finalmente guardará.

**Corrección propuesta:** acordar una regla explícita para la transición entre columnas, migrar o conservar el valor válido anterior y rechazar valores ausentes o fuera de rango con un error de validación. No modificar retroactivamente notas existentes sin conciliación.

### H03 — P1: alta de empleados incompatible con el esquema de constancias

**Evidencia:** `SaveEmpleadoController.php:436` llama a la vinculación y `:649` exige `curp` e `id_empleado` en constancias y tokens. No se encontró `id_empleado` en esas dos tablas de la base configurada ni una migración del repositorio que lo agregue.

**Reproducción sin escrituras:** se invocó la comprobación privada `assertEmployeeLinkTableReady('tbl_constancias')` y lanzó `ValidationException`: “La columna id_empleado no existe en public.tbl_constancias.”

**Impacto:** el alta que llegue a esa etapa no puede completar su transacción. Además, la vinculación implementada actualiza `id_empleado`, mientras el módulo de revisión exige `id_puesto`; son dos relaciones que deben armonizarse. Las 34 constancias sin puesto/estatus quedan fuera de la revisión y no existe una pantalla de conciliación en este módulo.

**Corrección propuesta:** definir la relación canónica de empleado y plaza, incorporar su migración y adaptar los consumidores. La relación por CURP debe contemplar varias plazas. Validar con un alta real únicamente en una base de prueba.

### H04 — P1: generación manual de IDs con bloqueos distintos entre módulos

**Evidencia:** constancias calcula `MAX(id_empl_accion) + 1` en `UpdateEstatusConstanciasController.php:571` bajo el bloqueo 5001. El alta de empleados hace el mismo cálculo en `SaveEmpleadoController.php:453`, pero utiliza el bloqueo 2026071401 (`:189`). El ID no tiene secuencia por defecto y sí tiene clave primaria.

**Escenario:** una aceptación que inserta un curso y un alta de empleado pueden leer el mismo máximo porque sus bloqueos no se coordinan. Al insertar, una operación puede fallar por clave duplicada. No se provocó esa carrera sobre la base revisada.

**Corrección propuesta:** generador de IDs gestionado por PostgreSQL y alineado con el máximo actual; si se conserva temporalmente el cálculo manual, todos los escritores necesitan coordinarse. Revisar por separado la unicidad requerida para el consecutivo de curso.

### H05 — P2: el envío de correo no tiene recuperación independiente

**Evidencia:** la aceptación asigna `fecha_hora_envio` dentro de la transacción (`UpdateEstatusConstanciasController.php:627`), pero el envío se intenta después (`:736`). El rechazo también confirma el estado antes de enviar. El servicio hace tres intentos síncronos y devuelve un booleano al controlador.

**Impacto:** puede haber una constancia procesada con fecha de “envío” aunque el correo falle. La petición posterior no permite repetir la decisión porque el estatus ya cambió. No existe una ruta de reenvío ni una tarea persistida para recuperar el envío. Los 734 valores nulos históricos impiden usar hoy esa columna como historial completo de notificaciones.

**Corrección propuesta:** separar decisión y notificación; guardar estado de notificación e intentos en una tabla o bandeja de salida, despachar después del commit y permitir reenvío idempotente. Registrar la fecha cuando el transporte acepte el mensaje y distinguir ese hecho de la entrega al buzón.

### H06 — P2: errores de validación se transforman en errores 500

**Evidencia:** `UpdateEstatusConstanciasController.php:49` valida dentro de un `try` cuyo `catch (Throwable)` en `:103` captura también `ValidationException`.

**Reproducción:** invocación del controlador con usuario sintético y petición vacía, sin lecturas o escrituras de constancias: HTTP 500 y mensaje genérico. El resultado correcto del contrato de validación debería permitir al cliente identificar los campos incorrectos, normalmente con 422.

**Corrección propuesta:** dejar salir `ValidationException` antes del bloque genérico, o validar mediante un Form Request. Añadir tipo y longitud a `id_respuesta`, enumeración a `accion` y restricciones concretas a año y estatus. Actualmente esos dos últimos filtros aceptan cualquier tipo y después intentan convertirlo a texto.

### H07 — P2: la instancia “OTRO” oculta el valor capturado en el campo alternativo

**Evidencia:** `UpdateEstatusConstanciasController.php:375` usa `instancia_otro` únicamente si `instancia` es exactamente una cadena vacía. Si trae `OTRO`, intenta mapear esa etiqueta. El detalle usa una precedencia equivalente.

**Datos actuales:** hay tres constancias con etiquetas de otro, una pendiente. La búsqueda por ID o nombre de la instancia elegida por el código deja una pendiente sin mapeo. La comprobación del resolver real con `OTRO` devolvió nulo en la base configurada.

**Corrección propuesta:** tratar las etiquetas de otro como selección del campo alternativo y normalizar espacios antes de decidir. Si el valor alternativo tampoco está catalogado, ofrecer un proceso explícito de conciliación.

### H08 — P2: no se garantiza la unicidad del identificador usado para decidir

**Evidencia:** `id_respuesta` no tiene restricción única ni clave primaria. Las lecturas usan `first()` y las escrituras de decisión filtran por ID y estatus (`UpdateEstatusConstanciasController.php:177`, `:631`, `:1420`), sin volver a aplicar la segmentación.

**Impacto potencial:** si el origen importara IDs repetidos, se podría leer y bloquear una fila permitida, pero actualizar varias pendientes con ese ID, incluso si otra tiene distinta segmentación. Hoy se verificaron cero IDs duplicados; no hay un incidente actual demostrado.

**Corrección propuesta:** una clave interna inequívoca y una restricción para el identificador externo dentro de su ámbito real. Actualizar por la clave bloqueada y exigir exactamente una fila afectada. Conciliar primero nulos y duplicados en cada entorno antes de agregar restricciones.

### H09 — P2: código y base no tienen la misma capacidad de segmentar administradores

**Evidencia:** no existe `administracion.users.id_adscripcion_scope` en la conexión revisada. La migración `2026_09_28_000001_add_adscripcion_scope_to_users` está en el repositorio, pero no aparece registrada en `administracion.migrations`. La clase de visibilidad sustituye la columna faltante por nulo (`ConstanciaVisibilityByName.php:192`).

**Impacto:** en esta base el límite opcional por adscripción no está disponible y los administradores se resuelven con alcance global. No se afirma que algún administrador concreto deba tener un límite; esa asignación requiere una decisión administrativa.

**Corrección propuesta:** revisar el despliegue pendiente y la política de alcance antes de habilitar cuentas que deban quedar restringidas. La migración también contiene concesiones de permisos a roles de base de datos: no conviene ejecutarla sin revisar su efecto completo.

### H10 — P2: una carga de cinco filas procesa un conjunto de empleados mucho mayor

**Evidencia:** tabla y detalle construyen un `DISTINCT ON` por CURP normalizada sobre toda la plantilla. El conteo de la tabla conserva el `join` de nombres aunque no se busque por nombre. Los índices actuales de CURP de plantilla son sobre el valor sin normalizar.

**Mediciones locales:** la llamada al controlador para un administrador tardó aproximadamente 1.20–1.26 segundos y la de un revisor aproximadamente 0.95–1.02 segundos. En una medición se ejecutaron 11 y 10 consultas, respectivamente. El conteo y el listado consumieron unos 0.5–0.6 segundos cada uno. Son muestras puntuales con la base configurada, sin prueba de carga ni SLA.

**Escala observada:** las estimaciones del catálogo PostgreSQL son aproximadamente 98,077 filas de plantilla y 452,396 acciones de empleados. Son estimaciones, no conteos exactos.

**Corrección propuesta:** evitar el `join` de nombres en el conteo si no hace falta, consultar solo las personas relevantes para la página y evaluar índices sobre las expresiones realmente usadas. Validar con planes de ejecución antes de fijar índices. Evitar resolver varias veces el mismo alcance en una petición.

El bloqueo asesor global 5001 serializa todas las aceptaciones, incluso de distintos trabajadores. Reemplazarlo exige resolver primero la asignación segura de IDs y las carreras entre escritores.

### H11 — P2: duplicidad, conclusión y conservación del historial necesitan una regla única

**Evidencia:** `actionAlreadyConcludedForEmployee()` en `UpdateEstatusConstanciasController.php:1374` solo exige fecha final. La tabla PAC define conclusión con más campos en `TablePacModel.php:355`. El correo también clasifica por fecha final. Además, la aceptación actualiza por puesto, CURP y curso sin ejercicio (`UpdateEstatusConstanciasController.php:564`).

**Impacto:** los módulos pueden discrepar sobre si un registro está concluido. Confirmar una nueva constancia puede sustituir fechas, nota, horas y observaciones anteriores. El texto de duplicado afirma que ya se generó y envió una constancia, aunque la condición solo verifica una fecha final.

**Decisión necesaria:** definir si un curso repetido en otro año es una nueva realización o una corrección de la anterior. Si se necesita historial, identificar la realización o edición y conservar versiones. Un curso concluido no acredita por sí mismo un envío de correo ni la emisión de un documento.

### H12 — P2: el catálogo se resuelve por texto sin validar vigencia ni ambigüedad

**Evidencia:** `UpdateEstatusConstanciasController.php:302` obtiene el primer curso que coincide por nombre normalizado, sin exigir estatus vigente ni contar coincidencias. En el catálogo actual hay un nombre no vacío compartido por dos acciones, de las cuales una está vigente. Ninguna pendiente actual corresponde a ese nombre ambiguo; una pendiente no tiene coincidencia alguna.

**Impacto:** una edición futura con nombres repetidos puede asociarse a un ID equivocado; un curso inactivo puede aceptarse y luego quedar fuera del historial de correo, que sí exige catálogo vigente.

**Corrección propuesta:** conservar ID de curso desde la captura o importación, validar su elegibilidad y mantener un proceso de conciliación para nombres externos. Si se permite catálogo histórico, debe quedar explícito y coincidir con la consulta del historial.

### H13 — P2: la auditoría no captura completamente lo que se reemplaza en PAC

**Evidencia:** la aceptación registra principalmente estatus de constancia, año, envío y datos de referencia (`UpdateEstatusConstanciasController.php:747`). No guarda las filas PAC completas antes y después del `update` ordinario. El evento se escribe después de enviar el correo. `UserActionLogger.php:79` registra errores de auditoría sin revertir la operación principal.

**Impacto:** una sustitución de fechas, horas, nota o observaciones anteriores es difícil de reconstruir. Si el proceso termina después del commit y antes del log, puede existir una decisión sin el evento esperado. La anulación especial 1000001 sí conserva información previa más detallada.

**Corrección propuesta:** identificar y registrar las filas PAC afectadas, valores previos y nuevos, actor, motivo de sustitución y un identificador de operación. Definir qué parte de la auditoría debe quedar atómica con la decisión y qué parte corresponde a los intentos de correo.

### H14 — P2: posibles respuestas fuera de orden y paginación sin límite de avance

**Evidencia:** `table.vue:825` aplica cualquier respuesta que llegue sin cancelar la anterior ni comprobar una secuencia. `table-pagination.vue:3` incrementa la página sin conocer el total. El spinner de tabla tampoco impide que se emitan búsquedas nuevas.

**Impacto:** al buscar rápidamente pueden reaparecer resultados de un filtro anterior. Se puede avanzar más allá de la última página y ver una tabla vacía aunque existan resultados. Después de una decisión, la página actual también puede quedar sin filas si el filtro excluye el registro procesado.

**Corrección propuesta:** usar cancelación o una secuencia como ya se hace para CLUES, desactivar navegación imposible y ajustar la página al nuevo total. Añadir estado de operación a los botones de decisión; el bloqueo del servidor protege la decisión repetida, pero no evita peticiones innecesarias ni mensajes confusos.

## 10. Observaciones de mantenimiento y decisiones de negocio

| Tema | Evaluación |
|---|---|
| `val_plantilla` | El filtro se implementó, pero todas las filas actuales son nulas; sus opciones están vacías. No es un fallo de carga del combo demostrado. No controla la aceptación. |
| Campos faltantes o inválidos | Las 34 filas sin puesto/estatus se ocultan y no tienen un flujo de saneamiento en el módulo. Conviene ofrecer diagnóstico o conciliación con permiso específico. |
| Fecha futura | Constancias verifica orden de fechas, pero no que sean anteriores o iguales a hoy; PAC sí lo valida. No hay pendientes futuras observadas. Definir una política común. |
| Año fijo en observaciones | Los IDs 1 y 2 siempre reciben `PAC 2025`, incluso con conclusión en otro año. Toda la muestra de constancias es 2026. Confirmar si se trata del plan de origen o de una etiqueta desactualizada. |
| Horas y evaluación | Rellenar horas vacías/cero con catálogo y forzar evaluación verdadera son reglas implementadas; requieren aprobación de negocio si deben acreditar participación efectiva. No se clasifican automáticamente como defectos. |
| Instancias y temática | El descubrimiento de tablas y columnas en cada aceptación introduce complejidad y dependencia de nombres. En esta base solo se encontró `cat_instancias` para ese patrón. Conviene una relación estable y explícita. |
| Excepciones dentro de transacciones | Algunos resolvers capturan errores SQL e intentan un respaldo. En PostgreSQL una sentencia fallida aborta la transacción hasta rollback; capturar PHP no crea un savepoint. Un respaldo posterior puede fallar también. Es un riesgo condicional; no se observó con las columnas actuales. |
| Retornos de error tras escribir | En aceptación, si el cambio final de estatus afecta cero filas (`UpdateEstatusConstanciasController.php:636`), se devuelve un arreglo de error desde la transacción después de modificar PAC. Un retorno normal no provoca rollback. El bloqueo de fila hace poco probable ese caso en el flujo actual, pero el manejo de error debería garantizar la reversión si llega a ocurrir. |
| Compatibilidad de columnas de rechazo | El código prueba varias columnas y permite continuar si no existe ninguna. En esta base sí existen motivo y fecha. En otro esquema podría rechazarse sin persistir el motivo; conviene asegurar columnas mediante migraciones. |
| Visibilidad alternativa | `ConstanciaVisibility.php` tiene reglas distintas y ninguna referencia de uso encontrada. Retirarla o documentarla evitaría que una modificación futura use la clase equivocada. |
| Código sin uso | `parseMailList()` en el controlador y `$calTexto` no participan en el resultado actual. |
| Errores de opciones | Vue silencia errores de `filter-options`, vacía combos y pone el indicador administrativo en falso. Conviene distinguir falta de permiso de fallo de red o servidor. |
| Interfaz incompleta | “Información Adicional de Mando” sigue como marcador pendiente. El mensaje de corregir enlaces directamente en base de datos no ofrece una acción para el usuario. |
| Búsqueda general | El servidor la soporta, pero el título usado en esta página no incluye el control `table-search`; actualmente se usan los filtros específicos. |
| Accesibilidad | Los modales no enlazan su título mediante `aria-labelledby`; el motivo de rechazo carece de relación explícita entre `label` y `textarea`. Falta comprobar navegación y foco con teclado en un navegador. |
| Orden del listado | No hay desempate por identificador único y dos campos ordenados son `date`. `fecha_envio` es texto. Empates o formatos heterogéneos pueden volver inestable la paginación por offset. |
| Despliegue desde cero | No hay migraciones de creación del esquema completo de constancias y PAC en este repositorio. Las migraciones de ampliación presuponen tablas preexistentes; se necesita un esquema base versionado. |

## 11. Correo: comportamiento, controles y límites

El servicio valida el formato del correo, prepara copias según aceptación, rechazo o duplicidad y realiza hasta tres intentos con espera de 250 ms entre fallos. El envío se hace dentro de la petición HTTP. Aunque el Mailable usa `Queueable`, no implementa un envío en cola en este flujo: se llama a `send()`.

La conexión revisada usa `smtp` y no tiene configuración Laravel cacheada. No se probó conectividad SMTP, autenticación, entrega ni recepción para no enviar mensajes.

Los tres asuntos se distinguen y se eliminan saltos de línea del asunto. La plantilla escapa nombres y datos del historial; el motivo usa `nl2br(e(...))`. Se renderizaron aceptación, rechazo y duplicidad con un motivo de prueba que contenía una etiqueta `script`; no quedó HTML ejecutable de esa prueba.

El correo incluye historial por CURP, sin restringir por puesto ni por el alcance del revisor, y las copias reciben ese mismo historial. El destino se toma del correo de la constancia. Es necesario confirmar que esa dirección esté verificada por el proceso de origen antes de enviar información de todas las plazas de una persona. No se demostró una dirección manipulada ni una filtración por esta vía.

El historial incluye acciones del catálogo vigente y registros de empleado con estatus nulo, 1 o 2. Clasifica concluido cuando existe fecha final; a pendientes les muestra cero horas y oculta calificación. No limita por ejercicio ni por cantidad de filas.

El HTML renderizado con una sola fila de historial ocupó aproximadamente 116–118 KB, incluyendo el tratamiento del logo incrustado en esa representación. Conviene medir el mensaje MIME real antes de atribuir el tamaño a una sola causa y simplificar la plantilla si crece mucho con historiales extensos. No se probó su apariencia en clientes de correo.

Las copias se leen mediante `env()` directamente en el servicio (`ConstanciaNotificationService.php:172`). El bootstrap local de Laravel omite cargar `.env` cuando la configuración está cacheada (`vendor/laravel/framework/src/Illuminate/Foundation/Bootstrap/LoadEnvironmentVariables.php:22`). Si esas variables solo se encuentran en `.env`, pueden dejar de estar disponibles tras cachear configuración. Deben pasar por un archivo de configuración y leerse mediante `config()`.

El servicio distingue los mailers `log` y `array` como simulados, lo cual es correcto. La distinción se basa en el nombre del mailer: con `failover`, una caída de SMTP seguida de respaldo a `log` podría contabilizarse como envío real. Ese mailer existe en configuración, pero no es el activo observado.

## 12. Controles existentes que conviene conservar

- Las cinco rutas exigen autenticación y rol.
- Tabla, opciones, detalle y decisión aplican la misma clase activa de segmentación.
- El servidor impide filtros administrativos a roles no administradores.
- El alcance sin datos suficientes cierra el acceso a filas.
- La constancia se bloquea y su estatus se vuelve a comprobar antes de decidir.
- Las escrituras principales de aceptación y la anulación especial comparten una transacción.
- La actualización comprueba que el estatus continúe pendiente.
- Los valores de filtros se pasan como parámetros; no se encontró concatenación directa de esos valores como SQL ejecutable en las consultas revisadas.
- Los mensajes usan escapado y el asunto elimina saltos de línea.
- El servicio informa cuando no puede enviar y diferencia simulaciones con mailers de prueba.
- Los cambios generan eventos de auditoría y no exponen al cliente las trazas internas del error genérico.

Estos controles no sustituyen pruebas de autorización por objeto, de concurrencia ni de recuperación de correo.

## 13. Verificaciones realizadas

| Verificación | Resultado y alcance |
|---|---|
| `php artisan route:list --path=constancias -vv` | Cinco rutas; se comprobaron middleware y destino. |
| `php -l` en 14 archivos del módulo y dependencias inmediatas | Sin errores de sintaxis. El lint de Blade no sustituye su renderizado. |
| `php vendor/bin/phpunit --do-not-cache-result` | Dos pruebas, dos aserciones, ambas pasan. Son una prueba de `true` y una de respuesta del login. |
| Compilación Vite en directorio temporal | Correcta; 100 módulos transformados. No se sustituyó el build público. |
| Renderizado de tres variantes de correo | Correcto; historial presente y motivo de prueba escapado. No hubo envío. |
| Consulta del controlador de tabla para un administrador | HTTP 200, 1,384 resultados revisables, cinco filas en página. |
| Consulta del controlador de tabla para un revisor existente | HTTP 200, ocho resultados dentro del alcance de esa cuenta, cinco filas en página. |
| Entrada vacía a actualización con usuario sintético | Reproduce HTTP 500; defecto H06. No ejecuta una decisión. |
| Resolver de horas con catálogo 8 y constancia nula, 0, 4, 10 | Devuelve respectivamente 8, 8, 4 y 8. Sin ambas duraciones devuelve nulo. |
| Resolver de instancia con etiqueta `OTRO` | Devuelve nulo en el catálogo actual. |
| Comprobación de esquema que usa el alta de empleados | Reproduce la excepción por falta de `id_empleado`. |
| Inspección de columnas, restricciones, índices, migraciones y disparadores | Contraste del código con esquema real; resultados relevantes documentados. |
| Comprobaciones de consistencia agregada | Cero IDs externos repetidos y cero aceptadas sin curso concluido correspondiente por puesto, CURP y nombre. |

La prueba de tabla se hizo llamando al controlador en el proceso PHP y estableciendo el usuario localmente. No valida el inicio de sesión, el middleware HTTP, el navegador ni toda la matriz de roles. Las comprobaciones dinámicas no se presentan como pruebas de aceptación del módulo completo.

No se ejecutaron decisiones sobre registros reales, pruebas destructivas, carreras de escritura ni envíos SMTP. No se realizó una sesión visual autenticada en navegador ni se verificó la disponibilidad o correspondencia de cada documento externo.

## 14. Pruebas que faltan antes de considerar estable el módulo

| Grupo | Casos necesarios en PostgreSQL de prueba |
|---|---|
| Autorización | Los cuatro roles, usuario sin rol, rol inactivo, usuario deshabilitado, objetos fuera del alcance, HRAES sin CLUES, CLUES exclusiva y administrador limitado por adscripción. |
| Contrato del detalle | Exclusión de código de acceso, semilla y campos no permitidos; ID inexistente y mal formado. |
| Calificaciones | Columna nueva, transición desde la anterior, nulos, decimales, valores inferiores a 70 y superiores a 100. |
| Aceptación | Curso existente/no existente/inactivo/ambiguo; instancia normal/otro; temática válida; fechas; trimestre; inserción y actualización. |
| Duplicidad | Confirmar, rechazar y volver a enviar; registro inactivo con fecha final; curso repetido en otro ejercicio; preservación de información previa. |
| Rechazo | Motivo obligatorio, límite de longitud, persistencia, correo y decisión repetida. |
| Ciberseguridad | 1000001 pendiente activo, concluido, en baja, varias filas y rollback si falla otra parte. |
| Concurrencia | Dos decisiones del mismo ID, dos constancias del mismo curso, aceptación frente a alta de empleado y edición PAC simultánea. |
| Notificación | SMTP falla, destinatario inválido, reenvío, reintentos, no duplicar envíos, configuración cacheada y failover a log. |
| Interfaz | Respuestas fuera de orden, última página, actualización que vacía página, foco de modales, teclado y errores 401/403/419/422/409/500. |
| Migraciones | Creación reproducible del esquema, transición de calificación, claves, índices y segmentación por adscripción. |

Las pruebas actuales no detectan ninguno de estos problemas específicos del módulo.

## 15. Orden de intervención propuesto

1. Reducir la respuesta de detalle a campos permitidos y resolver la transición de calificaciones; revisar expresamente las 39 pendientes cuya nota anterior difiere de 100.
2. Conciliar el esquema de alta de empleados y el despliegue de la segmentación por adscripción. Definir la relación entre empleado, puesto y constancia.
3. Sustituir la asignación manual de IDs y garantizar una clave inequívoca para decidir constancias. Hacerlo coordinadamente con los demás escritores de PAC.
4. Corregir validación HTTP, instancia alternativa y resolución de cursos de catálogo.
5. Separar notificaciones de la decisión, incorporar recuperación y mejorar la auditoría de las filas PAC sustituidas.
6. Acordar las reglas de historial, conclusión, calificación, horas, evaluación y etiquetas de ejercicio; después unificarlas entre PAC, constancias y correo.
7. Optimizar consultas y paginación con mediciones, y añadir la matriz de pruebas de PostgreSQL y navegador.

Este documento es el resultado de la revisión. No se aplicaron correcciones funcionales ni migraciones durante el análisis.
