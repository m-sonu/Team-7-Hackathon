<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Resume;
use Illuminate\Http\Request;

class ResumeController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'is_primary' => 'boolean',
        ]);

        $user = $request->user();

        $file = $request->file('resume');
        $path = $file->store('resumes', 'public');

        // If setting new primary → reset old one
        if ($request->is_primary) {
            Resume::where('user_id', $user->id)->update(['is_primary' => false]);
        }

        $resume = Resume::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'is_primary' => $request->is_primary ?? false,
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'data' => $resume,
        ]);
    }

    public function primary(Request $request)
    {
        $resume = Resume::where('user_id', $request->user()->id)
            ->where('is_primary', true)
            ->first();

        if (! $resume) {
            return response()->json([
                'message' => 'No primary resume found',
            ], 404);
        }

        return response()->json([
            'data' => $resume,
        ]);
    }
}
