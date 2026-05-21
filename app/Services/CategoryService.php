<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function getCategories(): LengthAwarePaginator
    {
        return Category::select('id', 'name', 'monthly_limit', 'is_active')
            ->orderBy('name', 'asc')
            ->paginate(10);
    }
}
