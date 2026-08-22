<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Exception;

class WhatsAppController extends Controller
{
    // Method to return the WhatsApp view
    public function index()
    {
        return view('whatsapp');
    }

    // Method to handle form submission and send a WhatsApp message via UltraMsg
    public function store(Request $request, WhatsAppService $whatsAppService)
    {
        $request->validate([
            'phone' => 'required',
            'message' => 'required',
        ]);

        try {
            $sent = $whatsAppService->sendMessage($request->phone, $request->message);

            if ($sent) {
                return redirect()->back()->with('success', '¡Mensaje enviado exitosamente a través de UltraMsg!');
            } else {
                return redirect()->back()->with('error', 'Error al enviar el mensaje vía UltraMsg. Consulta los registros (laravel.log) para más detalles.');
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Excepción al enviar mensaje: ' . $e->getMessage());
        }
    }
}