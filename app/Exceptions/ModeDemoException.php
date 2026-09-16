<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown when the shared demo account tries to write anything. Livewire pages turn it into a toast
 * (see the Notifies trait) so the visitor can keep exploring.
 */
class ModeDemoException extends Exception
{
    public const string PESAN = 'Mode demo: perubahan tidak disimpan. Silakan lanjut mencoba fitur lainnya.';

    public function __construct(string $message = self::PESAN)
    {
        parent::__construct($message);
    }
}
