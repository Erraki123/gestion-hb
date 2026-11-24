<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CommercialAiService;
use App\Services\HnbWhatsAppMessageAccess;
use Illuminate\Http\Request;

class HnbWhatsAppAssistantController extends Controller
{
    public function index(Request $request, HnbWhatsAppMessageAccess $access)
    {
        if ($request->user()->isAdmin()) return redirect()->route('admin.whatsapp.inbox');
        $messages = $access->query($request->user())->with('client:id,name,company')->orderByDesc('received_at')->orderByDesc('id')->simplePaginate(20);
        return response()->view('whatsapp.assistant-inbox', compact('messages'))->header('Cache-Control', 'no-store, private');
    }

    public function compose(Request $request, int $message, HnbWhatsAppMessageAccess $access, CommercialAiService $ai)
    {
        $message = $access->query($request->user())->with('client:id,name,company')->findOrFail($message);
        return response()->view('whatsapp.assistant-compose', ['message'=>$message,'aiEnabled'=>$ai->enabled(),'draft'=>null,'product'=>null,'notice'=>null,'language'=>'fr','sku'=>''])
            ->header('Cache-Control', 'no-store, private');
    }

    public function draft(Request $request, int $message, HnbWhatsAppMessageAccess $access, CommercialAiService $ai)
    {
        // Authorize before validation or any external call; never accept a supplied recipient.
        $message = $access->query($request->user())->with('client:id,name,company')->findOrFail($message);
        $data = $request->validate(['language'=>['required','in:fr,ar'], 'sku'=>['nullable','string','max:100'], 'summary'=>['nullable','string','max:800'], 'use_ai'=>['nullable','boolean']]);
        $language = $data['language'];
        $sku = trim($data['sku'] ?? '');
        $product = $sku === '' ? null : Product::query()->where('is_active', true)->where('sku', $sku)->first(['sku','name','sale_price','stock_quantity','unit','tax_rate']);
        $draft = $language === 'ar' ? 'السلام عليكم، شكراً على تواصلكم مع HN BLOOM. غادي نراجعو طلبكم ونرجعو ليكم بالتفاصيل المؤكدة.' : 'Bonjour, merci pour votre message et votre intérêt pour HN BLOOM. Nous allons vérifier votre demande et revenir vers vous avec les informations confirmées.';
        $notice = null;
        if ($request->boolean('use_ai')) {
            $summary = trim($data['summary'] ?? '');
            if (!$ai->enabled() || $summary === '') {
                $notice = 'IA indisponible ou résumé manquant : brouillon local proposé.';
            } else {
                try {
                    // Only the employee's explicitly supplied non-sensitive summary leaves the CRM.
                    // No inbound body, sender, client details, stock or price is included automatically.
                    $draft = $ai->draft('whatsapp', $language, $summary);
                    $notice = 'Brouillon IA : vérifier toutes les affirmations avant utilisation.';
                } catch (\Exception $e) {
                    $notice = 'Service IA indisponible : brouillon local proposé.';
                }
            }
        }
        if ($sku !== '' && !$product) $notice = trim(($notice ?? '').' Référence active introuvable.');
        return response()->view('whatsapp.assistant-compose', ['message'=>$message,'aiEnabled'=>$ai->enabled(),'draft'=>$draft,'product'=>$product,'notice'=>$notice,'language'=>$language,'sku'=>$sku])
            ->header('Cache-Control', 'no-store, private');
    }
}
