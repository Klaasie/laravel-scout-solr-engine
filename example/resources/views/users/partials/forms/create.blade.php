<div class=" relative flex items-top justify-center py-10">
    <div class="relative py-3 sm:max-w-xl sm:mx-auto">
        <div id="crud-form" class="relative px-4 py-10 bg-white mx-8 md:mx-0 shadow rounded-3xl sm:p-10">
            <div class="max-w-md mx-auto">
                <x-forms.heading icon="add" title="Create User" />
                <div class="divide-y divide-gray-200">
                    <form action="{{ route('users.store') }}" method="POST">
                        @csrf
                        <x-forms.container>
                            <x-forms.input.text label="Name" name="name" :value="old('name')" placeholder="Name">
                                @error('name')
                                    <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.text>
                            <x-forms.input.email label="Email" name="email" :value="old('email')" placeholder="Email">
                                @error('email')
                                    <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.email>

                            <x-forms.input.password label="Password" name="password" placeholder="Password">
                                @error('password')
                                    <x-forms.error :message="$message"/>
                                @enderror
                            </x-forms.input.password>
                        </x-forms.container>
                        <x-button.block text="Create"/>
                    </form>

                    <a href="{{ route('users.index') }}" class="flex items-center w-full text-gray-900 hover:text-gray-500 px-4 py-3 rounded-md focus:outline-none">
                         <span class="material-icons-outlined w-6 h-6 mr-1">arrow_back</span>Back
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
