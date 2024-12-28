<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
use App\Models\Content;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    public function __construct(private Content $contentModel) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contents = $this->contentModel->paginate(10);

        return inertia('Media/ContentIndex', compact('contents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Media/ContentCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContentRequest $request)
    {
        $data = $request->validated();

        if ($data['cover'] && $data['cover'] instanceof UploadedFile) {
            $data['cover'] = $data['cover']->store('media/contents', 'public');
        }

        $content = $this->contentModel->create($data);

        return redirect()->route('media.contents.edit', $content->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('media.contents.edit', $id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $content = $this->contentModel->with('videos')->findOrFail($id);

        return inertia('Media/ContentEdit', compact('content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ContentRequest $request, string $id)
    {
        $data = $request->validated();

        $content = $this->contentModel->findOrFail($id);

        if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
            if ($content->cover)
                Storage::disk('public')->delete($content->cover);

            $data['cover'] = $data['photo']->store('media/contents', 'public');
        }

        $content->update($data);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $content = $this->contentModel->findOrFail($id);
        $content->delete();

        return redirect()->back();
    }
}
