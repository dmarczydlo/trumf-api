<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

        DB::table('users')->insert([
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'group_id' => 1,
            'name' => 'Jan',
            'surname' => 'Nowak',
            'level' => 5
        ]);


        DB::table('groups')->insert([
            'name' => 'admin'
        ]);

        DB::table('groups')->insert([
            'name' => 'graphic'
        ]);

        DB::table('groups')->insert([
            'name' => 'graver'
        ]);


        $faker = Faker\Factory::create();
        foreach (range(1, 10) as $index) {
            DB::table('tasks')->insert([
                'name' => $faker->name,
                'status' => $faker->numberBetween($min = 1, $max = 3),
                'prio' => $faker->numberBetween($min = 1, $max = 10),
                'time_to_do' => $faker->numberBetween($min = 600, $max = 60000),
                'min_lvl' => $faker->numberBetween($min = 1, $max = 7)

            ]);

        }
    }
}
