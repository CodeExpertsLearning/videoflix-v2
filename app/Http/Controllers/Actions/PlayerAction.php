<?php

namespace App\Http\Controllers\Actions;

use App\Models\Content;

class PlayerAction
{
    public function __invoke(Content $content)
    {
        $videos = $content->activeVideos()->get();

        return inertia('Player', compact('content', 'videos'));
    }
}
