<div class="relative flex flex-col items-top justify-center sm:items-center py-4 sm:pt-0">
    <div class="flex flex-row-reverse w-full">
        <div class="pt-4 pb-4 flex items-center space-x-4">
            <a href="{{ route('users.create') }}" class="bg-blue-500 hover:bg-blue-700 flex justify-center items-center w-full text-white px-4 py-3 rounded-md focus:outline-none">Create user</a>
        </div>
        <div class="flex flex-1 flex-col-reverse text-center mb-4">
            <span class="italic"><span class="font-medium">{{ $queryTime }}</span> ms query time</span>
        </div>
    </div>



    <div class="flex flex-col w-full">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                #
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email verified at
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Created at
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Updated at
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $user->getId() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $user->getName() }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->getEmail() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ optional($user->getEmailVerifiedAt())->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ optional($user->getCreatedAt())->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ optional($user->getUpdatedAt())->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('users.edit', $user->getId()) }}" class="text-gray-400 hover:text-blue-500 mx-2">
                                        <i class="material-icons-outlined text-base md-18">edit</i>
                                    </a>
                                    <form action="{{ route('users.destroy', $user->getId()) }}" method="POST" class="inline text-gray-400 hover:text-blue-500 mx-2 cursor-pointer">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="material-icons-round text-base md-18">delete_outline</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        {{ $users->render() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
