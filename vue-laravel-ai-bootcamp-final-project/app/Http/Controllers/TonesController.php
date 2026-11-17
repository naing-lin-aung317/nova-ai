<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomToneRequest;
use App\Http\Requests\UpdateCustomToneRequest;
use App\Models\BuiltInTone;
use App\Models\CustomTone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TonesController extends Controller
{
    public function index(Request $request): Response
    {
        $editing = null;

        if ($request->filled('edit')) {
            $tone = CustomTone::query()->find($request->integer('edit'));

            if ($tone !== null) {
                $this->authorize('view', $tone);

                $editing = [
                    'id' => $tone->id,
                    'name' => $tone->name,
                    'description' => $tone->description,
                    'sample_content' => $tone->sample_content,
                ];
            }
        }

        return Inertia::render('app/Tones', [
            'builtInTones' => BuiltInTone::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'description']),
            'customTones' => $request->user()
                ->customTones()
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'sample_content']),
            'editing' => $editing,
            'creating' => $request->boolean('new'),
        ]);
    }

    public function store(StoreCustomToneRequest $request): RedirectResponse
    {
        $request->user()->customTones()->create($request->validated());

        return redirect()->route('tones');
    }

    public function update(UpdateCustomToneRequest $request, CustomTone $customTone): RedirectResponse
    {
        $this->authorize('update', $customTone);

        $customTone->update($request->validated());

        return redirect()
            ->route('tones')
            ->with('status', 'tones.updatedSuccess');
    }

    public function destroy(CustomTone $customTone): RedirectResponse
    {
        $this->authorize('delete', $customTone);

        $customTone->delete();

        return redirect()->route('tones');
    }
}
