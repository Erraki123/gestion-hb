<?php

namespace App\Services;

/** Deterministic plan for allowlisted, read-only CRM tools (no model-generated SQL). */
class CrmAgentPlanner
{
    public function plan(string $question): array
    {
        $q = function_exists('mb_strtolower') ? mb_strtolower(trim($question), 'UTF-8') : strtolower(trim($question));
        if (preg_match('/(?:scrap|recherche web|sur internet|sur google|concurren|competitor|linkedin|google maps|prospection externe|cherche.*(?:entreprises|installateurs).*\b(?:à|au|dans)\b)/iu', $q)) {
            return ['mode'=>'external', 'domains'=>[], 'search'=>''];
        }
        if (preg_match('/(?:envoi(?:e|er)?|exp[eé]die|supprime|modifie|change le prix|valide|confirme|ajoute.*(?:client|commande)|أرسل|احذف|بدل الثمن)/iu', $q)) {
            return ['mode'=>'action_request', 'domains'=>[], 'search'=>''];
        }
        $patterns = [
            'clients' => '/\b(client|clients|prospect|prospects|soci[eé]t[eé]|customer)\b|زبون|الزبناء|عملاء|شركة/u',
            'products' => '/\b(produit|produits|article|articles|stock|stocks|sku|inventaire|cam[eé]ra)\b|منتج|مخزون|سلعة/u',
            'quotes' => '/\b(devis|quotation|offre de prix)\b|عرض الثمن/u',
            'orders' => '/\b(commande|commandes|order)\b|طلبات|طلبية/u',
            'invoices' => '/\b(facture|factures|invoice)\b|فواتير|فاتورة/u',
        ];
        $domains=[];
        foreach ($patterns as $domain=>$pattern) {
            if (preg_match($pattern,$q)) $domains[]=$domain;
        }
        if (!$domains) return ['mode'=>'fallback','domains'=>[],'search'=>''];
        // The existing specialist is responsible for extracting exact quoted names/references.
        // Do not pretend a generic noun is a product name.
        $search='';
        if (preg_match('/["“«\']([^"”»\']{2,70})["”»\']/u',$question,$m)) {
            $search=trim($m[1]);
        } elseif (preg_match('/\b((?:DEV|FAC|COM|CMD|INV|PROD|CAM)(?:[-\/][A-Z0-9-]{2,30}|[0-9][A-Z0-9-]{1,29}))\b/i',$question,$m)) {
            $search=trim($m[1]);
        }
        $list = preg_match('/\b(liste|list|montre|affiche|tous|toutes|quels|quelles|r[eé]sum[eé]|aper[cç]u|rapport|combien)\b|عطيني|وريني|شحال|لائحة|ملخص/u',$q);
        if (count($domains)>1) return ['mode'=>'multi','domains'=>array_slice($domains,0,3),'search'=>$search];
        if ($list && $search==='') return ['mode'=>'list','domains'=>$domains,'search'=>''];
        return ['mode'=>'specialist','domains'=>$domains,'search'=>$search];
    }
}
