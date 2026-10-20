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
 * Vinculación masiva de tutores con estudiantes YA existentes.
 *
 * Reglas de seguridad:
 * - Nunca crea, edita ni elimina estudiantes.
 * - Si existe matrícula, se usa como identificador principal del estudiante.
 * - Si matrícula está vacía, identifica por nombre completo + fecha de nacimiento + grupo.
 * - Reutiliza tutores existentes por correo; como alternativa, por teléfono + nombre completo.
 * - Nunca sobrescribe los datos de un tutor ya existente.
 * - La relación se crea con fecha_verificacion = null; el consentimiento sigue pendiente.
 */
class StudentTutorCsvImportController extends Controller
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
        'nombre', 'apellido_paterno', 'grupo',
        'tutor_nombre', 'tutor_apellido_paterno', 'tutor_parentesco',
    ];

    public function index()
    {
        return view('admin.students.import_tutors', ['preview' => null]);
    }

    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::COLUMNS, ',', '"', '');
            fclose($out);
        }, 'plantilla_vinculacion_tutores.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'max:2048', function ($attribute, $value, $fail) {
                if (strtolower($value->getClientOriginalExtension()) !== 'csv') {
                    $fail('Sólo se aceptan archivos .csv.');
                }
            }],
        ]);

        $disk = Storage::disk('local');
        foreach ($disk->files('imports/student_tutors') as $stale) {
            if ($disk->lastModified($stale) < time() - 3600) {
                $disk->delete($stale);
            }
        }

        $prior = $request->session()->get('student_tutor_import');
        if ($prior && Str::startsWith($prior['path'] ?? '', 'imports/student_tutors/')) {
            $disk->delete($prior['path']);
        }

        $path = $request->file('archivo')->storeAs(
            'imports/student_tutors',
            Str::uuid().'.csv',
            'local'
        );

        $request->session()->put('student_tutor_import', [
            'path' => $path,
            'at' => time(),
            'owner' => $request->user()->id,
        ]);

        $preview = $this->inspect($path);

        return view('admin.students.import_tutors', compact('preview'));
    }

    public function commit(Request $request)
    {
        $pending = $request->session()->get('student_tutor_import');
        $disk = Storage::disk('local');

        if (!$pending
            || ($pending['owner'] ?? null) !== $request->user()->id
            || time() - ($pending['at'] ?? 0) > self::TTL
            || !Str::startsWith($pending['path'] ?? '', 'imports/student_tutors/')
            || !$disk->exists($pending['path'])) {
            throw ValidationException::withMessages([
                'archivo' => 'La vista previa venció. Cargue nuevamente el CSV.',
            ]);
        }

        // Siempre revalidar inmediatamente antes de escribir.
        $result = $this->inspect($pending['path']);
        if ($result['total_errors'] || !$result['rows']) {
            return view('admin.students.import_tutors', ['preview' => $result]);
        }

        $stats = [
            'tutors_created' => 0,
            'tutors_reused' => 0,
            'tutors_restored' => 0,
            'links_created' => 0,
            'links_existing' => 0,
        ];

        try {
            DB::transaction(function () use ($result, &$stats) {
                $guardians = [];

                // Crear o bloquear cada tutor una sola vez, incluso si tiene varios hijos.
                foreach ($result['guardians'] as $key => $profile) {
                    if ($profile['existing_id']) {
                        $guardian = Tutor::withTrashed()
                            ->with(['user' => fn ($q) => $q->withTrashed()])
                            ->whereKey($profile['existing_id'])
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (!$guardian->user
                            || !$guardian->user->is_approved
                            || !in_array($guardian->user->role, ['parent', 'tutor'], true)) {
                            throw new \RuntimeException('Un tutor existente cambió a un estado incompatible durante la confirmación.');
                        }

                        // Si el tutor fue eliminado suavemente, se reactiva el mismo registro.
                        // Nunca se crea un duplicado y nunca se sobrescriben sus datos con el CSV.
                        $restored = false;
                        if ($guardian->user->trashed()) {
                            $guardian->user->restore();
                            $restored = true;
                        }
                        if ($guardian->trashed()) {
                            $guardian->restore();
                            $restored = true;
                        }

                        if ($restored) {
                            $stats['tutors_restored']++;
                        } else {
                            $stats['tutors_reused']++;
                        }
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
                            // No se importan contraseñas desde CSV.
                            'password' => Str::random(48),
                            'role' => 'parent',
                            'is_approved' => true,
                        ]);

                        $guardian = Tutor::create([
                            'user_id' => $user->id,
                            'parentesco' => $profile['parentesco'],
                            'telefono' => $profile['telefono'],
                            'correo_notificaciones' => $profile['correo_notificaciones'] ?: $profile['email'],
                            // El CSV no equivale a consentimiento para alertas.
                            'alertas_correo_activadas' => false,
                            'alertas_whatsapp_activadas' => false,
                        ]);

                        $stats['tutors_created']++;
                    }

                    $guardians[$key] = $guardian;
                }

                foreach ($result['rows'] as $row) {
                    // Bloquear y volver a confirmar que el estudiante sigue existiendo.
                    $student = Estudiante::whereKey($row['student_id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $guardian = $guardians[$row['tutor_key']];

                    // No usar syncWithoutDetaching con fecha_verificacion=null porque podría
                    // sobrescribir una verificación ya existente.
                    $alreadyLinked = DB::table('estudiante_tutor')
                        ->where('estudiante_id', $student->id)
                        ->where('tutor_id', $guardian->id)
                        ->exists();

                    if ($alreadyLinked) {
                        // Vínculo existente: se ignora completamente. No se toca
                        // fecha_verificacion, consentimiento ni ningún dato del tutor/alumno.
                        $stats['links_existing']++;
                        continue;
                    }

                    $linkedToAnotherTutor = DB::table('estudiante_tutor')
                        ->where('estudiante_id', $student->id)
                        ->where('tutor_id', '!=', $guardian->id)
                        ->exists();

                    if ($linkedToAnotherTutor) {
                        throw new \RuntimeException(
                            "El estudiante {$student->nombre_completo} fue vinculado a otro tutor después de la vista previa."
                        );
                    }

                    $guardian->estudiantes()->attach($student->id, [
                        'fecha_verificacion' => null,
                    ]);
                    $stats['links_created']++;
                }
            }, 3);
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.students.tutors-import.index')
                ->with('error', 'No se aplicó ningún cambio. La base cambió durante la confirmación o existe un dato incompatible. Vuelva a cargar y validar el CSV.');
        }

        $disk->delete($pending['path']);
        $request->session()->forget('student_tutor_import');

        AuditLog::log(
            'WRITE',
            'tutores',
            null,
            "CSV vínculo tutores: {$stats['links_created']} vínculos nuevos; {$stats['links_existing']} ya existentes; {$stats['tutors_created']} tutores creados; {$stats['tutors_reused']} tutores reutilizados; {$stats['tutors_restored']} tutores restaurados. No se modificaron alumnos. Operador ".$request->user()->id
        );

        return redirect()
            ->route('admin.students.index')
            ->with('success',
                "Proceso completado: {$stats['links_created']} vínculos nuevos, {$stats['links_existing']} ya existentes; {$stats['tutors_created']} tutores nuevos, {$stats['tutors_reused']} reutilizados y {$stats['tutors_restored']} restaurados. Ningún alumno fue sobrescrito."
            );
    }

    private function inspect(string $path): array
    {
        $result = [
            'rows' => [],
            'guardians' => [],
            'errors' => [],
            'warnings' => [],
            'sample' => [],
            'count' => 0,
            'total_errors' => 0,
            'total_warnings' => 0,
            'students_matched' => 0,
            'tutors_new' => 0,
            'tutors_existing' => 0,
            'tutors_to_restore' => 0,
            'links_new' => 0,
            'links_existing' => 0,
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
                    $this->error($result, "Falta la columna {$required}.");
                }
            }
            if ($result['total_errors']) {
                return $result;
            }

            $unknown = array_diff($headers, self::COLUMNS);
            if ($unknown) {
                return $this->error($result, 'Columnas no reconocidas: '.implode(', ', $unknown));
            }

            $groups = Grupo::all();
            $groupLookup = $this->groupLookup($groups);

            // Cargar una sola vez los estudiantes existentes; NUNCA se crean desde este flujo.
            $students = Estudiante::with(['user', 'grupo'])->get();
            $studentsByMatricula = [];
            $studentsByIdentity = [];
            $studentsByNameGroup = [];

            foreach ($students as $student) {
                if (!$student->user) {
                    continue;
                }

                if ($student->matricula) {
                    $studentsByMatricula[mb_strtolower(trim($student->matricula), 'UTF-8')] = $student;
                }

                $person = $this->personKey([
                    'nombre' => $student->user->nombre,
                    'apellido_paterno' => $student->user->apellido_paterno,
                    'apellido_materno' => $student->user->apellido_materno,
                ]);
                $date = $student->fecha_nacimiento?->format('Y-m-d') ?? '';
                $identity = $person.'|'.$date.'|'.$student->grupo_id;
                $nameGroup = $person.'|'.$student->grupo_id;

                $studentsByIdentity[$identity][] = $student;
                $studentsByNameGroup[$nameGroup][] = $student;
            }

            $line = 1;
            $parsedRows = [];

            while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
                $line++;
                if (count($cells) === 1 && trim((string) $cells[0]) === '') {
                    continue;
                }

                $result['count']++;
                if ($result['count'] > self::LIMIT) {
                    return $this->error($result, 'Máximo '.self::LIMIT.' filas por archivo. Divida el CSV.');
                }

                if (count($cells) !== count($headers)) {
                    $this->error($result, "Fila {$line}: la cantidad de columnas no coincide con la cabecera.");
                    continue;
                }

                if (!mb_check_encoding(implode('', $cells), 'UTF-8')) {
                    $this->error($result, "Fila {$line}: contenido no UTF-8.");
                    continue;
                }

                $d = array_fill_keys(self::COLUMNS, '');
                foreach (array_combine($headers, $cells) as $key => $value) {
                    $d[$key] = trim((string) $value);
                }

                $errorsBefore = $result['total_errors'];

                foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'tutor_nombre', 'tutor_apellido_paterno', 'tutor_apellido_materno'] as $field) {
                    if (mb_strlen($d[$field]) > 100) {
                        $this->error($result, "Fila {$line}: {$field} supera 100 caracteres.");
                    }
                }

                foreach (['nombre', 'apellido_paterno', 'tutor_nombre', 'tutor_apellido_paterno'] as $field) {
                    if ($d[$field] === '') {
                        $this->error($result, "Fila {$line}: {$field} es obligatorio.");
                    }
                }

                $group = $this->resolveGroup($d['grupo'], $groupLookup);
                if (!$group) {
                    $this->error($result, "Fila {$line}: grupo '{$d['grupo']}' inexistente.");
                }

                $date = $this->dateValue($d['fecha_nacimiento']);
                if ($date === false) {
                    $this->error($result, "Fila {$line}: fecha_nacimiento inválida; use AAAA-MM-DD o DD/MM/AAAA.");
                }

                $tutorEmail = $this->emailValue($d['tutor_email']);
                if ($tutorEmail === false) {
                    $this->error($result, "Fila {$line}: tutor_email no es válido.");
                }

                $tutorPhone = $this->phoneValue($d['tutor_telefono']);
                if ($tutorPhone === false) {
                    $this->error($result, "Fila {$line}: tutor_telefono debe tener 10 dígitos (se acepta +52).");
                }

                $notificationEmail = $this->emailValue($d['tutor_correo_notificaciones']);
                if ($notificationEmail === false) {
                    $this->error($result, "Fila {$line}: tutor_correo_notificaciones no es válido.");
                }

                $relationship = $this->relationshipValue($d['tutor_parentesco']);
                if ($relationship === null) {
                    $this->error($result, "Fila {$line}: tutor_parentesco debe ser padre, madre o tutor_legal.");
                }

                if (!$tutorEmail && !$tutorPhone) {
                    $this->error($result, "Fila {$line}: indique tutor_email o tutor_telefono para identificar de forma segura al tutor.");
                }

                if ($result['total_errors'] !== $errorsBefore || !$group) {
                    continue;
                }

                $student = $this->resolveStudent(
                    $d,
                    $date,
                    $group,
                    $studentsByMatricula,
                    $studentsByIdentity,
                    $studentsByNameGroup,
                    $line,
                    $result
                );

                if (!$student) {
                    continue;
                }

                $profile = [
                    'nombre' => $d['tutor_nombre'],
                    'apellido_paterno' => $d['tutor_apellido_paterno'],
                    'apellido_materno' => $d['tutor_apellido_materno'] ?: null,
                    'parentesco' => $relationship,
                    'email' => $tutorEmail ?: null,
                    'telefono' => $tutorPhone ?: null,
                    'correo_notificaciones' => $notificationEmail ?: ($tutorEmail ?: null),
                    'existing_id' => null,
                    'restore_needed' => false,
                ];

                $tutorKey = $profile['email']
                    ? 'email:'.$profile['email']
                    : 'phone:'.$profile['telefono'].'|'.$this->personKey($profile);

                if (isset($result['guardians'][$tutorKey])) {
                    $previous = $result['guardians'][$tutorKey];
                    if ($this->personKey($previous) !== $this->personKey($profile)) {
                        $this->error($result, "Fila {$line}: el mismo identificador de tutor aparece con nombres diferentes.");
                        continue;
                    }
                    if ($previous['parentesco'] !== $profile['parentesco']) {
                        $this->warning($result, "Fila {$line}: el tutor repetido tiene otro parentesco en el CSV; si ya existe en BD se conservará su ficha actual.");
                    }
                } else {
                    $result['guardians'][$tutorKey] = $profile;
                }

                $parsedRows[] = [
                    'line' => $line,
                    'student_id' => $student->id,
                    'matricula' => $student->matricula,
                    'estudiante' => $student->nombre_completo,
                    'grupo' => $student->grupo?->nombre_visible ?? $group->nombre_visible,
                    'tutor_key' => $tutorKey,
                ];

                $result['students_matched']++;
            }

            // Unificar el mismo tutor si una fila trae correo y otra sólo teléfono,
            // siempre que teléfono y nombre completo sean coincidentes.
            $aliases = [];
            $identityKeys = [];
            foreach ($result['guardians'] as $key => $profile) {
                if (!$profile['telefono']) {
                    continue;
                }

                $identity = $profile['telefono'].'|'.$this->personKey($profile);
                if (!isset($identityKeys[$identity])) {
                    $identityKeys[$identity] = $key;
                    continue;
                }

                $canonical = $identityKeys[$identity];
                if ($canonical === $key) {
                    continue;
                }

                $original = $result['guardians'][$canonical];
                if ($original['email'] && $profile['email'] && $original['email'] !== $profile['email']) {
                    $this->error($result, 'El mismo tutor (nombre y teléfono) aparece con dos correos diferentes.');
                    continue;
                }

                // Preferir el perfil que tenga correo.
                if (!$original['email'] && $profile['email']) {
                    $result['guardians'][$canonical] = $profile;
                }
                $aliases[$key] = $canonical;
            }

            foreach ($aliases as $extra => $canonical) {
                unset($result['guardians'][$extra]);
            }
            foreach ($parsedRows as &$row) {
                $row['tutor_key'] = $aliases[$row['tutor_key']] ?? $row['tutor_key'];
            }
            unset($row);

            $this->resolveExistingTutors($result);

            // Eliminar filas repetidas del mismo alumno-tutor dentro del propio CSV.
            $rows = [];
            $pairsSeen = [];
            foreach ($parsedRows as $row) {
                $pair = $row['student_id'].'|'.$row['tutor_key'];
                if (isset($pairsSeen[$pair])) {
                    $this->warning($result, "Fila {$row['line']}: vínculo repetido en el mismo archivo; se procesará una sola vez.");
                    continue;
                }
                $pairsSeen[$pair] = true;
                $rows[] = $row;
            }

            // Detectar relaciones existentes. La tabla pivote no usa soft delete:
            // si el vínculo ya existe, se ignora y NO se modifica fecha_verificacion.
            $studentIds = array_values(array_unique(array_column($rows, 'student_id')));
            $existingTutorIds = array_values(array_unique(array_filter(array_map(
                fn ($p) => $p['existing_id'] ?? null,
                $result['guardians']
            ))));

            $existingPairs = [];
            $studentExistingTutors = [];
            if ($studentIds) {
                foreach (DB::table('estudiante_tutor')
                    ->whereIn('estudiante_id', $studentIds)
                    ->get(['estudiante_id', 'tutor_id']) as $pivot) {
                    $existingPairs[$pivot->estudiante_id.'|'.$pivot->tutor_id] = true;
                    $studentExistingTutors[$pivot->estudiante_id][] = (int) $pivot->tutor_id;
                }
            }

            foreach ($rows as &$row) {
                $profile = $result['guardians'][$row['tutor_key']];
                $row['tutor'] = trim(implode(' ', array_filter([
                    $profile['nombre'],
                    $profile['apellido_paterno'],
                    $profile['apellido_materno'],
                ])));

                if ($profile['existing_id']) {
                    $row['tutor_estado'] = $profile['restore_needed']
                        ? 'Tutor eliminado — se restaurará'
                        : 'Tutor existente';

                    $pair = $row['student_id'].'|'.$profile['existing_id'];
                    if (isset($existingPairs[$pair])) {
                        $row['vinculo_estado'] = 'Ya vinculado — se ignorará';
                        $result['links_existing']++;
                    } else {
                        $otherTutorIds = array_values(array_filter(
                            $studentExistingTutors[$row['student_id']] ?? [],
                            fn ($id) => (int) $id !== (int) $profile['existing_id']
                        ));

                        if ($otherTutorIds) {
                            $this->error(
                                $result,
                                "Fila {$row['line']}: el estudiante {$row['estudiante']} ya está vinculado con otro tutor. No se reemplazará ni agregará un segundo tutor automáticamente."
                            );
                            $row['vinculo_estado'] = 'Conflicto: ya tiene otro tutor';
                        } else {
                            $row['vinculo_estado'] = 'Se vinculará';
                            $result['links_new']++;
                        }
                    }
                } else {
                    // También respetar la regla actual del sistema: un tutor por estudiante.
                    if (!empty($studentExistingTutors[$row['student_id']])) {
                        $this->error(
                            $result,
                            "Fila {$row['line']}: el estudiante {$row['estudiante']} ya tiene un tutor vinculado. No se creará otro tutor ni se sustituirá la relación."
                        );
                        $row['tutor_estado'] = 'Tutor nuevo';
                        $row['vinculo_estado'] = 'Conflicto: ya tiene otro tutor';
                    } else {
                        $row['tutor_estado'] = 'Tutor nuevo';
                        $row['vinculo_estado'] = 'Se creará y vinculará';
                        $result['links_new']++;
                    }
                }
            }
            unset($row);

            $result['rows'] = $rows;
            $result['sample'] = array_slice($rows, 0, 20);

            return $result;
        } finally {
            fclose($handle);
        }
    }

    private function resolveStudent(
        array $d,
        string|false|null $date,
        Grupo $group,
        array $byMatricula,
        array $byIdentity,
        array $byNameGroup,
        int $line,
        array &$result
    ): ?Estudiante {
        $matricula = trim($d['matricula']);

        if ($matricula !== '') {
            $key = mb_strtolower($matricula, 'UTF-8');
            $student = $byMatricula[$key] ?? null;
            if (!$student) {
                $this->error($result, "Fila {$line}: no existe un estudiante con matrícula '{$matricula}'. No se creará ni modificará ningún alumno.");
                return null;
            }

            $csvPerson = $this->personKey($d);
            $dbPerson = $this->personKey([
                'nombre' => $student->user?->nombre,
                'apellido_paterno' => $student->user?->apellido_paterno,
                'apellido_materno' => $student->user?->apellido_materno,
            ]);
            if ($csvPerson !== $dbPerson) {
                $this->error($result, "Fila {$line}: la matrícula {$matricula} existe, pero el nombre del alumno no coincide con la base de datos.");
                return null;
            }

            if ((int) $student->grupo_id !== (int) $group->id) {
                $this->warning($result, "Fila {$line}: la matrícula {$matricula} está actualmente en '{$student->grupo?->nombre_visible}'. Se usará el alumno existente y NO se cambiará su grupo.");
            }

            return $student;
        }

        if (!$date) {
            $this->error($result, "Fila {$line}: matrícula vacía; para localizar al alumno se requiere fecha_nacimiento junto con nombre y grupo.");
            return null;
        }

        $person = $this->personKey($d);
        $identity = $person.'|'.$date.'|'.$group->id;
        $matches = $byIdentity[$identity] ?? [];

        if (count($matches) === 1) {
            return $matches[0];
        }
        if (count($matches) > 1) {
            $this->error($result, "Fila {$line}: hay más de un alumno con el mismo nombre, fecha de nacimiento y grupo. Capture la matrícula para identificarlo sin ambigüedad.");
            return null;
        }

        // Diagnóstico útil: mismo nombre y grupo pero fecha distinta.
        $nameGroupMatches = $byNameGroup[$person.'|'.$group->id] ?? [];
        if (count($nameGroupMatches) === 1) {
            $candidate = $nameGroupMatches[0];
            $dbDate = $candidate->fecha_nacimiento?->format('d/m/Y') ?? 'sin fecha';
            $this->error($result, "Fila {$line}: se encontró '{$candidate->nombre_completo}' en {$group->nombre_visible}, pero la fecha de nacimiento no coincide (BD: {$dbDate}). Revise el CSV o capture la matrícula.");
            return null;
        }

        $this->error($result, "Fila {$line}: no se encontró al alumno por nombre + fecha de nacimiento + grupo. El proceso no dará de alta estudiantes.");
        return null;
    }

    private function resolveExistingTutors(array &$result): void
    {
        $emails = array_values(array_unique(array_filter(array_map(
            fn ($p) => $p['email'] ?? null,
            $result['guardians']
        ))));
        // El correo de users es el identificador más fuerte. Incluir eliminados suaves
        // permite reutilizar y restaurar el mismo tutor en lugar de crear duplicados.
        $usersByEmail = User::withTrashed()
            ->with(['tutor' => fn ($q) => $q->withTrashed()])
            ->whereIn('email', $emails ?: ['--sin-correos--'])
            ->get()
            ->keyBy(fn ($u) => mb_strtolower(trim((string) $u->email), 'UTF-8'));

        // Buscar también por datos propios del tutor, incluyendo eliminados suaves.
        // Se cargan incluyendo eliminados suaves para poder normalizar teléfonos
        // aun cuando en registros históricos tengan espacios, +52, guiones, etc.
        $candidateTutors = Tutor::withTrashed()
            ->with(['user' => fn ($q) => $q->withTrashed()])
            ->get();

        foreach ($result['guardians'] as $key => &$profile) {
            $found = null;

            // 1) Correo de cuenta: si coincide, se considera el mismo tutor.
            // Las diferencias de nombre se advierten, pero NO provocan un duplicado.
            if ($profile['email']) {
                $user = $usersByEmail->get($profile['email']);
                if ($user) {
                    if (!in_array($user->role, ['parent', 'tutor'], true) || !$user->tutor) {
                        $this->error($result, "El correo {$profile['email']} ya pertenece a una cuenta que no es un tutor reutilizable.");
                        continue;
                    }
                    if (!$user->is_approved) {
                        $this->error($result, "El tutor {$profile['email']} existe, pero su cuenta no está aprobada. Revise el registro antes de vincularlo.");
                        continue;
                    }

                    $found = $user->tutor;
                    if ($this->personKey([
                        'nombre' => $user->nombre,
                        'apellido_paterno' => $user->apellido_paterno,
                        'apellido_materno' => $user->apellido_materno,
                    ]) !== $this->personKey($profile)) {
                        $this->warning($result, "Tutor {$profile['email']}: el nombre del CSV difiere del registro existente. Se reutilizará el tutor existente y no se sobrescribirán sus datos.");
                    }
                }
            }

            // 2) Correo de notificaciones + nombre, útil para registros antiguos donde
            // correo_notificaciones y users.email no son iguales.
            if (!$found && ($profile['correo_notificaciones'] || $profile['email'])) {
                $emailCandidates = array_values(array_unique(array_filter([
                    $profile['correo_notificaciones'],
                    $profile['email'],
                ])));

                $matches = $candidateTutors->filter(function ($tutor) use ($profile, $emailCandidates) {
                    if (!$tutor->user || !in_array($tutor->user->role, ['parent', 'tutor'], true)) {
                        return false;
                    }
                    $stored = mb_strtolower(trim((string) $tutor->correo_notificaciones), 'UTF-8');
                    return $stored !== ''
                        && in_array($stored, $emailCandidates, true)
                        && $this->personKey([
                            'nombre' => $tutor->user->nombre,
                            'apellido_paterno' => $tutor->user->apellido_paterno,
                            'apellido_materno' => $tutor->user->apellido_materno,
                        ]) === $this->personKey($profile);
                });

                if ($matches->count() > 1) {
                    $this->error($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: hay más de una coincidencia por correo de notificaciones y nombre.");
                    continue;
                }
                if ($matches->count() === 1) {
                    $found = $matches->first();
                }
            }

            // 3) Teléfono + nombre completo.
            if (!$found && $profile['telefono']) {
                $matches = $candidateTutors->filter(function ($tutor) use ($profile) {
                    if (!$tutor->user || !in_array($tutor->user->role, ['parent', 'tutor'], true)) {
                        return false;
                    }

                    $storedPhone = $this->phoneValue((string) ($tutor->telefono ?: $tutor->user->phone));
                    if ($storedPhone === false || $storedPhone === null || $storedPhone !== $profile['telefono']) {
                        return false;
                    }

                    return $this->personKey([
                        'nombre' => $tutor->user->nombre,
                        'apellido_paterno' => $tutor->user->apellido_paterno,
                        'apellido_materno' => $tutor->user->apellido_materno,
                    ]) === $this->personKey($profile);
                });

                if ($matches->count() > 1) {
                    $this->error($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: hay más de una coincidencia por nombre y teléfono.");
                    continue;
                }

                if ($matches->count() === 1) {
                    $found = $matches->first();
                    if ($profile['email'] && $found->user->email
                        && mb_strtolower(trim((string) $found->user->email), 'UTF-8') !== $profile['email']) {
                        $this->warning($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: el correo del CSV difiere del registro existente. Se reutilizará el tutor y se conservará su correo actual.");
                    }
                }
            }

            if ($found) {
                if (!$found->user || !$found->user->is_approved) {
                    $this->error($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: existe, pero su cuenta no está aprobada.");
                    continue;
                }

                $profile['existing_id'] = $found->id;
                $profile['restore_needed'] = $found->trashed() || $found->user->trashed();
                $result['tutors_existing']++;

                if ($profile['restore_needed']) {
                    $result['tutors_to_restore']++;
                    $this->warning($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: existe con eliminación suave; se restaurará el mismo registro y no se crearán duplicados.");
                }

                if ($profile['telefono'] && $found->telefono) {
                    $storedPhone = $this->phoneValue((string) $found->telefono);
                    if ($storedPhone && $storedPhone !== $profile['telefono']) {
                        $this->warning($result, "Tutor {$profile['nombre']} {$profile['apellido_paterno']}: el teléfono del CSV difiere de la ficha existente. Se conservará el teléfono actual.");
                    }
                }
            } else {
                $result['tutors_new']++;
            }
        }
        unset($profile);
    }

    private function groupLookup($groups): array
    {
        $lookup = [];
        foreach ($groups as $group) {
            $aliases = [
                $group->codigo_grupo,
                $group->nombre_grupo,
                $group->nombre_visible,
                'Grupo '.$group->codigo_grupo,
                'Grupo '.$group->nombre_grupo,
                'Grupo '.$group->codigo_grupo.' (Turno '.ucfirst($group->turno).')',
                'Grupo '.$group->codigo_grupo.' (Turno '.$group->turno.')',
                'Grupo '.$group->nombre_visible.' (Turno '.ucfirst($group->turno).')',
                'Grupo '.$group->nombre_visible.' (Turno '.$group->turno.')',
            ];

            // Compatibilidad con archivos antiguos: G-1-2 también puede venir como 1-2.
            if (preg_match('/^G-(.+)$/i', (string) $group->codigo_grupo, $matches)) {
                $aliases[] = $matches[1];
                $aliases[] = 'Grupo '.$matches[1];
                $aliases[] = 'Grupo '.$matches[1].' (Turno '.ucfirst($group->turno).')';
            }

            foreach (array_filter($aliases) as $alias) {
                $lookup[$this->groupKey((string) $alias)] = $group;
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

        if (preg_match('/^GRUPO\s+(.+?)\s*\(TURNO\s+.+\)$/u', $key, $matches)) {
            return $lookup[$this->groupKey($matches[1])] ?? null;
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

    private function personKey(array $data): string
    {
        $parts = [
            $data['nombre'] ?? '',
            $data['apellido_paterno'] ?? '',
            $data['apellido_materno'] ?? '',
        ];

        return implode('|', array_map(function ($value) {
            $value = strtoupper(Str::ascii(trim((string) $value)));
            return preg_replace('/\s+/', ' ', $value) ?? $value;
        }, $parts));
    }

    private function emailValue(string $value): string|false|null
    {
        if ($value === '') {
            return null;
        }

        $email = mb_strtolower(trim($value), 'UTF-8');
        return mb_strlen($email) <= 255 && filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $email
            : false;
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

    private function relationshipValue(string $value): ?string
    {
        $v = Str::lower(Str::ascii(trim($value)));
        $v = str_replace(['-', ' '], '_', $v);

        return match ($v) {
            'padre' => 'padre',
            'madre' => 'madre',
            'tutor', 'tutor_legal', 'tutorlegal' => 'tutor_legal',
            default => null,
        };
    }

    private function error(array &$result, string $message): array
    {
        $result['total_errors']++;
        if (count($result['errors']) < 50) {
            $result['errors'][] = $message;
        }
        return $result;
    }

    private function warning(array &$result, string $message): void
    {
        $result['total_warnings']++;
        if (count($result['warnings']) < 50) {
            $result['warnings'][] = $message;
        }
    }
}
