<?php
namespace Tests\Unit;
use App\Models\Product;
use App\Services\HnbMarketPriceComparison;
use Tests\TestCase;

class HnbMarketPriceComparisonTest extends TestCase
{
    public function test_same_tax_basis_is_used(): void
    {
        $p=new Product(['sale_price'=>1000,'tax_rate'=>20]);$service=new HnbMarketPriceComparison();
        $this->assertSame(100.0,$service->compare($p,'TTC',1100)['difference']);
        $this->assertSame(-100.0,$service->compare($p,'HT',1100)['difference']);
    }
    public function test_unknown_tax_basis_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new HnbMarketPriceComparison())->compare(new Product(['sale_price'=>1000]),'unknown',100);
    }
}
