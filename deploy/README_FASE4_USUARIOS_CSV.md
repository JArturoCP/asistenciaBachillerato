# asistenciaBachillerato · Fase 4 integral: roles + cuentas + estudiantes/tutores por CSV

**Este ZIP sustituye la anterior Fase 4**, no se aplica encima de forma separada; contiene los archivos de roles, matrícula manual y la importación ampliada. Es una actualización incremental del core; NO contiene ni toca `.env`, `vendor`, `storage`, `whatsapp-service`, `node_modules`, sesiones `auth/` ni base de datos de producción.

## Importación integral

Ruta: `/admin/students/import`, permiso `students.import`. Descargar siempre desde esa página la plantilla vigente. Cabecera del CSV (16 columnas):

`matricula,nombre,apellido_paterno,apellido_materno,fecha_nacimiento,grupo,estudiante_email,estudiante_telefono,estudiante_activo,tutor_nombre,tutor_apellido_paterno,tutor_apellido_materno,tutor_parentesco,tutor_email,tutor_telefono,tutor_correo_notificaciones`

- **Una fila = un estudiante y su tutor**, alta del alumno + alta o reutilización del tutor + vínculo en `estudiante_tutor`, atómicamente en transacción. Ningún registro del lote se importa si hay al menos un error. No altera alumnos existentes ni perfiles de tutores existentes.
- Estudiante: requeridos `matricula`, `nombre`, `apellido_paterno`, `grupo` (código ya existente); los demás campos son opcionales. `estudiante_activo`: `1/0`, `si/no`, en blanco = activo. `matricula` es texto (mantiene ceros iniciales); el QR UUID se genera internamente. Foto y contraseña no se importan por CSV.
- Tutor: requeridos `tutor_nombre`, `tutor_apellido_paterno`, `tutor_parentesco` y **al menos `tutor_email` o `tutor_telefono`**. El parentesco admite `padre`, `madre`, `tutor_legal`. Los apellidos, correo de notificaciones y teléfono se guardan en sus campos reales. El parentesco hoy es un atributo del tutor, no del vínculo: para el mismo tutor debe ser consistente en todas las filas.
- Formato UTF-8 con coma o punto y coma. Fechas `AAAA-MM-DD` o `DD/MM/AAAA`. Teléfono nacional de 10 dígitos, con o sin prefijo `+52`; se guarda como 10 dígitos. Matrícula: hasta 50 caracteres, letras, números, punto, guion, guion bajo y barra (sin espacios). Correos normalizados a minúscula. CSV privado, máximo 2 MB/2.000 estudiantes; vista previa 30 min y validación nueva antes de confirmar.
- Tutor repetido por correo normalizado; sin correo, por **nombre completo + teléfono**. Si en una fila tiene correo y en otra no, con el mismo nombre+teléfono se consolida. Coincidencias ambiguas o datos contradictorios se rechazan. Nunca se vincula por teléfono a secas, ni se modifica un tutor que ya existe: si hay conflicto, corregir la fuente. Se permite que hermanos utilicen el mismo tutor y que un tutor existente obtenga vínculos nuevos.
- Nuevos usuarios tutores reciben una contraseña aleatoria no comunicada en CSV; si tienen correo, podrán configurar su acceso mediante recuperación de contraseña conforme al flujo de la institución. Si no lo tienen, completar correo y acceso desde administración.

### Consentimiento y notificaciones

**El CSV no constituye una prueba de consentimiento**. La relación `estudiante_tutor` se crea con `fecha_verificacion = NULL`; **no se genera un consentimiento aceptado**, y nuevos tutores tienen alertas de correo/WhatsApp desactivadas. El `ScanController` incluido exige consentimiento vigente, aceptado y no revocado para ese tutor y ese alumno **además** de la preferencia de alertas. Así, incluso un tutor ya registrado con alertas activas no recibe notificaciones sobre un alumno recién importado sin consentimiento documentado. Vinculación y autorización se distinguen. Para habilitar alertas posteriormente, verificar consentimiento en el módulo de tutores y activar las preferencias válidas.

Advertencia para instalaciones históricas: con este control, vínculos antiguos que no tengan un registro `consentimientos` aceptado y vigente tampoco enviarán avisos hasta regularizarse; la asistencia seguirá registrándose.

## Despliegue Linux (ruta real `/var/www/asistenciaBachillerato`)

Antes: probar en un clon/staging con base real anonimizada y tomar copia de proyecto + BD. No sobrescribir `.env` ni `whatsapp-service`.

```bash
cd /var/www/asistenciaBachillerato
sudo -u www-data php artisan down --retry=60
mkdir -p /home/administrador/fase4_integral_extract
unzip -q /home/administrador/asistenciaBachillerato_fase4_integral_tutores_csv.zip -d /home/administrador/fase4_integral_extract
sudo rsync -avnc /home/administrador/fase4_integral_extract/ /var/www/asistenciaBachillerato/
# Revisar qué se sobrescribe; sin --delete:
sudo rsync -av /home/administrador/fase4_integral_extract/ /var/www/asistenciaBachillerato/
cd /var/www/asistenciaBachillerato
sudo -u www-data php artisan optimize:clear
```

Si **la Fase 4 todavía NO se ha aplicado**, revisar `php artisan migrate:status` y ejecutar únicamente:

```bash
sudo -u www-data php artisan migrate --path=database/migrations/2026_09_29_000001_create_access_roles_permissions.php --force
```

Si **la Fase 4 anterior ya está instalada**, no volver a ejecutar migración: esta ampliación sólo modifica controlador, vista, plantilla, flujo de avisos y README; no requiere migraciones nuevas.

```bash
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan route:list --path=admin
sudo -u www-data php artisan up
```

Sin `npm install`, sin reiniciar Baileys ni el worker (para cambios en clases de jobs, `queue:restart`; aquí no se modifica el Job). Si hay PHP OPcache con configuración de despliegue agresiva, recargar PHP-FPM según procedimiento habitual de su servidor.

## Pruebas antes de producción

1. CSV con dos hermanos y tutor compartido: crear 2 alumnos, 1 tutor, 2 filas `estudiante_tutor`, 0 consentimientos aceptados. Probar también tutor ya existente: crea solamente los nuevos alumnos y vínculos.
2. Número de cuenta `00001234` y otro distinto; verificar ceros iniciales, QR UUID distinto de matrícula.
3. Repetir matrícula en CSV o usar una cuenta ya existente: rechazar lote entero, sin crear tutores huérfanos.
4. Correo de tutor ya ocupado por empleado/alumno o tutor con datos contradictorios: rechazar lote; no asociar incorrectamente.
5. Correo de tutor vacío con teléfono+nombre; si ya existe exactamente una coincidencia se reutiliza. Teléfono igual con nombres distintos NO identifica a la misma persona.
6. Grupo inexistente, fecha imposible, correo inválido, teléfono inválido, tutor sin email y sin teléfono: vista previa con error, sin grabar.
7. Nuevo alumno vinculado sin consentimiento: escaneo registra asistencia, pero NO envía correo ni WhatsApp. Tras documentar aceptación y activar preferencias, sí debe enviar avisos.
8. Verificar con un colaborador que el permiso `students.import` autoriza también crear/reutilizar tutores mediante CSV, pero no otorga acceso general a `guardians.manage`.
9. Comprobar `/admin/roles`, `/admin/users`, matrícula manual, kiosco, worker y WhatsApp para descartar regresiones.

Este parche fue revisado con `php -l` y coherencia de la cabecera CSV; el core del usuario no trae Laravel completo (`vendor`/`bootstrap`) ni una BD de pruebas, por lo que la ejecución HTTP/DB requiere el entorno propio antes del paso a producción.
