<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ModuleNavigationController extends Controller
{
    public function index()
    {
        return view('admin.navigation.ebims-modules');
    }

    public function clients()
    {
        return view('admin.navigation.clients');
    }

    public function loanPortfolio()
    {
        return view('admin.navigation.loan-portfolio');
    }

    public function collections()
    {
        return view('admin.navigation.collections');
    }

    public function reportsAccounting()
    {
        return view('admin.navigation.reports-accounting');
    }

    public function investments()
    {
        return view('admin.navigation.investments');
    }
}
