<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MovieStreamController extends Controller
{
    /**
     * Stream a movie file from the "movies" disk with HTTP range support.
     */
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        $disk = Storage::disk('movies');

        if (str_contains($path, '..') || ! $disk->exists($path)) {
            throw new NotFoundHttpException;
        }

        return response()->file($disk->path($path));
    }
}
