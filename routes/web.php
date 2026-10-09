<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\AdminLoginController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ManifestoChatController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MerchandiseController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\VolunteerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/platforms', [PageController::class, 'platforms'])->name('platforms');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/events/{id}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp')->middleware('throttle:12,1');

Route::get('/news', [NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');

Route::post('/manifesto-chat', [ManifestoChatController::class, 'respond'])->name('manifesto-chat')->middleware('throttle:manifesto-chat');

Route::get('/materials', [MaterialController::class, 'index'])->name('materials.index');
Route::get('/materials/{id}/download', [MaterialController::class, 'download'])->name('materials.download');

Route::get('/merchandise', [MerchandiseController::class, 'index'])->name('merchandise.index');
Route::get('/merchandise/{slug}', [MerchandiseController::class, 'show'])->name('merchandise.show');

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:contact');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:newsletter');
Route::get('/newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::post('/volunteer/register', [VolunteerController::class, 'store'])->name('volunteer.store')->middleware('throttle:volunteer');
Route::post('/t/e', \App\Http\Controllers\TrackEventController::class)->name('track.event')->middleware('throttle:60,1');

// Admin auth routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->post('/login', [AdminLoginController::class, 'store'])->name('login.store');
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
});
Route::post('/admin/logout', [AdminLoginController::class, 'destroy'])->middleware('auth')->name('admin.logout');

// Alias for default Laravel login route (used by auth middleware)
Route::redirect('/login', '/admin/login')->name('login');

// Admin panel (protected routes)
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // CRM Features (all authenticated users)
    Route::get('analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('bulk-email', [Admin\BulkEmailController::class, 'compose'])->name('bulk-email.compose');
    Route::post('bulk-email', [Admin\BulkEmailController::class, 'send'])->name('bulk-email.send')->middleware('throttle:5,1');
    Route::post('bulk-email/test', [Admin\BulkEmailController::class, 'test'])->name('bulk-email.test')->middleware('throttle:10,1');

    // Notes (polymorphic)
    Route::post('{type}/{id}/notes', [Admin\NotesController::class, 'store'])->name('notes.store')
        ->where('type', 'contact|volunteer');
    Route::delete('notes/{note}', [Admin\NotesController::class, 'destroy'])->name('notes.destroy');

    // Tags
    Route::get('tags', [Admin\TagController::class, 'index'])->name('tags.index');
    Route::post('tags', [Admin\TagController::class, 'store'])->name('tags.store');
    Route::post('{type}/{id}/tags', [Admin\TagController::class, 'attach'])->name('tags.attach')
        ->where('type', 'contact|volunteer');
    Route::delete('{type}/{id}/tags/{tag}', [Admin\TagController::class, 'detach'])->name('tags.detach')
        ->where('type', 'contact|volunteer');

    // Exports
    Route::prefix('exports')->name('exports.')->group(function () {
        Route::get('contacts', [Admin\ExportController::class, 'contacts'])->name('contacts');
        Route::get('volunteers', [Admin\ExportController::class, 'volunteers'])->name('volunteers');
        Route::get('subscribers', [Admin\ExportController::class, 'subscribers'])->name('subscribers');
        Route::get('events/{event}/rsvps', [Admin\ExportController::class, 'rsvps'])->name('rsvps');
    });

    // Content management (admin + editor only)
    Route::middleware('admin.role:admin,editor')->group(function () {
        Route::resource('hero-slides', Admin\HeroSlideController::class)->except(['show']);
        Route::post('hero-slides/reorder', [Admin\HeroSlideController::class, 'reorder'])->name('hero-slides.reorder');
        Route::patch('hero-slides/{heroSlide}/toggle', [Admin\HeroSlideController::class, 'toggle'])->name('hero-slides.toggle');
        Route::resource('platform-pillars', Admin\PlatformPillarController::class)->except(['show']);
        Route::post('platform-pillars/reorder', [Admin\PlatformPillarController::class, 'reorder'])->name('platform-pillars.reorder');
        Route::patch('platform-pillars/{platformPillar}/toggle', [Admin\PlatformPillarController::class, 'toggle'])->name('platform-pillars.toggle');
        Route::resource('campaign-stats', Admin\CampaignStatController::class);
        Route::resource('events', Admin\EventController::class);
        Route::resource('news', Admin\NewsController::class)->except(['show']);
        Route::post('news/{article}/publish', [Admin\NewsController::class, 'publish'])->name('news.publish');
        Route::resource('materials', Admin\MaterialController::class)->except(['show']);
        Route::patch('materials/{material}/toggle', [Admin\MaterialController::class, 'toggle'])->name('materials.toggle');
        Route::get('materials/{material}/download', [Admin\MaterialController::class, 'download'])->name('materials.download');
        Route::post('materials/{material}/extract', [Admin\MaterialController::class, 'extract'])->name('materials.extract');
        Route::post('merchandise/reorder', [Admin\MerchandiseController::class, 'reorder'])->name('merchandise.reorder');
        Route::resource('merchandise', Admin\MerchandiseController::class)->except(['show']);
        Route::patch('merchandise/{merchandise}/toggle', [Admin\MerchandiseController::class, 'toggle'])->name('merchandise.toggle');
        Route::post('merchandise/{merchandise}/images', [Admin\MerchandiseImageController::class, 'store'])->name('merchandise.images.store');
        Route::post('merchandise/{merchandise}/images/reorder', [Admin\MerchandiseImageController::class, 'reorder'])->name('merchandise.images.reorder');
        Route::patch('merchandise/images/{image}', [Admin\MerchandiseImageController::class, 'update'])->name('merchandise.images.update');
        Route::delete('merchandise/images/{image}', [Admin\MerchandiseImageController::class, 'destroy'])->name('merchandise.images.destroy');
        Route::post('merchandise/images/{image}/set-primary', [Admin\MerchandiseImageController::class, 'setPrimary'])->name('merchandise.images.set-primary');
        Route::resource('testimonials', Admin\TestimonialController::class);
        Route::resource('gallery', Admin\GalleryController::class)->except(['show']);
        Route::patch('gallery/{gallery}/toggle-featured', [Admin\GalleryController::class, 'toggleFeatured'])->name('gallery.toggle-featured');
        Route::patch('gallery/{gallery}/toggle-active', [Admin\GalleryController::class, 'toggleActive'])->name('gallery.toggle-active');
        Route::post('gallery-categories', [Admin\ImageCategoryController::class, 'store'])->name('gallery-categories.store');
        Route::patch('gallery-categories/{category}', [Admin\ImageCategoryController::class, 'update'])->name('gallery-categories.update');
        Route::delete('gallery-categories/{category}', [Admin\ImageCategoryController::class, 'destroy'])->name('gallery-categories.destroy');
    });

    // Read + status management (all authenticated users)
    Route::get('contacts', [Admin\ContactController::class, 'index'])->name('contacts.index');
    Route::get('contacts/{contact}', [Admin\ContactController::class, 'show'])->name('contacts.show');
    Route::patch('contacts/{contact}', [Admin\ContactController::class, 'update'])->name('contacts.update');

    Route::get('events/{event}/rsvps', [Admin\EventRsvpController::class, 'index'])->name('events.rsvps.index');
    Route::patch('events/{event}/rsvps/{rsvp}', [Admin\EventRsvpController::class, 'update'])->name('rsvps.update');
    Route::delete('events/{event}/rsvps/{rsvp}', [Admin\EventRsvpController::class, 'destroy'])->name('rsvps.destroy');

    Route::get('newsletter', [Admin\NewsletterController::class, 'index'])->name('newsletter.index');
    Route::post('newsletter/{subscriber}/resend', [Admin\NewsletterController::class, 'resend'])
        ->name('newsletter.resend')->middleware('throttle:10,1');

    Route::get('volunteers', [Admin\VolunteerController::class, 'index'])->name('volunteers.index');
    Route::get('volunteers/{volunteer}', [Admin\VolunteerController::class, 'show'])->name('volunteers.show');
    Route::patch('volunteers/{volunteer}', [Admin\VolunteerController::class, 'update'])->name('volunteers.update');

    // User management (admin only)
    Route::resource('users', Admin\UserController::class)->except(['show']);
    Route::patch('users/{user}/access', [Admin\UserController::class, 'toggleAccess'])->name('users.access');
});
