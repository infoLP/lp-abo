<?php
namespace App\Http\Controllers;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class ContactController extends Controller
{
    public function show() { return view('pages.contact'); }
    public function store(Request $request)
    {
        $v = $request->validate(['first_name'=>'required|max:100','last_name'=>'required|max:100','email'=>'required|email','phone'=>'nullable|max:20','subject'=>'required|max:255','message'=>'required|max:5000']);
        try { Contact::create($v); return redirect()->route('contact')->with('success','Message envoye.'); }
        catch (\Exception $e) { Log::error($e->getMessage()); return redirect()->route('contact')->withInput()->with('error','Erreur, reessayez.'); }
    }
}
