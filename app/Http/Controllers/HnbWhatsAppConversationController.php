<?php
namespace App\Http\Controllers;

use App\Models\HnbWhatsAppOutboundMessage as Outbound;
use App\Services\HnbWhatsAppConversation as Conversation;
use App\Services\HnbWhatsAppMessageAccess;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HnbWhatsAppConversationController extends Controller
{
    public function show(Request $request, int $message, HnbWhatsAppMessageAccess $access, Conversation $conversation, WhatsAppService $wa)
    {
        $message=$access->query($request->user())->with('client:id,name,company')->findOrFail($message);
        $canSend=$conversation->validTarget($message);
        $incoming=$access->query($request->user())->where('client_id',$message->client_id)->where('sender',$message->sender)->latest('id')->limit(30)->get();
        $outgoing=$message->client_id ? Outbound::where('client_id',$message->client_id)->where('recipient',$message->sender)->with('actor:id,name','director:id,name')->latest('id')->limit(30)->get() : collect();
        $timeline=$incoming->toBase()->map(fn($m)=>['when'=>$m->received_at ?? $m->created_at,'incoming'=>true,'message'=>$m])->concat($outgoing->map(fn($m)=>['when'=>$m->created_at,'incoming'=>false,'message'=>$m]))->sortBy('when');
        return response()->view('whatsapp.conversation',['message'=>$message,'timeline'=>$timeline,'canSend'=>$canSend,'enabled'=>$wa->enabled(),'templates'=>Conversation::TEMPLATES])->header('Cache-Control','private, no-store');
    }

    public function send(Request $request, int $message, Conversation $conversation)
    {
        $message=$conversation->target($request->user(),$message);
        $data=$request->validate(['request_key'=>['required','uuid'],'preset'=>['nullable',Rule::in(array_keys(Conversation::TEMPLATES))],'body'=>['nullable','string','max:4096','required_without:preset'],'confirm'=>['accepted']]);
        $row=$conversation->create($request->user(),$message,$data);
        return redirect()->route('whatsapp.assistant.conversation',$message->id)->with('success','Message enregistré : '.$row->status.'. Consultez son état ci-dessous.');
    }

    public function pending(Request $request)
    {
        abort_unless($request->user()->isAdmin(),403);
        $messages=Outbound::where('status','pending')->with('actor:id,name')->oldest('id')->paginate(20);
        return response()->view('whatsapp.pending',compact('messages'))->header('Cache-Control','private, no-store');
    }

    public function decide(Request $request, int $outbound, Conversation $conversation)
    {
        abort_unless($request->user()->isAdmin(),403);
        $data=$request->validate(['decision'=>['required','in:approve,reject'],'confirm'=>['accepted']]);
        $row=$conversation->decide($request->user(),$outbound,$data['decision']==='approve');
        return redirect()->route('whatsapp.assistant.pending')->with('success','Décision enregistrée : '.$row->status.'.');
    }
}
