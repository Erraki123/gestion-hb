# HN BLOOM — Phase 5B : découverte quotidienne de prospects

## Ce que cette phase fait réellement
- Un job Laravel planifiable 1 fois/jour, désactivé par défaut.
- Un collecteur Overpass/OpenStreetMap **limité** : une ville marocaine par jour en rotation, une requête plafonnée, noms de commerces de sécurité et catégories apparentées.
- Des flux HTTPS JSON **sous contrat/licence**, uniquement après ajout explicite des URL approuvées dans `config/prospect_discovery.php`.
- Déduplication par source et identifiant, scoring **heuristique** (qualité du contact, PAS Machine Learning), conservation des sources.
- Suggestions dans la page **Prospection** existante, volet dépliable réservé à Admin ; accepter avec affectation Commercial ou ignorer.
- Aucune communication WhatsApp/email, aucune création automatique de client. Import seulement après validation.

## Limitations
- Les données OSM peuvent être incomplètes; un concurrent et un installateur ne sont pas toujours distinguables. Vérification humaine obligatoire.
- Les sites n'autorisent pas tous le scraping. Ne pas ajouter de collecteurs non autorisés, ni utiliser un login ou contourner CAPTCHA/robots/CGU.
- Public Overpass a des quotas et une disponibilité non garantie ; **usage commercial récurrent : privilégier un hébergement dédié, un extrait OSM sous licence ou un fournisseur payant**.
- Ne pas utiliser le Nominatim public pour aspirer quotidiennement tout le Maroc (interdit par sa politique).
- Les URL de flux doivent être configurées par un administrateur de confiance ; vérifier l'hébergeur et les droits d'usage.

## Activation volontaire (sur ENV de serveur, valeurs non secrètes)
PROSPECT_DISCOVERY_ENABLED=true
PROSPECT_DISCOVERY_OSM_ENABLED=true
PROSPECT_DISCOVERY_USER_AGENT="HNBloom-CRM/1.0 (contact: tech@votre-domaine.ma)"

Remplacez l'adresse fictive par une adresse technique publique valide et vérifiez les conditions du service choisi. Sur public Overpass, limiter le volume / préférer l'usage d'essai et passer à une instance contractuelle pour une exécution commerciale quotidienne.

### Source JSON approuvée
Dans `config/prospect_discovery.php`, `approved_feeds` :
`'partenaire1' => 'https://votre-partenaire-autorise.ma/companies.json'`
Réponse : `{ "companies": [{"id":"a1", "company":"Société Exemple", "city":"Casablanca", "category":"installateur", "phone":"...", "email":"...", "website":"https://...", "source_url":"https://..."}] }`
L'URL ci-dessus est illustrative et ne constitue PAS une source disponible.

## Test
`php artisan migrate`
`php artisan prospects:discover --dry-run`
`php artisan test --filter=ProspectDiscoveryServiceTest`
`php artisan schedule:list`

## Production / automatisation
Le ZIP ajoute au `routes/console.php` **un seul job quotidien à 09:15**. Pour qu'il tourne chaque jour, il faut que `php artisan schedule:run` soit appelé chaque minute par le cron système de votre serveur, et que le job soit activé. Il ne s'exécute pas automatiquement si le PC est éteint. Le scheduler s'exécutera dans le timezone configuré Laravel : configurez `Africa/Casablanca` si désiré.

## Important
La migration ajoute `discovered_prospects`; sauvegardez votre DB avant de migrer. Le code ne modifie pas les tarifs, commandes ou factures. Pas de `migrate:fresh`.
