<div class="flex items-center space-x-5">
    <div class="h-14 w-14 bg-yellow-200 rounded-full flex flex-shrink-0 justify-center items-center text-yellow-500 text-2xl font-mono">
        <i class="material-icons-outlined text-base md-18">
            {{ $icon }}
        </i>
    </div>
    <div class="block pl-2 font-semibold text-xl self-start text-gray-700">
        <h2 class="leading-relaxed">{{ $title }}</h2>
        @if($subtitle !== null)
            <p class="text-sm text-gray-500 font-normal leading-relaxed">
                {{ $subtitle }}
            </p>
        @endif
    </div>
</div>
