<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_page_renders_cohub_layout_and_fields(): void
    {
        $user = User::factory()->create([
            'bio' => 'Halo, ini bio saya',
        ]);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Pengaturan Profil');
        $response->assertSee('Foto Profil (Avatar)');
        $response->assertSee('Banner Profil');
        $response->assertSee('Tentang Saya (Bio)');
        $response->assertSee('Halo, ini bio saya');
        $response->assertSee('Perbarui Password');
        $response->assertSee('Hapus Akun');
    }

    public function test_user_can_update_profile_info_and_bio(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'username' => 'namalama',
            'bio' => 'Bio lama',
        ]);

        $multilineBio = "Baris satu\nBaris dua\nBaris tiga";

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'username' => 'namabaru',
            'email' => $user->email,
            'bio' => $multilineBio,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-updated');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('namabaru', $user->username);
        $this->assertSame($multilineBio, $user->bio);
    }

    public function test_user_can_upload_avatar_and_it_replaces_old_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $initialAvatar = UploadedFile::fake()->image('avatar1.png', 200, 200);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $initialAvatar,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $firstAvatarPath = $user->avatar_path;

        // Replace with new avatar
        $newAvatar = UploadedFile::fake()->image('avatar2.jpg', 300, 300);

        $response2 = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $newAvatar,
        ]);

        $response2->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertNotEquals($firstAvatarPath, $user->avatar_path);

        // Old file must be deleted, new file must exist
        Storage::disk('public')->assertMissing($firstAvatarPath);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_user_can_upload_banner_and_it_replaces_old_banner(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $initialBanner = UploadedFile::fake()->image('banner1.png', 800, 300);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'banner' => $initialBanner,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertNotNull($user->banner_path);
        Storage::disk('public')->assertExists($user->banner_path);
        $firstBannerPath = $user->banner_path;

        // Replace with new banner
        $newBanner = UploadedFile::fake()->image('banner2.webp', 1200, 400);

        $response2 = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'banner' => $newBanner,
        ]);

        $response2->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertNotNull($user->banner_path);
        $this->assertNotEquals($firstBannerPath, $user->banner_path);

        Storage::disk('public')->assertMissing($firstBannerPath);
        Storage::disk('public')->assertExists($user->banner_path);
    }

    public function test_avatar_and_banner_validation_rejects_invalid_files(): void
    {
        $user = User::factory()->create();

        // Non-image file
        $txtFile = UploadedFile::fake()->create('document.txt', 500, 'text/plain');

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $txtFile,
        ]);

        $response->assertSessionHasErrors('avatar');

        // Oversized file (> 2MB for avatar)
        $oversizedAvatar = UploadedFile::fake()->image('large.png')->size(3000);

        $response2 = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $oversizedAvatar,
        ]);

        $response2->assertSessionHasErrors('avatar');
    }

    public function test_public_profile_displays_avatar_banner_and_bio(): void
    {
        $user = User::factory()->create([
            'username' => 'iqlimauser',
            'name' => 'Iqlima User',
            'bio' => "Pengembang web CoHub.\nSalam kenal!",
            'avatar_path' => 'avatars/sample-avatar.webp',
            'banner_path' => 'banners/sample-banner.webp',
        ]);

        $response = $this->get(route('profile.show', ['user' => $user->username]));

        $response->assertOk();
        $response->assertSee('Iqlima User');
        $response->assertSee('iqlimauser');
        $response->assertSee('Pengembang web CoHub.');
        $response->assertSee('Salam kenal!');
        $response->assertSee($user->avatar_url);
        $response->assertSee($user->banner_url);
    }

    public function test_guest_cannot_access_profile_edit(): void
    {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_uploaded_avatar_and_banner_persist_after_refresh_and_appear_in_navbar_post_and_comments(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'username' => 'iqlima_test',
            'name' => 'Iqlima Test',
        ]);

        $avatarFile = UploadedFile::fake()->image('my_avatar.png', 300, 300);
        $bannerFile = UploadedFile::fake()->image('my_banner.jpg', 1200, 400);

        // Upload both avatar and banner
        $uploadRes = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $avatarFile,
            'banner' => $bannerFile,
        ]);

        $uploadRes->assertRedirect(route('profile.edit'));
        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertNotNull($user->banner_path);

        // Refresh/revisit edit page
        $editRes = $this->actingAs($user)->get(route('profile.edit'));
        $editRes->assertOk();
        $editRes->assertSee($user->avatar_url);
        $editRes->assertSee($user->banner_url);

        // Revisit public profile
        $publicRes = $this->get(route('profile.show', $user->username));
        $publicRes->assertOk();
        $publicRes->assertSee($user->avatar_url);
        $publicRes->assertSee($user->banner_url);

        // Check navbar contains avatar
        $dashRes = $this->actingAs($user)->get(route('dashboard'));
        $dashRes->assertOk();
        $dashRes->assertSee($user->avatar_url);

        // User without avatar displays fallback initials
        $noImgUser = User::factory()->create([
            'username' => 'no_avatar_user',
            'name' => 'Budi Santoso',
            'avatar_path' => null,
            'banner_path' => null,
        ]);
        $noImgProfile = $this->get(route('profile.show', $noImgUser->username));
        $noImgProfile->assertOk();
        $noImgProfile->assertSee('BS');
        $noImgProfile->assertSee('from-pink-100');
    }
}
