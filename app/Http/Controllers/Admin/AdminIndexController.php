<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminIndexController extends Controller
{

    public function index(Request $request)
    {

        $uzytkownik = $request->user();

        return view('admin.index')->with('uzytkownik', $uzytkownik);

    }

}
