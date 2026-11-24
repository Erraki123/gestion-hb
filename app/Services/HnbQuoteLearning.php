<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class HnbQuoteLearning
{
    private const TABLE = 'hnb_quote_learning_samples';

    private function eligible(): Builder
    {
        return DB::table('quotes as q')->whereNull('q.deleted_at')
            ->whereIn('q.status',['brouillon','envoye'])->where('q.approval_status','approved')
            ->where('q.total_ttc','>',0)->whereDate('q.issue_date','<=',today())
            ->where(fn($q)=>$q->whereNull('q.valid_until')->orWhereDate('q.valid_until','>=',today()))
            ->whereNotExists(fn($q)=>$q->selectRaw('1')->from('orders as o')->whereColumn('o.quote_id','q.id'));
    }

    /** Explicit bounded collection, immutable first snapshot. No business data writes. */
    public function collect(): array
    {
        $now=now()->toDateTimeString();
        $resolved=0; $captured=0;
        $candidates=DB::table(self::TABLE.' as s')->join('quotes as q','q.id','=','s.quote_id')
            ->leftJoin('orders as o','o.quote_id','=','q.id')->whereNull('s.outcome')->where('s.captured_at','<',$now)
            ->whereNull('q.deleted_at')->whereColumn('q.client_id','s.client_id')
            ->where(fn($q)=>$q->where(fn($q)=>$q->whereNull('o.id')->where('q.status','refuse'))
                ->orWhere(fn($q)=>$q->whereNull('o.deleted_at')->whereIn('o.status',['confirmee','terminee'])->where('o.approval_status','approved')->whereColumn('o.client_id','s.client_id')->whereColumn('o.created_at','>=','s.captured_at')))
            ->orderBy('s.id')->limit(1000)->get(['s.id','s.quote_id','s.client_id','s.captured_at']);
        foreach ($candidates as $s) {
            $resolved+=DB::transaction(function() use($s,$now) {
                $q=DB::table('quotes')->where('id',$s->quote_id)->lockForUpdate()->first();
                if (!$q || $q->deleted_at || (int)$q->client_id !== (int)$s->client_id) return 0;
                $o=DB::table('orders')->where('quote_id',$s->quote_id)->lockForUpdate()->first();
                $outcome=null;
                if (!$o && $q->status==='refuse') $outcome=0;
                if ($o && !$o->deleted_at && (int)$o->client_id===(int)$s->client_id && $o->created_at >= $s->captured_at && in_array($o->status,['confirmee','terminee'],true) && $o->approval_status==='approved') $outcome=1;
                if ($outcome===null) return 0;
                return DB::table(self::TABLE)->where('id',$s->id)->whereNull('outcome')->update(['outcome'=>$outcome,'observed_at'=>$now]);
            });
        }
        $ids=$this->eligible()->whereNotExists(fn($q)=>$q->selectRaw('1')->from(self::TABLE.' as s')->whereColumn('s.quote_id','q.id'))->orderBy('q.id')->limit(500)->pluck('q.id');
        foreach ($ids as $id) {
            $captured+=DB::transaction(function() use($id) {
                $q=$this->eligible()->where('q.id',$id)->lockForUpdate()->first(['q.id','q.client_id','q.total_ttc','q.issue_date']);
                if (!$q) return 0;
                $at=now();
                $age=(int)CarbonImmutable::parse($q->issue_date)->startOfDay()->diffInDays($at->copy()->startOfDay());
                return DB::table(self::TABLE)->insertOrIgnore(['quote_id'=>$q->id,'client_id'=>$q->client_id,'amount_ttc'=>$q->total_ttc,'age_days'=>max(0,min(90,$age)),'captured_at'=>$at->toDateTimeString()]);
            });
        }
        return ['captured'=>$captured,'resolved'=>$resolved];
    }

    public function summary(): array
    {
        $q=DB::table('quotes')->whereNull('deleted_at');
        $history=['refused'=>(clone $q)->where('status','refuse')->whereNotExists(fn($q)=>$q->selectRaw('1')->from('orders')->whereColumn('orders.quote_id','quotes.id'))->count(),
            'converted'=>(clone $q)->whereExists(fn($q)=>$q->selectRaw('1')->from('orders')->whereColumn('orders.quote_id','quotes.id')->whereNull('orders.deleted_at')->whereIn('orders.status',['confirmee','terminee'])->where('orders.approval_status','approved'))->count()];
        return ['history'=>$history,'captured'=>DB::table(self::TABLE)->count(), 'positive'=>DB::table(self::TABLE)->where('outcome',1)->count(),'negative'=>DB::table(self::TABLE)->where('outcome',0)->count()];
    }

    public function report(HnbQuoteLearningModel $model): array
    {
        // Last 2000 resolved records, not a guarantee of future conversion.
        $rows=DB::table(self::TABLE.' as s')->join('quotes as q','q.id','=','s.quote_id')->whereNull('q.deleted_at')->whereColumn('q.client_id','s.client_id')->whereNotNull('s.outcome')->orderByDesc('s.captured_at')->orderByDesc('s.id')->limit(2000)->get(['s.*'])->map(function($r){$a=(array)$r;$a['outcome']=(int)$a['outcome'];return $a;})->all();
        $evaluation=$model->evaluate($rows); $scores=[];
        if ($evaluation['ready']) {
            $open=$this->eligible()->join(self::TABLE.' as s','s.quote_id','=','q.id')->whereNull('s.outcome')->whereColumn('q.client_id','s.client_id')->whereColumn('q.total_ttc','s.amount_ttc')->where('s.captured_at','>=',now()->subDays(30))->orderByDesc('s.captured_at')->orderByDesc('s.id')->limit(100)->get(['q.id','q.number','s.amount_ttc','s.age_days','s.captured_at']);
            foreach ($open as $r) $scores[]=['number'=>$r->number,'id'=>$r->id,'captured_at'=>$r->captured_at,'score'=>(int)round(100*$model->predict($evaluation['weights'],(float)$r->amount_ttc,(int)$r->age_days))];
            usort($scores,fn($a,$b)=>$b['score']<=>$a['score']); $scores=array_slice($scores,0,30);
        }
        unset($evaluation['weights']);
        return ['evaluation'=>$evaluation,'scores'=>$scores];
    }
}
