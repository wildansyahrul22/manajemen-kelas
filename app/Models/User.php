<?php

namespace App\Models;

use App\Enums\ModulLog;
use App\Enums\Role;
use App\Models\Concerns\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['npm', 'name', 'no_hp', 'password', 'role', 'kelas_id', 'kelas_terbang_semester_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_id');
    }

    /**
     * The one semester a "kelas terbang" student belongs to this kelas; null for regular members.
     */
    public function semesterKelasTerbang(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'kelas_terbang_semester_id');
    }

    public function kelompok(): BelongsToMany
    {
        return $this->belongsToMany(Kelompok::class, 'kelompok_anggota', 'user_id', 'kelompok_id')
            ->withPivot('is_ketua')
            ->withTimestamps();
    }

    #[Scope]
    protected function forKelas(Builder $query, int $kelasId): void
    {
        $query->where('kelas_id', $kelasId);
    }

    /**
     * Students of a kelas: plain mahasiswa plus admin kelas (an admin is a student too).
     */
    #[Scope]
    protected function anggotaKelas(Builder $query): void
    {
        $query->whereIn('role', [Role::Mahasiswa, Role::Admin]);
    }

    /**
     * Members that count in the given semester: regular users plus kelas terbang users of that semester.
     */
    #[Scope]
    protected function aktifDiSemester(Builder $query, int $semesterId): void
    {
        $query->where(function (Builder $query) use ($semesterId) {
            $query->whereNull('users.kelas_terbang_semester_id')
                ->orWhere('users.kelas_terbang_semester_id', $semesterId);
        });
    }

    /**
     * Same as aktifDiSemester, but against the parent kelas' active semester — for correlated
     * subqueries such as Kelas::withCount(['mahasiswa' => fn ($q) => $q->aktifDiSemesterKelas()]).
     */
    #[Scope]
    protected function aktifDiSemesterKelas(Builder $query): void
    {
        $query->where(function (Builder $query) {
            $query->whereNull('users.kelas_terbang_semester_id')
                ->orWhereColumn('users.kelas_terbang_semester_id', 'kelas.semester_aktif_id');
        });
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('npm', 'like', "%{$term}%");
        });
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === Role::Mahasiswa;
    }

    /**
     * The shared account /demo signs visitors in as, or null when no demo is configured.
     */
    public static function demoAccount(): ?self
    {
        $npm = config('demo.npm');

        return $npm === null || $npm === '' ? null : self::query()->where('npm', $npm)->first();
    }

    /**
     * Whether this is that account, so the app can say so while someone is looking around.
     */
    public function isDemo(): bool
    {
        $npm = config('demo.npm');

        return $npm !== null && $npm !== '' && $this->npm === $npm;
    }

    public function isKelasTerbang(): bool
    {
        return $this->kelas_terbang_semester_id !== null;
    }

    /**
     * Whether this user is a member of the kelas in the given semester.
     */
    public function aktifPadaSemester(int $semesterId): bool
    {
        return ! $this->isKelasTerbang() || $this->kelas_terbang_semester_id === $semesterId;
    }

    /**
     * Admin kelas or super admin: may manage academic data of a kelas.
     */
    public function canManageKelas(int $kelasId): bool
    {
        return $this->isSuperAdmin() || ($this->isAdmin() && $this->kelas_id === $kelasId);
    }

    /**
     * Whether the user may operate on data of the given kelas at all.
     */
    public function belongsToKelas(int $kelasId): bool
    {
        return $this->isSuperAdmin() || $this->kelas_id === $kelasId;
    }

    /**
     * Human friendly phone number, e.g. +62 812-3456-7890 (null when not set).
     */
    public function noHpFormatted(): ?string
    {
        if ($this->no_hp === null || $this->no_hp === '') {
            return null;
        }

        $digits = substr($this->no_hp, 2);

        return '+62 '.substr($digits, 0, 3).'-'.implode('-', str_split(substr($digits, 3), 4));
    }

    /**
     * WhatsApp link for the phone number, if any.
     */
    public function whatsappUrl(): ?string
    {
        return $this->no_hp ? "https://wa.me/{$this->no_hp}" : null;
    }

    public function initials(): string
    {
        return collect(explode(' ', trim($this->name)))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function activityModul(): ModulLog
    {
        return ModulLog::User;
    }

    public function activityLabel(): string
    {
        return "{$this->name} ({$this->npm})";
    }

    public function activityKelasId(): ?int
    {
        return $this->kelas_id;
    }
}
