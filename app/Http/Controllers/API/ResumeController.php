<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadResumeRequest;
use App\Models\Resume;
use App\Services\AI\OpenAIService;
use App\Services\Extractor\ResumeExtractorService;
use Exception;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function __construct(
        protected ResumeExtractorService $resumeExtractorService, OpenAIService $openAIService
    ) {
        $this->resumeExtractorService = $resumeExtractorService;
        $this->openAIService = $openAIService;
    }

    public function upload(UploadResumeRequest $request)
    {
        try {
            $user = $request->user();
            $file = $request->file('resume');
            $path = $file->store('resumes', 'public');

            // If setting new primary → reset old one
            if ($request->is_primary) {
                Resume::where('user_id', $user->id)->update(['is_primary' => false]);
            }
            $rawText = $this->resumeExtractorService->extractText($file);
            $extractedJsonText = $this->openAIService->extractJson($rawText);

            $resume = Resume::create([
                'user_id' => $user->id,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'is_primary' => $request->is_primary ?? false,
                'extracted_data' => $extractedJsonText,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Resume uploaded successfully',
                'data' => $resume,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process resume',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }

    public function primary(Request $request)
    {
        try {
            $resume = Resume::where('user_id', $request->user()->id)
                ->where('is_primary', true)
                ->first();

            if (! $resume) {
                return response()->json([
                    'success' => false,
                    'message' => 'No primary resume found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Primary resume retrieved successfully',
                'data' => $resume,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve primary resume',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $resumes = Resume::where('user_id', $request->user()->id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Resumes retrieved successfully',
                'data' => $resumes,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve resumes',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
            ], 500);
        }
    }
}
