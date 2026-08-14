<x-layouts.app>
<x-slot name="title">{{ $project->exists ? __('Edit Project') : __('Create Project') }} — georeference.it</x-slot>

<div class="max-w-2xl mx-auto space-y-4 pb-16">
    <h1 class="text-2xl font-bold text-gray-900">{{ $project->exists ? __('Edit Project') : __('Create Project') }}</h1>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($project->exists && !empty($project->invalid_keys))
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-800">
        {{ __(':count of the pasted keys did not match any occurrence:', ['count' => count($project->invalid_keys)]) }}
        <div class="font-mono text-xs mt-1 break-all">{{ implode(', ', $project->invalid_keys) }}</div>
    </div>
    @endif

    <form method="POST"
          action="{{ $project->exists ? route('projects.update', $project->id) : route('projects.store') }}"
          enctype="multipart/form-data"
          class="space-y-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"
          x-data="{
              mode: '{{ old('mode', $project->mode ?? 'criteria') }}',
              conditions: {{ json_encode(old('conditions', $project->criteria ?: [['field' => '', 'operator' => 'equals', 'value' => '']])) }},
              numericFields: {{ json_encode($numericFields) }},
              textOperators: {{ json_encode($textOperators) }},
              numericOperators: {{ json_encode($numericOperators) }},
              operatorLabels: {
                  equals: '=', contains: '{{ __('contains') }}', starts_with: '{{ __('starts with') }}',
                  gt: '>', lt: '<', gte: '≥', lte: '≤',
              },
              addCondition() { this.conditions.push({field: '', operator: 'equals', value: ''}); },
              removeCondition(i) { this.conditions.splice(i, 1); },
              // year/month/day switch to comparison operators — reset away from a
              // text-only operator (contains/starts_with) that would no longer be valid,
              // same restriction ProjectCriteriaEvaluator enforces server-side.
              operatorsFor(field) { return this.numericFields.includes(field) ? this.numericOperators : this.textOperators; },
              onFieldChange(cond) {
                  if (!this.operatorsFor(cond.field).includes(cond.operator)) {
                      cond.operator = 'equals';
                  }
              }
          }">
        @csrf
        @if($project->exists) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Title') }}</label>
            <input type="text" name="title" value="{{ old('title', $project->title) }}" required
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Description') }}</label>
            <textarea name="description" rows="3"
                      class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-500">{{ old('description', $project->description) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Tags') }}</label>
            <input type="text" name="tags" value="{{ old('tags', $project->tags) }}" placeholder="{{ __('comma-separated') }}"
                   class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Image') }}</label>
            @if($project->image)
            <img src="{{ $project->image }}" alt="" class="w-20 h-20 rounded-lg object-cover mb-2">
            @endif
            <input type="file" name="image" accept="image/*" class="text-sm">
        </div>

        <div>
            <span class="block text-sm font-medium text-gray-700 mb-1">{{ __('Visibility') }}</span>
            <div class="flex gap-4 text-sm">
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="visibility" value="private" {{ old('visibility', $project->visibility) === 'private' ? 'checked' : '' }}>
                    {{ __('Private (only you)') }}
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="visibility" value="public" {{ old('visibility', $project->visibility) === 'public' ? 'checked' : '' }}>
                    {{ __('Public (listed in the directory)') }}
                </label>
            </div>
        </div>

        <hr class="border-gray-200 dark:border-gray-700">

        <div>
            <span class="block text-sm font-medium text-gray-700 mb-1">{{ __('How is this project defined?') }}</span>
            <div class="flex gap-4 text-sm mb-3">
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="mode" value="criteria" x-model="mode">
                    {{ __('By criteria') }}
                </label>
                <label class="flex items-center gap-1.5">
                    <input type="radio" name="mode" value="id_list" x-model="mode">
                    {{ __('By a list of GBIF occurrence keys') }}
                </label>
            </div>

            <div x-show="mode === 'criteria'" class="space-y-2">
                <p class="text-xs text-gray-500">{{ __('All conditions must match (AND only).') }}</p>
                <template x-for="(cond, i) in conditions" :key="i">
                    <div class="flex gap-2 items-center">
                        <select :name="'conditions['+i+'][field]'" x-model="cond.field" @change="onFieldChange(cond)"
                                class="text-xs border border-gray-300 rounded px-2 py-1.5 flex-1">
                            <option value="">{{ __('Field...') }}</option>
                            @foreach($fields as $field)
                            <option value="{{ $field }}">{{ $field }}</option>
                            @endforeach
                        </select>
                        <select :name="'conditions['+i+'][operator]'" @change="cond.operator = $event.target.value"
                                class="text-xs border border-gray-300 rounded px-2 py-1.5">
                            <template x-for="op in operatorsFor(cond.field)" :key="op">
                                <option :value="op" :selected="cond.operator === op" x-text="operatorLabels[op]"></option>
                            </template>
                        </select>
                        <input type="text" :name="'conditions['+i+'][value]'" x-model="cond.value" placeholder="{{ __('Value') }}"
                               class="text-xs border border-gray-300 rounded px-2 py-1.5 flex-1">
                        <button type="button" @click="removeCondition(i)" x-show="conditions.length > 1"
                                class="text-gray-400 hover:text-red-500 text-sm px-1">×</button>
                    </div>
                </template>
                <button type="button" @click="addCondition()" class="text-xs text-green-600 hover:underline">
                    {{ __('+ Add condition') }}
                </button>
            </div>

            <div x-show="mode === 'id_list'">
                <textarea name="occurrence_keys_raw" rows="6" placeholder="{{ __('One GBIF occurrence key per line, or comma-separated') }}"
                          class="w-full text-xs font-mono border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-green-500">{{ old('occurrence_keys_raw', implode("\n", $project->submitted_keys ?? [])) }}</textarea>
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
                {{ $project->exists ? __('Save') : __('Create Project') }}
            </button>
            <a href="{{ route('projects') }}" class="text-sm text-gray-500 hover:text-gray-700">{{ __('Cancel') }}</a>
        </div>
    </form>
</div>
</x-layouts.app>
