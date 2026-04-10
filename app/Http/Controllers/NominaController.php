<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NominaController extends Controller
{
    public function index()
    {
        return view('nomina.index');
    }
}
