<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DiscoveredProspect;
use App\Models\ProspectDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class DiscoveryReviewController extends Controller {
    public function accept(Request $request, DiscoveredProspect $candidate) {
        abort_unless($candidate->status==='pending',409);
        $data=$request->validate(['commercial_id'=>['required','integer','exists:users,id']]);
        abort_unless(User::whereKey($data['commercial_id'])->where('role','commercial')->where('is_active',true)->exists(),422);
        if (!$candidate->email && !$candidate->phone) throw ValidationException::withMessages(['candidate'=>'Contact professionnel manquant. Vérifiez la source avant import.']);
        DB::transaction(function () use ($candidate,$data) {
            $candidate=DiscoveredProspect::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            abort_unless($candidate->status==='pending',409);
            $duplicate=Client::withoutGlobalScopes()->where(function($q) use ($candidate){
                if ($candidate->email) $q->orWhere('email',$candidate->email);
                if ($candidate->phone) $q->orWhere('phone',$candidate->phone);
            })->exists();
            if ($duplicate) throw ValidationException::withMessages(['candidate'=>'Contact déjà dans le CRM.']);
            $client=Client::create(['commercial_id'=>$data['commercial_id'],'name'=>$candidate->company,'company'=>$candidate->company,
                'phone'=>$candidate->phone,'email'=>$candidate->email,'city'=>$candidate->city,'status'=>'prospect']);
            ProspectDetail::create(['client_id'=>$client->id,'source'=>$candidate->source,'category'=>in_array($candidate->category,['revendeur','installateur','client_final'],true)?$candidate->category:'client_final','stage'=>'nouveau']);
            $candidate->update(['status'=>'accepted','client_id'=>$client->id]);
        });
        return back()->with('success','Entreprise ajoutée comme prospect. Aucun message envoyé.');
    }
    public function dismiss(DiscoveredProspect $candidate) {
        abort_unless($candidate->status==='pending',409);
        $candidate->update(['status'=>'dismissed']);
        return back()->with('success','Suggestion ignorée.');
    }
}
