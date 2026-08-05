<div class="container py-4" style="max-width:700px;">
    @if($submitted)
        <div class="alert alert-success">✅ Thanks! Your response has been recorded.</div>
    @else
        <h3>{{ $form->title }}</h3>
        @if($form->description)<p class="text-muted">{{ $form->description }}</p>@endif

        <form wire:submit.prevent="submit">
            {{-- Honeypot: hidden from real users via CSS, bots tend to fill every field --}}
            <input type="text" wire:model="website" class="d-none" tabindex="-1" autocomplete="off">

            @foreach($form->schema['fields'] ?? [] as $field)
                <div class="mb-3">
                    @if($field['type'] === 'heading')
                        <h5 class="mt-4">{{ $field['label'] }}</h5>
                        @continue
                    @endif

                    <label class="form-label">
                        {{ $field['label'] }}
                        @if($field['required'] ?? false)<span class="text-danger">*</span>@endif
                    </label>

                    @switch($field['type'])
                        @case('textarea')
                            <textarea wire:model="data.{{ $field['key'] }}" class="form-control" placeholder="{{ $field['placeholder'] }}"></textarea>
                            @break
                        @case('dropdown')
                            <select wire:model="data.{{ $field['key'] }}" class="form-select">
                                <option value="">Select...</option>
                                @foreach($field['options'] ?? [] as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                            @break
                        @case('radio')
                            @foreach($field['options'] ?? [] as $opt)
                                <div class="form-check">
                                    <input type="radio" wire:model="data.{{ $field['key'] }}" value="{{ $opt }}" class="form-check-input">
                                    <label class="form-check-label">{{ $opt }}</label>
                                </div>
                            @endforeach
                            @break
                        @case('checkbox')
                            @foreach($field['options'] ?? [] as $opt)
                                <div class="form-check">
                                    <input type="checkbox" wire:model="data.{{ $field['key'] }}" value="{{ $opt }}" class="form-check-input">
                                    <label class="form-check-label">{{ $opt }}</label>
                                </div>
                            @endforeach
                            @break
                        @case('file')
                            <input type="file" wire:model="data.{{ $field['key'] }}" class="form-control">
                            @break
                        @case('date')
                            <input type="date" wire:model="data.{{ $field['key'] }}" class="form-control">
                            @break
                        @default
                            <input type="{{ $field['type'] === 'phone' ? 'tel' : $field['type'] }}"
                                wire:model="data.{{ $field['key'] }}" class="form-control"
                                placeholder="{{ $field['placeholder'] }}">
                    @endswitch

                    @if($field['help_text'] ?? false)<div class="form-text">{{ $field['help_text'] }}</div>@endif
                    @error('data.' . $field['key'])<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endif
</div>
