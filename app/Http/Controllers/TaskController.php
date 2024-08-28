<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Task;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TasksExport;


class TaskController extends Controller
{
    public function index()
    {
        try {
            $userId = auth()->id(); 
            $tasks = Task::with('category')
                         ->get()
                         ->map(function($task) {
                             return [
                                 'id' => $task->id,
                                 'name' => $task->name,
                                 'description' => $task->description,
                                 'start_date' => $task->start_date,
                                 'end_date' => $task->end_date,
                                 'category_name' => $task->category->name, 
                                 'image' => $task->image,
                             ];
                         });
    
            return response()->json($tasks);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve tasks.'], 500);
        }
    }
    

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $data = $validatedData;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $data['image'] = $imagePath;
            }

            $task = Task::create($data);
            return response()->json([
                'message' => 'Task created successfully.'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to create task.'], 500);
        }
    }

    public function show(Task $task)
    {
        try {
            return response()->json($task->load('category'), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to retrieve task.'], 500);
        }
    }

    public function update(Request $request, Task $task)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            ]);

            $data = $validatedData;
            if ($request->hasFile('image')) {
                if ($task->image) {
                    Storage::disk('public')->delete($task->image);
                }
                $imagePath = $request->file('image')->store('images', 'public');
                $data['image'] = $imagePath;
            }

            $task->update($data);
            return response()->json($task, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to update task.'], 500);
        }
    }

    public function destroy(Task $task)
    {
        try {
            if ($task->image) {
                Storage::disk('public')->delete($task->image);
            }
            $task->delete();
            return response()->json([
                'message' => 'Task deleted successfully.'
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Task not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to delete task.'], 500);
        }
    }

    public function filter(Request $request)
{
    $query = Task::query();
    if ($request->has('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    $tasks = $query->with('category')->get();

    if ($tasks->isEmpty()) {
        return response()->json(['message' => 'No tasks found for the given category.'], 404);
    }

    return response()->json($tasks, 200);
}

public function search(Request $request)
{
    try {
        $query = Task::query();
        $searchTerm = $request->input('query', '');

        if ($searchTerm !== '') {
            $query->where('name', 'like', '%' . $searchTerm . '%');
        }

        $results = $query->with('category')->get();
        if ($results->isEmpty()) {
            return response()->json(['message' => 'No tasks found.'], 404);
        }
        return response()->json($results, 200);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to search tasks.'], 500);
    }
}

public function export()
    {
        try {
            return Excel::download(new TasksExport, 'tasks.xlsx');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to export tasks.'], 500);
        }
    }


}
