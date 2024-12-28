<?php

use App\Http\Controllers\ProfileController;
use App\Models\Content;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function (Content $content) {

    $contents = $content->whereHas('videos', fn($query) => $query->whereNotNull('code')
        ->whereisProcessed(true))
        ->get()->groupBy('type');

    return inertia('Dashboard', compact('contents'));
})->middleware(['auth', 'verified'])->name('dashboard');


Route::prefix('media')->name('media.')->middleware('auth')->group(function () {
    Route::resource(
        '/contents',
        \App\Http\Controllers\Media\ContentController::class
    );

    Route::get(
        '/contents/{content}/videos/upload',
        [\App\Http\Controllers\Media\VideoController::class, 'upload']
    )
        ->name('contents.videos.upload');

    Route::post(
        '/contents/{content}/videos',
        [\App\Http\Controllers\Media\VideoController::class, 'store']
    )
        ->name('contents.videos.store');

    Route::post('/contents/{content}/videos/{video}/process', [\App\Http\Controllers\Media\VideoController::class, 'process'])
        ->name('contents.videos.upload.process');
    Route::delete('/videos/{video}', [\App\Http\Controllers\Media\VideoController::class, 'destroy'])
        ->name('contents.videos.destroy');

    Route::match(['PUT', 'PATCH'], '/contents/{content}/videos/{video}', [\App\Http\Controllers\Media\VideoController::class, 'update'])
        ->name('contents.videos.update');
});


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/watch/{content:slug}', \App\Http\Controllers\Actions\PlayerAction::class)
    ->middleware(['auth'])
    ->name('video.player');


Route::get('resources/{code}/{video}', function ($code, $video = null) {
    $video = $code . '/' . $video;

    return \Illuminate\Support\Facades\Storage::disk('videos_processed')
        ->response(
            $video,
            null,
            [
                'Content-Type' => 'application/x-mpegURL',
                'isHls' => true
            ]
        );
})->name('stream_player')->middleware(['auth']);


require __DIR__ . '/auth.php';
