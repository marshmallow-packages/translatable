<?php

namespace Marshmallow\Translatable\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Marshmallow\Translatable\Models\Language;
use Marshmallow\Translatable\Models\Translation;
use Marshmallow\Translatable\TranslatableConfig;
use Marshmallow\Translatable\Translators\TranslatorManager;

class TranslationMatrixController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Translation::query()->with('language');

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        if ($request->filled('context')) {
            $query->where('context', $request->input('context'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->boolean('missing_only')) {
            $query->whereNull('value')->orWhere('value', '');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $translations = $query
            ->orderBy('group')
            ->orderBy('key')
            ->orderBy('context')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'translations' => $translations,
            'languages' => Language::active()->ordered()->get(),
            'groups' => Translation::distinct()->pluck('group')->filter()->values(),
            'contexts' => Translation::distinct()->whereNotNull('context')->pluck('context')->filter()->values(),
            'sources' => Translation::distinct()->pluck('source')->filter()->values(),
            'defaultLanguage' => TranslatableConfig::getDefaultLanguage(),
        ]);
    }

    public function grouped(Request $request): JsonResponse
    {
        $languages = Language::active()->ordered()->get();
        $defaultLanguage = TranslatableConfig::getDefaultLanguage();

        $query = Translation::query();

        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        if ($request->boolean('missing_only')) {
            $query->whereNull('value')->orWhere('value', '');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                    ->orWhere('value', 'like', "%{$search}%");
            });
        }

        $translations = $query->get();

        $grouped = $translations->groupBy(function ($t) {
            return "{$t->group}.{$t->key}" . ($t->context ? ".{$t->context}" : '');
        })->map(function ($items, $fullKey) use ($languages) {
            $first = $items->first();
            $byLanguage = $items->keyBy('language_id');

            $row = [
                'group' => $first->group,
                'key' => $first->key,
                'context' => $first->context,
                'fullKey' => $fullKey,
                'values' => [],
            ];

            foreach ($languages as $language) {
                $translation = $byLanguage->get($language->id);

                $row['values'][$language->code] = [
                    'id' => $translation?->id,
                    'value' => $translation?->value,
                    'source' => $translation?->source,
                    'is_locked' => $translation?->is_locked ?? false,
                ];
            }

            return $row;
        })->values();

        return response()->json([
            'rows' => $grouped,
            'languages' => $languages,
            'groups' => Translation::distinct()->pluck('group')->filter()->values(),
            'defaultLanguage' => $defaultLanguage,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $translation = Translation::findOrFail($id);

        if ($translation->is_locked) {
            return response()->json([
                'message' => 'Translation is locked and cannot be modified.',
            ], 403);
        }

        $translation->update([
            'value' => $request->input('value'),
            'source' => 'manual',
        ]);

        return response()->json([
            'translation' => $translation->fresh(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'language_id' => 'required|exists:languages,id',
            'group' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'value' => 'nullable|string',
            'context' => 'nullable|string|max:255',
        ]);

        $translation = Translation::create([
            'language_id' => $request->input('language_id'),
            'group' => $request->input('group'),
            'key' => $request->input('key'),
            'context' => $request->input('context'),
            'value' => $request->input('value'),
            'source' => 'manual',
        ]);

        return response()->json([
            'translation' => $translation,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $translation = Translation::findOrFail($id);

        if ($translation->is_locked) {
            return response()->json([
                'message' => 'Translation is locked and cannot be deleted.',
            ], 403);
        }

        $translation->delete();

        return response()->json([
            'message' => 'Translation deleted successfully.',
        ]);
    }

    public function lock(int $id): JsonResponse
    {
        $translation = Translation::findOrFail($id);

        $translation->lock();

        return response()->json([
            'translation' => $translation->fresh(),
        ]);
    }

    public function unlock(int $id): JsonResponse
    {
        $translation = Translation::findOrFail($id);

        $translation->unlock();

        return response()->json([
            'translation' => $translation->fresh(),
        ]);
    }

    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:translations,id',
            'driver' => 'required|string',
            'from' => 'required|string|size:2',
            'to' => 'required|string|size:2',
        ]);

        $translation = Translation::findOrFail($request->input('id'));

        if ($translation->is_locked) {
            return response()->json([
                'message' => 'Translation is locked and cannot be modified.',
            ], 403);
        }

        $sourceTranslation = Translation::query()
            ->where('group', $translation->group)
            ->where('key', $translation->key)
            ->where('context', $translation->context)
            ->whereHas('language', fn ($q) => $q->where('code', $request->input('from')))
            ->first();

        if (! $sourceTranslation || ! $sourceTranslation->value) {
            return response()->json([
                'message' => 'Source translation not found or empty.',
            ], 404);
        }

        $manager = app(TranslatorManager::class);
        $driver = $manager->driver($request->input('driver'));

        if (! $driver->isConfigured()) {
            return response()->json([
                'message' => "Translator '{$request->input('driver')}' is not configured.",
            ], 422);
        }

        $translatedValue = $driver->translate(
            $sourceTranslation->value,
            $request->input('from'),
            $request->input('to')
        );

        $translation->update([
            'value' => $translatedValue,
            'source' => $driver->getIdentifier(),
        ]);

        return response()->json([
            'translation' => $translation->fresh(),
        ]);
    }

    public function translateBatch(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:translations,id',
            'driver' => 'required|string',
            'from' => 'required|string|size:2',
            'to' => 'required|string|size:2',
        ]);

        $manager = app(TranslatorManager::class);
        $driver = $manager->driver($request->input('driver'));

        if (! $driver->isConfigured()) {
            return response()->json([
                'message' => "Translator '{$request->input('driver')}' is not configured.",
            ], 422);
        }

        $translations = Translation::whereIn('id', $request->input('ids'))
            ->where('is_locked', false)
            ->get();

        $fromLanguage = Language::where('code', $request->input('from'))->first();

        if (! $fromLanguage) {
            return response()->json([
                'message' => 'Source language not found.',
            ], 404);
        }

        $sourceTexts = [];
        $translationMap = [];

        foreach ($translations as $translation) {
            $sourceTranslation = Translation::query()
                ->where('group', $translation->group)
                ->where('key', $translation->key)
                ->where('context', $translation->context)
                ->where('language_id', $fromLanguage->id)
                ->first();

            if ($sourceTranslation && $sourceTranslation->value) {
                $sourceTexts[$translation->id] = $sourceTranslation->value;
                $translationMap[$translation->id] = $translation;
            }
        }

        if (empty($sourceTexts)) {
            return response()->json([
                'message' => 'No valid source translations found.',
            ], 404);
        }

        $translatedTexts = $driver->translateBatch(
            $sourceTexts,
            $request->input('from'),
            $request->input('to')
        );

        $updated = [];

        foreach ($translatedTexts as $id => $translatedValue) {
            $translation = $translationMap[$id];

            $translation->update([
                'value' => $translatedValue,
                'source' => $driver->getIdentifier(),
            ]);

            $updated[] = $translation->fresh();
        }

        return response()->json([
            'translations' => $updated,
            'count' => count($updated),
        ]);
    }

    public function getTranslators(): JsonResponse
    {
        $manager = app(TranslatorManager::class);

        $translators = collect($manager->getAvailableDrivers())
            ->map(fn ($driver) => [
                'identifier' => $driver,
                'name' => $manager->driver($driver)->getName(),
                'configured' => $manager->driver($driver)->isConfigured(),
            ]);

        return response()->json([
            'translators' => $translators,
            'default' => config('translatable.translators.default'),
        ]);
    }
}
