# HN BLOOM ATLAS 6S — Offline Smart Scoring V2

This patch works with the user's local Phase 6F/6K CSV files. It is **not AI inference**, does not require a key, and does not query the Internet. It does not touch Laravel models, existing scores, scheduler, backups, or the database.

Installation from project root: expand ZIP, then `powershell -ExecutionPolicy Bypass -File .\INSTALL_ATLAS_PHASE6S.ps1`.

Execute: `python .\tools\atlas_smart_score.py`.

Outputs in `storage/app/prospect-imports`: `atlas-smart-score-v2.csv` and `atlas-smart-score-v2.json`. These contain rule-based opportunity priority, data confidence, sector hypothesis, source, and next action. `review_required` is never upgraded into verified. No API key necessary. Nothing is automatically shown in the Laravel UI yet. Review output before any DB import.
