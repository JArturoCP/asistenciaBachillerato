import 'dotenv/config'

import crypto from 'node:crypto'
import { rm } from 'node:fs/promises'
import path from 'node:path'
import process from 'node:process'

import makeWASocket, {
  DisconnectReason,
  makeCacheableSignalKeyStore,
  useMultiFileAuthState,
} from '@whiskeysockets/baileys'
import express from 'express'
import pino from 'pino'
import QRCode from 'qrcode'

const app = express()
app.use(express.json({ limit: '64kb' }))

const HOST = process.env.HOST || '127.0.0.1'
const PORT = Number(process.env.PORT || 3001)
const TOKEN = process.env.WHATSAPP_SERVICE_TOKEN || ''
const AUTH_DIR = path.resolve(process.cwd(), process.env.AUTH_DIR || './auth')
const LOG_LEVEL = process.env.LOG_LEVEL || 'info'

const logger = pino({ level: LOG_LEVEL })
const baileysLogger = pino({ level: process.env.BAILEYS_LOG_LEVEL || 'warn' })

let socket = null
let latestQr = null
let qrUpdatedAt = null
let connectionStatus = 'starting'
let connectedNumber = null
let connectedAt = null
let connecting = false
let reconnectTimer = null

function tokenMatches(providedToken) {
  if (!TOKEN || !providedToken) return false

  const expected = Buffer.from(TOKEN)
  const provided = Buffer.from(providedToken)

  if (expected.length !== provided.length) return false

  return crypto.timingSafeEqual(expected, provided)
}

function authenticate(req, res, next) {
  const authorization = req.get('authorization') || ''
  const providedToken = authorization.startsWith('Bearer ')
    ? authorization.slice(7)
    : ''

  if (!TOKEN) {
    return res.status(500).json({
      ok: false,
      message: 'WHATSAPP_SERVICE_TOKEN no está configurado en el microservicio.',
    })
  }

  if (!tokenMatches(providedToken)) {
    return res.status(401).json({
      ok: false,
      message: 'No autorizado.',
    })
  }

  next()
}

function normalizePhone(phone) {
  let digits = String(phone || '').replace(/\D/g, '')

  if (digits.length === 10) {
    digits = `52${digits}`
  }

  // Compatibilidad con números mexicanos guardados con el antiguo prefijo +521.
  if (digits.length === 13 && digits.startsWith('521')) {
    digits = `52${digits.slice(3)}`
  }

  return digits
}

function displayNumber(userId) {
  if (!userId) return null

  const raw = String(userId).split('@')[0].split(':')[0]
  return raw || null
}

function disconnectStatusCode(error) {
  return error?.output?.statusCode
    ?? error?.data?.statusCode
    ?? error?.statusCode
    ?? null
}

function scheduleReconnect(delayMs = 3000) {
  if (reconnectTimer) clearTimeout(reconnectTimer)

  reconnectTimer = setTimeout(() => {
    connectWhatsApp().catch((error) => {
      logger.error({ err: error }, 'No fue posible reconectar WhatsApp')
      scheduleReconnect(5000)
    })
  }, delayMs)
}

async function connectWhatsApp() {
  if (connecting || connectionStatus === 'connected') return

  connecting = true
  connectionStatus = 'connecting'

  try {
    const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR)

    const newSocket = makeWASocket({
      auth: {
        creds: state.creds,
        keys: makeCacheableSignalKeyStore(state.keys, baileysLogger),
      },
      logger: baileysLogger,
      syncFullHistory: false,
      markOnlineOnConnect: false,
      generateHighQualityLinkPreview: false,
    })

    socket = newSocket
    newSocket.ev.on('creds.update', saveCreds)

    newSocket.ev.on('connection.update', (update) => {
      const { connection, lastDisconnect, qr } = update

      if (qr) {
        latestQr = qr
        qrUpdatedAt = new Date().toISOString()
        connectionStatus = 'waiting_qr'
        connectedNumber = null
        connectedAt = null
        logger.info('Nuevo QR de WhatsApp disponible')
      }

      if (connection === 'open') {
        latestQr = null
        qrUpdatedAt = null
        connectionStatus = 'connected'
        connectedNumber = displayNumber(newSocket.user?.id)
        connectedAt = new Date().toISOString()
        connecting = false
        logger.info({ number: connectedNumber }, 'WhatsApp conectado')
      }

      if (connection === 'close') {
        const statusCode = disconnectStatusCode(lastDisconnect?.error)
        const loggedOut = statusCode === DisconnectReason.loggedOut

        socket = null
        latestQr = null
        qrUpdatedAt = null
        connectedNumber = null
        connectedAt = null
        connecting = false
        connectionStatus = loggedOut ? 'logged_out' : 'disconnected'

        logger.warn({ statusCode, loggedOut }, 'Conexión de WhatsApp cerrada')

        if (!loggedOut) {
          scheduleReconnect()
        }
      }
    })
  } catch (error) {
    connecting = false
    connectionStatus = 'error'
    throw error
  }
}

app.get('/health', (_req, res) => {
  res.json({
    ok: true,
    service: 'asistencia-bachillerato-whatsapp-service',
    status: connectionStatus,
  })
})

app.get('/status', authenticate, (_req, res) => {
  res.json({
    ok: true,
    status: connectionStatus,
    connected: connectionStatus === 'connected',
    number: connectedNumber,
    connectedAt,
    hasQr: Boolean(latestQr),
    qrUpdatedAt,
  })
})

app.get('/qr', authenticate, async (_req, res) => {
  if (connectionStatus === 'connected') {
    return res.json({
      ok: true,
      connected: true,
      qr: null,
    })
  }

  if (!latestQr) {
    return res.status(404).json({
      ok: false,
      connected: false,
      qr: null,
      message: 'El código QR todavía no está disponible.',
    })
  }

  try {
    const qrImage = await QRCode.toDataURL(latestQr, {
      width: 360,
      margin: 1,
    })

    return res.json({
      ok: true,
      connected: false,
      qr: qrImage,
      qrUpdatedAt,
    })
  } catch (error) {
    logger.error({ err: error }, 'No fue posible generar la imagen del QR')

    return res.status(500).json({
      ok: false,
      message: 'No fue posible generar el código QR.',
    })
  }
})

app.post('/send', authenticate, async (req, res) => {
  const phone = normalizePhone(req.body?.phone)
  const message = typeof req.body?.message === 'string'
    ? req.body.message.trim()
    : ''

  if (!phone || !message) {
    return res.status(422).json({
      ok: false,
      message: 'phone y message son obligatorios.',
    })
  }

  if (!socket || connectionStatus !== 'connected') {
    return res.status(503).json({
      ok: false,
      message: 'WhatsApp no está conectado.',
    })
  }

  try {
    const matches = await socket.onWhatsApp(phone)
    const recipient = matches?.find((item) => item?.exists)?.jid

    if (!recipient) {
      return res.status(404).json({
        ok: false,
        message: 'El número indicado no está disponible en WhatsApp.',
      })
    }

    const sent = await socket.sendMessage(recipient, { text: message })

    return res.json({
      ok: true,
      recipient: phone,
      jid: recipient,
      messageId: sent?.key?.id || null,
    })
  } catch (error) {
    logger.error({ err: error, phone }, 'Error enviando mensaje de WhatsApp')

    return res.status(500).json({
      ok: false,
      message: 'No fue posible enviar el mensaje.',
    })
  }
})

app.post('/logout', authenticate, async (_req, res) => {
  if (reconnectTimer) {
    clearTimeout(reconnectTimer)
    reconnectTimer = null
  }

  try {
    if (socket) {
      try {
        await socket.logout()
      } catch (error) {
        logger.warn({ err: error }, 'La sesión ya estaba cerrada o no respondió al logout')
      }
    }

    socket = null
    latestQr = null
    qrUpdatedAt = null
    connectedNumber = null
    connectedAt = null
    connecting = false
    connectionStatus = 'starting'

    await rm(AUTH_DIR, { recursive: true, force: true })
    scheduleReconnect(1200)

    return res.json({
      ok: true,
      message: 'Sesión desvinculada. Se generará un nuevo QR.',
    })
  } catch (error) {
    logger.error({ err: error }, 'No fue posible desvincular WhatsApp')

    return res.status(500).json({
      ok: false,
      message: 'No fue posible desvincular WhatsApp.',
    })
  }
})

app.use((_req, res) => {
  res.status(404).json({ ok: false, message: 'Ruta no encontrada.' })
})

const server = app.listen(PORT, HOST, () => {
  logger.info({ host: HOST, port: PORT }, 'asistenciaBachillerato WhatsApp Service iniciado')
})

connectWhatsApp().catch((error) => {
  logger.error({ err: error }, 'Error iniciando la conexión de WhatsApp')
  scheduleReconnect(5000)
})

function shutdown(signal) {
  logger.info({ signal }, 'Cerrando asistenciaBachillerato WhatsApp Service')

  if (reconnectTimer) clearTimeout(reconnectTimer)

  server.close(() => process.exit(0))

  setTimeout(() => process.exit(1), 5000).unref()
}

process.on('SIGINT', () => shutdown('SIGINT'))
process.on('SIGTERM', () => shutdown('SIGTERM'))
process.on('unhandledRejection', (error) => {
  logger.error({ err: error }, 'Unhandled promise rejection')
})
