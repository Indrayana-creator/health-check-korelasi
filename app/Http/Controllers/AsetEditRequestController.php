<?php

namespace App\Http\Controllers;

use App\Models\AsetEditRequest;
use Illuminate\Http\Request;

class AsetEditRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = AsetEditRequest::with(['aset.uker', 'requester'])
            ->orderByRaw("status = 'Menunggu' desc")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('edit-requests.index', compact('requests'));
    }
}
