<div class="flex flex-row-reverse w-full">
    <div class="pt-4 pb-4 flex items-center space-x-4">
        <a href="{{ route('books.create') }}" class="bg-blue-500 hover:bg-blue-700 flex justify-center items-center w-full text-white px-4 py-3 rounded-md focus:outline-none">Create book</a>
    </div>
    <div class="flex flex-1 flex-col-reverse text-center mb-4">
        <span class="italic"><span class="font-medium">{{ $queryTime }}</span> ms query time</span>
    </div>
</div>

<div class="relative flex items-top justify-center sm:items-center py-4 sm:pt-0">
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="min-w-full flex flex-wrap -mx-1 lg:-mx-4">
                        @foreach($books as $book)
                            <div class="flex my-1 px-1 w-full md:w-1/2 lg:my-4 lg:px-4 lg:w-1/2 xl:w-1/3">
                                <div class="bg-gray-100 ">
                                    <div class="pt-6 px-6 pb-3 bg-white flex rounded-lg shadow-md h-full flex flex-col items-center justify-center">
                                        <div class="flex flex-col flex-1">
                                            <div class="text-gray-900 font-semibold text-lg">{{ $book->getTitle() }}</div>
                                            <div class="flex flex-row">
                                                <div class="flex-1 text-sm text-gray-500">by {{ $book->getAuthor() }}</div>
                                                <span class="text-gray-500 text-sm">{{ $book->getPublicationDate()->format('Y-m-d')  }}</span>
                                            </div>
                                            <p class="text-gray-600 text-sm mt-4 flex-1">{{ $book->getSummary() }}</p>
                                            <div class="w-full border-t border-gray-100 my-4"></div>
                                            <div class="flex flex-row divide-x">
                                                <a href="{{ route('books.edit', $book->getId()) }}" class="text-gray-400 hover:text-blue-500 mx-2 flex-1 flex justify-center">
                                                    <i class="material-icons-outlined text-base md-18">edit</i>
                                                </a>
                                                <form action="{{ route('books.destroy', $book->getId()) }}" method="POST" class="inline text-gray-400 hover:text-blue-500 mx-2 cursor-pointer flex-1 flex justify-center">
                                                    @method('DELETE')
                                                    @csrf
                                                    <button type="submit" class="material-icons-round text-base md-18">delete_outline</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $books->render() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
