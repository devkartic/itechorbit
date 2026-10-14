<?php

namespace Database\Factories\Admin;

use App\Models\Admin\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->sentence,
            'body' => '<p>'.$this->faker->paragraph(5).'</p>',
            'category_id' => $this->faker->numberBetween(1, 10),
            'is_private' => 0,
            'status' => 1,
            'user_id' => $this->faker->numberBetween(1, 1000),
            'updated_by' => $this->faker->numberBetween(1, 1000),
        ];
    }
}
