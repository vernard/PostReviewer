<?php

namespace App\Jobs;

use App\Events\MediaProcessed;
use App\Models\Media;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVideo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    public function __construct(
        public Media $media
    ) {}

    public function handle(): void
    {
        $disk = Storage::disk($this->media->disk);
        $videoPath = $disk->path($this->media->path);

        if (!file_exists($videoPath)) {
            Log::error("ProcessVideo: Video file not found", ['media_id' => $this->media->id, 'path' => $videoPath]);
            $this->media->update(['status' => 'failed']);
            broadcast(new MediaProcessed('failed', $this->media->brand_id, $this->media->id));
            return;
        }

        // Extract video metadata using ffprobe
        $metadata = $this->extractMetadata($videoPath);

        if ($metadata === null) {
            Log::error("ProcessVideo: Failed to extract metadata", ['media_id' => $this->media->id]);
            $this->media->update(['status' => 'failed']);
            broadcast(new MediaProcessed('failed', $this->media->brand_id, $this->media->id));
            return;
        }

        // Generate thumbnail
        $thumbnails = $this->generateThumbnail($videoPath);

        // Update media record
        $this->media->update([
            'width' => $metadata['width'],
            'height' => $metadata['height'],
            'duration' => $metadata['duration'],
            'thumbnails' => $thumbnails,
            'status' => 'ready',
        ]);

        // Broadcast to frontend that processing is complete
        broadcast(new MediaProcessed('ready', $this->media->brand_id, $this->media->id, $this->media->fresh()));

        Log::info("ProcessVideo: Video processed successfully", ['media_id' => $this->media->id]);
    }

    private function extractMetadata(string $videoPath): ?array
    {
        $command = sprintf(
            'ffprobe -v quiet -print_format json -show_streams -show_format %s',
            escapeshellarg($videoPath)
        );

        $output = shell_exec($command);

        if ($output === null) {
            return null;
        }

        $data = json_decode($output, true);

        if (!isset($data['streams'])) {
            return null;
        }

        // Find the video stream
        $videoStream = null;
        foreach ($data['streams'] as $stream) {
            if ($stream['codec_type'] === 'video') {
                $videoStream = $stream;
                break;
            }
        }

        if ($videoStream === null) {
            return null;
        }

        $duration = isset($data['format']['duration'])
            ? (int) round((float) $data['format']['duration'])
            : null;

        return [
            'width' => (int) $videoStream['width'],
            'height' => (int) $videoStream['height'],
            'duration' => $duration,
        ];
    }

    private function generateThumbnail(string $videoPath): array
    {
        $disk = Storage::disk($this->media->disk);
        $directory = dirname($this->media->path);
        $basename = pathinfo($this->media->path, PATHINFO_FILENAME);

        $thumbnails = [];
        $sizes = config('platforms.thumbnails');

        foreach ($sizes as $sizeName => $dimensions) {
            $thumbnailPath = "{$directory}/{$basename}_thumb_{$sizeName}.jpg";
            $thumbnailFullPath = $disk->path($thumbnailPath);

            // Extract frame at 1 second (or 0 if video is shorter)
            $command = sprintf(
                'ffmpeg -y -i %s -ss 00:00:01 -vframes 1 -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:black" %s 2>/dev/null',
                escapeshellarg($videoPath),
                $dimensions['width'],
                $dimensions['height'],
                $dimensions['width'],
                $dimensions['height'],
                escapeshellarg($thumbnailFullPath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($thumbnailFullPath)) {
                $thumbnails[$sizeName] = $thumbnailPath;
            }
        }

        return $thumbnails;
    }
}
