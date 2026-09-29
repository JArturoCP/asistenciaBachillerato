<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AccessRole, AccessPermission, User, AuditLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccessRoleController extends Controller
{
    public function index()
    {
        $roles = AccessRole::with('permissions')->orderBy('is_system','desc')->orderBy('name')->get();
        $permissions = AccessPermission::orderBy('group_name')->orderBy('name')->get()->groupBy('group_name');
        return view('admin.access.roles', compact('roles','permissions'));
    }

    private function validateData(Request $request, ?AccessRole $role = null): array
    {
        return $request->validate([
            'name'=>['required','string','max:100'],
            'slug'=>['required','string','max:30','regex:/^[a-z][a-z0-9_]*$/', Rule::unique('access_roles','slug')->ignore($role?->id)],
            'permissions'=>['nullable','array'],
            'permissions.*'=>['string', Rule::in(array_keys(config('access.permissions')))],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $role = DB::transaction(function () use ($data) {
            $role = AccessRole::create(['name'=>$data['name'], 'slug'=>$data['slug'], 'is_system'=>false]);
            $ids = AccessPermission::whereIn('code',$data['permissions'] ?? [])->pluck('id');
            $role->permissions()->sync($ids);
            return $role;
        });
        AuditLog::log('WRITE','access_roles',$role->id,'Rol creado: '.$role->slug);
        return back()->with('success','Rol creado correctamente.');
    }

    public function update(Request $request, AccessRole $accessRole)
    {
        abort_if($accessRole->is_system,403,'Los roles base no se modifican desde aquí.');
        $data = $this->validateData($request,$accessRole);
        abort_if($data['slug'] !== $accessRole->slug && User::withTrashed()->where('role',$accessRole->slug)->exists(),422,'No cambie la clave de un rol asignado.');
        DB::transaction(function () use ($data,$accessRole) {
            $accessRole->update(['name'=>$data['name'],'slug'=>$data['slug']]);
            $ids = AccessPermission::whereIn('code',$data['permissions'] ?? [])->pluck('id');
            $accessRole->permissions()->sync($ids);
        });
        AuditLog::log('WRITE','access_roles',$accessRole->id,'Permisos de rol actualizados: '.$accessRole->slug);
        return back()->with('success','Rol y permisos actualizados.');
    }

    public function destroy(AccessRole $accessRole)
    {
        abort_if($accessRole->is_system,403);
        abort_if(User::withTrashed()->where('role',$accessRole->slug)->exists(),422,'El rol está asignado a usuarios. Reasígnelos antes.');
        $name = $accessRole->name;
        $accessRole->delete();
        AuditLog::log('WRITE','access_roles',null,'Rol eliminado: '.$name);
        return back()->with('success','Rol eliminado.');
    }
}
