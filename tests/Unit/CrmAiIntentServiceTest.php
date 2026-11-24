<?php

namespace Tests\Unit;

use App\Services\CrmAiIntentService;
use PHPUnit\Framework\TestCase;

class CrmAiIntentServiceTest extends TestCase
{
    public function test_recognizes_product_reference(): void
    {
        $result=(new CrmAiIntentService())->detect('Quel est le stock du produit "CAM-001" ?');
        $this->assertSame('lookup',$result['mode']);
        $this->assertSame('products',$result['domain']);
        $this->assertSame('CAM-001',$result['search']);
    }

    public function test_ambiguous_question_does_not_query_the_database(): void
    {
        $result=(new CrmAiIntentService())->detect('Donne moi les clients et toutes les factures');
        $this->assertSame('clarify',$result['mode']);
    }

    public function test_draft_is_detected_without_write_actions(): void
    {
        $result=(new CrmAiIntentService())->detect('Rédige un email professionnel court pour un client');
        $this->assertSame('draft',$result['mode']);
        $this->assertSame('email',$result['type']);
    }

    public function test_no_broad_database_dump_without_reference(): void
    {
        $result=(new CrmAiIntentService())->detect('Montre le stock disponible');
        $this->assertSame('need_search',$result['mode']);
    }
}
