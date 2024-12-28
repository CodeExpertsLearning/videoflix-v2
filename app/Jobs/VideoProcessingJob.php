<?php

namespace App\Jobs;

use App\Events\VideoEncodingFinished;
use App\Events\VideoEncodingProgress;
use App\Events\VideoEncodingStarted;
use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Storage;

class VideoProcessingJob implements ShouldQueue
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
        VideoEncodingStarted::dispatch($this->video);

        $nameNewVideo = str_replace(strrchr($this->video->video, '.'), '', $this->video->video) . '.m3u8';

        $lowBitrateFormat  = (new X264)->setKiloBitrate(500);
        $midBitrateFormat  = (new X264)->setKiloBitrate(1500);
        $highBitrateFormat = (new X264)->setKiloBitrate(3000);

        FFMpeg::fromDisk('videos')
            ->open($this->video->video)
            ->exportForHLS()
            ->addFormat($lowBitrateFormat)
            ->addFormat($midBitrateFormat)
            ->addFormat($highBitrateFormat)
            ->onProgress(function ($progress) {
                VideoEncodingProgress::dispatch($this->video, $progress);
            })
            ->toDisk('videos_processed')
            ->save($this->video->code . '/' . $nameNewVideo);


        Storage::disk('videos')->delete($this->video->video);

        $this->video->update([
            'video' => $nameNewVideo,
            'is_processed' => 1
        ]);

        VideoEncodingFinished::dispatch($this->video);
    }
}
