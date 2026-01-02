<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Export as ExportJob;
use App\Models\Export;
use App\Models\ExportSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExportController extends Controller
{
    /**
     * List all exports.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 15), 100);

        $exports = Export::latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $exports,
        ]);
    }

    /**
     * Create a new export and queue it for processing.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'user_id' => ['required', 'string', 'max:20', 'regex:/^[0-9_-]+$/'],
            'video_id' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'video_aspect_ratio' => ['required', 'string', Rule::in(ExportSetting::getAspectRatioKeys())],
            'video_resolution' => ['required', 'string', Rule::in(ExportSetting::getResolutionKeys())],
            'video_outro' => 'boolean',
        ]);

        $export = Export::create([
            'service' => $validated['service'],
            'userId' => $validated['user_id'],
            'videoId' => $validated['video_id'],
            'videoAspectRatio' => $validated['video_aspect_ratio'],
            'videoResolution' => $validated['video_resolution'],
            'videoOutro' => $validated['video_outro'] ?? false,
            'status' => 'pending',
        ]);

        // Dispatch the export job
        ExportJob::dispatch(
            $export->service,
            $export->userId,
            $export->videoId,
            $export->videoAspectRatio,
            $export->videoResolution,
            $export->videoOutro,
            $export->id
        )->onQueue('exports');

        return response()->json([
            'success' => true,
            'message' => 'Export queued for processing',
            'data' => [
                'id' => $export->id,
                'service' => $export->service,
                'user_id' => $export->userId,
                'video_id' => $export->videoId,
                'video_aspect_ratio' => $export->videoAspectRatio,
                'video_resolution' => $export->videoResolution,
                'video_outro' => $export->videoOutro,
                'status' => $export->status,
                'created_at' => $export->created_at,
            ],
        ], 201);
    }

    /**
     * Get a specific export by ID.
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function show(Export $export): JsonResponse
    {
        $data = [
            'id' => $export->id,
            'service' => $export->service,
            'user_id' => $export->userId,
            'video_id' => $export->videoId,
            'video_aspect_ratio' => $export->videoAspectRatio,
            'video_resolution' => $export->videoResolution,
            'video_outro' => $export->videoOutro,
            'status' => $export->status,
            'file_path' => $export->file_path,
            'created_at' => $export->created_at,
            'updated_at' => $export->updated_at,
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get the status of an export.
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function status(Export $export): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $export->id,
                'status' => $export->status,
                'created_at' => $export->created_at,
                'updated_at' => $export->updated_at,
            ],
        ]);
    }

    /**
     * Download an export file.
     *
     * @param Export $export
     * @return JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(Export $export)
    {
        if ($export->status !== 'completed') {
            return response()->json([
                'success' => false,
                'error' => 'Export not ready',
                'message' => 'The export is not yet completed. Current status: ' . $export->status,
            ], 400);
        }

        # Get the file_path from model
        $filePath = storage_path('app/public/exports/' . basename($export->file_path));

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'error' => 'File not found',
                'message' => 'The export file could not be found.',
            ], 404);
        }

        return response()->download($filePath, $export->userId . '_' . $export->videoId . '.mp4');
    }

    /**
     * Cancel a pending export.
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function cancel(Export $export): JsonResponse
    {
        if (!in_array($export->status, ['pending', 'in_progress'])) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot cancel export',
                'message' => 'Only pending or in-progress exports can be cancelled.',
            ], 400);
        }

        $export->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Export cancelled successfully',
            'data' => [
                'id' => $export->id,
                'status' => $export->status,
            ],
        ]);
    }

    /**
     * Retry a failed export.
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function retry(Export $export): JsonResponse
    {
        if ($export->status !== 'failed') {
            return response()->json([
                'success' => false,
                'error' => 'Cannot retry export',
                'message' => 'Only failed exports can be retried. Current status: ' . $export->status,
            ], 400);
        }

        // Clear old file path and process output on retry
        $export->update([
            'status' => 'pending',
            'file_path' => null,
            'process_output' => null,
        ]);

        // Dispatch the export job again
        ExportJob::dispatch(
            $export->service,
            $export->userId,
            $export->videoId,
            $export->videoAspectRatio,
            $export->videoResolution,
            $export->videoOutro,
            $export->id
        )->onQueue('exports');

        return response()->json([
            'success' => true,
            'message' => 'Export queued for retry',
            'data' => [
                'id' => $export->id,
                'status' => $export->status,
                'created_at' => $export->created_at,
            ],
        ]);
    }

    /**
     * Delete an export.
     *
     * @param Export $export
     * @return JsonResponse
     */
    public function destroy(Export $export): JsonResponse
    {
        // Delete the file if it exists
        $filePath = storage_path('app/public/exports/' . $export->userId . '.' . $export->videoId . '.mp4');
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $export->delete();

        return response()->json([
            'success' => true,
            'message' => 'Export deleted successfully',
        ]);
    }
}
