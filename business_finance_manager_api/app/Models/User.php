<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @method static where(string $string, mixed $email)
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'business_name',
        'business_logo',
        'default_currency',
        'is_admin',
        'is_protected',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // ===== JWT methods =====
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // ===== Relationships =====
    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function transfers()
    {
        return $this->hasMany(AccountTransfer::class);
    }

    public function monthlySales()
    {
        return $this->hasMany(MonthlySales::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function posOrders()
    {
        return $this->hasMany(PosOrder::class);
    }

    public function incomes()
    {
        return $this->hasMany(Income::class);
    }

    public function shopifySettings()
    {
        return $this->hasOne(ShopifySettings::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            if ($user->email === 'admin@admin.com' || $user->is_protected) {
                throw new HttpResponseException(
                    response()->json([
                        'message' => 'The default admin user cannot be deleted.',
                    ], 403),
                );
            }
        });
    }
}
