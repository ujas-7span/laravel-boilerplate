<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use SensitiveParameter;
use App\Traits\HasMedia;
use App\Traits\ApiQueryable;
use Laravel\Sanctum\HasApiTokens;
use Database\Factories\UserFactory;
use App\Attributes\RequiresRelation;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use ApiQueryable, HasApiTokens, HasFactory, HasMedia, Notifiable;

    /**
     * Default computed attributes automatically attached into resource responses.
     *
     * @var list<string>
     */
    protected $appends = ['is_verified'];

    /**
     * Whitelisted relationships for eager loading via ?include=...
     *
     * @var array<string>
     */
    public array $allowedIncludes = ['tokens', 'media'];

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

    /**
     * Computed accessor: User initials (e.g. "John Doe" -> "JD").
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! array_key_exists('name', $this->attributes)) {
                    return 'U';
                }

                $words = explode(' ', trim((string) $this->name));
                $initials = '';
                foreach (array_slice($words, 0, 2) as $w) {
                    $initials .= strtoupper($w[0] ?? '');
                }

                return $initials !== '' ? $initials : 'U';
            }
        );
    }

    /**
     * Computed accessor: Email verification status (included in default $appends).
     */
    protected function isVerified(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => array_key_exists('email_verified_at', $this->attributes) && $this->email_verified_at !== null
        );
    }

    /**
     * Computed accessor: Latest token name.
     * Declares its relation requirement directly on the function via #[RequiresRelation].
     * The query pipeline automatically eager loads 'tokens' to prevent N+1 queries.
     */
    #[RequiresRelation('tokens:id,tokenable_id,tokenable_type,name,created_at')]
    protected function latestTokenName(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value !== null) {
                    return $value;
                }

                return $this->relationLoaded('tokens')
                    ? $this->tokens->sortByDesc('id')->first()?->name
                    : null;
            }
        );
    }

    /**
     * Computed accessor: Avatar URL.
     * Declares its relation requirement directly via #[RequiresRelation].
     * The query pipeline automatically eager loads 'media' to prevent N+1 queries.
     */
    #[RequiresRelation('media')]
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value !== null) {
                    return $value;
                }

                return $this->relationLoaded('media')
                    ? $this->getFirstMediaUrl('avatar')
                    : null;
            }
        );
    }

    /**
     * Send the password reset notification via queued notification.
     */
    public function sendPasswordResetNotification(#[SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\Auth\ResetPasswordNotification($token));
    }
}
