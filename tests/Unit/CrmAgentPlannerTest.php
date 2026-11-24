<?php

namespace Tests\Unit;

use App\Services\CrmAgentPlanner;
use PHPUnit\Framework\TestCase;

class CrmAgentPlannerTest extends TestCase
{
    public function test_combines_multiple_crm_domains(): void
    {
        $p=(new CrmAgentPlanner())->plan('Montre les clients et les factures');
        $this->assertSame('multi',$p['mode']);
        $this->assertSame(['clients','invoices'],$p['domains']);
    }

    public function test_lists_products_without_fake_reference(): void
    {
        $p=(new CrmAgentPlanner())->plan('Montre les produits en stock');
        $this->assertSame('list',$p['mode']);
        $this->assertSame('',$p['search']);
    }

    public function test_routes_external_prospection_to_explicit_not_connected_message(): void
    {
        $this->assertSame('external',(new CrmAgentPlanner())->plan('Recherche web concurrents à Casablanca')['mode']);
    }

    public function test_sensitive_action_is_not_executed(): void
    {
        $this->assertSame('action_request',(new CrmAgentPlanner())->plan('Change le prix du produit')['mode']);
    }

    public function test_specific_client_stays_with_specialist(): void
    {
        $this->assertSame('specialist',(new CrmAgentPlanner())->plan('le client abdo')['mode']);
    }
}
