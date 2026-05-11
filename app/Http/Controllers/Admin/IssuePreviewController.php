<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MagazineIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class IssuePreviewController extends Controller
{
    public function reader(Request $request, MagazineIssue $issue)
    {
        Gate::authorize('admin-preview');

        if (!$issue->pdf_file || !Storage::disk('local')->exists($issue->pdf_file)) {
            abort(404);
        }

        return view('admin.issue-reader', compact('issue'));
    }

    public function stream(Request $request, MagazineIssue $issue)
    {
        Gate::authorize('admin-preview');

        if (!$issue->pdf_file || !Storage::disk('local')->exists($issue->pdf_file)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($issue->pdf_file),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']
        );
    }
}
