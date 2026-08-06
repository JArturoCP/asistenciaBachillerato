<?php

namespace App\Mail;

use App\Models\Estudiante;
use App\Models\Asistencia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendanceRecordedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Estudiante $student;
    public Asistencia $attendance;

    public function __construct(Estudiante $student, Asistencia $attendance)
    {
        $this->student = $student;
        $this->attendance = $attendance;
    }

    public function envelope(): Envelope
    {
        $statusText = strtoupper($this->attendance->estado);
        return new Envelope(
            subject: "Notificación de Asistencia Escolar: {$this->student->nombre_completo} ({$statusText})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendance_recorded',
        );
    }
}
