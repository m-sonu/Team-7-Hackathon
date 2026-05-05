<?php

namespace App\DTOs;

use App\Http\Requests\StoreBillRequest;
use App\Models\User;

class StoreBillDTO
{
    /**
     * @param  array<int, array{path: string, original_name: string}>  $files
     */
    public function __construct(
        public readonly User $user,
        public readonly int $categoryId,
        public readonly array $files
    ) {}

    public static function fromRequest(StoreBillRequest $request): self
    {
        $user = $request->user() ?? User::find(1);
        $categoryId = (int) $request->input('category_id');
        $files = $request->file('files');

        $storedFiles = [];
        foreach ($files as $file) {
            $storedFiles[] = [
                'path' => $file->store('bills'),
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        return new self($user, $categoryId, $storedFiles);
    }
}
