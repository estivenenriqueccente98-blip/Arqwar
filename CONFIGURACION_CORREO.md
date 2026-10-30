# ARQWAR v1.11 - Correo de recuperacion por Gmail

La recuperacion de contraseña usa SMTP autenticado. En XAMPP ya no depende de `mail()` por defecto.

## Configuracion rapida

Edita:

`arqwar/config/config.php`

En la seccion `mail` coloca:

- `driver`: `smtp`
- `host`: `smtp.gmail.com`
- `port`: `587`
- `encryption`: `tls`
- `username`: tu correo Gmail
- `password`: una **contraseña de aplicacion de Google** de 16 caracteres
- `from_email`: el mismo correo Gmail

No uses la contraseña normal de Gmail.

## Como obtener la contraseña de aplicacion

1. Entra a tu cuenta Google.
2. Activa la verificacion en dos pasos.
3. En Seguridad busca **Contraseñas de aplicaciones**.
4. Crea una contraseña para ARQWAR.
5. Copia los 16 caracteres generados en `password`.
6. Guarda `config.php` y reinicia Apache en XAMPP.

## Probar sin usar recuperacion

Inicia sesion como administrador y abre:

`mail-test.php`

Escribe un correo de destino y presiona **Enviar correo de prueba**.

Si llega el correo, la recuperacion funciona asi:

`Login -> ¿Olvidaste tu contraseña? -> Gmail -> codigo de 6 digitos -> verificar -> nueva contraseña -> login`

El codigo vence en 10 minutos y se almacena como hash SHA-256 en la base de datos.

## Si aparece "No se pudo enviar"

Comprueba:

- Apache esta iniciado.
- PHP tiene la extension OpenSSL habilitada.
- El servidor puede salir a Internet por el puerto 587.
- El usuario es un correo Gmail valido.
- La contraseña es una **contraseña de aplicacion**, no la clave normal.
- `from_email` coincide con la cuenta autenticada.

No publiques tu contraseña de aplicacion ni la subas a un repositorio publico.
