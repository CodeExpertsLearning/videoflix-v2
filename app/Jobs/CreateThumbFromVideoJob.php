<?php

namespace App\Jobs;

use App\Events\VideoThumbCreated;
use App\Models\Video;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;


class CreateThumbFromVideoJob implements ShouldQueue
{
    use Queueable, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(private Video $video)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $thumb = 'thumbnails/' . $this->video->code . '/thumbnail.png';

        FFMpeg::fromDisk('videos')
            ->open($this->video->video)
            ->getFrameFromSeconds(5)
            ->export()
            ->toDisk('public')
            ->save($thumb);

        $this->video->update([
            'thumb' => $thumb
        ]);

        VideoThumbCreated::dispatch($this->video);
    }
}
