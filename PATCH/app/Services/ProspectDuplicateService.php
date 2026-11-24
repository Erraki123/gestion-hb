<?php

namespace App\Services;

use App\Models\Client;

/** Detect duplicate prospect contacts across owners, without disclosing their details. */
class ProspectDuplicateService
{
    public function normalizePhone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $raw);
        if ($digits === '') return null;

        if (str_starts_with($digits, '00212')) $digits = substr($digits, 3);
        if (str_starts_with($digits, '212') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 9 && preg_match('/^[567]/', $digits)) {
            $digits = '0' . $digits;
        }
        if (strlen($digits) < 8 || strlen($digits) > 15) return null;

        return $digits;
    }

    public function samePhone(?string $left, ?string $right): bool
    {
        $a = $this->normalizePhone($left);
        return $a !== null && $a === $this->normalizePhone($right);
    }

    public function normalizeEmail(?string $raw): ?string
    {
        $email = mb_strtolower(trim((string) $raw));
        return $email !== '' ? $email : null;
    }

    public function sameEmail(?string $left, ?string $right): bool
    {
        $email = $this->normalizeEmail($left);
        return $email !== null && $email === $this->normalizeEmail($right);
    }

    /** Returns only a boolean; never exposes another commercial's prospect. */
    public function exists(?string $email, ?string $phone): bool
    {
        $email = $this->normalizeEmail($email);
        $normalizedPhone = $this->normalizePhone($phone);

        if ($email !== null && Client::withoutGlobalScopes()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
            return true;
        }

        if ($normalizedPhone === null) return false;

        // Current client table has no dedicated normalized-phone column.
        // Iterate only ID/phone and compare canonical forms to catch 06 / +2126 / formatted numbers.
        // A normalized indexed column can replace this for large production datasets.
        foreach (Client::withoutGlobalScopes()->whereNotNull('phone')
            ->select(['id', 'phone'])->lazyById(250) as $client) {
            if ($this->samePhone($normalizedPhone, $client->phone)) return true;
        }
        return false;
    }
}
