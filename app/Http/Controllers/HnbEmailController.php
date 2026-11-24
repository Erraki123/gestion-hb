<?php
namespace App\Http\Controllers;
use App\Models\HnbEmailMessage as Outbound;
use App\Services\{HnbEmailConversation as Email,CommercialAiService};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class HnbEmailController extends Controller
{
    public function index(Request $request,Email $email) {
        $clients=$email->clients($request->user())->orderBy('name')->paginate(25);
        return response()->view('hnb-email.index',compact('clients'))->header('Cache-Control','private, no-store');
    }
    public function show(Request $request,int $client,Email $email,CommercialAiService $ai) { return $this->page($request,$client,$email,$ai); }
    private function page(Request $request,int $id,Email $email,CommercialAiService $ai,string $draft='',?string $notice=null,string $language='fr') {
        $client=$email->clients($request->user())->findOrFail($id);
        $messages=Outbound::where('client_id',$client->id)->with('actor:id,name')->latest('id')->paginate(20);
        return response()->view('hnb-email.client',['client'=>$client,'messages'=>$messages,'enabled'=>$email->enabled(),'recipient'=>$email->recipient($client),'aiEnabled'=>$ai->enabled(),'draft'=>$draft,'notice'=>$notice,'language'=>$language,'templates'=>Email::TEMPLATES])->header('Cache-Control','private, no-store');
    }
    public function draft(Request $request,int $client,Email $email,CommercialAiService $ai) {
        $email->clients($request->user())->findOrFail($client);
        $data=$request->validate(['language'=>'required|in:fr,ar','summary'=>'nullable|string|max:800','use_ai'=>'nullable|boolean']);
        $language=$data['language']; $draft=$language==='ar'?Email::TEMPLATES['ack_ar'][1]:Email::TEMPLATES['ack_fr'][1]; $notice=null;
        if ($request->boolean('use_ai')) {
            $summary=trim($data['summary']??'');
            try { if (!$ai->enabled() || $summary==='') throw new \RuntimeException(); $draft=$ai->draft('email',$language,$summary); $notice='Brouillon IA : vérifiez le texte et séparez l’objet du message.'; }
            catch (\Throwable $e) { $notice='IA indisponible ou résumé manquant : brouillon local proposé.'; }
        }
        return $this->page($request,$client,$email,$ai,$draft,$notice,$language);
    }
    public function send(Request $request,int $client,Email $email) {
        $target=$email->clients($request->user())->findOrFail($client);
        $data=$request->validate(['request_key'=>'required|uuid','preset'=>['nullable',Rule::in(array_keys(Email::TEMPLATES))],'subject'=>'nullable|string|max:180|required_without:preset','body'=>'nullable|string|max:6000|required_without:preset','confirm'=>'accepted']);
        $row=$email->create($request->user(),$target,$data);
        return redirect()->route('hnb.email.show',$client)->with('success','Email enregistré : '.$row->status);
    }
    public function pending(Request $request) {
        abort_unless($request->user()->isAdmin(),403);
        $messages=Outbound::where('status','pending')->with('actor:id,name')->oldest('id')->paginate(20);
        return response()->view('hnb-email.pending',compact('messages'))->header('Cache-Control','private, no-store');
    }
    public function decide(Request $request,int $message,Email $email) {
        abort_unless($request->user()->isAdmin(),403);
        $data=$request->validate(['decision'=>'required|in:approve,reject','confirm'=>'accepted']);
        $row=$email->decide($request->user(),$message,$data['decision']==='approve');
        return redirect()->route('hnb.email.pending')->with('success','Décision enregistrée : '.$row->status);
    }
}
