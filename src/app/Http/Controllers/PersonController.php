<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PersonController extends Controller
{
    private function imageUrl($path)
    {
        return $path ? asset('storage/' . $path) : null;
    }

    private function format($person)
    {
        return [
            'id'    => $person->id,
            'user_id' => $person->user_id,
            'name'  => $person->name,
            'title' => $person->title,
            'image' => $this->imageUrl($person->image),
        ];
    }

    public function index()
    {
        $people = Person::latest()->get()->map(fn($p) => $this->format($p));

        return response()->json([
            'success' => true,
            'data' => $people
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // One-to-One check
        if (Person::where('user_id', auth()->id())->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User already has a person profile'
            ], 400);
        }

        $imagePath = $this->handleImage($request);

        $person = Person::create([
            'user_id' => auth()->id(),
            'name'    => $request->name,
            'title'   => $request->title,
            'image'   => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Created successfully',
            'data' => $this->format($person),
        ]);
    }

    public function show($id)
    {
        $person = Person::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->format($person),
        ]);
    }

    public function update(Request $request, $id)
    {
        $person = Person::findOrFail($id);

        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'title' => 'sometimes|string|max:255',
            'image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [];

        if ($request->filled('name')) {
            $data['name'] = $request->name;
        }

        if ($request->filled('title')) {
            $data['title'] = $request->title;
        }

        // handle image
        if ($request->hasFile('image')) {

            if ($person->image) {
                Storage::disk('public')->delete($person->image);
            }

            $data['image'] = $this->handleImage($request);
        }

        $person->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Updated successfully',
            'data'    => $this->format($person),
        ]);
    }

    public function destroy($id)
    {
        $person = Person::findOrFail($id);

        if ($person->image) {
            Storage::disk('public')->delete($person->image);
        }

        $person->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }

    private function handleImage(Request $request)
    {
        if ($request->hasFile('image')) {

            $file = $request->file('image');
            $ext = $file->getClientOriginalExtension();

            $filename = Str::random(10) . '_' . time() . '.' . $ext;

            return $file->storeAs('people', $filename, 'public');
        }

        if ($request->image && filter_var($request->image, FILTER_VALIDATE_URL)) {

            try {
                $response = Http::timeout(10)->get($request->image);

                if (
                    $response->successful() &&
                    str_contains($response->header('Content-Type'), 'image')
                ) {
                    $ext = 'jpg';

                    if (str_contains($response->header('Content-Type'), 'png')) {
                        $ext = 'png';
                    } elseif (str_contains($response->header('Content-Type'), 'webp')) {
                        $ext = 'webp';
                    }

                    $fileName = 'people/' . Str::random(10) . '_' . time() . '.' . $ext;

                    Storage::disk('public')->put($fileName, $response->body());

                    return $fileName;
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}

    // public function update(Request $request, $id)
    // {
    //     $person = Person::findOrFail($id);

    //     $request->validate([
    //         'name'  => 'sometimes|string|max:255',
    //         'title' => 'sometimes|string|max:255',
    //         'image' => 'sometimes|file|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     // update image if new uploaded
    //     if ($request->hasFile('image')) {

    //         if ($person->image) {
    //             Storage::disk('public')->delete($person->image);
    //         }

    //         $person->image = $this->handleImage($request);
    //     }

    //     $person->name  = $request->name ?? $person->name;
    //     $person->title = $request->title ?? $person->title;

    //     $person->save();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Updated successfully',
    //         'data' => $this->format($person),
    //     ]);
    // }

