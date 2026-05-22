<?php

namespace App\Http\Controllers\API;

use App\Enums\UserRole;
use App\Http\Controllers\ApiController;
use App\Models\Bill;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends ApiController
{
    /**
     * Stream the file associated with the given bill.
     * Accessible by the bill owner or any admin.
     */
    public function preview(Request $request, Bill $bill): BinaryFileResponse|JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return $this->sendError('Forbidden', 403);
        }

        $user = User::query()->findOrFail($request->query('user'));

        if ($user->id !== $bill->user_id && $user->role !== UserRole::ADMIN) {
            return $this->sendError('Unauthorized', 403);
        }

        $media = $bill->getFirstMedia('bills');

        if (! $media) {
            return $this->sendError('File not found', 404);
        }

        return response()->file($media->getPath());
    }
}
