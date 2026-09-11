<?php

namespace App\Support;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Session\Session;

/**
 * Resolves which kelas the current request operates on.
 *
 * Mahasiswa and admin are locked to their own kelas. Super admin picks one
 * from the header filter; the choice is remembered in the session.
 */
class KelasContext
{
    public const string SESSION_KEY = 'kelas_aktif_id';

    protected ?Kelas $kelas = null;

    /**
     * Id of the user the memoised kelas was resolved for (null = not resolved yet).
     */
    protected ?int $resolvedFor = null;

    public function __construct(
        protected AuthFactory $auth,
        protected Session $session,
    ) {}

    public function current(): ?Kelas
    {
        /** @var User|null $user */
        $user = $this->auth->guard()->user();

        if ($user === null) {
            return null;
        }

        if ($this->resolvedFor === $user->id) {
            return $this->kelas;
        }

        $this->resolvedFor = $user->id;

        return $this->kelas = $user->isSuperAdmin()
            ? $this->resolveForSuperAdmin()
            : $this->find($user->kelas_id);
    }

    public function id(): ?int
    {
        return $this->current()?->id;
    }

    /**
     * Switch the super admin's active kelas.
     */
    public function switchTo(Kelas $kelas): void
    {
        $this->session->put(self::SESSION_KEY, $kelas->id);
        $this->kelas = $kelas->loadMissing('semesterAktif');
        $this->resolvedFor = $this->auth->guard()->id();
    }

    /**
     * Drop the memoised kelas so the next call re-reads it (e.g. after the active semester changed).
     */
    public function refresh(): void
    {
        $this->resolvedFor = null;
        $this->kelas = null;
    }

    protected function resolveForSuperAdmin(): ?Kelas
    {
        $kelas = $this->find($this->session->get(self::SESSION_KEY))
            ?? Kelas::query()->with('semesterAktif')->orderBy('nama')->first();

        if ($kelas !== null && $this->session->get(self::SESSION_KEY) !== $kelas->id) {
            $this->session->put(self::SESSION_KEY, $kelas->id);
        }

        return $kelas;
    }

    protected function find(?int $id): ?Kelas
    {
        if ($id === null) {
            return null;
        }

        return Kelas::query()->with('semesterAktif')->find($id);
    }
}
