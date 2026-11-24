<?php

namespace App\Services;

/** Identify the CRM entity and an optional lookup term; never build dynamic SQL. */
class CrmAiIntentService
{
    public function detect(string $question): array
    {
        $q = function_exists('mb_strtolower') ? mb_strtolower(trim($question), 'UTF-8') : strtolower(trim($question));
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
        foreach ($patterns as $domain => $pattern) {
            if (preg_match($pattern, $q)) $found[]=$domain;
        }
        if (count($found) !== 1) return ['mode'=>'clarify','domain'=>null,'search'=>'','type'=>null];
        $domain=$found[0];
        $search='';
        if (preg_match('/["“«\']([^"”»\']{2,70})["”»\']/u', $question, $m)) {
            $search=trim($m[1]);
        } elseif (preg_match('/\b((?:DEV|FAC|COM|CMD|INV|PROD)(?:[-\/][A-Z0-9-]{2,30}|[0-9][A-Z0-9-]{1,29}))\b/i', $question, $m)) {
            $search=trim($m[1]);
        } elseif (preg_match('/(?:produit|client|soci[eé]t[eé]|entreprise|stock de|stock du|sku|r[eé]f[eé]rence)\s*:\s*([^?,.!\n]{2,70})/iu', $question, $m)) {
            $search=trim($m[1]);
        } else {
            $search=$this->naturalSearch($question, $domain);
        }
        $search=$this->cleanSearch($search);
        if ($search==='') return ['mode'=>'need_search','domain'=>$domain,'search'=>'','type'=>null];
        return ['mode'=>'lookup','domain'=>$domain,'search'=>$search,'type'=>null];
    }

    private function naturalSearch(string $question, string $domain): string
    {
        // Short natural language questions such as "le client qui abdo" or "stock du produit caméra".
        $patterns = match ($domain) {
            'clients' => [
                '/\b(?:client|prospect|soci[eé]t[eé]|entreprise)\s+(?:qui\s+)?(?:s[\x{2019}\']appelle\s+|nomm[eé]e?\s+|appel[eé]e?\s+|qui\s+s[\x{2019}\']appelle\s+|est\s+|qui\s+|de\s+|du\s+)?([\p{L}\p{N}][\p{L}\p{N}\s.\'-]{1,65})/iu',
                '/(?:زبون|الزبون|عميل|شركة)\s+([\p{L}\p{N}\s.\'-]{2,65})/u',
            ],
            'products' => [
                '/\bstock\s+(?:du|de la|de l[\x{2019}\']|de)\s+produit\s+([\p{L}\p{N}][\p{L}\p{N}\s.\'-]{1,65})/iu',
                '/\b(?:stock|quantit[eé])\s+(?:de\s+|du\s+|pour\s+|disponible\s+pour\s+)?(?:produit\s+)?([\p{L}\p{N}][\p{L}\p{N}\s.\'-]{1,65})/iu',
                '/\b(?:produit|article|sku)\s+(?:de\s+|du\s+|appel[eé]\s+)?([\p{L}\p{N}][\p{L}\p{N}\s.\'-]{1,65})/iu',
                '/(?:منتج|مخزون|سلعة)\s+([\p{L}\p{N}\s.\'-]{2,65})/u',
            ],
            'quotes' => ['/\bdevis\s+(?:num[eé]ro\s*|n[°o]\s*|de\s+)?([\p{L}\p{N}][\p{L}\p{N}-]{1,45})/iu'],
            'orders' => ['/\bcommande\s+(?:num[eé]ro\s*|n[°o]\s*|de\s+)?([\p{L}\p{N}][\p{L}\p{N}-]{1,45})/iu'],
            'invoices' => ['/\bfacture\s+(?:num[eé]ro\s*|n[°o]\s*|de\s+)?([\p{L}\p{N}][\p{L}\p{N}-]{1,45})/iu'],
            default => [],
        };
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $question, $m)) return $m[1];
        }
        return '';
    }

    private function cleanSearch(string $term): string
    {
        $term=trim($term, " \t\n\r\0\x0B?,.!:;\"' ");
        $term=preg_replace('/\s+(?:s[\x{2019}\']il|stp|svp|s[\x{2019}\']il vous pla[iî]t|merci|dans (?:le|la) crm|sur (?:le|la) crm|et (?:son|sa|ses) .*)$/iu', '', $term);
        $term=trim((string)$term);
        $generic=['qui','est','de','du','les','des','la','le','un','une','tous','toutes','disponible','disponibles','avec','dans','pour','combien','infos','informations','qui est'];
        if (in_array(function_exists('mb_strtolower') ? mb_strtolower($term, 'UTF-8') : strtolower($term), $generic, true)) return '';
        // Do not turn broad list requests into an arbitrary client/stock search.
        if (preg_match('/^(?:tous|toutes|les|liste|total)\b/iu',$term)) return '';
        return function_exists('mb_substr') ? mb_substr($term,0,70) : substr($term,0,70);
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
