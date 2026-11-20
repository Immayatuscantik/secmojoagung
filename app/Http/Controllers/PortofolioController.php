<?php

namespace App\Http\Controllers;

use App\Support\Portofolio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortofolioController extends Controller
{
    // Portofolio mentor yang sedang login
    public function index(Request $request)
    {
        $mentor = Auth::user()->mentor->load(['user', 'bidang', 'keahlian']);

        return view('mentor.portofolio', Portofolio::data($mentor, $request) + [
            'mentor' => $mentor,
            'rute'   => 'mentor.portofolio',
            'dasar'  => [],
            'pengelola' => false,
        ]);
    }
}