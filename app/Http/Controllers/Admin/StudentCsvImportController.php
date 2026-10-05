<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Estudiante, Grupo, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Importación de estudiantes sin tutor.
 * La matrícula es opcional: si queda vacía se genera como AÑO-GRUPO-CONSECUTIVO.
 */
class StudentCsvImportController extends Controller
{
    private const LIMIT = 2000;
    private const TTL = 1800;

    private const COLUMNS = [
        'matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento',
        'grupo', 'estudiante_email', 'estudiante_telefono', 'estudiante_activo',
    ];

    // Para poder reutilizar temporalmente el CSV integral anterior sin crear tutores.
    private const IGNORED_LEGACY_COLUMNS = [
        'tutor_nombre', 'tutor_apellido_paterno', 'tutor_apellido_materno',
        'tutor_parentesco', 'tutor_email', 'tutor_telefono', 'tutor_correo_notificaciones',
    ];

    private const REQUIRED_COLUMNS = [
        'nombre', 'apellido_paterno', 'grupo',
    ];

    public function index()
    {
        return view('admin.students.import', ['preview' => null]);
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS, ',', '"', '');
            fclose($out);
        }, 'plantilla_estudiantes.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request)
    {
        $request->validate(['archivo' => ['required', 'file', 'max:2048', function ($attribute, $value, $fail) {
            if (strtolower($value->getClientOriginalExtension()) !== 'csv') {
                $fail('Sólo se aceptan archivos .csv.');
            }
        }]]);

        $disk = Storage::disk('local');
        foreach ($disk->files('imports/students') as $stale) {
            if ($disk->lastModified($stale) < time() - 3600) {
                $disk->delete($stale);
            }
        }

        $prior = $request->session()->get('students_import');
        if ($prior && Str::startsWith($prior['path'] ?? '', 'imports/students/')) {
            $disk->delete($prior['path']);
        }

        $path = $request->file('archivo')->storeAs('imports/students', Str::uuid().'.csv', 'local');
        $request->session()->put('students_import', [
            'path' => $path,
            'at' => time(),
            'owner' => $request->user()->id,
        ]);

        $preview = $this->inspect($path);
        return view('admin.students.import', compact('preview'));
    }

    public function commit(Request $request)
    {
        $pending = $request->session()->get('students_import');
        $disk = Storage::disk('local');

        if (!$pending
            || ($pending['owner'] ?? null) !== $request->user()->id
            || time() - ($pending['at'] ?? 0) > self::TTL
            || !Str::startsWith($pending['path'] ?? '', 'imports/students/')
            || !$disk->exists($pending['path'])) {
            throw ValidationException::withMessages([
                'archivo' => 'La vista previa venció. Cargue nuevamente el CSV.',
            ]);
        }

        // Revalidar contra la base antes de confirmar.
        $result = $this->inspect($pending['path']);
        if ($result['total_errors'] || !$result['rows']) {
            return view('admin.students.import', ['preview' => $result]);
        }

        try {
            DB::transaction(function () use ($result) {
                foreach ($result['rows'] as $row) {
                    $studentUser = User::create([
                        'nombre' => $row['nombre'],
                        'apellido_paterno' => $row['apellido_paterno'],
                        'apellido_materno' => $row['apellido_materno'],
                        'email' => $row['estudiante_email'],
                        'phone' => $row['estudiante_telefono'],
                        'role' => 'student',
                        'is_approved' => true,
                    ]);

                    Estudiante::create([
                        'user_id' => $studentUser->id,
                        'matricula' => $row['matricula'],
                        'fecha_nacimiento' => $row['fecha_nacimiento'],
                        'grupo_id' => $row['grupo_id'],
                        'is_active' => $row['estudiante_activo'],
                    ]);
                }
            }, 3);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('admin.students.import.index')->with('error',
                'No se aplicó ningún cambio. La base cambió durante la confirmación o existe un dato duplicado. Vuelva a validar el CSV.');
        }

        $students = count($result['rows']);
        $automatic = $result['automatic_count'];
        $manual = $students - $automatic;

        $disk->delete($pending['path']);
        $request->session()->forget('students_import');

        AuditLog::log('WRITE', 'estudiantes', null,
            "CSV estudiantes: {$students} registros; {$automatic} matrículas automáticas; {$manual} manuales; sin carga de tutores. Operador ".$request->user()->id);

        return redirect()->route('admin.students.index')->with('success',
            "Se registraron {$students} estudiantes ({$automatic} matrículas automáticas y {$manual} manuales). Los tutores no fueron creados ni vinculados.");
    }

    private function inspect(string $path): array
    {
        $result = [
            'rows' => [],
            'errors' => [],
            'sample' => [],
            'count' => 0,
            'total_errors' => 0,
            'automatic_count' => 0,
            'ignored_tutor_columns' => false,
        ];

        $handle = fopen(Storage::disk('local')->path($path), 'rb');
        if (!$handle) {
            return $this->error($result, 'No fue posible leer el archivo.');
        }

        try {
            $first = fgets($handle);
            if ($first === false) {
                return $this->error($result, 'El archivo está vacío.');
            }
            if (!mb_check_encoding($first, 'UTF-8')) {
                return $this->error($result, 'Guarde el CSV en UTF-8.');
            }

            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            rewind($handle);

            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            if (!$headers) {
                return $this->error($result, 'Falta el encabezado.');
            }

            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            $headers = array_map(fn ($h) => $this->headerKey((string) $h), $headers);

            if (count($headers) !== count(array_unique($headers))) {
                return $this->error($result, 'Hay encabezados repetidos.');
            }

            foreach (self::REQUIRED_COLUMNS as $required) {
                if (!in_array($required, $headers, true)) {
                    $this->error($result, "Falta la columna {$required}. Descargue la plantilla actualizada.");
                }
            }
            if ($result['total_errors']) {
                return $result;
            }

            $allowed = array_merge(self::COLUMNS, self::IGNORED_LEGACY_COLUMNS);
            $unknown = array_diff($headers, $allowed);
            if ($unknown) {
                return $this->error($result, 'Columnas no reconocidas: '.implode(', ', $unknown));
            }
            $result['ignored_tutor_columns'] = (bool) array_intersect($headers, self::IGNORED_LEGACY_COLUMNS);

            $groups = Grupo::all();
            $groupLookup = $this->groupLookup($groups);
            $accountsSeen = [];
            $studentEmailsSeen = [];
            $autoSequences = [];
            $line = 1;

            while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($cells) === 1 && trim((string) $cells[0]) === '') {
                    continue;
                }

                $result['count']++;
                if ($result['count'] > self::LIMIT) {
                    return $this->error($result, 'Máximo '.self::LIMIT.' estudiantes por archivo. Divida el CSV.');
                }

                if (count($cells) !== count($headers)) {
                    $this->error($result, "Fila {$line}: la cantidad de columnas no coincide con la cabecera.");
                    continue;
                }

                if (!mb_check_encoding(implode('', $cells), 'UTF-8')) {
                    $this->error($result, "Fila {$line}: contenido no UTF-8.");
                    continue;
                }

                $d = array_fill_keys($allowed, '');
                foreach (array_combine($headers, $cells) as $key => $value) {
                    $d[$key] = trim((string) $value);
                }

                $errorsBefore = $result['total_errors'];

                foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $field) {
                    if (mb_strlen($d[$field]) > 100) {
                        $this->error($result, "Fila {$line}: {$field} supera 100 caracteres.");
                    }
                }
                foreach (['nombre', 'apellido_paterno'] as $field) {
                    if ($d[$field] === '') {
                        $this->error($result, "Fila {$line}: {$field} es obligatorio.");
                    }
                }

                $group = $this->resolveGroup($d['grupo'], $groupLookup);
                if (!$group) {
                    $this->error($result, "Fila {$line}: grupo '{$d['grupo']}' inexistente. Se acepta el código corto (ej. 1-3) o 'Grupo 1-3 (Turno Matutino)'.");
                }

                $date = $this->dateValue($d['fecha_nacimiento']);
                if ($date === false) {
                    $this->error($result, "Fila {$line}: fecha_nacimiento inválida; use AAAA-MM-DD o DD/MM/AAAA.");
                }

                $active = $this->booleanValue($d['estudiante_activo'], true);
                if ($active === null) {
                    $this->error($result, "Fila {$line}: estudiante_activo debe ser 1/0 o sí/no.");
                }

                $studentEmail = $this->emailValue($d['estudiante_email']);
                if ($studentEmail === false) {
                    $this->error($result, "Fila {$line}: estudiante_email no es válido.");
                }

                $studentPhone = $this->phoneValue($d['estudiante_telefono']);
                if ($studentPhone === false) {
                    $this->error($result, "Fila {$line}: estudiante_telefono debe tener 10 dígitos (se acepta +52).");
                }

                if ($studentEmail && isset($studentEmailsSeen[$studentEmail])) {
                    $this->error($result, "Fila {$line}: estudiante_email duplicado (fila {$studentEmailsSeen[$studentEmail]}).");
                }
                if ($studentEmail) {
                    $studentEmailsSeen[$studentEmail] = $line;
                }

                if ($result['total_errors'] !== $errorsBefore || !$group) {
                    continue;
                }

                $manualMatricula = trim($d['matricula']);
                $automatic = $manualMatricula === '';

                if ($automatic) {
                    if (!isset($autoSequences[$group->id])) {
                        $autoSequences[$group->id] = Estudiante::nextMatriculaSequence($group);
                    }
                    do {
                        $matricula = Estudiante::formatMatricula($group, $autoSequences[$group->id]++);
                        $accountKey = mb_strtolower($matricula, 'UTF-8');
                    } while (isset($accountsSeen[$accountKey]));
                } else {
                    $matricula = $manualMatricula;
                    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,49}$/D', $matricula)) {
                        $this->error($result, "Fila {$line}: matrícula inválida; máximo 50 caracteres y sin espacios.");
                        continue;
                    }
                    $accountKey = mb_strtolower($matricula, 'UTF-8');
                    if (isset($accountsSeen[$accountKey])) {
                        $this->error($result, "Fila {$line}: matrícula duplicada (fila {$accountsSeen[$accountKey]}).");
                        continue;
                    }
                }

                $accountsSeen[$accountKey] = $line;
                if ($automatic) {
                    $result['automatic_count']++;
                }

                $result['rows'][] = [
                    'matricula' => $matricula,
                    'matricula_automatica' => $automatic,
                    'nombre' => $d['nombre'],
                    'apellido_paterno' => $d['apellido_paterno'],
                    'apellido_materno' => $d['apellido_materno'] ?: null,
                    'fecha_nacimiento' => $date ?: null,
                    'grupo_id' => $group->id,
                    'grupo' => $group->codigo_grupo,
                    'estudiante_email' => $studentEmail ?: null,
                    'estudiante_telefono' => $studentPhone ?: null,
                    'estudiante_activo' => $active,
                ];
            }

            // Validación en lote contra registros existentes, incluyendo bajas lógicas.
            $accounts = array_column($result['rows'], 'matricula');
            if ($accounts) {
                $used = Estudiante::withTrashed()->whereIn('matricula', $accounts)->pluck('matricula')->all();
                $used = array_fill_keys(array_map(fn ($v) => mb_strtolower((string) $v, 'UTF-8'), $used), true);
                foreach ($result['rows'] as $row) {
                    if (isset($used[mb_strtolower($row['matricula'], 'UTF-8')])) {
                        $this->error($result, 'Número de cuenta ya registrado, incluso dado de baja: '.$row['matricula']);
                    }
                }
            }

            $emails = array_keys($studentEmailsSeen);
            if ($emails) {
                $existingEmails = User::withTrashed()->whereIn('email', $emails)
                    ->pluck('email')
                    ->map(fn ($v) => mb_strtolower((string) $v, 'UTF-8'))
                    ->all();
                $existingEmails = array_fill_keys($existingEmails, true);
                foreach ($emails as $email) {
                    if (isset($existingEmails[$email])) {
                        $this->error($result, "El correo de estudiante {$email} ya está ocupado.");
                    }
                }
            }

            $result['sample'] = array_slice($result['rows'], 0, 15);
            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function groupLookup($groups): array
    {
        $lookup = [];
        foreach ($groups as $group) {
            $aliases = [
                $group->codigo_grupo,
                'Grupo '.$group->codigo_grupo,
                'Grupo '.$group->codigo_grupo.' (Turno '.ucfirst($group->turno).')',
                'Grupo '.$group->codigo_grupo.' (Turno '.$group->turno.')',
            ];

            foreach ($aliases as $alias) {
                $lookup[$this->groupKey($alias)] = $group;
            }
        }
        return $lookup;
    }

    private function resolveGroup(string $value, array $lookup): ?Grupo
    {
        $key = $this->groupKey($value);
        if (isset($lookup[$key])) {
            return $lookup[$key];
        }

        // Compatibilidad con etiquetas provenientes de una exportación visual.
        if (preg_match('/^GRUPO\s+(.+?)\s*\(TURNO\s+.+\)$/u', $key, $matches)) {
            $short = $this->groupKey($matches[1]);
            return $lookup[$short] ?? null;
        }

        return null;
    }

    private function groupKey(string $value): string
    {
        $value = strtoupper(Str::ascii(trim($value)));
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function headerKey(string $value): string
    {
        return Str::of(Str::ascii(trim($value)))
            ->lower()
            ->replace(' ', '_')
            ->toString();
    }

    private function emailValue(string $value): string|false|null
    {
        if ($value === '') {
            return null;
        }
        $email = mb_strtolower($value, 'UTF-8');
        return mb_strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    private function phoneValue(string $value): string|false|null
    {
        if ($value === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $value);
        if (preg_match('/^521\d{10}$/', $digits)) {
            $digits = substr($digits, 3);
        } elseif (preg_match('/^52\d{10}$/', $digits)) {
            $digits = substr($digits, 2);
        }
        return preg_match('/^\d{10}$/D', $digits) ? $digits : false;
    }

    private function dateValue(string $value): string|false|null
    {
        if ($value === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }
        return false;
    }

    private function booleanValue(string $value, bool $default): ?bool
    {
        if ($value === '') {
            return $default;
        }
        $v = Str::lower(Str::ascii(trim($value)));
        if (in_array($v, ['1', 'si', 'yes', 'true'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'no', 'false'], true)) {
            return false;
        }
        return null;
    }

    private function error(array &$result, string $message): array
    {
        $result['total_errors']++;
        if (count($result['errors']) < 50) {
            $result['errors'][] = $message;
        }
        return $result;
    }
}
