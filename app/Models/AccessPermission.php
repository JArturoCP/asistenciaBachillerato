<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessPermission extends Model
{
    protected $table = 'access_permissions';
    public $timestamps = false;
    protected $fillable = ['code','group_name','name'];
}
