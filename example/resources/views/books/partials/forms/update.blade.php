<div class="relative flex items-top justify-center py-10">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
        <div id="crud-form" class="relative px-4 py-10 bg-white mx-8 md:mx-0 shadow rounded-3xl sm:p-10">
            <div class="max-w-md mx-auto">
                <x-forms.heading icon="edit" title="Update Book" />
                <div class="divide-y divide-gray-200">
                    <form action="{{ route('books.update', $book->getId()) }}" method="POST">
                        @method('PUT')
                        @csrf
                        <x-forms.container>
                            <x-forms.input.text label="Title" name="title" :value="old('title', $book->title)" placeholder="Title">
                                @error('title')
                                    <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.text>
                            <x-forms.input.text label="Author" name="author" :value="old('author', $book->author)" placeholder="Author">
                                @error('author')
                                    <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.text>
                            <x-forms.input.text label="Publication date" name="publication_date" :value="old('publication_date', $book->publication_date->format('Y-m-d'))" placeholder="Publication date">
                                @error('publication_date')
                                <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.text>
                            <x-forms.input.text-area label="Summary" name="summary" :value="old('summary', $book->summary)" placeholder="Summary">
                                @error('summary')
                                    <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.text-area>
                        </x-forms.container>
                        <x-button.block text="Update"/>
                    </form>

                    <a href="{{ route('books.index') }}" class="flex items-center w-full text-gray-900 hover:text-gray-500 px-4 py-3 rounded-md focus:outline-none">
                         <span class="material-icons-outlined w-6 h-6 mr-1">arrow_back</span>Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ mix('js/publication_date.js') }}"></script>
@endpush

