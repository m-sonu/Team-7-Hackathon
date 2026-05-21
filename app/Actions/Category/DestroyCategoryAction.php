<?php

namespace App\Actions\Category;

use App\Models\Category;

class DestroyCategoryAction
{
    public function execute(Category $category): void
    {
        $category->delete();
    }
}
