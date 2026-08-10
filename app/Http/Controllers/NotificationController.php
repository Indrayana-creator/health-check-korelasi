<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // Ditandai dibaca terus diarahkan ke halaman relevan (url yang kesimpen
    // di data notifikasinya) -- dipanggil pas notifikasi diklik di dropdown lonceng.
    public function markRead(Request $request, string $notification)
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $item->markAsRead();

        return redirect($item->data['url'] ?? route('dashboard'));
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }
}
