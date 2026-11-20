<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfilMentorController extends Controller
{
    // Profil Saya (mentor hanya dapat melihat; perubahan dilakukan owner/admin)
    public function index(Request $request)
    {
        $mentor = Auth::user()->mentor->load(['user', 'bidang', 'keahlian']);
        $tab = $request->tab === 'bidang' ? 'bidang' : 'profil';

        return view('mentor.profil', compact('mentor', 'tab'));
    }
}