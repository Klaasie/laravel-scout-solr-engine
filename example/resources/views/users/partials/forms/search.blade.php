<div class=" relative flex items-top justify-center py-10">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
        <div class="relative px-4 py-10 bg-white mx-8 md:mx-0 shadow rounded-3xl sm:p-10">
            <div class="max-w-md mx-auto">
                <x-forms.heading icon="search" title="Laravel Scout Apache Solr example">
                    <x-slot:subtitle>
                        Use the form below to filter results. Use <span class="inline-block py-1 px-2 rounded bg-indigo-50 text-indigo-500 text-xs font-medium tracking-widest">*</span> to create a LIKE query.
                    </x-slot:subtitle>
                </x-forms.heading>
                <div class="divide-y divide-gray-200">
                    <form action="{{ Request::path() }}">
                        <x-forms.container>
                            <x-forms.input.text label="Name" name="name" :value="$name" placeholder="Name" />
                            <x-forms.input.email label="Email" name="email" :value="$email" placeholder="Email" />
                        </x-forms.container>
                        <x-button.block text="Search"/>
                    </form>
                    <x-forms.reset />
                </div>
            </div>
        </div>
    </div>
</div>
