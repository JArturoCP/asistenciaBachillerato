<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Estudiante, Grupo, Tutor, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Alta integral: estudiante + tutor + vínculo. No presume consentimiento LFPDPPP.
 * El tutor puede repetirse en varias filas (hermanos); nunca se crean credenciales en el CSV.
 */
class StudentCsvImportController extends Controller
{
    private const LIMIT = 2000;
    private const TTL = 1800;
    private const COLUMNS = [
        'matricula', 'nombre', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento',
        'grupo', 'estudiante_email', 'estudiante_telefono', 'estudiante_activo',
        'tutor_nombre', 'tutor_apellido_paterno', 'tutor_apellido_materno',
        'tutor_parentesco', 'tutor_email', 'tutor_telefono', 'tutor_correo_notificaciones',
    ];
    private const REQUIRED_COLUMNS = [
        'matricula', 'nombre', 'apellido_paterno', 'grupo',
        'tutor_nombre', 'tutor_apellido_paterno', 'tutor_parentesco',
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
        }, 'plantilla_estudiantes_tutores.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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
            if ($disk->lastModified($stale) < time() - 3600) $disk->delete($stale);
        }
        $prior = $request->session()->get('students_import');
        if ($prior && Str::startsWith($prior['path'] ?? '', 'imports/students/')) {
            $disk->delete($prior['path']);
        }
        $path = $request->file('archivo')->storeAs('imports/students', Str::uuid().'.csv', 'local');
        $request->session()->put('students_import', [
            'path' => $path, 'at' => time(), 'owner' => $request->user()->id,
        ]);
        $preview = $this->inspect($path);
        return view('admin.students.import', compact('preview'));
    }

    public function commit(Request $request)
    {
        $pending = $request->session()->get('students_import');
        $disk = Storage::disk('local');
        if (!$pending || ($pending['owner'] ?? null) !== $request->user()->id
            || time() - ($pending['at'] ?? 0) > self::TTL
            || !Str::startsWith($pending['path'] ?? '', 'imports/students/')
            || !$disk->exists($pending['path'])) {
            throw ValidationException::withMessages(['archivo' => 'La vista previa venció. Cargue nuevamente el CSV.']);
        }
        // La base puede haber cambiado entre vista previa y confirmación.
        $result = $this->inspect($pending['path']);
        if ($result['total_errors'] || !$result['rows']) {
            return view('admin.students.import', ['preview' => $result]);
        }

        try {
            DB::transaction(function () use ($result) {
                // Una sola cuenta de tutor para todos los hermanos que comparten identidad.
                $guardians = [];
                foreach ($result['guardians'] as $key => $profile) {
                    if ($profile['existing_id']) {
                        $guardian = Tutor::whereKey($profile['existing_id'])->lockForUpdate()->firstOrFail();
                        // Los perfiles existentes jamás se sobrescriben desde el archivo.
                    } else {
                        if ($profile['email'] && User::withTrashed()->where('email', $profile['email'])->exists()) {
                            throw new \RuntimeException('Un correo de tutor fue registrado mientras se confirmaba el archivo.');
                        }
                        $user = User::create([
                            'nombre' => $profile['nombre'],
                            'apellido_paterno' => $profile['apellido_paterno'],
                            'apellido_materno' => $profile['apellido_materno'],
                            'email' => $profile['email'],
                            'phone' => $profile['telefono'],
                            // Nunca almacenar ni exportar claves de acceso en CSV.
                            'password' => Str::random(48), // User tiene cast hashed.
                            'role' => 'parent',
                            'is_approved' => true,
                        ]);
                        $guardian = Tutor::create([
                            'user_id' => $user->id,
                            'parentesco' => $profile['parentesco'],
                            'telefono' => $profile['telefono'],
                            'correo_notificaciones' => $profile['correo_notificaciones'] ?: $profile['email'],
                            'alertas_correo_activadas' => false,
                            'alertas_whatsapp_activadas' => false,
                        ]);
                    }
                    $guardians[$key] = $guardian;
                }

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
                    $student = Estudiante::create([
                        'user_id' => $studentUser->id,
                        'matricula' => $row['matricula'],
                        'fecha_nacimiento' => $row['fecha_nacimiento'],
                        'grupo_id' => $row['grupo_id'],
                        'is_active' => $row['estudiante_activo'],
                    ]);
                    // Fecha de verificación nula: vinculado, pendiente de consentimiento documentado.
                    $guardians[$row['tutor_key']]->estudiantes()->syncWithoutDetaching([
                        $student->id => ['fecha_verificacion' => null],
                    ]);
                }
            }, 3);
        } catch (Throwable $e) {
            report($e);
            return redirect()->route('admin.students.import.index')->with('error',
                'No se aplicó ningún cambio. Revise si otra persona registró alguna cuenta/correo durante la carga y vuelva a validar el CSV.');
        }

        $students = count($result['rows']);
        $tutorsCreated = $result['tutors_new'];
        $tutorsReused = $result['tutors_existing'];
        $disk->delete($pending['path']);
        $request->session()->forget('students_import');
        AuditLog::log('WRITE', 'estudiantes', null,
            "CSV integral: {$students} alumnos vinculados; {$tutorsCreated} tutores creados; {$tutorsReused} tutores reutilizados; consentimiento pendiente. Operador ". $request->user()->id);
        return redirect()->route('admin.students.index')->with('success',
            "Se registraron {$students} estudiantes y se vincularon sus tutores ({$tutorsCreated} nuevos, {$tutorsReused} existentes). La verificación del consentimiento está pendiente; no se activan alertas automáticamente.");
    }

    private function inspect(string $path): array
    {
        $result = [
            'rows' => [], 'guardians' => [], 'errors' => [], 'sample' => [], 'count' => 0,
            'total_errors' => 0, 'tutors_new' => 0, 'tutors_existing' => 0,
        ];
        $handle = fopen(Storage::disk('local')->path($path), 'rb');
        if (!$handle) return $this->error($result, 'No fue posible leer el archivo.');
        try {
            $first = fgets($handle);
            if ($first === false) return $this->error($result, 'El archivo está vacío.');
            if (!mb_check_encoding($first, 'UTF-8')) return $this->error($result, 'Guarde el CSV en UTF-8.');
            $first = preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
            rewind($handle);
            $headers = fgetcsv($handle, 0, $delimiter, '"', '');
            if (!$headers) return $this->error($result, 'Falta el encabezado.');
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
            $headers = array_map(fn ($h) => Str::of(Str::ascii(trim($h)))->lower()->replace(' ', '_')->toString(), $headers);
            if (count($headers) !== count(array_unique($headers))) return $this->error($result, 'Hay encabezados repetidos.');
            foreach (self::REQUIRED_COLUMNS as $required) {
                if (!in_array($required, $headers, true)) {
                    $this->error($result, "Falta la columna {$required}. Descargue la plantilla integral actualizada.");
                }
            }
            if ($result['total_errors']) return $result;
            $unknown = array_diff($headers, self::COLUMNS);
            if ($unknown) return $this->error($result, 'Columnas no reconocidas: '.implode(', ', $unknown));

            $groups = Grupo::all()->keyBy(fn ($g) => mb_strtoupper(trim($g->codigo_grupo), 'UTF-8'));
            $accountsSeen = []; $studentEmailsSeen = [];
            $line = 1;
            while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($cells) === 1 && trim((string) $cells[0]) === '') continue;
                $result['count']++;
                if ($result['count'] > self::LIMIT) {
                    return $this->error($result, 'Máximo '.self::LIMIT.' estudiantes por archivo. Divida el CSV.');
                }
                if (count($cells) !== count($headers)) {
                    $this->error($result, "Fila {$line}: la cantidad de columnas no coincide con la cabecera."); continue;
                }
                if (!mb_check_encoding(implode('', $cells), 'UTF-8')) {
                    $this->error($result, "Fila {$line}: contenido no UTF-8."); continue;
                }
                $d = array_fill_keys(self::COLUMNS, '');
                foreach (array_combine($headers, $cells) as $key => $value) $d[$key] = trim((string) $value);
                $errorsBefore = $result['total_errors'];

                $matricula = $d['matricula'];
                if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,49}$/D', $matricula)) {
                    $this->error($result, "Fila {$line}: matrícula inválida; máximo 50 caracteres y sin espacios.");
                }
                $accountKey = mb_strtolower($matricula, 'UTF-8');
                if ($matricula !== '' && isset($accountsSeen[$accountKey])) {
                    $this->error($result, "Fila {$line}: matrícula duplicada (fila {$accountsSeen[$accountKey]}).");
                }
                $accountsSeen[$accountKey] = $line;
                foreach (['nombre','apellido_paterno','apellido_materno','tutor_nombre','tutor_apellido_paterno','tutor_apellido_materno'] as $field) {
                    if (mb_strlen($d[$field]) > 100) $this->error($result, "Fila {$line}: {$field} supera 100 caracteres.");
                }
                foreach (['nombre','apellido_paterno','tutor_nombre','tutor_apellido_paterno'] as $field) {
                    if ($d[$field] === '') $this->error($result, "Fila {$line}: {$field} es obligatorio.");
                }
                $groupCode = mb_strtoupper($d['grupo'], 'UTF-8');
                $group = $groups->get($groupCode);
                if (!$group) $this->error($result, "Fila {$line}: grupo '{$groupCode}' inexistente; debe crearlo antes.");

                $date = $this->dateValue($d['fecha_nacimiento']);
                if ($date === false) $this->error($result, "Fila {$line}: fecha_nacimiento inválida; AAAA-MM-DD o DD/MM/AAAA.");
                $active = $this->booleanValue($d['estudiante_activo'], true);
                if ($active === null) $this->error($result, "Fila {$line}: estudiante_activo debe ser 1/0 o sí/no.");
                $studentEmail = $this->emailValue($d['estudiante_email']);
                $tutorEmail = $this->emailValue($d['tutor_email']);
                $notifyEmail = $this->emailValue($d['tutor_correo_notificaciones']);
                foreach (['estudiante_email' => $studentEmail, 'tutor_email' => $tutorEmail, 'tutor_correo_notificaciones' => $notifyEmail] as $field => $value) {
                    if ($value === false) $this->error($result, "Fila {$line}: {$field} no es un correo válido.");
                }
                $studentPhone = $this->phoneValue($d['estudiante_telefono']);
                $tutorPhone = $this->phoneValue($d['tutor_telefono']);
                if ($studentPhone === false) $this->error($result, "Fila {$line}: estudiante_telefono debe tener 10 dígitos (se acepta +52).");
                if ($tutorPhone === false) $this->error($result, "Fila {$line}: tutor_telefono debe tener 10 dígitos (se acepta +52).");
                if (!$tutorEmail && !$tutorPhone) $this->error($result, "Fila {$line}: indique al menos correo o teléfono del tutor.");
                if (!in_array($d['tutor_parentesco'], ['padre','madre','tutor_legal'], true)) {
                    $this->error($result, "Fila {$line}: tutor_parentesco debe ser padre, madre o tutor_legal.");
                }
                if ($studentEmail && isset($studentEmailsSeen[$studentEmail])) {
                    $this->error($result, "Fila {$line}: estudiante_email duplicado (fila {$studentEmailsSeen[$studentEmail]}).");
                }
                if ($studentEmail) $studentEmailsSeen[$studentEmail] = $line;
                if ($result['total_errors'] !== $errorsBefore) continue;

                $profile = [
                    'nombre' => $d['tutor_nombre'],
                    'apellido_paterno' => $d['tutor_apellido_paterno'],
                    'apellido_materno' => $d['tutor_apellido_materno'] ?: null,
                    'parentesco' => $d['tutor_parentesco'],
                    'email' => $tutorEmail, 'telefono' => $tutorPhone,
                    'correo_notificaciones' => $notifyEmail,
                    'existing_id' => null,
                ];
                $personKey = $this->personKey($profile);
                $tutorKey = $tutorEmail ? 'email:'.$tutorEmail : 'phone:'.$tutorPhone.'|'.$personKey;
                if (isset($result['guardians'][$tutorKey])) {
                    $previous = &$result['guardians'][$tutorKey];
                    if ($this->personKey($previous) !== $personKey || $previous['parentesco'] !== $profile['parentesco']) {
                        $this->error($result, "Fila {$line}: el tutor repetido tiene nombres o parentesco diferentes.");
                    }
                    foreach (['email','telefono','correo_notificaciones'] as $field) {
                        if ($profile[$field] && $previous[$field] && $profile[$field] !== $previous[$field]) {
                            $this->error($result, "Fila {$line}: datos contradictorios para el mismo tutor ({$field}).");
                        } elseif (!$previous[$field] && $profile[$field]) {
                            $previous[$field] = $profile[$field];
                        }
                    }
                    unset($previous);
                    if ($result['total_errors'] !== $errorsBefore) continue;
                } else {
                    $result['guardians'][$tutorKey] = $profile;
                }
                $result['rows'][] = [
                    'matricula' => $matricula, 'nombre' => $d['nombre'],
                    'apellido_paterno' => $d['apellido_paterno'], 'apellido_materno' => $d['apellido_materno'] ?: null,
                    'fecha_nacimiento' => $date ?: null, 'grupo_id' => $group->id, 'grupo' => $group->codigo_grupo,
                    'estudiante_email' => $studentEmail, 'estudiante_telefono' => $studentPhone,
                    'estudiante_activo' => $active, 'tutor_key' => $tutorKey,
                ];
            }

            // Unificar un tutor que aparece unas filas con correo y otras sin correo,
            // siempre que teléfono y nombre completo coincidan exactamente.
            $identityKeys = [];
            $alias = [];
            foreach ($result['guardians'] as $key => $profile) {
                if (!$profile['telefono']) continue;
                $identity = $profile['telefono'].'|'.$this->personKey($profile);
                if (!isset($identityKeys[$identity])) {
                    $identityKeys[$identity] = $key;
                    continue;
                }
                $canonical = $identityKeys[$identity];
                if ($canonical === $key) continue;
                $original = &$result['guardians'][$canonical];
                if (($original['email'] && $profile['email'] && $original['email'] !== $profile['email'])
                    || $original['parentesco'] !== $profile['parentesco']) {
                    $this->error($result, 'El mismo nombre y teléfono del tutor aparece con correos o parentescos contradictorios.');
                } else {
                    foreach (['email', 'correo_notificaciones'] as $field) {
                        if ($original[$field] && $profile[$field] && $original[$field] !== $profile[$field]) {
                            $this->error($result, 'Un mismo tutor tiene datos contradictorios en '.$field.'.');
                        } elseif (!$original[$field] && $profile[$field]) {
                            $original[$field] = $profile[$field];
                        }
                    }
                    $alias[$key] = $canonical;
                }
                unset($original);
            }
            foreach ($alias as $extra => $canonical) unset($result['guardians'][$extra]);
            foreach ($result['rows'] as &$row) {
                $row['tutor_key'] = $alias[$row['tutor_key']] ?? $row['tutor_key'];
            }
            unset($row);

            // Revisión en lote contra matrícula, correos (incluso soft-deleted) y tutores existentes.
            $accounts = array_column($result['rows'], 'matricula');
            $used = Estudiante::withTrashed()->whereIn('matricula', $accounts)->pluck('matricula')->all();
            $used = array_fill_keys(array_map(fn ($v) => mb_strtolower($v,'UTF-8'), $used), true);
            foreach ($result['rows'] as $row) {
                if (isset($used[mb_strtolower($row['matricula'],'UTF-8')])) {
                    $this->error($result, 'Número de cuenta ya registrado, incluso dado de baja: '.$row['matricula']);
                }
            }
            $tutorEmails = array_values(array_filter(array_column($result['guardians'], 'email')));
            foreach (array_keys($studentEmailsSeen) as $email) {
                if (in_array($email, $tutorEmails, true)) $this->error($result, "El correo {$email} aparece como estudiante y tutor.");
            }
            $allEmails = array_unique(array_merge(array_keys($studentEmailsSeen), $tutorEmails));
            $existingUsers = User::withTrashed()->with(['tutor' => fn ($q) => $q->withTrashed()])
                ->whereIn('email', $allEmails ?: ['--sin-correos--'])->get()
                ->keyBy(fn ($u) => mb_strtolower($u->email ?? '', 'UTF-8'));
            foreach (array_keys($studentEmailsSeen) as $email) {
                if ($existingUsers->has($email)) $this->error($result, "El correo de estudiante {$email} ya está ocupado.");
            }
            $phones = array_values(array_unique(array_filter(array_column($result['guardians'], 'telefono'))));
            $byPhone = Tutor::withTrashed()->with(['user' => fn ($q) => $q->withTrashed()])->whereIn('telefono', $phones ?: ['--sin-telefonos--'])->get()->groupBy('telefono');
            foreach ($result['guardians'] as $key => &$profile) {
                $found = null;
                if ($profile['email']) {
                    $u = $existingUsers->get($profile['email']);
                    if ($u) {
                        if ($u->trashed() || !in_array($u->role, ['parent','tutor'], true)
                            || !$u->tutor || $u->tutor->trashed() || !$u->is_approved) {
                            $this->error($result, "El correo de tutor {$profile['email']} pertenece a una cuenta incompatible, eliminada o pendiente de autorización.");
                            continue;
                        }
                        $found = $u->tutor;
                    }
                }
                // Si no tiene correo, se reutiliza por teléfono + nombre COMPLETO, nunca sólo teléfono.
                if (!$found && $profile['telefono']) {
                    $matches = ($byPhone->get($profile['telefono']) ?? collect())->filter(
                        fn ($t) => $t->user && $this->personKey([
                            'nombre' => $t->user->nombre, 'apellido_paterno' => $t->user->apellido_paterno,
                            'apellido_materno' => $t->user->apellido_materno,
                        ]) === $this->personKey($profile)
                    );
                    if ($matches->count() > 1) {
                        $this->error($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: coincidencia ambigua por nombre y teléfono; revise el dato.");
                        continue;
                    }
                    if ($matches->count() === 1) {
                        $candidate = $matches->first();
                        if ($profile['email'] && mb_strtolower($candidate->user?->email ?? '', 'UTF-8') !== $profile['email']) {
                            $this->error($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: ya existe con otro correo. Use el correo registrado.");
                            continue;
                        }
                        if ($candidate->trashed() || $candidate->user->trashed() || !$candidate->user->is_approved) {
                            $this->error($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: registro dado de baja o sin aprobación.");
                            continue;
                        }
                        $found = $candidate;
                    }
                }
                if ($found) {
                    if ($this->personKey([
                        'nombre' => $found->user->nombre, 'apellido_paterno' => $found->user->apellido_paterno,
                        'apellido_materno' => $found->user->apellido_materno,
                    ]) !== $this->personKey($profile) || $found->parentesco !== $profile['parentesco']) {
                        $this->error($result, "Tutor {$profile['email']}: nombre o parentesco diferentes a su ficha actual; revise antes de asociar.");
                    }
                    if ($profile['telefono'] && $found->telefono && $profile['telefono'] !== $found->telefono) {
                        $this->error($result, "Tutor {$profile['email']}: teléfono diferente al de su ficha actual.");
                    }
                    if ($profile['correo_notificaciones'] && $found->correo_notificaciones
                        && mb_strtolower($found->correo_notificaciones,'UTF-8') !== $profile['correo_notificaciones']) {
                        $this->error($result, "Tutor {$profile['email']}: correo de notificaciones diferente al actual.");
                    }
                    $profile['existing_id'] = $found->id;
                    $result['tutors_existing']++;
                } else {
                    $result['tutors_new']++;
                }
            }
            unset($profile);
            foreach ($result['rows'] as &$row) {
                $profile = $result['guardians'][$row['tutor_key']];
                $row['tutor_nombre_completo'] = trim(implode(' ', array_filter([
                    $profile['nombre'], $profile['apellido_paterno'], $profile['apellido_materno'],
                ])));
                $row['tutor_estado'] = $profile['existing_id'] ? 'Se vincula a tutor existente' : 'Se crea tutor';
            }
            unset($row);
            $result['sample'] = array_slice($result['rows'], 0, 10);
            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function personKey(array $profile): string
    {
        return Str::lower(Str::ascii(trim(preg_replace('/\s+/u', ' ', implode(' ', [
            $profile['nombre'] ?? '', $profile['apellido_paterno'] ?? '', $profile['apellido_materno'] ?? '',
        ])))));
    }

    private function emailValue(string $value): string|false|null
    {
        if ($value === '') return null;
        $email = mb_strtolower($value, 'UTF-8');
        return mb_strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
    }

    private function phoneValue(string $value): string|false|null
    {
        if ($value === '') return null;
        $digits = preg_replace('/\D+/', '', $value);
        if (preg_match('/^521\d{10}$/', $digits)) $digits = substr($digits, 3);
        elseif (preg_match('/^52\d{10}$/', $digits)) $digits = substr($digits, 2);
        return preg_match('/^\d{10}$/D', $digits) ? $digits : false;
    }

    private function dateValue(string $value): string|false|null
    {
        if ($value === '') return null;
        foreach (['Y-m-d','d/m/Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date && $date->format($format) === $value) return $date->format('Y-m-d');
        }
        return false;
    }

    private function booleanValue(string $value, bool $default): ?bool
    {
        if ($value === '') return $default;
        $v = Str::lower(Str::ascii(trim($value)));
        if (in_array($v, ['1','si','yes','true'], true)) return true;
        if (in_array($v, ['0','no','false'], true)) return false;
        return null;
    }

    private function error(array &$result, string $message): array
    {
        $result['total_errors']++;
        if (count($result['errors']) < 50) $result['errors'][] = $message;
        return $result;
    }
}
