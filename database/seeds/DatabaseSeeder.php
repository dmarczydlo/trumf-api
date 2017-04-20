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


        DB::table('groups')->insert([
            'name' => 'admin'
        ]);

        DB::table('groups')->insert([
            'name' => 'grafika'
        ]);

        DB::table('groups')->insert([
            'name' => 'grawernia'
        ]);

        DB::table('groups')->insert([
            'name' => 'kierownik grafiki'
        ]);

        DB::table('groups')->insert([
            'name' => 'kierownik grawernii'
        ]);


        // $this->call(UsersTableSeeder::class);

        DB::table('users')->insert([
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin'),
            'group_id' => 1,
            'name' => 'Jan',
            'surname' => 'Nowak',
            'level' => 5,
            'avatar'=>'m3.png'
        ]);


        DB::table('users')->insert([
            'email' => 'grawer@gmail.com',
            'password' => bcrypt('grawer'),
            'group_id' => 3,
            'name' => 'Jan-Grawer',
            'surname' => 'Nowak',
            'level' => 5,
            'avatar'=>'m2.png'
        ]);

        DB::table('users')->insert([
            'email' => 'grawer2@gmail.com',
            'password' => bcrypt('grawer'),
            'group_id' => 3,
            'name' => 'Adam-Grawer',
            'surname' => 'Pasko',
            'level' => 2
        ]);


        DB::table('users')->insert([
            'email' => 'grafik@gmail.com',
            'password' => bcrypt('grafik'),
            'group_id' => 2,
            'name' => 'Krystyna-Grafik',
            'surname' => 'Baran',
            'level' => 8,
            'avatar'=>'f2.png'
        ]);

        DB::table('users')->insert([
            'email' => 'grafik2@gmail.com',
            'password' => bcrypt('grafik'),
            'group_id' => 2,
            'name' => 'Synek-Grafik',
            'surname' => 'Szefa',
            'level' => 2,
            'avatar'=>'m2.png'
        ]);


        DB::table('users')->insert([
            'email' => 'grafikk@gmail.com',
            'password' => bcrypt('grafik'),
            'group_id' => 4,
            'name' => 'Kierownik',
            'surname' => 'Grafiki',
            'level' => 5,
            'avatar'=>'m1.png'
        ]);

        DB::table('users')->insert([
            'email' => 'grawerk@gmail.com',
            'password' => bcrypt('grawer'),
            'group_id' => 5,
            'name' => 'Kierownik',
            'surname' => 'Grawernii',
            'level' => 5,
            'avatar'=>'m2.png'
        ]);




//        $faker = Faker\Factory::create();
//        for ($i = 1; $i < 300; $i++) {
//            DB::table('tasks')->insert([
//                'name' => $faker->name,
//                'status' => $faker->numberBetween($min = 1, $max = 10),
//                'prio' => $faker->numberBetween($min = 1, $max = 10),
//                'order_number' => $faker->unique()->randomDigit,
//                'date_add' => $faker->dateTimeBetween('-2 years', '+0 days'),
//                'date_order' => $faker->dateTimeBetween('-2 years', '+0 days'),
//                'client' => $faker->company,
//                'employee' => $faker->name,
//                'done' => $faker->numberBetween($min = 0, $max = 1),
//                'graphic_time' => $faker->numberBetween($min = 100, $max = 800),
//                'graver_time' => $faker->numberBetween($min = 100, $max = 800),
//                'min_lvl' => $faker->numberBetween($min = 1, $max = 7),
//                'softlab_id' => $i
//            ]);
//        }
    }
}
