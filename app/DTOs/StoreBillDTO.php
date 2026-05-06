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
        public readonly string $title,
        public readonly string $currency,
        public readonly int $categoryId,
        public readonly array $files
    ) {}

    public static function fromRequest(StoreBillRequest $request): self
    {
        $user = $request->user() ?? User::find(1);
        $title = (string) $request->input('title');
        $currency = (string) $request->input('currency');
        $categoryId = (int) $request->input('category_id');
        $files = $request->file('files');

        $storedFiles = [];
        foreach ($files as $file) {
            $path = $file->store('temp', 'local');
            $storedFiles[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        return new self($user, $title, $currency, $categoryId, $storedFiles);
    }
}
