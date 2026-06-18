<?php

namespace App\Http\Controllers;

use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryController extends Controller
{
    /**
     * Verified operator directory.
     */
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $operators = DemoData::operators();

        if ($category) {
            $operators = array_values(array_filter(
                $operators,
                fn (array $operator) => $operator['category_slug'] === $category,
            ));
        }

        return view('directory.index', [
            'categories'     => DemoData::categories(),
            'operators'      => $operators,
            'activeCategory' => $category,
        ]);
    }

    /**
     * Operator profile page, resolved by UUID (not auto-increment PK).
     */
    public function show(string $operator): View
    {
        $record = DemoData::operator($operator);

        if (! $record) {
            throw new NotFoundHttpException('Operator not found.');
        }

        return view('directory.show', [
            'operator' => $record,
            'products' => DemoData::products(),
            'reviews'  => DemoData::reviews(),
        ]);
    }
}
