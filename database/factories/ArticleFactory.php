<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    public function definition()
    {
        // Optionally generate a published_at date
        $publishedAt = $this->faker->optional()->dateTimeBetween('-1 year', 'now');

        return [
            'title'        => $this->faker->sentence,
            'author'       => $this->faker->optional()->name,
            'url'          => $this->faker->unique()->url,
            'source'       => $this->faker->randomElement(['BBC News', 'CNN', 'Reuters', 'The Guardian', 'The New York Times']),
            'category'     => $this->faker->optional()->word,
            'published_at' => $publishedAt,
            'content'      => $this->faker->paragraphs(3, true),
            'image'        => $this->faker->optional()->imageUrl(640, 480, 'news'),
        ];
    }
}
