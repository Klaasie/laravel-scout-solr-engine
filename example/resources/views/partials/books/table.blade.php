<div class="relative flex items-top justify-center sm:items-center py-4 sm:pt-0">
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="min-w-full flex flex-wrap -mx-1 lg:-mx-4">

                        @foreach($books as $book)
                        <div class="flex my-1 px-1 w-full md:w-1/2 lg:my-4 lg:px-4 lg:w-1/3">
                            <div class="bg-gray-100 grid items-center justify-center">
                                <div class="p-6 bg-white flex rounded-lg shadow-md h-full">
                                    <div>
                                        <span class="material-icons-outlined" style="font-size:2.25rem">book</span>
                                    </div>
                                    <div class="flex flex-col">
                                        <div class="flex flex-row-reverse">
                                            <span class="bg-blue-500 py-1 px-2 m-2 text-sm font-semibold text-white rounded-full cursor-pointer">{{ $book->getPublicationDate()->format('Y/m/d')  }}</span>
                                        </div>
                                        <div class="text-gray-900 font-semibold text-lg">{{ $book->getTitle() }}</div>
                                        <div class="text-sm text-gray-500">by {{ $book->getAuthor() }}</div>
                                        <p class="text-gray-600 text-sm mt-4">{{ $book->getSummary() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        <!-- END Column -->
                    </div>
                    <div class="px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $books->render() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
