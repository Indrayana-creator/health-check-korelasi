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

    // Dipanggil berkala via fetch dari lonceng notifikasi di topbar, biar
    // badge & daftarnya keupdate tanpa user harus reload halaman.
    public function poll(Request $request)
    {
        $notifications = $request->user()->notifications()->latest()->take(8)->get();

        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'items' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? '',
                'read' => (bool) $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }
}
