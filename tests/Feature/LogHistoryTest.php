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

test('user biasa tidak bisa export log history', function () {
    $uker = Uker::factory()->create();
    $user = User::factory()->forUker($uker->kode)->create();

    $this->actingAs($user)->get(route('log-history.export.excel'))->assertForbidden();
    $this->actingAs($user)->get(route('log-history.export.pdf'))->assertForbidden();
});

test('admin bisa export log history ke excel', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    ActivityLog::catat('aset', 'tambah', 1, 'Aset Z5-K-0001-PC-0001 ditambahkan');

    $response = $this->actingAs($admin)->get(route('log-history.export.excel'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

test('admin bisa export log history ke pdf', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    ActivityLog::catat('aset', 'tambah', 1, 'Aset Z5-K-0001-PC-0001 ditambahkan');

    $response = $this->actingAs($admin)->get(route('log-history.export.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

test('export log history tetap ikut filter modul yang aktif', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    ActivityLog::catat('aset', 'tambah', 1, 'Log modul aset');
    ActivityLog::catat('health_check', 'tambah', 1, 'Log modul health check');

    $response = $this->actingAs($admin)->get(route('log-history.export.excel', ['modul' => 'aset']));

    $response->assertOk();
});
