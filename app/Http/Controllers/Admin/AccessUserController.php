<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AccessRole, User, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;

class AccessUserController extends Controller
{
    // Roles institucionales que usan User sin perfiles académicos adicionales.
    private const INSTITUTIONAL = ['supervisor','director','subdirector','orientador','pedagogo','secretario_escolar'];
    private function assignableRoles(User $actor)
    {
        return AccessRole::with('permissions')->where(fn ($q) => $q->where('is_system', false)->orWhereIn('slug', self::INSTITUTIONAL))->orderBy('name')->get()
            ->filter(function (AccessRole $role) use ($actor) {
                if ($actor->isSuperAdmin()) return true;
                $forbidden = ['users.manage','roles.manage','whatsapp.manage'];
                return $role->permissions->every(fn ($permission) => !in_array($permission->code, $forbidden, true) && $actor->hasPermission($permission->code));
            });
    }

    private function checkTarget(User $target, User $actor): void
    {
        abort_if($target->isSuperAdmin() || $target->id === $actor->id, 403);
        abort_unless($target->accessRole && (!$target->accessRole->is_system || in_array($target->role, self::INSTITUTIONAL, true)), 403, 'Esta pantalla no modifica cuentas administrativas, docentes, tutores ni estudiantes.');
        abort_unless($this->assignableRoles($actor)->contains('slug', $target->role), 403);
    }

    public function index(Request $request)
    {
        $roles = $this->assignableRoles($request->user());
        $users = User::with('accessRole')->whereHas('accessRole', fn ($q) => $q->where(fn ($i) => $i->where('is_system', false)->orWhereIn('slug', self::INSTITUTIONAL)))
            ->orderBy('apellido_paterno')->orderBy('nombre')->paginate(20);
        return view('admin.access.users', compact('roles','users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required','string','max:100'],
            'apellido_paterno' => ['required','string','max:100'],
            'apellido_materno' => ['nullable','string','max:100'],
            'email' => ['required','email','max:255', Rule::unique('users','email')],
            'phone' => ['nullable','digits:10'],
            'role' => ['required','string','max:30'],
            'password' => ['required','confirmed', Password::min(10)],
        ]);
        abort_unless($this->assignableRoles($request->user())->contains('slug', $data['role']), 403);
        $user = DB::transaction(fn () => User::create(array_merge($data, ['is_approved'=>true])));
        AuditLog::log('WRITE', 'users', $user->id, 'Alta de colaborador con rol: '.$user->role);
        return redirect()->route('admin.users.index')->with('success','Usuario colaborador creado.');
    }

    public function update(Request $request, User $user)
    {
        $this->checkTarget($user, $request->user());
        $data = $request->validate([
            'nombre'=>['required','string','max:100'],
            'apellido_paterno'=>['required','string','max:100'],
            'apellido_materno'=>['nullable','string','max:100'],
            'email'=>['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'phone'=>['nullable','digits:10'],
            'role'=>['required','string','max:30'],
            'password'=>['nullable','confirmed', Password::min(10)],
        ]);
        abort_unless($this->assignableRoles($request->user())->contains('slug', $data['role']), 403);
        if (empty($data['password'])) unset($data['password']);
        $user->update($data);
        AuditLog::log('WRITE','users',$user->id,'Actualización de colaborador y rol: '.$user->role);
        return back()->with('success','Colaborador actualizado.');
    }

    public function status(Request $request, User $user)
    {
        $this->checkTarget($user, $request->user());
        $data = $request->validate(['is_approved'=>['required','boolean']]);
        $user->update(['is_approved'=>(bool) $data['is_approved']]);
        AuditLog::log('WRITE','users',$user->id, 'Acceso de colaborador '.($user->is_approved ? 'habilitado' : 'suspendido'));
        return back()->with('success', 'Estado de acceso actualizado.');
    }
}
