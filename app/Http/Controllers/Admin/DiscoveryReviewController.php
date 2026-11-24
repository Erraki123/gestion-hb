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
        DB::transaction(function () use ($candidate,$data) {
            $candidate=DiscoveredProspect::whereKey($candidate->id)->lockForUpdate()->firstOrFail();
            abort_unless($candidate->status==='pending',409);
            if ($candidate->category === 'concurrent') throw ValidationException::withMessages(['candidate'=>'Concurrent : veille uniquement, import commercial interdit.']);
            $email=trim((string)$candidate->email);
            $phone=trim((string)$candidate->phone);
            $duplicates=app(\App\Services\ProspectDuplicateService::class);
            if (($email !== '' && !filter_var($email,FILTER_VALIDATE_EMAIL)) || ($phone !== '' && (!$duplicates->normalizePhone($phone) || !preg_match('/^\+?[0-9 .()\-]+$/', $phone)))) {
                throw ValidationException::withMessages(['candidate'=>'Coordonnées invalides. Vérifiez la source avant import.']);
            }
            if ($email === '' && $phone === '') throw ValidationException::withMessages(['candidate'=>'Contact professionnel manquant. Vérifiez la source avant import.']);
            if ($duplicates->exists($email ?: null,$phone ?: null)) throw ValidationException::withMessages(['candidate'=>'Contact déjà dans le CRM.']);
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
