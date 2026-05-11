<?php
namespace App\Http\Controllers;
use App\Models\Magazine;
class PageController extends Controller
{
    public function home() { return view('pages.home', ['magazines' => Magazine::where('is_active',true)->orderBy('sort_order')->get()]); }
    public function about() { return view('pages.about'); }
    public function magazines() { return view('pages.magazines', ['magazines' => Magazine::where('is_active',true)->with(['subscriptionPlans'=>fn($q)=>$q->where('is_active',true)->orderBy('sort_order')])->orderBy('sort_order')->get()]); }
}
