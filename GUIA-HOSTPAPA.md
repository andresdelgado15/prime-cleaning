# Cómo publicar la web en HostPapa

La web es **HTML + CSS + JavaScript + un archivo PHP** (no necesita WordPress).
Es más rápida, más segura y no requiere actualizar plugins.

## Archivos que debes subir

```
index.html
privacy.html      ← política de privacidad (borrador: que la revise un abogado)
terms.html        ← términos y condiciones (borrador: que los revise un abogado)
send.php          ← envía la copia del formulario a tu correo
assets/           ← carpeta completa (css, js, img, fonts)
```

No subas `assets/orig/`, `.claude/` ni este archivo `.md` (no hacen falta).

## Paso 1 – Entrar al cPanel
1. Entra a https://www.hostpapa.com y haz login en **My Dashboard**.
2. Ve a **My cPanel** (o *Hosting → Manage → cPanel*).

## Paso 2 – Respaldar la web vieja (WordPress)
Si `primecleaningteam.com.au` hoy tiene WordPress en ese mismo hosting:
1. En cPanel abre **File Manager → public_html**.
2. Crea una carpeta `old-wordpress` y **mueve** dentro todo lo que hay en `public_html`
   (o descarga un backup desde *cPanel → Backup*). Así no pierdes nada.

## Paso 3 – Subir los archivos nuevos
1. En tu Mac, comprime el contenido de la carpeta `primecleaning-web` en un `.zip`
   (selecciona `index.html`, `privacy.html`, `terms.html`, `send.php` y `assets` → clic derecho → *Comprimir*).
2. En **File Manager → public_html** → **Upload** → sube el `.zip`.
3. Clic derecho sobre el zip → **Extract**. Comprueba que `index.html` quede
   directamente en `public_html` (no dentro de una subcarpeta).
4. Borra el `.zip`.

## Paso 4 – Crear el correo y probar el formulario
1. En cPanel → **Email Accounts**, asegúrate de que existan:
   - `info@primecleaningteam.com.au` (donde llegan las solicitudes)
   - `website@primecleaningteam.com.au` (remitente; puede ser una cuenta simple)
2. Si usas otros correos, cámbialos en las primeras líneas de `send.php`.
3. Abre la web, llena el formulario y verifica:
   - se abre WhatsApp con el mensaje listo (el cliente solo pulsa *Enviar*);
   - llega un correo a `info@…` (revisa también *Spam* la primera vez).

## Paso 5 – Activar HTTPS (candado)
cPanel → **SSL/TLS Status** → *Run AutoSSL*. Tarda unos minutos.

## Cambiar el número de WhatsApp
- `assets/js/main.js`, línea `WHATSAPP_NUMBER` (formato internacional sin `+`: `61452226386`).
- En `index.html` busca `61452226386` y `0452 226 386` y reemplázalos.
