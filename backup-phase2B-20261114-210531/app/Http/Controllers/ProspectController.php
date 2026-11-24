<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ProspectDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProspectController extends Controller
{
    private const STAGES = ['nouveau','a_qualifier','contacte','interesse','devis_envoye','negociation','gagne','perdu'];
    private const CATEGORIES = ['revendeur','installateur','client_final'];

    public function index(Request $request)
    {
        $query = Client::query()->where('status', 'prospect')->with(['commercial','prospectDetail']);
        if ($request->filled('q')) {
            $term = '%' . addcslashes(trim((string)$request->q), '\\%_') . '%';
            $query->where(fn($q) => $q->where('name','like',$term)->orWhere('company','like',$term)->orWhere('phone','like',$term)->orWhere('email','like',$term));
        }
        if (in_array($request->input('stage'), self::STAGES, true)) {
            $query->whereHas('prospectDetail', fn($q) => $q->where('stage', $request->input('stage')));
        }
        $prospects = $query->latest()->paginate(12)->withQueryString();
        $overdue = Client::query()->where('status','prospect')->whereHas('prospectDetail', fn($q) => $q->where('do_not_contact',false)->where('next_follow_up_at','<=',now())->whereNotIn('stage',['gagne','perdu']))->count();
        $count = Client::query()->where('status','prospect')->count();
        return view('prospects.index', compact('prospects','overdue','count'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'=>['required','string','max:255'], 'company'=>['nullable','string','max:255'],
            'phone'=>['nullable','string','max:30'], 'email'=>['nullable','email','max:255'],
            'city'=>['nullable','string','max:100'], 'category'=>['required','in:revendeur,installateur,client_final'],
            'source'=>['nullable','string','max:120'], 'next_follow_up_at'=>['nullable','date'],
        ]);
        if (empty($data['phone']) && empty($data['email'])) {
            throw ValidationException::withMessages(['phone'=>'Téléphone ou e-mail obligatoire.']);
        }
        // Ne pas révéler les coordonnées de prospects appartenant à un autre commercial.
        $duplicate = Client::withoutGlobalScopes()->where(function($q) use($data) {
            if (!empty($data['email'])) $q->orWhereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])]);
            if (!empty($data['phone'])) $q->orWhere('phone', $data['phone']);
        })->exists();
        if ($duplicate) throw ValidationException::withMessages(['phone'=>'Contact déjà présent dans le CRM. Demandez une vérification à l’administrateur.']);
        DB::transaction(function() use($data) {
            $client = Client::create([
                'commercial_id'=>auth()->id(), 'name'=>$data['name'], 'company'=>$data['company']??null,
                'phone'=>$data['phone']??null, 'email'=>$data['email']??null, 'city'=>$data['city']??null, 'status'=>'prospect',
            ]);
            ProspectDetail::create([
                'client_id'=>$client->id,'category'=>$data['category'],'source'=>$data['source']??null,
                'next_follow_up_at'=>$data['next_follow_up_at']??null,'stage'=>'nouveau',
            ]);
        });
        return redirect()->route('prospects.index')->with('success','Prospect enregistré.');
    }

    public function update(Request $request, Client $client)
    {
        Gate::authorize('update',$client);
        abort_unless($client->status === 'prospect', 422, 'Ce client n’est pas un prospect.');
        $data = $request->validate([
            'stage'=>['required','in:'.implode(',',self::STAGES)],
            'category'=>['required','in:'.implode(',',self::CATEGORIES)],
            'next_follow_up_at'=>['nullable','date'],
            'do_not_contact'=>['required','boolean'],
        ]);
        $detail=$client->prospectDetail()->firstOrCreate([],['stage'=>'nouveau','category'=>'client_final']);
        $detail->update($data);
        $client->activities()->create([
            'user_id'=>auth()->id(),'type'=>'autre',
            'description'=>'Prospection : statut '.str_replace('_',' ',$data['stage']).($data['do_not_contact']?' · contact désinscrit':''),
            'happened_at'=>now(),
        ]);
        return back()->with('success','Suivi mis à jour.');
    }
}
