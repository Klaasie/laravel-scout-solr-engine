<div class="flex flex-col">
    <label class="leading-loose">
        {{ $label }}
    </label>
    <input type="text"
           name="{{ $name }}"
           value="{{ $value }}"
           class="px-4 py-2 border focus:ring-gray-500 focus:border-gray-900 w-full sm:text-sm border-gray-300 rounded-md focus:outline-none text-gray-600 @error('name') border-red-500 @enderror"
           placeholder="{{ $placeholder }}">
    {{ $slot }}
</div>
