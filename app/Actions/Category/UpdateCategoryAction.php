<?php

namespace App\Actions\Category;

use App\Models\Category;

class UpdateCategoryAction
{
    public function execute(Category $category, array $data): Category
    {
        $category->update($data);

        return $category;
    }
}
