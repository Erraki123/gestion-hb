<?php

namespace Tests\Feature;

use App\Services\CrmAgentPlanner;
use App\Services\CrmAiAnswerService;
use App\Services\CrmAiIntentService;
use Tests\TestCase;

class HnbAiGroundingBaselineTest extends TestCase
{
    public function test_explicit_document_reference_is_extracted_without_sql(): void
    {
        $intent = app(CrmAiIntentService::class)->detect('Quel est le statut de la facture "FAC-2026-001" ?');

        $this->assertSame('lookup', $intent['mode']);
        $this->assertSame('invoices', $intent['domain']);
        $this->assertSame('FAC-2026-001', $intent['search']);
    }

    public function test_broad_client_list_is_treated_as_preview_not_exact_search(): void
    {
        $plan = app(CrmAgentPlanner::class)->plan('Liste des clients');

        $this->assertSame('list', $plan['mode']);
        $this->assertSame(['clients'], $plan['domains']);
        $this->assertSame('', $plan['search']);
    }

    public function test_combined_domains_are_planned_as_read_only_preview(): void
    {
        $plan = app(CrmAgentPlanner::class)->plan('Montre les clients et les produits');

        $this->assertSame('multi', $plan['mode']);
        $this->assertContains('clients', $plan['domains']);
        $this->assertContains('products', $plan['domains']);
    }

    public function test_sensitive_price_change_is_classified_as_action_request(): void
    {
        $plan = app(CrmAgentPlanner::class)->plan('Change le prix du produit');

        $this->assertSame('action_request', $plan['mode']);
    }

    public function test_external_prospect_search_is_not_mistaken_for_internal_data(): void
    {
        $plan = app(CrmAgentPlanner::class)->plan('Recherche web concurrents Casablanca');

        $this->assertSame('external', $plan['mode']);
    }

    public function test_local_fallback_uses_only_supplied_records_and_does_not_invent_totals(): void
    {
        config()->set('commercial_ai.enabled', false);
        $context = [
            'domaine' => 'invoices',
            'limite' => 12,
            'resultats' => [
                ['numero' => 'FAC-2026-777', 'statut' => 'emise', 'total_ttc' => 1500.0, 'paye' => 250.0],
            ],
        ];

        $answer = app(CrmAiAnswerService::class)->answer('Quelle est la facture FAC-2026-777 ?', $context);

        $this->assertStringContainsString('FAC-2026-777', $answer);
        $this->assertStringContainsString('1500', $answer);
        $this->assertStringNotContainsString('FAC-OTHER', $answer);
        $this->assertStringNotContainsString('ventes de ce mois', $answer);
    }

    public function test_empty_authorized_result_does_not_invent_customer(): void
    {
        config()->set('commercial_ai.enabled', false);
        $answer = app(CrmAiAnswerService::class)->answer('Client X ?', [
            'domaine' => 'clients', 'resultats' => [],
        ]);

        $this->assertStringContainsString('aucun résultat', $answer);
    }
}
