<?php

namespace App\Services;

use App\Models\DiscoveredProspect;
use Illuminate\Database\Eloquent\Builder;

class ProspectReviewFilterService
{
    /** Restrict filters to short, validated values before building DB queries. */
    public function normalize(array $data): array
    {
        $category = (string) ($data['d_category'] ?? '');
        $category = in_array($category, ['installateur', 'revendeur', 'client_final', 'concurrent'], true) ? $category : '';
        $minScore = (string) ($data['d_score'] ?? '');
        $minScore = in_array($minScore, ['50', '70', '85'], true) ? (int) $minScore : null;
        $clean = static fn ($value, $max) => mb_substr(trim(is_string($value) ? $value : ''), 0, $max);
        return [
            'q' => $clean($data['d_q'] ?? '', 80),
            'city' => $clean($data['d_city'] ?? '', 80),
            'source' => $clean($data['d_source'] ?? '', 50),
            'category' => $category,
            'min_score' => $minScore,
        ];
    }

    public function query(array $filters): Builder
    {
        $query = DiscoveredProspect::query()->where('status', 'pending');
        if ($filters['q'] !== '') {
            $value = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $query->where(function (Builder $sub) use ($value) {
                $sub->where('company', 'like', $value)
                    ->orWhere('email', 'like', $value)
                    ->orWhere('phone', 'like', $value);
            });
        }
        if ($filters['city'] !== '') $query->where('city', $filters['city']);
        if ($filters['source'] !== '') $query->where('source', $filters['source']);
        if ($filters['category'] !== '') $query->where('category', $filters['category']);
        if ($filters['min_score'] !== null) {
            $query->whereRaw('COALESCE(qualification_score, score) >= ?', [$filters['min_score']]);
        }
        return $query->orderByRaw('COALESCE(qualification_score, score) DESC')->orderBy('id');
    }
}
