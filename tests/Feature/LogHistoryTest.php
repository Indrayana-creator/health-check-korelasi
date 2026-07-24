<?php

use App\Models\ActivityLog;
use App\Models\Uker;
use App\Models\User;

test('user biasa tidak bisa akses log history', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('log-history.index'))->assertForbidden();
});

test('admin bisa melihat log history dan filter berdasarkan modul', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    ActivityLog::catat('aset', 'upload_massal', 5, 'Upload massal dari file: test.xlsx');
    ActivityLog::catat('health_check', 'delete_massal', 2, 'Delete massal dari file: test2.xlsx');

    $response = $this->actingAs($admin)->get(route('log-history.index', ['modul' => 'aset']));

    $response->assertOk();
    $logs = $response->viewData('logs');
    expect($logs->total())->toBe(1);
    expect($logs->first()->modul)->toBe('aset');
});
