<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HnbQuoteLearning;
use App\Services\HnbQuoteLearningModel;

final class HnbQuoteLearningController extends Controller
{
    public function index(HnbQuoteLearning $learning)
    {
        return view('quotes.learning',['summary'=>$learning->summary(),'report'=>null]);
    }
    public function collect(HnbQuoteLearning $learning)
    {
        $r=$learning->collect();
        return redirect()->route('admin.quote-learning.index')->with('success',"{$r['captured']} instantané(s) créé(s), {$r['resolved']} résultat(s) observé(s).");
    }
    public function report(HnbQuoteLearning $learning,HnbQuoteLearningModel $model)
    {
        return view('quotes.learning',['summary'=>$learning->summary(),'report'=>$learning->report($model)]);
    }
}
