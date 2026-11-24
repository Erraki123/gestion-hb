<?php

namespace App\Services;

use App\Models\Client;
use App\Models\DiscoveredProspect;
use RuntimeException;

/** Import only explicitly provided, authorized local CSV data; never crawls arbitrary URLs. */
class ProspectCsvImportService
{
    public const HEADERS = ['company', 'city', 'category', 'phone', 'email', 'website', 'source_url', 'id'];

    public function normalize(array $values, string $source): ?array
    {
        $company = trim((string) ($values['company'] ?? ''));
        if (mb_strlen($company) < 3 || mb_strlen($company) > 255) return null;
        $email = mb_strtolower(trim((string) ($values['email'] ?? '')));
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $email = '';
        $website = trim((string) ($values['website'] ?? ''));
        if ($website && (!$this->safeUrl($website))) $website = '';
        $sourceUrl = trim((string) ($values['source_url'] ?? ''));
        if ($sourceUrl && !$this->safeUrl($sourceUrl)) $sourceUrl = '';
        $phone = trim((string) ($values['phone'] ?? ''));
        $phone = mb_substr($phone, 0, 50);
        $category = strtolower(trim((string) ($values['category'] ?? '')));
        if (!in_array($category, ['revendeur','installateur','client_final','concurrent'], true)) $category='client_final';
        $id=trim((string)($values['id'] ?? ''));
        if ($id === '') $id=hash('sha256', mb_strtolower($company).'|'.mb_strtolower(trim((string)($values['city']??''))));
        if (mb_strlen($id)>180) return null;
        return [
            'source' => $source, 'source_id' => $id, 'company' => $company,
            'city'=>mb_substr(trim((string)($values['city']??'')),0,100) ?: null,
            'category'=>$category, 'phone'=>$phone ?: null, 'email'=>$email ?: null,
            'website'=>mb_substr($website,0,500) ?: null,
            'source_url'=>mb_substr($sourceUrl,0,700) ?: null,
            'score'=>min(95,30+($website?15:0)+($phone?20:0)+($email?20:0)),
        ];
    }

    private function safeUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || strtolower((string) parse_url($url, PHP_URL_SCHEME)) !== 'https') return false;
        $host = strtolower((string)parse_url($url, PHP_URL_HOST));
        return $host !== '' && !in_array($host,['localhost'],true) && !filter_var($host,FILTER_VALIDATE_IP);
    }

    /** Row parser is pure so unit tests do not need DB/network. */
    public function readCsv(string $file, string $source, int $limit = 300): array
    {
        $fp=@fopen($file,'rb');
        if (!$fp) throw new RuntimeException('CSV inaccessible.');
        try {
            $first=fgets($fp);
            if ($first===false) throw new RuntimeException('CSV vide.');
            $first=preg_replace('/^\xEF\xBB\xBF/', '', $first);
            $separator=substr_count($first,';')>substr_count($first,',')?';':',';
            rewind($fp);
            $head=fgetcsv($fp,0,$separator,'"','');
            if (!$head) throw new RuntimeException('CSV vide.');
            $head=array_map(fn($v)=>strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '',(string)$v))),$head);
            if (count(array_unique($head))!==count($head) || !in_array('company',$head,true) || count(array_diff($head,self::HEADERS))) {
                throw new RuntimeException('Colonnes CSV invalides. Colonne company obligatoire.');
            }
            $entries=[]; $invalid=0; $line=0;
            while (($row=fgetcsv($fp,0,$separator,'"',''))!==false && $line<$limit) {
                $line++;
                if (count($row)!==count($head)) {$invalid++;continue;}
                $normalized=$this->normalize(array_combine($head,$row),$source);
                if ($normalized) $entries[]=$normalized; else $invalid++;
            }
            return ['rows'=>$entries,'invalid'=>$invalid,'truncated'=>!feof($fp)];
        } finally { fclose($fp); }
    }

    public function import(string $filename,string $source,bool $dryRun=false,int $limit=300): array
    {
        if (!preg_match('/^[a-z0-9_-]{2,40}$/i',$source)) throw new RuntimeException('Nom de source invalide.');
        if (!preg_match('/^[a-zA-Z0-9_.-]+\.csv$/',$filename) || str_contains($filename,'..')) throw new RuntimeException('Nom de fichier invalide.');
        $directory=storage_path('app/prospect-imports');
        $path=$directory.DIRECTORY_SEPARATOR.$filename;
        if (!is_file($path) || is_link($path) || realpath(dirname($path))!==realpath($directory)) throw new RuntimeException('Fichier introuvable dans storage/app/prospect-imports.');
        $parsed=$this->readCsv($path,$source,min(500,max(1,$limit)));
        $report=['source'=>$source,'new'=>0,'existing'=>0,'in_crm'=>0,'invalid'=>$parsed['invalid'],'truncated'=>$parsed['truncated'],'dry_run'=>$dryRun];
        $seen=[];
        foreach ($parsed['rows'] as $entry) {
            $fingerprint=$entry['source'].'|'.$entry['source_id'];
            if (isset($seen[$fingerprint])) {$report['existing']++;continue;}
            $seen[$fingerprint]=true;
            if (DiscoveredProspect::where('source',$entry['source'])->where('source_id',$entry['source_id'])->exists()) {$report['existing']++;continue;}
            $duplicate=DiscoveredProspect::where(function($query) use ($entry){
                if ($entry['email']) $query->orWhere('email',$entry['email']);
                if ($entry['phone']) $query->orWhere('phone',$entry['phone']);
                if ($entry['website']) $query->orWhere('website',$entry['website']);
            });
            if ($entry['email'] || $entry['phone'] || $entry['website']) {
                if ($duplicate->exists()) {$report['existing']++;continue;}
            } else {
                // No contact identifier: avoid repeated names from different lists in same city.
                if (DiscoveredProspect::where('company',$entry['company'])->where('city',$entry['city'])->exists()) {
                    $report['existing']++; continue;
                }
            }
            if ($entry['email'] || $entry['phone']) {
                $crm=Client::withoutGlobalScopes()->where(function($query) use ($entry){
                    if ($entry['email']) $query->orWhere('email',$entry['email']);
                    if ($entry['phone']) $query->orWhere('phone',$entry['phone']);
                });
                if ($crm->exists()) {$report['in_crm']++;continue;}
            }
            if (!$dryRun) DiscoveredProspect::create($entry+['status'=>'pending','discovered_at'=>now()]);
            $report['new']++;
        }
        return $report;
    }
}
