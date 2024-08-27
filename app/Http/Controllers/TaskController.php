<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Task;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
   
    public function index()
    {
        // Fetch tasks with their associated categories
        $tasks = Task::with('category')->get();
        
        // Return the tasks as a JSON response
        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $data['image'] = $imagePath;
        }

        $task = Task::create($data);
        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json($task->load('category'), 200);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $data = $request->all();
        if ($request->hasFile('image')) {
            if ($task->image) {
                Storage::disk('public')->delete($task->image);
            }
            $imagePath = $request->file('image')->store('images', 'public');
            $data['image'] = $imagePath;
        }

        $task->update($data);
        return response()->json($task, 200);
    }

    public function destroy(Task $task): JsonResponse
    {
        if ($task->image) {
            Storage::disk('public')->delete($task->image);
        }
        
        $task->delete();
        return response()->json(null, 204);
    }

    public function filter(Request $request): JsonResponse
    {
        $query = Task::query();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        return response()->json($query->with('category')->get(), 200);
    }

    public function search(Request $request): JsonResponse
    {
        $query = Task::query();

        if ($request->has('query')) {
            $query->where('name', 'like', '%' . $request->query . '%')
                  ->orWhere('description', 'like', '%' . $request->query . '%');
        }

        return response()->json($query->with('category')->get(), 200);
    }
}
