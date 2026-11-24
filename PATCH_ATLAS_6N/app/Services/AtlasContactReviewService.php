<?php

namespace App\Services;

/**
 * Read-only suggestions from reviewed OSM evidence CSV.
 * Never writes CRM data or treats a suggestion as verified.
 */
final class AtlasContactReviewService
{
    private ?array $index = null;

    public function __construct(private ?string $file = null)
    {
        $this->file ??= storage_path('app/prospect-imports/atlas-contact-review.csv');
    }

    public function forCompany(string $company, ?string $city, ?string $sourceUrl = null): ?array
    {
        $key = self::normalize($company).'|'.self::normalize($city ?? '');
        $rows = $this->load()[$key] ?? [];
        // Duplicates (including branches with the same name/city) are ambiguous.
        if (count($rows) !== 1) {
            return null;
        }
        $row = $rows[0];
        if (($row['contact_status'] ?? '') !== 'review_required') {
            return null;
        }
        // Require provenance identity, not only a name/city match.
        $csvSource = trim($row['source_url'] ?? '');
        if (!$csvSource || !$sourceUrl || trim($sourceUrl) !== $csvSource) {
            return null;
        }
        $evidence = self::httpsUrl($row['contact_evidence_url'] ?? '');
        if ($evidence === null) {
            return null;
        }
        $phone = trim($row['phone'] ?? '');
        if (!preg_match('/^\+?[0-9 .()\-]{8,25}$/', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 9) {
            $phone = '';
        }
        $email = trim($row['email'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = '';
        }
        $website = self::httpsUrl($row['website'] ?? '') ?? '';
        if (!$phone && !$email && !$website) {
            return null;
        }
        return [
            'phone' => $phone,
            'email' => $email,
            'website' => $website,
            'evidence_url' => $evidence,
            'status' => 'review_required',
            'note' => 'Valeurs publiées dans OSM; correspondance proposée, non vérifiée.',
        ];
    }

    private function load(): array
    {
        if ($this->index !== null) {
            return $this->index;
        }
        $this->index = [];
        $file = $this->file;
        if (!is_file($file) || filesize($file) > 2 * 1024 * 1024) {
            return $this->index;
        }
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return $this->index;
        }
        try {
            $header = fgetcsv($handle, 0, ',', '"', '');
            if (!is_array($header)) {
                return $this->index;
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $required = ['company', 'city', 'source_url', 'contact_status', 'contact_evidence_url', 'phone', 'email', 'website'];
            if (array_diff($required, $header)) {
                return $this->index;
            }
            for ($count = 0; $count < 2000 && ($values = fgetcsv($handle, 0, ',', '"', '')) !== false; $count++) {
                if (count($values) !== count($header)) {
                    continue;
                }
                $row = array_combine($header, $values);
                $key = self::normalize($row['company']).'|'.self::normalize($row['city']);
                $this->index[$key][] = $row;
            }
        } finally {
            fclose($handle);
        }
        return $this->index;
    }

    private static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim($value ?? ''), 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }
        return trim(preg_replace('/[^a-z0-9]+/i', ' ', $value));
    }

    private static function httpsUrl(string $value): ?string
    {
        $value = trim($value);
        if (strlen($value) > 2048 || !filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($value);
        return is_array($parts) && strtolower($parts['scheme'] ?? '') === 'https'
            && !isset($parts['user']) && !isset($parts['pass'])
            && !empty($parts['host']) ? $value : null;
    }
}
