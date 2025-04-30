<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    public const CONFIG_KEYS = [
        'similarity_threshold',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'image',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    // Get all configs as an associative array: [key => value]
    public function getConfigs(): array
    {
        return $this->configs->pluck('value', 'key')->toArray();
    }

    // Get a specific config by key
    public function getConfig(string $key): mixed
    {
        return $this->configs->firstWhere('id', $key)?->value;
    }

    // Method to set config for a user
    public function setConfig(string $key, mixed $value): Config
    {
        return $this->configs()->updateOrCreate(
            ['key' => $key], // Criteria to find the config for this user
            ['value' => $value] // The value to update or create with
        );
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            // Get default configs (where user_id is null)
            $defaults = Config::whereNull('user_id')
                ->whereIn('id', self::CONFIG_KEYS)
                ->get();

            // Clone them for the new user
            $userConfigs = $defaults->map(function (Config $config) use ($user) {
                return [
                    'key'       => $config->key,
                    'value'    => $config->getRawOriginal('value'), // avoid casting issues
                    'user_id'  => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

            // Insert all in one go
            Config::insert($userConfigs->toArray());
        });
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function answerKeys()
    {
        return $this->hasMany(AnswerKey::class);
    }

    public function answerSheets()
    {
        return $this->hasMany(AnswerSheet::class);
    }

    public function configs()
    {
        return $this->hasMany(Config::class);
    }
}