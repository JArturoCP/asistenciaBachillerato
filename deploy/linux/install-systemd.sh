#!/usr/bin/env bash
set -euo pipefail

usage() {
  echo "Uso: sudo bash install-systemd.sh /var/www/asistenciaBachillerato [usuario] [grupo]"
  echo "Ejemplo: sudo bash install-systemd.sh /var/www/asistenciaBachillerato www-data www-data"
}

if [[ ${EUID} -ne 0 ]]; then
  echo "ERROR: este instalador debe ejecutarse con sudo/root." >&2
  exit 1
fi

if [[ $# -lt 1 || $# -gt 3 ]]; then
  usage
  exit 1
fi

APP_DIR="$(readlink -f "$1")"
APP_USER="${2:-www-data}"
APP_GROUP="${3:-$APP_USER}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

if [[ ! -f "$APP_DIR/artisan" ]]; then
  echo "ERROR: no se encontró artisan en $APP_DIR" >&2
  exit 1
fi

if [[ ! -f "$APP_DIR/whatsapp-service/package.json" ]]; then
  echo "ERROR: no se encontró whatsapp-service/package.json" >&2
  exit 1
fi

if [[ ! -f "$APP_DIR/whatsapp-service/.env" ]]; then
  echo "ERROR: falta $APP_DIR/whatsapp-service/.env" >&2
  exit 1
fi

if [[ ! -f "$APP_DIR/.env" ]]; then
  echo "ERROR: falta $APP_DIR/.env" >&2
  exit 1
fi

if [[ ! -d "$APP_DIR/whatsapp-service/node_modules" ]]; then
  echo "ERROR: faltan node_modules. Ejecute primero: cd $APP_DIR/whatsapp-service && npm ci (o npm install si aún no hay lockfile)." >&2
  exit 1
fi

if ! id "$APP_USER" >/dev/null 2>&1; then
  echo "ERROR: el usuario $APP_USER no existe." >&2
  exit 1
fi

if ! getent group "$APP_GROUP" >/dev/null 2>&1; then
  echo "ERROR: el grupo $APP_GROUP no existe." >&2
  exit 1
fi

NODE_BIN="$(command -v node || true)"
PHP_BIN="$(command -v php || true)"

if [[ -z "$NODE_BIN" ]]; then
  echo "ERROR: Node.js no está instalado o no está en PATH." >&2
  exit 1
fi

if [[ -z "$PHP_BIN" ]]; then
  echo "ERROR: PHP CLI no está instalado o no está en PATH." >&2
  exit 1
fi

NODE_MAJOR="$($NODE_BIN -p 'Number(process.versions.node.split(".")[0])')"
if (( NODE_MAJOR < 22 )); then
  echo "ERROR: Node.js $($NODE_BIN -v) no es adecuado para producción. Instale Node 22 LTS o, preferentemente, Node 24 LTS." >&2
  exit 1
fi

TOKEN_NODE="$(grep -E '^WHATSAPP_SERVICE_TOKEN=' "$APP_DIR/whatsapp-service/.env" | tail -n1 | cut -d= -f2- || true)"
TOKEN_LARAVEL="$(grep -E '^WHATSAPP_SERVICE_TOKEN=' "$APP_DIR/.env" | tail -n1 | cut -d= -f2- || true)"
URL_LARAVEL="$(grep -E '^WHATSAPP_SERVICE_URL=' "$APP_DIR/.env" | tail -n1 | cut -d= -f2- || true)"

if [[ -z "$TOKEN_NODE" || -z "$TOKEN_LARAVEL" ]]; then
  echo "ERROR: WHATSAPP_SERVICE_TOKEN debe existir en ambos .env." >&2
  exit 1
fi

if [[ "$TOKEN_NODE" != "$TOKEN_LARAVEL" ]]; then
  echo "ERROR: WHATSAPP_SERVICE_TOKEN no coincide entre Laravel y whatsapp-service." >&2
  exit 1
fi

if [[ ${#TOKEN_NODE} -lt 32 ]]; then
  echo "ERROR: WHATSAPP_SERVICE_TOKEN es demasiado corto. Use al menos 32 caracteres aleatorios." >&2
  exit 1
fi

if [[ "$URL_LARAVEL" != "http://127.0.0.1:3001" && "$URL_LARAVEL" != "http://localhost:3001" ]]; then
  echo "ADVERTENCIA: WHATSAPP_SERVICE_URL=$URL_LARAVEL"
  echo "Para el despliegue recomendado debe ser http://127.0.0.1:3001"
fi

install -d -o "$APP_USER" -g "$APP_GROUP" -m 0750 /var/lib/asistencia-bachillerato-whatsapp
install -d -o "$APP_USER" -g "$APP_GROUP" -m 0700 /var/lib/asistencia-bachillerato-whatsapp/auth

mkdir -p "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chown -R "$APP_USER:$APP_GROUP" "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

render_unit() {
  local source="$1"
  local target="$2"

  sed \
    -e "s|__APP_DIR__|$APP_DIR|g" \
    -e "s|__APP_USER__|$APP_USER|g" \
    -e "s|__APP_GROUP__|$APP_GROUP|g" \
    -e "s|__NODE_BIN__|$NODE_BIN|g" \
    -e "s|__PHP_BIN__|$PHP_BIN|g" \
    "$source" > "$target"

  chmod 0644 "$target"
}

render_unit "$SCRIPT_DIR/asistencia-bachillerato-whatsapp.service.template" /etc/systemd/system/asistencia-bachillerato-whatsapp.service
render_unit "$SCRIPT_DIR/asistencia-bachillerato-queue.service.template" /etc/systemd/system/asistencia-bachillerato-queue.service

systemctl daemon-reload
systemctl enable asistencia-bachillerato-whatsapp.service asistencia-bachillerato-queue.service
systemctl restart asistencia-bachillerato-whatsapp.service
systemctl restart asistencia-bachillerato-queue.service

sleep 2

echo
echo "Estado de asistenciaBachillerato WhatsApp:"
systemctl --no-pager --full status asistencia-bachillerato-whatsapp.service || true

echo
echo "Estado del worker:"
systemctl --no-pager --full status asistencia-bachillerato-queue.service || true

echo
echo "Healthcheck local:"
if command -v curl >/dev/null 2>&1; then
  curl --fail --silent --show-error http://127.0.0.1:3001/health || true
  echo
else
  echo "curl no está instalado; omitiendo petición /health."
fi

echo
echo "Instalación finalizada."
echo "Logs Baileys: journalctl -u asistencia-bachillerato-whatsapp -f"
echo "Logs Queue:   journalctl -u asistencia-bachillerato-queue -f"
