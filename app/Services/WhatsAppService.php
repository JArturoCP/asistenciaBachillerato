<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    protected function isConfigured(): bool
    {
        return filled(config('services.whatsapp.url'))
            && filled(config('services.whatsapp.token'));
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.whatsapp.url'), '/'))
            ->withToken((string) config('services.whatsapp.token'))
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout((int) config('services.whatsapp.timeout', 15));
    }

    public function status(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'connected' => false,
                'status' => 'not_configured',
                'message' => 'El servicio de WhatsApp no está configurado en Laravel.',
            ];
        }

        try {
            $response = $this->client()->get('/status');

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'connected' => false,
                    'status' => 'unavailable',
                    'message' => "El servicio de WhatsApp respondió HTTP {$response->status()}.",
                ];
            }

            return $response->json() ?: [
                'ok' => false,
                'connected' => false,
                'status' => 'invalid_response',
                'message' => 'El servicio de WhatsApp devolvió una respuesta inválida.',
            ];
        } catch (Throwable $e) {
            Log::warning('No fue posible consultar el estado de Baileys: '.$e->getMessage());

            return [
                'ok' => false,
                'connected' => false,
                'status' => 'offline',
                'message' => 'El microservicio de WhatsApp no está disponible.',
            ];
        }
    }

    public function qr(): array
    {
        if (! $this->isConfigured()) {
            return [
                'ok' => false,
                'qr' => null,
                'message' => 'El servicio de WhatsApp no está configurado en Laravel.',
            ];
        }

        try {
            $response = $this->client()->get('/qr');

            if ($response->status() === 404) {
                return [
                    'ok' => false,
                    'qr' => null,
                    'message' => $response->json('message') ?: 'El QR todavía no está disponible.',
                ];
            }

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'qr' => null,
                    'message' => "No fue posible obtener el QR (HTTP {$response->status()}).",
                ];
            }

            return $response->json() ?: [
                'ok' => false,
                'qr' => null,
                'message' => 'El servicio devolvió una respuesta inválida al solicitar el QR.',
            ];
        } catch (Throwable $e) {
            Log::warning('No fue posible obtener el QR de Baileys: '.$e->getMessage());

            return [
                'ok' => false,
                'qr' => null,
                'message' => 'El microservicio de WhatsApp no está disponible.',
            ];
        }
    }

    public function sendMessage(string $phone, string $message): bool
    {
        if (! $this->isConfigured()) {
            Log::error('WhatsApp no configurado. Revise WHATSAPP_SERVICE_URL y WHATSAPP_SERVICE_TOKEN.');

            return false;
        }

        try {
            $response = $this->client()
                ->retry(2, 500)
                ->post('/send', [
                    'phone' => $phone,
                    'message' => $message,
                ]);

            $data = $response->json();

            if ($response->successful() && ($data['ok'] ?? false) === true) {
                $messageId = $data['messageId'] ?? 'OK';
                $recipient = $data['recipient'] ?? $phone;

                Log::info("WhatsApp enviado vía Baileys a {$recipient}. MsgID: {$messageId}");

                try {
                    AuditLog::log(
                        'WRITE',
                        'notificaciones_whatsapp',
                        null,
                        "WhatsApp enviado a {$recipient} vía Baileys (MsgID: {$messageId})"
                    );
                } catch (Throwable $auditException) {
                    Log::warning('El mensaje se envió, pero no se pudo registrar AuditLog: '.$auditException->getMessage());
                }

                return true;
            }

            $detail = is_array($data)
                ? ($data['message'] ?? json_encode($data, JSON_UNESCAPED_UNICODE))
                : $response->body();

            Log::warning("Baileys no pudo enviar WhatsApp a {$phone}. HTTP {$response->status()}: {$detail}");

            return false;
        } catch (Throwable $e) {
            Log::error("Excepción en WhatsAppService para {$phone}: {$e->getMessage()}");

            return false;
        }
    }

    public function logout(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = $this->client()->post('/logout');

            return $response->successful() && $response->json('ok') === true;
        } catch (Throwable $e) {
            Log::error('No fue posible desvincular WhatsApp: '.$e->getMessage());

            return false;
        }
    }
}
