# ARQWAR — Actualización de avatares 3D animados

## Qué cambia

Solo el sistema visual de avatares y las representaciones relacionadas. Se mantienen intactos el formulario y la acción de guardar del participante (`POST action=update_mine`), los alias, los puntajes, las partidas y los equipos.

- 24 diseños 3D de la imagen de referencia, incorporados como **WebP optimizados** para el selector de `participants.php`.
- Animación suave, efecto luminoso, brillo de selección y profundidad al mover el puntero. En móviles se adapta a 2 columnas.
- Recuadros de avatar actualizados en las fichas de participantes, equipos, ranking, administración y chat del juego.
- Nuevo botón **Explorar avatar en 3D** dentro de `participants.php`, con giro automático y control de órbita/zoom. Utiliza los 24 archivos originales `.glb` como modelos tridimensionales independientes.
- Si el navegador no tiene WebGL2, el visor muestra una **imagen animada de respaldo**, no una pantalla vacía.

**Importante:** Las imágenes de las tarjetas conservan el aspecto detallado de la referencia. Los `.glb` son prototipos reales de estilo low-poly; no son copias 3D hiperrealistas del diseño de las imágenes. Los modelos no tienen animación esquelética: el giro es de cámara/visor y los demás movimientos son efectos de interfaz.

## Cómo instalar en XAMPP o hosting PHP

1. Haz una copia de seguridad de tu proyecto instalado.
2. **Recomendado:** utiliza el ZIP *solo parche* para sobreescribir únicamente los archivos de avatares y añadir los recursos nuevos. Extrae el parche en la carpeta padre de `arqwar_chat_version_LEVEL`, respetando su estructura interna.
3. Si utilizas el ZIP del proyecto completo para una instalación separada, no sustituyas tu propia configuración de base de datos (`config/`) ni tus datos reales de producción por los que contiene la copia del proyecto.
4. Accede a `arqwar/participants.php`, selecciona un avatar y pulsa **Guardar mis datos**. Recarga la página y verifica que el elegido permanece.
5. Para comprobar la vista interactiva, pulsa **Explorar ... en 3D**. También puedes abrir directamente `arqwar/assets/avatars-3d/visor_3d.html` desde tu servidor HTTP.
6. Comprueba las mismas imágenes en `teams.php`, `participant-ranking.php`, el panel de administración y el chat del juego.

No es necesaria ninguna migración SQL. Se conserva la columna **`participants.avatar_emoji`** y sus claves (`cyber_skull`, `mecha_helmet`, etc.). Las claves y emojis antiguos se siguen reconociendo automáticamente.

### Dependencias

- PHP y MySQL tal como los requiere el proyecto existente.
- Navegador moderno con imágenes WebP, JavaScript y opcionalmente WebGL2 para girar los `.glb`.
- Sin dependencias CDN para el visor 3D.

### Archivos principales que se modifican

- `participants.php`: imágenes, selección de avatar y botón/diálogo del visor.
- `includes/avatars.php`: catálogo conservado y nuevo renderizado central de imágenes.
- `teams.php`, `participant-ranking.php`, `admin.php`, `game-master.php`: visualización de avatares vinculados.
- `api/chat.php` y `assets/js/gm.js`: avatar gráfico en los mensajes del chat y nombres legibles en la selección de participantes.

### Recursos nuevos

- `assets/css/avatar-3d.css`
- `assets/js/avatar-3d.js`
- `assets/img/avatars-3d/`: 24 imágenes de selector y 24 imágenes para iconos pequeños, todas WebP.
- `assets/avatars-3d/modelos/`: 24 modelos `.glb` reales.
- `assets/avatars-3d/visor_3d.html` y `assets/avatars-3d/miniaturas/`.

El menú, logo general de ARQWAR, tablas, lógica de puntos, salas, cartas y el resto de los módulos no se alteran.
