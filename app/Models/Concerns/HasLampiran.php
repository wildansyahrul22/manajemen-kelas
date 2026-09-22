<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Parent side of the attachments (informasi, tugas): the upload limits every parent shares, the
 * scoped route binding for /{induk}/lampiran/{lampiran}, and the count helpers the lists and
 * WhatsApp messages use. Using models define LAMPIRAN_DIR and the lampiran() relation.
 */
trait HasLampiran
{
    /** Private disk: attachments are streamed through a route that checks kelas membership. */
    public const string LAMPIRAN_DISK = 'local';

    /** @var list<string> */
    public const array LAMPIRAN_EKSTENSI = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

    /** Per file. */
    public const int LAMPIRAN_MAKS_KB = 2048;

    /** Per parent record. */
    public const int LAMPIRAN_MAKS_JUMLAH = 2;

    abstract public function lampiran(): HasMany;

    public static function bootHasLampiran(): void
    {
        // Deleting through the models (not the FK cascade) so each attachment removes its file. The
        // listener must return nothing: a value would halt the event and skip the later listeners
        // (ModeDemo's refusal among them).
        static::deleting(function (self $model): void {
            $model->lampiran()->get()->each->delete();
        });
    }

    /**
     * Scoped route binding for /{induk}/{ulid}/lampiran/{lampiran}: the relation is named "lampiran"
     * (Indonesian has no plural form), not the "lampirans" Laravel would guess.
     */
    public function resolveChildRouteBinding($childType, $value, $field): ?Model
    {
        if ($childType === 'lampiran') {
            $relasi = $this->lampiran();

            return $relasi->where($field ?? $relasi->getRelated()->getRouteKeyName(), $value)->first();
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }

    /**
     * Number of attached files, from withCount('lampiran') when present, else the loaded relation.
     */
    public function jumlahLampiran(): int
    {
        if (array_key_exists('lampiran_count', $this->attributes)) {
            return (int) $this->attributes['lampiran_count'];
        }

        return $this->lampiran->count();
    }

    public function hasLampiran(): bool
    {
        return $this->jumlahLampiran() > 0;
    }
}
