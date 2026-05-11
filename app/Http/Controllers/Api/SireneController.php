<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\SireneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class SireneController extends Controller
{
    public function lookup(Request $request, SireneService $s): JsonResponse
    {
        $request->validate(['identifier'=>'required|string|min:9|max:14']);
        $result = $s->lookup(preg_replace('/\s+/', '', $request->input('identifier')));
        if (!$result) return response()->json(['success'=>false,'message'=>'Non trouve.'], 404);
        return response()->json(['success'=>true,'data'=>$result,'warning'=>($result['status']??'')==='F'?'Etablissement ferme':null]);
    }
    public function search(Request $request, SireneService $s): JsonResponse
    {
        $request->validate(['q'=>'required|string|min:2']);
        return response()->json(['success'=>true,'data'=>$s->search($request->input('q'))]);
    }
}
