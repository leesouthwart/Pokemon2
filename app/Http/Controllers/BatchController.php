<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Batch;
use Illuminate\Support\Facades\Auth;

class BatchController extends Controller
{
    public function create()
    {
        return view('batch.create');
    }

    public function index()
    {
        $batches = Batch::where('user_id', Auth::id())->get();
        return view('batch.index', compact('batches'));
    }

    public function view(Batch $batch)
    {
        // Check if the batch belongs to the authenticated user
        if ($batch->user_id !== Auth::id()) {
            return redirect()->route('dashboard')->with('error', 'You do not have permission to view this batch.');
        }

        return view('batch.view', compact('batch'));
    }
}
