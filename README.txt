HN BLOOM - AI Grounding Baseline Tests

Scope: 7 non-mutating tests of current planner/intent/fallback behavior.
No controller, service, migration, PHP config, database, deployment or API key changes.

Install: extract this ZIP at Laravel project root.
Run: php artisan test --filter=HnbAiGroundingBaselineTest

These tests DO NOT verify DeepSeek live connectivity or provide accurate total sales figures.
The audited CrmAiContextService fetches maximum 12 rows per domain, and does not have
an aggregate financial reporting service. Reliable sales and receivables totals are a
separate next implementation, after clarifying dates, statuses and commercial scopes.
