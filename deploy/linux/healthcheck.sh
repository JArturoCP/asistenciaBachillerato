#!/usr/bin/env bash
set -euo pipefail

printf '%-24s' 'asistencia-bachillerato-whatsapp:'
if systemctl is-active --quiet asistencia-bachillerato-whatsapp.service; then
  echo 'ACTIVO'
else
  echo 'INACTIVO'
fi

printf '%-24s' 'asistencia-bachillerato-queue:'
if systemctl is-active --quiet asistencia-bachillerato-queue.service; then
  echo 'ACTIVO'
else
  echo 'INACTIVO'
fi

printf '%-24s' 'Baileys /health:'
if curl --fail --silent --show-error http://127.0.0.1:3001/health; then
  echo
else
  echo 'ERROR'
  exit 1
fi
