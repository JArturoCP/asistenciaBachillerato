<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccessRole extends Model
{
    protected $table = 'access_roles';
    protected $fillable = ['slug','name','is_system'];
    protected $casts = ['is_system'=>'boolean'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AccessPermission::class, 'access_permission_role', 'role_id', 'permission_id');
    }
}
