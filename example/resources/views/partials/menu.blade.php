<nav class="bg-white shadow">
    <div class="container flex items-center justify-center px-6 py-8 mx-auto text-gray-600 capitalize">
        <a href="{{ route('users.index') }}" class="text-gray-800 border-b-2 border-transparent pb-1 @if($menu === 'users') border-blue-500 @endif hover:border-blue-500 mx-1.5 sm:mx-6 flex">
            <i class="material-icons-outlined text-base md-18 mr-2">people</i>
            Users
        </a>

        <a href="{{ route('books.index') }}" class="text-gray-800 border-b-2 border-transparent pb-1 @if($menu === 'books') border-blue-500 @endif hover:border-blue-500 mx-1.5 sm:mx-6 flex">
            <i class="material-icons-outlined text-base md-18 mr-2">library_books</i>
            Books
        </a>
    </div>
</nav>
