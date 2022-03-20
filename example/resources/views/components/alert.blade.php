@if($message = Session::get('success'))
    <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800 font-medium mt-3 shadow" role="alert">
        {{ $message }}
    </div>
@endif
