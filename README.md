# ARQWAR v1.6

Juego híbrido físico + digital para competencias de arquitectura tecnológica. Funciona con PHP + MySQL + HTML5 + CSS3 + Bootstrap + JavaScript y está pensado para ejecutarse en XAMPP.

## Accesos iniciales

- Usuario general: `general`
- Contraseña general: `Arqwar2026!`
- Usuario administrador: `admin`
- Contraseña administrador: `AdminArqwar2026!`

El usuario **General** puede operar el juego, crear participantes, crear equipos, asignar participantes, crear partidas, jugar cartas, usar el dado y consultar rankings. No puede eliminar participantes/equipos/batallas ni modificar los catálogos maestros.

El usuario **Administrador** tiene todo lo anterior y además acceso a **Administración CRUD** para crear, editar, activar/desactivar y eliminar registros maestros.

## CRUD administrador

Desde `admin.php` se administran:

- Participantes
- Equipos e integrantes
- Batallas / partidas
- Casuísticas
- Cartas / herramientas
- Incidentes
- Combos
- Usuarios y roles

Las entidades que ya tienen historial pueden bloquear el borrado físico para proteger la integridad del juego; en esos casos se pueden desactivar.

## Instalación limpia

1. Copia la carpeta `arqwar` a `C:\xampp\htdocs\`.
2. Inicia Apache y MySQL.
3. Abre `http://localhost/arqwar/setup.php`.
4. Ejecuta la instalación.
5. Entra por `http://localhost/arqwar/login.php`.

## Actualización desde v1.4

1. Haz copia de seguridad de la carpeta y de la base `arqwar`.
2. Reemplaza los archivos por v1.6.
3. Abre `http://localhost/arqwar/upgrade.php`. Si detecta una base v1.4 sin columna de roles, habilita automáticamente el modo de migración legado.
4. Ejecuta **Actualizar a v1.6**.
5. Vuelve al login con las nuevas credenciales.

El upgrade conserva partidas/equipos/rankings, agrega la columna de rol y crea/restablece los accesos integrados `admin` y `general`.

## Seguridad de roles

- `admin`: CRUD global y operaciones del juego.
- `operator`: uso operativo del juego sin borrado maestro.
- `setup.php` queda restringido al administrador si ARQWAR ya está instalado.
- `upgrade.php` requiere sesión administrativa.

## Requisitos

- PHP 8+
- MySQL/MariaDB
- Apache (XAMPP recomendado)
- Extensiones PHP PDO MySQL / MySQLi en el entorno local


## v1.6
- Refresh visual del sistema con interfaz más premium.
- Selector visual de avatar para participantes.
- Paleta ampliada de colores/temas para equipos.
- Ranking con filtro por alias de batalla.
- Cartas con marca visual/logo contextual del servicio o proveedor.


## Modo de batalla individual

La pantalla **Game Master > Nueva partida** permite elegir entre **Batalla por equipos** y **Batalla individual**. En el modo individual no se seleccionan equipos: cada usuario que ingresa a la sala obtiene automáticamente una casilla propia con su nombre, participante, dado, cartas y un color distintivo.

- Los jugadores pueden entrar a una sala individual aunque no pertenezcan a un equipo.
- Cada jugador solo puede lanzar su propio dado y modificar sus propias cartas.
- El Game Master conserva el control de inicio, rondas e información de la sala.
- El chat del modo individual muestra únicamente **General**.
- La estructura de base de datos se actualiza automáticamente al cargar el sistema.

## Recuperación de contraseña v1.10

El acceso incluye recuperación mediante correo electrónico. La cuenta debe tener un correo registrado. El flujo usa un código de 6 dígitos con vigencia de 10 minutos y permite establecer una nueva contraseña sin crear otro usuario ni otro participante.

Las cuentas nuevas registran el correo durante el alta. Las cuentas existentes pueden registrar o actualizar su correo desde **Mi cuenta**.

Para enviar correo desde XAMPP, revisa `CONFIGURACION_CORREO.md` y la sección `mail` de `config/config.php`.

## Recuperacion por Gmail SMTP v1.11

La version v1.11 reemplaza el envio `mail()` por SMTP autenticado como opcion predeterminada. Para Gmail se utiliza `smtp.gmail.com:587` con STARTTLS y una contraseña de aplicacion de Google.

1. Abre `config/config.php`.
2. En `mail.username` coloca tu cuenta Gmail.
3. En `mail.password` coloca la contraseña de aplicacion de Google.
4. Mantiene `host=smtp.gmail.com`, `port=587` y `encryption=tls`.
5. Reinicia Apache.
6. Entra como administrador a `mail-test.php` para probar el envio.

No se debe colocar la contraseña normal de Gmail en el proyecto.
