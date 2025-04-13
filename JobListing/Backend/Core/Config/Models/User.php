<?php 

namespace App\Core\Config\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model {
    use SoftDeletes;

    protected $table = 'users';
    
    protected $fillable = [
        'email',
        'usertype',
        'srcode',
        'password',
        'status'
    ];

    protected $hidden = [
        'password'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function setPasswordAttribute($value) {
        $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
    }

    public function verifyPassword($password) {
        return password_verify($password, $this->attributes['password']);
    }

    public function setSrcodeAttribute($value) {
        $this->attributes['srcode'] = $value;
    }
}

?>