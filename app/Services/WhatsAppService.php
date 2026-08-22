<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Exception;

class WhatsAppService
{
    public function sendMessage(string $phone, string $message): bool
    {
        $instanceId = config('services.ultramsg.instance_id') ?? env('ULTRAMSG_INSTANCE_ID');
        $token = config('services.ultramsg.token') ?? env('ULTRAMSG_TOKEN');

        if (empty($instanceId) || empty($token)) {
            Log::error("UltraMsg WhatsApp credentials missing in config/services.php or .env");
            return false;
        }

        // Clean and format recipient phone number
        $cleanPhone = preg_replace('/[^\d+]/', '', trim($phone));
        
        // If 10 digits without country code, default to Mexican country code +52
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '+52' . $cleanPhone;
        }

        try {
            $response = Http::timeout(15)
                ->retry(3, 1000)
                ->asForm()
                ->post("https://api.ultramsg.com/{$instanceId}/messages/chat", [
                    'token' => $token,
                    'to' => $cleanPhone,
                    'body' => $message,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['sent']) && ($data['sent'] === 'true' || $data['sent'] === true)) {
                    Log::info("WhatsApp enviado vía UltraMsg a {$cleanPhone}. MsgID: " . ($data['id'] ?? 'OK'));
                    AuditLog::log('WRITE', 'notificaciones_whatsapp', null, "WhatsApp de asistencia enviado a {$cleanPhone} vía UltraMsg (MsgID: " . ($data['id'] ?? 'OK') . ")");
                    return true;
                } else {
                    $errorText = is_array($data) ? json_encode($data) : $response->body();
                    Log::warning("UltraMsg devolvió error al enviar a {$cleanPhone}: {$errorText}");
                    return false;
                }
            } else {
                Log::error("Error HTTP ({$response->status()}) al enviar WhatsApp vía UltraMsg a {$cleanPhone}: {$response->body()}");
                return false;
            }
        } catch (Exception $e) {
            Log::error("Excepción en WhatsAppService para {$cleanPhone}: " . $e->getMessage());
            return false;
        }
    }
}
