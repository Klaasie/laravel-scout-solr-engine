<div class=" relative flex items-top justify-center py-10">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
        <div class="relative px-4 py-10 bg-white mx-8 md:mx-0 shadow rounded-3xl sm:p-10">
            <div class="max-w-md mx-auto">
                <div class="flex items-center space-x-5">
                    <div class="h-14 w-14 bg-yellow-200 rounded-full flex flex-shrink-0 justify-center items-center text-yellow-500 text-2xl font-mono"><i class="material-icons-outlined text-base md-18">search</i></div>
                    <div class="block pl-2 font-semibold text-xl self-start text-gray-700">
                        <h2 class="leading-relaxed">Laravel Scout Apache Solr example</h2>
                        <p class="text-sm text-gray-500 font-normal leading-relaxed">Use the form below to filter results. Use <span class="inline-block py-1 px-2 rounded bg-indigo-50 text-indigo-500 text-xs font-medium tracking-widest">*</span> to create a LIKE query.</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-200">
                    <form action="/books">
                        <div class="pt-8 pb-1 text-base leading-6 space-y-4 text-gray-700 sm:text-lg sm:leading-7">
                            <div class="flex flex-col">
                                <label class="leading-loose">Title</label>
                                <input type="text" name="title" value="{{ $title  }}" class="px-4 py-2 border focus:ring-gray-500 focus:border-gray-900 w-full sm:text-sm border-gray-300 rounded-md focus:outline-none text-gray-600" placeholder="Title">
                            </div>
                            <div class="flex flex-col">
                                <label class="leading-loose">Author</label>
                                <input type="text" name="author" value="{{ $author }}" class="px-4 py-2 border focus:ring-gray-500 focus:border-gray-900 w-full sm:text-sm border-gray-300 rounded-md focus:outline-none text-gray-600" placeholder="Author">
                            </div>
                            <div class="flex flex-col">
                                <label class="leading-loose">Summary</label>
                                <input type="text" name="summary" value="{{ $summary }}" class="px-4 py-2 border focus:ring-gray-500 focus:border-gray-900 w-full sm:text-sm border-gray-300 rounded-md focus:outline-none text-gray-600" placeholder="Summary">
                            </div>
                            <div class="flex flex-col">
                                <label class="leading-loose">Publication date</label>
                                <div class="flex flex-col flex-1 mr-2">
                                    <input type="text" name="publication_date" value="{{ $publicationDate }}" class="px-4 py-2 border focus:ring-gray-500 focus:border-gray-900 w-full sm:text-sm border-gray-300 rounded-md focus:outline-none text-gray-600" placeholder="YYYY-MM-DD">
                                </div>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div class="pt-4 pb-4 flex items-center space-x-4">
                                <button class="bg-blue-500 hover:bg-blue-700 flex justify-center items-center w-full text-white px-4 py-3 rounded-md focus:outline-none">Search</button>
                            </div>
                        </div>
                    </form>

                    <a href="/books" class="flex justify-center items-center w-full text-gray-900 hover:text-gray-500 px-4 py-3 rounded-md focus:outline-none">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg> Reset
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mb-4">
    <span class="italic"><span class="font-medium">{{ $queryTime }}</span> ms query time</span>
</div>
