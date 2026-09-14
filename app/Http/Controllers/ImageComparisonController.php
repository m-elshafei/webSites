<?php

namespace App\Http\Controllers;

use App\Actions\ProcessImageComparisonAction;
use App\Http\Requests\ImageComparisonRequest;
use App\Http\Resources\ImageComparisonResource;
use Illuminate\Contracts\View\View;

class ImageComparisonController extends Controller
{
    /**
     * Display the image comparison page and upload form.
     */
    public function index(): View
    {
        return view('image-comparison', [
            'comparisons' => null,
        ]);
    }

    /**
     * Handle the uploaded image, process comparison versions,
     * and return an API Resource for JSON requests or the Blade view for web requests.
     */
    public function store(ImageComparisonRequest $request, ProcessImageComparisonAction $action): View|ImageComparisonResource
    {
        $comparisons = $action->execute($request->file('image'));

        if ($request->expectsJson() || $request->is('api/*')) {
            return new ImageComparisonResource($comparisons);
        }

        return view('image-comparison', [
            'comparisons' => $comparisons,
        ]);
    }
}
