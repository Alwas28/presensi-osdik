<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_a_presensi_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.pengguna')
            ->set('nama', 'Petugas Gerbang A')
            ->set('email', 'petugas.a@umkendari.ac.id')
            ->set('password', 'password123')
            ->set('role', 'presensi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'petugas.a@umkendari.ac.id',
            'role' => 'presensi',
        ]);
    }

    public function test_super_admin_can_create_a_panitia_account(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.pengguna')
            ->set('nama', 'Panitia Baru')
            ->set('email', 'panitia.baru@umkendari.ac.id')
            ->set('password', 'password123')
            ->set('role', 'panitia')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'panitia.baru@umkendari.ac.id',
            'role' => 'panitia',
        ]);
    }

    public function test_email_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['email' => 'sudah.ada@umkendari.ac.id']);

        Volt::actingAs($admin)
            ->test('pages.admin.pengguna')
            ->set('nama', 'Duplikat')
            ->set('email', 'sudah.ada@umkendari.ac.id')
            ->set('password', 'password123')
            ->set('role', 'presensi')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_role_must_be_panitia_or_presensi(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);

        Volt::actingAs($admin)
            ->test('pages.admin.pengguna')
            ->set('nama', 'Coba Jadi Admin')
            ->set('email', 'coba@umkendari.ac.id')
            ->set('password', 'password123')
            ->set('role', 'super_admin')
            ->call('save')
            ->assertHasErrors(['role']);
    }
}
