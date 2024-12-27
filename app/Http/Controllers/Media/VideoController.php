<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Jobs\CreateThumbFromVideoJob;
use App\Jobs\VideoProcessingJob;
use Illuminate\Http\Request;
use App\Models\{Content, Video};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Pion\Laravel\ChunkUpload\Handler\ContentRangeUploadHandler;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;
use Illuminate\Support\Facades\Bus;

class VideoController extends Controller
{
    public function __construct(private Video $videoModel) {}

    public function upload(Content $content)
    {
        return inertia('Media/VideoUpload', compact('content'));
    }

    public function store(Content $content, Request $request)
    {
        $data = $request->only('name');

        $video = $content->videos()->create([
            'name' => (string) $data['name'],
            'code' => Str::uuid()
        ]);

        return $video;
    }

    public function update(Content $content, $video, Request $request)
    {
        $video = $content->videos()->findOrFail($video);
        $video->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return redirect()->back();
    }

    public function destroy(Content $content, string $id)
    {
        $video = $content->videos()->findOrFail($id);
        $video->delete();

        redirect()->back();
    }

    public function process(Content $content, string $id, Request $request)
    {
        $receiver = new FileReceiver(
            UploadedFile::fake()->createWithContent('file', $request->getContent()),
            $request,
            ContentRangeUploadHandler::class
        );

        $save = $receiver->receive();

        if ($save->isFinished()) {
            $video = $content->videos()->findOrFail($id);
            $video->update([
                'video' => $save->getFile()->storeAs('', Str::uuid(), 'videos')
            ]);

            //Jogar estes videos pro job para serem encodados & extraido as thumbs...

            Bus::batch([
                new CreateThumbFromVideoJob($video),
                new VideoProcessingJob($video)
            ])->allowFailures()->dispatch();
        }

        $save->handler();
    }
}
