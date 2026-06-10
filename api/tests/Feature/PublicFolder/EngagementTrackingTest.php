<?php

namespace Tests\Feature\PublicFolder;

use App\Enums\FolderViewType;
use App\Enums\FolderVisibility;
use App\Models\Folder;
use App\Models\FolderView;
use App\Models\FolderViewDailyCount;
use App\Models\Sign;
use App\Models\SignCopy;
use App\Models\SignCopyDailyCount;
use App\Models\User;
use App\Models\VisitorSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EngagementTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function createPublicFolderWithSign(): array
    {
        $user = User::factory()->create();

        $folder = Folder::factory()->for($user)->create([
            'name' => 'Public Folder',
            'slug' => 'public-folder',
            'public_slug' => 'public-folder',
            'visibility' => FolderVisibility::Public,
        ]);

        $sign = Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
        ]);

        return [$folder, $sign];
    }

    public function test_loading_a_public_folder_page_records_a_unique_full_view(): void
    {
        [$folder] = $this->createPublicFolderWithSign();

        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();
        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();

        $this->assertSame(1, FolderView::query()
            ->where('folder_id', $folder->id)
            ->where('view_type', FolderViewType::Full)
            ->count());

        $view = FolderView::query()->where('folder_id', $folder->id)->first();
        $this->assertNotEquals($view->first_seen_at->timestamp, 0);
        $this->assertSame(64, strlen($view->ip_hash));
        $this->assertNotEquals('127.0.0.1', $view->ip_hash);
    }

    public function test_copying_a_sign_records_a_unique_copy_per_ip(): void
    {
        [$folder, $sign] = $this->createPublicFolderWithSign();

        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNoContent();
        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNoContent();

        $this->assertSame(1, SignCopy::query()->where('sign_id', $sign->id)->count());

        $copy = SignCopy::query()->where('sign_id', $sign->id)->first();
        $this->assertSame($folder->id, $copy->folder_id);
        $this->assertSame(64, strlen($copy->ip_hash));
    }

    public function test_private_folders_cannot_be_tracked(): void
    {
        $user = User::factory()->create();

        $folder = Folder::factory()->for($user)->create([
            'name' => 'Private Folder',
            'slug' => 'private-folder',
            'public_slug' => 'private-folder',
            'visibility' => FolderVisibility::Private,
        ]);

        $sign = Sign::factory()->create([
            'user_id' => $user->id,
            'folder_id' => $folder->id,
            'variant_id' => $folder->defaultVariant->id,
        ]);

        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNotFound();

        $this->assertSame(0, FolderView::query()->where('folder_id', $folder->id)->count());
        $this->assertSame(0, SignCopy::query()->where('sign_id', $sign->id)->count());
    }

    public function test_visitor_session_is_recorded_on_folder_view(): void
    {
        [$folder] = $this->createPublicFolderWithSign();

        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();
        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();

        $this->assertSame(1, VisitorSession::query()->count());
        $session = VisitorSession::query()->first();
        $this->assertSame(64, strlen($session->ip_hash));
        $this->assertSame(today()->toDateString(), $session->session_date);
    }

    public function test_visitor_session_is_recorded_on_sign_copy(): void
    {
        [$folder, $sign] = $this->createPublicFolderWithSign();

        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNoContent();

        $this->assertSame(1, VisitorSession::query()->count());
    }

    public function test_returning_visitor_is_counted_when_active_on_two_days(): void
    {
        $ipHash = hash_hmac('sha256', '127.0.0.1', (string) config('app.key'));

        VisitorSession::create(['ip_hash' => $ipHash, 'session_date' => today()->subDay()->toDateString()]);
        VisitorSession::create(['ip_hash' => $ipHash, 'session_date' => today()->toDateString()]);

        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/engagement?days=30')
            ->assertOk()
            ->assertJsonPath('summary.returning_visitors', 1);
    }

    public function test_single_day_visitor_is_not_counted_as_returning(): void
    {
        $ipHash = hash_hmac('sha256', '127.0.0.1', (string) config('app.key'));

        VisitorSession::create(['ip_hash' => $ipHash, 'session_date' => today()->toDateString()]);

        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/engagement?days=30')
            ->assertOk()
            ->assertJsonPath('summary.returning_visitors', 0);
    }

    public function test_admin_engagement_endpoint_reports_aggregated_stats(): void
    {
        [$folder, $sign] = $this->createPublicFolderWithSign();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();
        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNoContent();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/engagement?days=30')
            ->assertOk()
            ->assertJsonPath('summary.total_visitors', 1)
            ->assertJsonPath('summary.new_visitors', 1)
            ->assertJsonPath('summary.returning_visitors', 0)
            ->assertJsonPath('summary.folder_opens', 1)
            ->assertJsonPath('summary.sign_copies', 1)
            ->assertJsonPath('top_folders.0.folder_id', $folder->id)
            ->assertJsonPath('top_folders.0.opens', 1)
            ->assertJsonPath('top_signs.0.sign_id', $sign->id)
            ->assertJsonPath('top_signs.0.copies', 1);

        $response->assertJsonStructure([
            'timeseries' => ['daily_active_visitors', 'new_vs_returning', 'folder_opens', 'sign_copies'],
        ]);
    }

    public function test_repeat_visits_increase_daily_volume_counts(): void
    {
        [$folder, $sign] = $this->createPublicFolderWithSign();

        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();
        $this->getJson('/api/public/folders/'.$folder->public_slug)->assertOk();
        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNoContent();
        $this->postJson("/api/public/folders/{$folder->public_slug}/signs/{$sign->id}/copy")->assertNoContent();

        $this->assertSame(2, FolderViewDailyCount::query()
            ->where('folder_id', $folder->id)
            ->where('date', today()->toDateString())
            ->value('count'));

        $this->assertSame(2, SignCopyDailyCount::query()
            ->where('sign_id', $sign->id)
            ->where('date', today()->toDateString())
            ->value('count'));

        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/engagement?days=30')
            ->assertOk()
            ->assertJsonPath('summary.folder_opens', 2)
            ->assertJsonPath('summary.sign_copies', 2)
            ->assertJsonPath('top_folders.0.opens', 2)
            ->assertJsonPath('top_signs.0.copies', 2);
    }

    public function test_new_vs_returning_timeseries_classifies_visitor_by_first_session(): void
    {
        $ipHash = hash_hmac('sha256', '127.0.0.1', (string) config('app.key'));

        VisitorSession::create(['ip_hash' => $ipHash, 'session_date' => today()->subDay()->toDateString()]);
        VisitorSession::create(['ip_hash' => $ipHash, 'session_date' => today()->toDateString()]);

        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/admin/engagement?days=30')->assertOk();

        $points = collect($response->json('timeseries.new_vs_returning'))->keyBy('date');

        $this->assertSame(1, $points[today()->subDay()->toDateString()]['new']);
        $this->assertSame(0, $points[today()->subDay()->toDateString()]['returning']);
        $this->assertSame(0, $points[today()->toDateString()]['new']);
        $this->assertSame(1, $points[today()->toDateString()]['returning']);
    }
}
