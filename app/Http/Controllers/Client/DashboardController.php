<?php
namespace App\Http\Controllers\Client;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function index(Request $request, SubscriptionService $ss)
    {
        $client = $request->user()->client;
        if (!$client) return redirect()->route('client.profile.create');
        $publications = $ss->getAccessiblePublications($client);
        $subscriptions = $client->subscriptions()->with('magazine','subscriptionPlan','payer')->latest()->get();
        $recentIssues = $ss->getAllAccessibleIssues($client)->take(10);
        $beneficiaries = collect(); $paidSubscriptions = collect(); $payerAccount = $client->payerAccount;
        if ($client->is_payer) {
            $beneficiaries = $client->directBeneficiaries()->with(['activeSubscriptions.magazine'])->get();
            $paidSubscriptions = $client->paidSubscriptions()->with(['client','magazine'])->where('status','active')->get();
        }
        return view('client.dashboard', compact('client','publications','subscriptions','recentIssues','beneficiaries','paidSubscriptions','payerAccount'));
    }
}
