<?php

namespace App\Http\Controllers\Church;

use App\Actions\Churches\ManageCategoryAction;
use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\ChurchCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChurchCategoryController extends Controller
{
    public function index(Request $request, Church $church): Response
    {
        $this->authorize('update', $church);

        $categories = ChurchCategory::query()
            ->where(function ($q) use ($church) {
                $q->where('church_id', $church->id)
                    ->orWhere('is_system', true);
            })
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        return Inertia::render('Church/Categories/Index', [
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
            ],
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, Church $church, ManageCategoryAction $action): RedirectResponse
    {
        $this->authorize('update', $church);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'key' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:intention,mass'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $action->create($church, $validated, $request->user());

        return back()->with('success', "Category '{$validated['label']}' created.");
    }

    public function toggleActive(Request $request, Church $church, ChurchCategory $category, ManageCategoryAction $action): RedirectResponse
    {
        $this->authorize('update', $church);

        if ($category->church_id !== $church->id && ! $category->is_system) {
            abort(404);
        }

        if ($category->is_active) {
            $reason = $request->input('reason', 'Deactivated by Church Administrator.');
            $action->deactivate($category, $request->user(), $reason);
            $msg = "Category '{$category->label}' deactivated.";
        } else {
            $action->activate($category, $request->user());
            $msg = "Category '{$category->label}' reactivated.";
        }

        return back()->with('success', $msg);
    }
}
