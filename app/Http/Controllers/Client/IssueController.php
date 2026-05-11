<?php
namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class IssueController extends Controller
{
    public function publication(Request $request, Magazine $magazine, SubscriptionService $ss)
    {
        Gate::authorize('view', $magazine);

        return view('client.issues', [
            'magazine' => $magazine,
            'issues'   => $ss->getAccessibleIssues($request->user()->client, $magazine),
        ]);
    }

    public function reader(Request $request, MagazineIssue $issue)
    {
        Gate::authorize('view', $issue);

        if (!$issue->pdf_file || !Storage::disk('local')->exists($issue->pdf_file)) {
            abort(404);
        }

        return view('client.reader', compact('issue'));
    }

    public function stream(Request $request, MagazineIssue $issue)
    {
        Gate::authorize('stream', $issue);

        if (!$issue->pdf_file || !Storage::disk('local')->exists($issue->pdf_file)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($issue->pdf_file),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']
        );
    }
}
