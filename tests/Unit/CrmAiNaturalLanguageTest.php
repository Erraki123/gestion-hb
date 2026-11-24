<?php

namespace Tests\Unit;

use App\Services\CrmAiIntentService;
use PHPUnit\Framework\TestCase;

class CrmAiNaturalLanguageTest extends TestCase
{
    public function test_client_who_abdo_is_searched_directly(): void
    {
        $detected=(new CrmAiIntentService())->detect('le client qui abdo');
        $this->assertSame('lookup',$detected['mode']);
        $this->assertSame('clients',$detected['domain']);
        $this->assertSame('abdo',$detected['search']);
    }

    public function test_stock_of_named_product_is_searched(): void
    {
        $detected=(new CrmAiIntentService())->detect('stock du produit caméra IP');
        $this->assertSame('lookup',$detected['mode']);
        $this->assertSame('products',$detected['domain']);
        $this->assertSame('caméra IP',$detected['search']);
    }

    public function test_vague_stock_question_never_dumps_full_table(): void
    {
        $detected=(new CrmAiIntentService())->detect('Montre le stock disponible');
        $this->assertSame('need_search',$detected['mode']);
    }
}
