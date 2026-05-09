<?php

namespace App\Http\Controllers;

use App\Models\AppIntro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class AppIntroController extends Controller
{
    private function format($intro)
    {
        return [
            "id" => $intro->id,
            "title" => $intro->title,
            "description" => $intro->description,
            "image" => $intro->image ? asset('storage/' . $intro->image) : null,
            "button_text" => $intro->button_text,
            "is_active" => $intro->is_active,
            "order_no" => $intro->order_no,
            "created_at" => $intro->created_at,
            "updated_at" => $intro->updated_at,
        ];
    }

    private function handleImage(Request $request)
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = $file->getClientOriginalExtension();
            $filename = Str::random(10) . '_' . time() . '.' . $ext;
            return $file->storeAs('intro', $filename, 'public');
        }

        if ($request->image && filter_var($request->image, FILTER_VALIDATE_URL)) {
            try {
                $url = $request->image;

                if (
                    str_contains($url, '127.0.0.1') ||
                    str_contains($url, 'localhost') ||
                    str_contains($url, '169.254.')
                ) {
                    return null;
                }

                $response = Http::timeout(10)->get($url);

                if (!$response->successful()) {
                    return null;
                }

                $contentType = $response->header('Content-Type');

                if (!str_starts_with($contentType, 'image/')) {
                    return null;
                }

                $ext = match ($contentType) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };

                $fileName = 'intro/' . Str::random(10) . '_' . time() . '.' . $ext;

                Storage::disk('public')->put($fileName, $response->body());

                return $fileName;

            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    public function index()
    {
        $intros = AppIntro::orderBy('order_no', 'asc')->get();

        return response()->json([
            "success" => true,
            "data" => $intros->map(fn($intro) => $this->format($intro))
        ]);
    }

    public function show($id)
    {
        $intro = AppIntro::find($id);

        if (!$intro) {
            return response()->json([
                "success" => false,
                "message" => "App intro not found"
            ], 404);
        }

        return response()->json([
            "success" => true,
            "data" => $this->format($intro)
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            "title" => "required|string|max:255",
            "description" => "required|string",
            "image" => "nullable",
            "button_text" => "nullable|string|max:100",
            "is_active" => "nullable|boolean",
            "order_no" => "nullable|integer",
        ]);

        $intro = AppIntro::create([
            "title" => $request->title,
            "description" => $request->description,
            "image" => $this->handleImage($request),
            "button_text" => $request->button_text,
            "is_active" => $request->is_active ?? true,
            "order_no" => $request->order_no ?? 1,
        ]);

        return response()->json([
            "success" => true,
            "message" => "Created successfully",
            "data" => $this->format($intro)
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $intro = AppIntro::find($id);

        if (!$intro) {
            return response()->json([
                "success" => false,
                "message" => "App intro not found"
            ], 404);
        }

        $request->validate([
            "title" => "sometimes|required|string|max:255",
            "description" => "sometimes|required|string",
            "image" => "nullable",
            "button_text" => "nullable|string|max:100",
            "is_active" => "nullable|boolean",
            "order_no" => "nullable|integer",
        ]);

        $data = array_filter(
            $request->only([
                "title",
                "description",
                "button_text",
                "is_active",
                "order_no"
            ]),
            fn($v) => !is_null($v)
        );

        if ($request->hasFile('image') || $request->image) {
            $data['image'] = $this->handleImage($request);
        }

        $intro->update($data);

        return response()->json([
            "success" => true,
            "message" => "Updated successfully",
            "data" => $this->format($intro)
        ]);
    }

    public function destroy($id)
    {
        $intro = AppIntro::find($id);

        if (!$intro) {
            return response()->json([
                "success" => false,
                "message" => "App intro not found"
            ], 404);
        }

        if ($intro->image && Storage::disk('public')->exists($intro->image)) {
            Storage::disk('public')->delete($intro->image);
        }

        $intro->delete();

        return response()->json([
            "success" => true,
            "message" => "Deleted successfully"
        ]);
    }
}