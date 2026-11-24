<?php

namespace Tests\Unit;

use App\Services\CrmConversationContextService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CrmConversationContextTest extends TestCase
{
    #[Test]
    public function it_extracts_customer_name_from_natural_question(): void
    {
        $service=new CrmConversationContextService();
        $this->assertSame('Abdo', $service->extractClientName('Cherche le client Abdo et ses devis'));
        $this->assertSame('Abdo', $service->extractClientName('le client qui Abdo'));
    }

    #[Test]
    public function it_detects_customer_followups(): void
    {
        $service=new CrmConversationContextService();
        $this->assertSame('invoices',$service->followupKind('Et ses factures impayées ?'));
        $this->assertSame('quotes',$service->followupKind('Et ses devis ?'));
        $this->assertNull($service->followupKind('Toutes les factures de ce mois'));
    }
}
