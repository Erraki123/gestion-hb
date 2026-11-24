<?php

namespace Tests\Unit;

use App\Services\ProspectReviewFilterService;
use PHPUnit\Framework\TestCase;

class ProspectReviewFilterServiceTest extends TestCase
{
    public function test_normalizes_valid_filters(): void
    {
        $result = (new ProspectReviewFilterService())->normalize([
            'd_q' => '  sécurité  ', 'd_city' => 'Agadir', 'd_category' => 'revendeur',
            'd_source' => 'annuaire_maroc', 'd_score' => '70',
        ]);
        $this->assertSame('sécurité', $result['q']);
        $this->assertSame('Agadir', $result['city']);
        $this->assertSame('revendeur', $result['category']);
        $this->assertSame('annuaire_maroc', $result['source']);
        $this->assertSame(70, $result['min_score']);
    }

    public function test_disallows_unknown_filter_values(): void
    {
        $result = (new ProspectReviewFilterService())->normalize(['d_score'=>'999', 'd_category'=>'admin', 'd_q'=>['bad']]);
        $this->assertNull($result['min_score']);
        $this->assertSame('', $result['category']);
        $this->assertSame('', $result['q']);
    }
}
