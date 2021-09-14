<?php

namespace Database\Factories;

use App\Models\Book;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * @throws Exception
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(random_int(2, 8), true),
            'author' => $this->faker->name(),
            'publication_date' => Carbon::today()
                ->subYears(random_int(0, 25))
                ->subMonths(random_int(0, 12))
                ->subDays(random_int(0, 365)),
            'summary' => $this->faker->realTextBetween(450, 900)
        ];
    }
}
