<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Batch;

class BatchController extends Controller
{
    public function create()
    {
        return view('batch.create');
    }

    public function index()
    {
        $batches = Batch::all();
        return view('batch.index', compact('batches'));
    }

    public function view(Batch $batch)
    {
        return view('batch.view', compact('batch'));
    }
}
