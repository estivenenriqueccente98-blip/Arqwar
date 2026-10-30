# ARQWAR v1.6

## Control de acceso
- Nuevo modelo de roles: `admin` y `operator`.
- Usuario general para operación cotidiana sin permisos destructivos.
- Usuario administrador con acceso total a mantenimiento.
- Rol incluido en sesión y mostrado en Inicio/Mi cuenta.

## Administración CRUD
- Nueva consola `admin.php`.
- CRUD de participantes.
- CRUD de equipos e integrantes.
- Administración y eliminación de batallas/partidas.
- CRUD de casuísticas.
- CRUD de cartas/herramientas.
- CRUD de incidentes.
- CRUD de combos.
- CRUD de usuarios y roles.
- Protecciones frente a eliminación de registros usados por historial.

## Permisos generales
- El usuario general mantiene la posibilidad de crear participantes y equipos.
- El usuario general no puede eliminar participantes, retirar integrantes, desactivar equipos ni entrar al CRUD administrativo.
- El Game Master continúa disponible para ambos roles.

## Instalación y upgrade
- Instalación limpia crea `general` y `admin`.
- Upgrade agrega la columna `role` sin eliminar partidas existentes.
- Setup queda restringido cuando ya existe una instalación.
- Upgrade exige sesión administrativa.


## v1.6
- Refresh visual del sistema con interfaz más premium.
- Selector visual de avatar para participantes.
- Paleta ampliada de colores/temas para equipos.
- Ranking con filtro por alias de batalla.
- Cartas con marca visual/logo contextual del servicio o proveedor.


## Modo individual - 2026-10-13
- Agregado selector de batalla por equipos / individual en Game Master.
- En modo individual, cada usuario que entra a una sala obtiene automáticamente una casilla propia, color, dado y participante.
- Restricción de jugadores: solo pueden lanzar dado y modificar cartas de su propia casilla.
- Chat individual limitado al canal General.
- Migración automática de columnas `games.mode`, `teams.owner_user_id` y `teams.participant_id`.

## v1.8 - Vinculacion de cuentas y participantes
- Cada cuenta de ARQWAR queda vinculada a un unico participante mediante `participants.user_id`.
- El registro de cuenta crea automaticamente el participante asociado.
- Las cuentas y participantes antiguos se vinculan por alias cuando es posible.
- El chat y las partidas identifican al jugador desde la sesion, sin depender de un participante elegido manualmente.
- Se mantiene el historial de puntos, equipos, cartas y partidas existentes.

## v1.9 - Avatares en chat y ranking
- Catalogo de avatares centralizado en `includes/avatars.php`.
- Chat general, chat de equipo y chat individual muestran el avatar seleccionado por cada participante.
- La identidad de envio del chat muestra el avatar del usuario autenticado.
- Ranking individual usa el avatar del participante en lugar del icono generico de nivel.
- Animacion neon sutil aplicada a los avatares del chat y ranking.

## v1.11 - Recuperacion Gmail y acceso HUD

- Se reemplazo el envio por `mail()` por SMTP autenticado como driver predeterminado.
- Gmail configurado para `smtp.gmail.com`, puerto 587 y STARTTLS.
- Se agrego envio de correo HTML con identidad visual DEVIOZ ARQWAR y codigo de 6 digitos.
- Se agrego `mail-test.php` para validar el canal SMTP desde una cuenta administradora.
- Se mejoro visualmente el login, recuperacion, verificacion y cambio de contraseña con HUD tecnologico responsive.
- Se mantuvo intacta la logica de partidas, salas, equipos, participantes, chat y rankings.
