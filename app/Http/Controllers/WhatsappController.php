<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappController extends Controller
{
    public function index(WhatsAppService $whatsAppService): View
    {
        $status = $whatsAppService->status();
        $qr = null;
        $qrMessage = null;

        if (! ($status['connected'] ?? false)) {
            $qrResponse = $whatsAppService->qr();
            $qr = $qrResponse['qr'] ?? null;
            $qrMessage = $qrResponse['message'] ?? null;
        }

        return view('whatsapp', compact('status', 'qr', 'qrMessage'));
    }

    public function status(WhatsAppService $whatsAppService): JsonResponse
    {
        $status = $whatsAppService->status();
        $qr = null;

        if (! ($status['connected'] ?? false)) {
            $qrResponse = $whatsAppService->qr();
            $qr = $qrResponse['qr'] ?? null;
        }

        return response()->json([
            'status' => $status,
            'qr' => $qr,
        ]);
    }

    public function store(Request $request, WhatsAppService $whatsAppService): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:25'],
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $sent = $whatsAppService->sendMessage(
            $validated['phone'],
            $validated['message']
        );

        if ($sent) {
            return back()->with('success', 'Mensaje de prueba enviado correctamente por WhatsApp.');
        }

        return back()
            ->withInput()
            ->with('error', 'No fue posible enviar el mensaje. Verifique que Baileys esté activo y WhatsApp aparezca como conectado.');
    }

    public function logout(WhatsAppService $whatsAppService): RedirectResponse
    {
        if ($whatsAppService->logout()) {
            return back()->with('success', 'WhatsApp fue desvinculado. En unos segundos aparecerá un nuevo código QR.');
        }

        return back()->with('error', 'No fue posible desvincular WhatsApp. Revise el estado del microservicio.');
    }
}
