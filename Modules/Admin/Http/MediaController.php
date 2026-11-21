<?php

namespace Modules\Admin\Http;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Services\MediaService;
use UniSharp\LaravelFilemanager\Controllers\FolderController;
use UniSharp\LaravelFilemanager\Lfm;
use UniSharp\LaravelFilemanager\LfmPath;

class MediaController
{
    public function index(Request $request, Lfm $helper)
    {
        if (! $request->boolean('picker') && ! $request->boolean('embedded')) {
            return response()->view('admin::media-library');
        }

        return response()->view('admin::file-manager', compact('helper'))->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function items(Request $request, LfmPath $path, Lfm $helper)
    {
        $directory = rtrim($path->path('working_dir'), '/');
        $keyword = mb_substr($request->string('keyword')->trim()->toString(), 0, 120);
        $files = array_values(array_filter($path->files(), fn ($item) => $keyword === '' || mb_stripos($item->name, $keyword) !== false));
        if ($request->input('sort_type') === 'time') {
            usort($files, fn ($left, $right) => $right->time <=> $left->time);
        }
        $perPage = $helper->getPaginationPerPage();
        $page = max(1, min($request->integer('page', 1), (int) ceil(count($files) / $perPage)));

        return response()->json([
            'items' => array_map(fn ($file) => $file->fill()->attributes, array_slice($files, ($page - 1) * $perPage, $perPage)),
            'paginator' => ['current_page' => $page, 'total' => count($files), 'per_page' => $perPage],
            'working_dir' => $directory,
            'folder_name' => $directory === '/library' ? 'Thư viện ảnh' : basename($directory),
            'parent_dir' => $directory === '/library' ? null : dirname($directory),
            'folders' => array_map(fn ($folder) => ['name' => $folder->name, 'url' => $folder->url], array_values($path->folders())),
            'display' => $helper->getDisplayMode(),
        ]);
    }

    public function upload(Request $request, MediaService $media)
    {
        try {
            $request->validate([
                'upload' => 'required|array|size:1',
                'upload.0' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120|dimensions:max_width=5000,max_height=5000',
            ]);
        } catch (ValidationException $exception) {
            return response()->json([$exception->validator->errors()->first()]);
        }

        $stored = $media->upload($request->file('upload.0'), $request->input('working_dir'));

        return response()->json($stored ? 'OK' : ['Chỉ nhận ảnh JPEG, PNG, WebP hoặc GIF đúng đuôi tệp và có tối đa 12 megapixel.']);
    }

    public function folder(Request $request, FolderController $controller)
    {
        $request->validate(['name' => 'required|string|max:60|regex:/^[a-z0-9_-]+$/iD|not_in:thumbs']);

        return $controller->getAddfolder();
    }

    public function deleteFolder(Request $request, MediaService $media)
    {
        $data = $request->validate([
            'folder' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9_-]+$/iD', 'not_in:thumbs'],
        ]);

        abort_unless($media->deleteFolder($data['folder'], $request->input('working_dir')), 500);

        return response('OK');
    }

    public function delete(Request $request, MediaService $media)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1|max:30',
            'items.*' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9_-]+\.(jpg|jpeg|png|webp|gif)$/iD'],
        ]);
        abort_unless($media->delete($data['items'], $request->input('working_dir')), 500);

        return response('OK');
    }
}
