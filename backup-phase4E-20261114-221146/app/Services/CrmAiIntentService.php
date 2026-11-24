<?php

namespace App\Services;

/** Simple, explainable intent detection. Does not execute model-generated SQL. */
class CrmAiIntentService
{
    public function detect(string $question): array
    {
        $q = function_exists("mb_strtolower") ? mb_strtolower(trim($question), "UTF-8") : strtolower(trim($question));
        $draft = $this->isDraft($q);
        if ($draft !== null) return ['mode'=>'draft','type'=>$draft,'domain'=>null,'search'=>''];

        $patterns = [
            'invoices' => '/\b(facture|factures|invoice|fac\s*[-#]?\s*\d+)\b|فاتور/u',
            'quotes' => '/\b(devis|quotation|offre de prix|dev\s*[-#]?\s*\d+)\b|عرض الثمن/u',
            'orders' => '/\b(commande|commandes|order|com\s*[-#]?\s*\d+)\b|طلبي|طلبات/u',
            'products' => '/\b(produit|produits|product|stock|stocks|quantit[eé]|r[eé]f[eé]rence|sku|cam[eé]ra|nvr|dvr)\b|منتج|مخزون|كامير|سلعة/u',
            'clients' => '/\b(client|clients|prospect|prospects|soci[eé]t[eé]|entreprise|customer)\b|زبون|عميل|شركة/u',
        ];
        $found=[];
        foreach ($patterns as $domain=>$pattern) {
            if (preg_match($pattern,$q)) $found[]=$domain;
        }
        if (count($found) !== 1) {
            return ['mode'=>'clarify','domain'=>null,'search'=>'','type'=>null];
        }
        $domain=$found[0];
        // Extract a precise quoted or coded identifier. Otherwise do not pretend to know a lookup term.
        $search='';
        if (preg_match('/["“«\']([^"”»\']{2,70})["”»\']/u',$question,$m)) {
            $search=trim($m[1]);
        } elseif (preg_match('/\b((?:DEV|FAC|COM|CMD|INV|PROD)[-\/]?[A-Z0-9-]{2,30})\b/i',$question,$m)) {
            $search=trim($m[1]);
        } elseif (preg_match('/(?:produit|client|soci[eé]t[eé]|entreprise|stock de|stock du|sku|r[eé]f[eé]rence)\s*:\s*([^?,.!\n]{2,70})/iu',$question,$m)) {
            $search=trim($m[1]);
        }
        if ($search === '') {
            return ['mode'=>'need_search','domain'=>$domain,'search'=>'','type'=>null];
        }
        return ['mode'=>'lookup','domain'=>$domain,'search'=>$search,'type'=>null];
    }

    private function isDraft(string $q): ?string
    {
        if (!preg_match('/\b(r[eé]dig|[eé]cri|pr[eé]pare|g[eé]n[eè]re|r[eé]ponse [àa]|write|draft)\w*\b|كتب|صيغ|وجد.*رسالة/u',$q)) return null;
        if (preg_match('/whatsapp|واتساب/u',$q)) return 'whatsapp';
        if (preg_match('/relance|rappel|تابع|تذكير/u',$q)) return 'relance';
        if (preg_match('/email|e-mail|mail|courriel|إيميل|بريد/u',$q)) return 'email';
        return null;
    }
}
