<?php

namespace App\Models\Concerns;

use App\Enums\AksiLog;
use App\Enums\ModulLog;
use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Writes an ActivityLog row whenever the model is created, updated (with the changed attributes)
 * or deleted. Models say which modul they are, how to label a row, and which kelas it belongs to.
 */
trait LogsActivity
{
    abstract public function activityModul(): ModulLog;

    abstract public function activityLabel(): string;

    abstract public function activityKelasId(): ?int;

    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => $model->catatAktivitas(AksiLog::Buat));

        static::updated(function (Model $model) {
            $perubahan = $model->activityChanges();

            if ($perubahan !== []) {
                $model->catatAktivitas(AksiLog::Ubah, $perubahan);
            }
        });

        static::deleted(fn (Model $model) => $model->catatAktivitas(AksiLog::Hapus));
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $perubahan
     */
    public function catatAktivitas(AksiLog $aksi, array $perubahan = []): ActivityLog
    {
        return ActivityLog::catat(
            aksi: $aksi,
            modul: $this->activityModul(),
            label: $this->activityLabel(),
            kelasId: $this->activityKelasId(),
            subjekId: $this->getKey(),
            perubahan: $perubahan,
        );
    }

    /**
     * Attributes never written to the log (their new value is masked).
     *
     * @return list<string>
     */
    protected function activityHidden(): array
    {
        return ['password'];
    }

    /**
     * Attributes ignored entirely (remember_token rotates on every logout).
     *
     * @return list<string>
     */
    protected function activityIgnored(): array
    {
        return ['created_at', 'updated_at', 'remember_token'];
    }

    /**
     * Changed attributes of the save that just happened, as attribute => [before, after].
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function activityChanges(): array
    {
        $perubahan = [];

        foreach (Arr::except($this->getChanges(), $this->activityIgnored()) as $key => $after) {
            if (in_array($key, $this->activityHidden(), true)) {
                $perubahan[$key] = ['••••••', '(diubah)'];

                continue;
            }

            $before = $this->getRawOriginal($key);

            // Raw values on both sides, except booleans which the form sets as true/false and the DB stores as 0/1.
            if ($this->hasCast($key, ['bool', 'boolean'])) {
                $before = $before === null ? null : (bool) $before;
                $after = $after === null ? null : (bool) $after;
            }

            $perubahan[$key] = [self::activityValue($before), self::activityValue($after)];
        }

        return $perubahan;
    }

    protected static function activityValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if (is_string($value) && mb_strlen($value) > ActivityLog::PANJANG_NILAI) {
            return mb_substr($value, 0, ActivityLog::PANJANG_NILAI).'…';
        }

        return $value;
    }
}
