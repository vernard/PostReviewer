<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideo;
use App\Models\Brand;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get brand IDs the user has access to
        if ($user->isManager()) {
            $brandIds = $user->agency->brands()->pluck('id');
        } else {
            $brandIds = $user->brands()->pluck('brands.id');
        }

        $query = Media::with('brand')
            ->whereIn('brand_id', $brandIds)
            ->where('status', 'ready');

        // Filter by brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by filename
        if ($request->has('search')) {
            $query->where('original_filename', 'like', '%' . $request->search . '%');
        }

        $media = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 30));

        return response()->json($media);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'brand_id' => ['required', 'exists:brands,id'],
            'file' => ['required', 'file', 'max:102400'], // 100MB max
        ]);

        $brand = Brand::findOrFail($request->brand_id);

        if (!$user->hasBrandAccess($brand)) {
            return response()->json([
                'message' => 'You do not have access to this brand.',
            ], 403);
        }

        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        // Determine type
        $imageTypes = config('platforms.supported_image_types');
        $videoTypes = config('platforms.supported_video_types');

        if (in_array($mimeType, $imageTypes)) {
            $type = 'image';
            $maxSize = config('platforms.max_image_size');
        } elseif (in_array($mimeType, $videoTypes)) {
            $type = 'video';
            $maxSize = config('platforms.max_video_size');
        } else {
            return response()->json([
                'message' => 'Unsupported file type.',
            ], 422);
        }

        if ($file->getSize() > $maxSize) {
            return response()->json([
                'message' => 'File size exceeds the maximum allowed.',
            ], 422);
        }

        // Check agency storage quota
        $agency = $brand->agency;
        if ($agency && !$agency->hasStorageAvailable($file->getSize())) {
            return response()->json([
                'message' => 'Storage quota exceeded. Please delete some files or contact support.',
                'storage_used' => $agency->storage_used_formatted,
                'storage_quota' => $agency->storage_quota_formatted,
            ], 422);
        }

        // Store file
        $path = $file->store("brands/{$brand->id}/media", 'public');

        // Create media record
        $media = Media::create([
            'brand_id' => $brand->id,
            'user_id' => $user->id,
            'type' => $type,
            'original_filename' => $file->getClientOriginalName(),
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
            'status' => $type === 'image' ? 'ready' : 'processing',
        ]);

        // For images, get dimensions and generate thumbnails
        if ($type === 'image') {
            $this->processImage($media);
        } else {
            ProcessVideo::dispatch($media);
        }

        // Update agency storage usage
        if ($agency) {
            $agency->incrementStorageUsed($file->getSize());
        }

        return response()->json([
            'media' => $media->fresh(),
        ], 201);
    }

    public function show(Request $request, Media $media): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($media->brand)) {
            return response()->json([
                'message' => 'You do not have access to this media.',
            ], 403);
        }

        return response()->json([
            'media' => $media->load('brand'),
        ]);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasBrandAccess($media->brand)) {
            return response()->json([
                'message' => 'You do not have access to this media.',
            ], 403);
        }

        // Check if media is attached to any posts
        if ($media->posts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete media that is attached to posts.',
            ], 422);
        }

        // Delete file from storage
        Storage::disk($media->disk)->delete($media->path);

        // Delete thumbnails
        if ($media->thumbnails) {
            foreach ($media->thumbnails as $thumbnail) {
                Storage::disk($media->disk)->delete($thumbnail);
            }
        }

        // Update agency storage usage
        $agency = $media->brand->agency;
        $mediaSize = $media->size;

        $media->delete();

        if ($agency) {
            $agency->decrementStorageUsed($mediaSize);
        }

        return response()->json([
            'message' => 'Media deleted successfully.',
        ]);
    }

    private function processImage(Media $media): void
    {
        $path = Storage::disk($media->disk)->path($media->path);
        $imageInfo = getimagesize($path);

        if ($imageInfo) {
            $media->update([
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
            ]);
        }

        // TODO: Generate thumbnails using Intervention Image
        // $thumbnails = $this->generateThumbnails($media);
        // $media->update(['thumbnails' => $thumbnails]);
    }
}
