asistenciaBachillerato - WhatsApp Service con Baileys
===================================

Desarrollo/local
----------------
1. Requiere Node.js 22+ (para producción se recomienda Node.js 24 LTS).
2. Copie .env.example a .env.
3. Use el mismo WHATSAPP_SERVICE_TOKEN en Laravel y en este microservicio.
4. Ejecute npm install.
5. Ejecute npm start.
6. Abra /admin/whatsapp en asistenciaBachillerato con un usuario admin/superadmin.
7. Escanee el QR y envíe un mensaje de prueba.

Producción Linux
----------------
Consulte deploy/linux/DEPLOY-LINUX.txt.
La sesión persistente se moverá a /var/lib/asistencia-bachillerato-whatsapp/auth mediante systemd.
El puerto 3001 debe permanecer limitado a 127.0.0.1.
