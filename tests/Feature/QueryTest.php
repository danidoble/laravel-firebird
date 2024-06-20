<?php

use Danidoble\Firebird\Tests\Support\Models\Order;
use Danidoble\Firebird\Tests\Support\Models\User;
use Danidoble\Firebird\Tests\TestCase;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

uses(TestCase::class);

it('has the correct connection', function () {
    expect(DB::getDefaultConnection())->toEqual('firebird');
});

it('can get', function () {
    Order::factory()->count(3)->create();

    $users = DB::table('users')->get();

    expect($users)->toHaveCount(3)
        ->and($users)->toBeInstanceOf(Collection::class)
        ->and($users->first())->toBeObject()
        ->and($users->toArray())->toBeArray();

    $orders = DB::table('orders')->get();

    expect($orders)->toHaveCount(3)
        ->and($orders)->toBeInstanceOf(Collection::class)
        ->and($orders->first())->toBeObject()
        ->and($orders->toArray())->toBeArray();
});

it('can select', function () {
    User::factory()->create([
        'name' => 'Anna',
        'city' => 'Sydney',
        'country' => 'Australia',
    ]);

    $result = DB::table('users')
        ->select(['name', 'city', 'country'])
        ->first();

    expect((array)$result)->toHaveCount(3)
        ->and($result)->toBeObject()
        ->and($result)->toHaveProperties(['name', 'city', 'country'])
        ->and($result->name)->toEqual('Anna')
        ->and($result->city)->toEqual('Sydney')
        ->and($result->country)->toEqual('Australia');
});

it('can select with aliases', function () {
    User::factory()->create([
        'name' => 'Anna',
        'city' => 'Sydney',
        'country' => 'Australia',
    ]);

    $result = DB::table('users')
        ->select([
            'name as USER_NAME',
            'city as user_city',
            'country as User_Country',
        ])
        ->first();

    expect((array)$result)->toHaveCount(3)
        ->and($result)->toBeObject()
        ->and($result)->toHaveProperties(['USER_NAME', 'user_city', 'User_Country'])
        ->and($result->USER_NAME)->toEqual('Anna')
        ->and($result->user_city)->toEqual('Sydney')
        ->and($result->User_Country)->toEqual('Australia');

});

it('can select distinct', function () {
    Order::factory()->count(1)->create(['price' => 10]);
    Order::factory()->count(10)->create(['price' => 50]);
    Order::factory()->count(5)->create(['price' => 100]);

    $results = DB::table('orders')->select('price')->distinct()->get();

    expect($results)->toHaveCount(3);
});

it('can filter where with results', function () {
    User::factory()->count(5)->create(['name' => 'Frank']);
    User::factory()->count(2)->create(['name' => 'Inigo']);
    User::factory()->count(7)->create(['name' => 'Ashley']);

    $results = DB::table('users')
        ->where('name', 'Frank')
        ->get();

    expect($results)->toHaveCount(5)
        ->and($results->pluck('name')->unique())->toHaveCount(1)
        ->and($results->random()->name)->toEqual('Frank');
});

it('can filter where without results', function () {
    User::factory()->count(25)->create();

    $results = DB::table('users')
        ->where('id', 26)
        ->get();

    expect($results)->toHaveCount(0)
        ->and($results)->toBeInstanceOf(Collection::class)
        ->and($results->toArray())->toEqual([])
        ->and($results->first())->toBeNull();
});

it('can filter where gt', function () {
    Order::factory()
        ->count(8)
        ->state(new Sequence(
            ['price' => 5],
            ['price' => 25],
            ['price' => 50],
            ['price' => 99],
            ['price' => 100],
            ['price' => 101],
            ['price' => 150],
            ['price' => 200],
        ))
        ->create();

    $results = DB::table('orders')
        ->where('price', '>', 100)
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where gte', function () {
    Order::factory()
        ->count(8)
        ->state(new Sequence(
            ['price' => 5],
            ['price' => 25],
            ['price' => 50],
            ['price' => 99],
            ['price' => 100],
            ['price' => 101],
            ['price' => 150],
            ['price' => 200],
        ))
        ->create();

    $results = DB::table('orders')
        ->where('price', '>=', 100)
        ->get();

    expect($results)->toHaveCount(4);
});

it('can filter where lt', function () {
    Order::factory()
        ->count(8)
        ->state(new Sequence(
            ['price' => 5],
            ['price' => 25],
            ['price' => 50],
            ['price' => 99],
            ['price' => 100],
            ['price' => 101],
            ['price' => 150],
            ['price' => 200],
        ))
        ->create();

    $results = DB::table('orders')
        ->where('price', '<', 100)
        ->get();

    expect($results)->toHaveCount(4);
});

it('can filter where lte', function () {
    Order::factory()
        ->count(8)
        ->state(new Sequence(
            ['price' => 5],
            ['price' => 25],
            ['price' => 50],
            ['price' => 99],
            ['price' => 100],
            ['price' => 101],
            ['price' => 150],
            ['price' => 200],
        ))
        ->create();

    $results = DB::table('orders')
        ->where('price', '<=', 100)
        ->get();

    expect($results)->toHaveCount(5);
});

it('can filter where not equal', function () {
    Order::factory()
        ->count(8)
        ->state(new Sequence(
            ['price' => 5],
            ['price' => 25],
            ['price' => 50],
            ['price' => 99],
            ['price' => 100],
            ['price' => 101],
            ['price' => 150],
            ['price' => 200],
        ))
        ->create();

    $results = DB::table('orders')
        ->where('price', '!=', 100)
        ->get();

    expect($results)->toHaveCount(7);

    $results = DB::table('orders')
        ->where('price', '<>', 100)
        ->get();

    expect($results)->toHaveCount(7);
});

it('can filter where like', function () {
    // "Like" is case-sensitive. For case-insensitive, use "containing".
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'like', 'Shirt%')
        ->get();

    expect($results)->toHaveCount(3);

    $results = DB::table('orders')
        ->where('name', 'like', '%Small')
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where not like', function () {
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'not like', 'Shirt%')
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where array', function () {
    Order::factory()->create(['name' => 'Pants Small', 'price' => 60]);
    Order::factory()->create(['name' => 'Pants Large', 'price' => 80]);
    Order::factory()->create(['name' => 'Shirt Small', 'price' => 50]);
    Order::factory()->create(['name' => 'Shirt Medium', 'price' => 60]);
    Order::factory()->create(['name' => 'Shirt Large', 'price' => 70]);

    $results = DB::table('orders')
        ->where([
            ['price', '>=', 60],
            ['name', 'like', '%Large%'],
            ['name', 'not like', '%Pants%'],
        ])
        ->get();

    expect($results)->toHaveCount(1);
});

it('can filter or where', function () {
    Order::factory()
        ->count(8)
        ->state(new Sequence(
            ['price' => 5],
            ['price' => 25],
            ['price' => 50],
            ['price' => 99],
            ['price' => 100],
            ['price' => 100],
            ['price' => 150],
            ['price' => 200],
        ))
        ->create();

    $results = DB::table('orders')
        ->where('price', 100)
        ->orWhere('price', 5)
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter grouped or where', function () {
    Order::factory()->count(2)->create(['price' => 100]);
    Order::factory()->create(['price' => 25, 'quantity' => 1]);
    Order::factory()->create(['price' => 30, 'quantity' => 3]);

    $results = DB::table('orders')
        ->where('price', '>=', 100)
        ->orWhere(function ($query) {
            $query->where('price', '>', 10)
                ->where('quantity', '>', 2);
        })
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where in', function () {
    Order::factory()->count(1)->create(['price' => 75]);
    Order::factory()->count(3)->create(['price' => 100]);
    Order::factory()->count(5)->create(['price' => 125]);

    $results = DB::table('orders')
        ->whereIn('price', [100, 125])
        ->get();

    expect($results)->toHaveCount(8);
});

it('can filter where in exceeds firebird 2 limit', function () {
    Order::factory()
        ->count(1505)
        ->for(User::factory())
        ->create(['price' => 100]);

    $results = DB::table('orders')
        ->whereIn('price', [100])
        ->count();

    expect($results)->toEqual(1505);
});

it('can filter where not in', function () {
    Order::factory()->count(1)->create(['price' => 75]);
    Order::factory()->count(3)->create(['price' => 100]);
    Order::factory()->count(5)->create(['price' => 125]);

    $results = DB::table('orders')
        ->whereNotIn('price', [100, 125])
        ->get();

    expect($results)->toHaveCount(1);
});

it('can filter where between', function () {
    Order::factory()->create(['price' => 10]);
    Order::factory()->create(['price' => 20]);
    Order::factory()->create(['price' => 30]);
    Order::factory()->create(['price' => 40]);
    Order::factory()->create(['price' => 50]);

    $results = DB::table('orders')
        ->whereBetween('price', [30, 60])
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where not between', function () {
    Order::factory()->create(['price' => 10]);
    Order::factory()->create(['price' => 20]);
    Order::factory()->create(['price' => 30]);
    Order::factory()->create(['price' => 40]);
    Order::factory()->create(['price' => 50]);

    $results = DB::table('orders')
        ->whereNotBetween('price', [30, 60])
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where null', function () {
    Order::factory()->count(10)->create();

    $results = DB::table('orders')
        ->whereNull('deleted_at')
        ->get();

    expect($results)->toHaveCount(10);
});

it('can filter where not null', function () {
    Order::factory()->count(10)->create();

    $results = DB::table('orders')
        ->whereNotNull('created_at')
        ->get();

    expect($results)->toHaveCount(10);
});

it('can filter where date', function () {
    // @phpstan-ignore-next-line
    $this->markTestSkipped('The necessary grammar for whereDate() has not been implemented.');

    Order::factory()->count(10)->create();

    $results = DB::table('orders')
        ->whereDate('created_at', now())
        ->get();

    expect($results)->toHaveCount(10);
});

it('can filter where time', function () {
    // @phpstan-ignore-next-line
    $this->markTestSkipped('The necessary grammar for whereTime() has not been implemented.');

    Order::factory()->count(10)->create();

    $results = DB::table('orders')
        ->whereTime('created_at', now())
        ->get();

    expect($results)->toHaveCount(10);
});

it('can filter where day', function () {
    Order::factory()->count(3)->create(['created_at' => now()]);
    Order::factory()->count(5)->create(['created_at' => now()->subDay()]);

    $results = DB::table('orders')
        ->whereDay('created_at', now())
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where month', function () {
    Order::factory()->count(3)->create(['created_at' => now()]);
    Order::factory()->count(5)->create(['created_at' => now()->subMonth()]);

    $results = DB::table('orders')
        ->whereMonth('created_at', now())
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where year', function () {
    Order::factory()->count(3)->create(['created_at' => now()]);
    Order::factory()->count(5)->create(['created_at' => now()->subYear()]);

    $results = DB::table('orders')
        ->whereYear('created_at', now())
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where containing', function () {
    // "Containing" is a case-insensitive alternative to "like". Also, the
    // % wildcard operators are not required.
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'containing', 'shirt')
        ->get();

    expect($results)->toHaveCount(3);

    $results = DB::table('orders')
        ->where('name', 'containing', 'small')
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where not containing', function () {
    // "Containing" is a case-insensitive alternative to "like". Also, the
    // % wildcard operators are not required.
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'not containing', 'shirt')
        ->get();

    expect($results)->toHaveCount(2);

    $results = DB::table('orders')
        ->where('name', 'not containing', 'small')
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where starting with', function () {
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'starting with', 'Shirt')
        ->get();

    expect($results)->toHaveCount(3);

    $results = DB::table('orders')
        ->where('name', 'starting with', 'Pants')
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where not starting with', function () {
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'not starting with', 'Shirt')
        ->get();

    expect($results)->toHaveCount(2);

    $results = DB::table('orders')
        ->where('name', 'not starting with', 'Pants')
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where similar to', function () {
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'similar to', 'Pants (Medium|Large)')
        ->get();

    expect($results)->toHaveCount(1);

    $results = DB::table('orders')
        ->where('name', 'similar to', 'Shirt (Medium|Large)')
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where not similar to', function () {
    Order::factory()->create(['name' => 'Pants Small']);
    Order::factory()->create(['name' => 'Pants Large']);
    Order::factory()->create(['name' => 'Shirt Small']);
    Order::factory()->create(['name' => 'Shirt Medium']);
    Order::factory()->create(['name' => 'Shirt Large']);

    $results = DB::table('orders')
        ->where('name', 'not similar to', 'Pants (Medium|Large)')
        ->get();

    expect($results)->toHaveCount(4);

    $results = DB::table('orders')
        ->where('name', 'not similar to', 'Shirt (Medium|Large)')
        ->get();

    expect($results)->toHaveCount(3);
});

it('can filter where is distinct from', function () {
    User::factory()->create(['state' => null]);
    User::factory()->create(['state' => 'NY']);
    User::factory()->create(['state' => 'AK']);

    $results = DB::table('users')
        ->where('state', 'is distinct from', 'NY')
        ->get();

    expect($results)->toHaveCount(2);

    $results = DB::table('users')
        ->where('state', 'is distinct from', null)
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where is not distinct from', function () {
    User::factory()->create(['state' => null]);
    User::factory()->create(['state' => 'NY']);
    User::factory()->create(['state' => 'AK']);

    $results = DB::table('users')
        ->where('state', 'is not distinct from', 'NY')
        ->get();

    expect($results)->toHaveCount(1);

    $results = DB::table('users')
        ->where('state', 'is not distinct from', null)
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter where exists', function () {
    Order::factory()->count(2)->create(['price' => 120]);
    Order::factory()->count(3)->create(['price' => 80]);

    $results = DB::table('users')
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('orders')
                ->whereColumn('orders.user_id', 'users.id')
                ->where('price', '>', 100);
        })
        ->get();

    expect($results)->toHaveCount(2);
});

it('can filter sub query where', function () {
    Order::factory()->count(2)->create(['price' => 100]);
    Order::factory()->count(3)->create(['price' => 80]);
    Order::factory()->count(6)->create(['price' => 120]);

    $results = DB::table('users')
        ->where(function ($query) {
            $query->select('price')
                ->from('orders')
                ->whereColumn('orders.user_id', 'users.id')
                ->limit(1);
        }, 100)
        ->get();

    expect($results)->toHaveCount(2);
});

it('can order by asc', function () {
    Order::factory()->create(['price' => 100]);
    Order::factory()->create(['price' => 200]);
    Order::factory()->create(['price' => 300]);

    $results = DB::table('orders')->orderBy('price')->get();

    expect($results->first()->price)->toEqual(100)
        ->and($results->last()->price)->toEqual(300);
});

it('can order by desc', function () {
    Order::factory()->create(['price' => 100]);
    Order::factory()->create(['price' => 200]);
    Order::factory()->create(['price' => 300]);

    $results = DB::table('orders')->orderByDesc('price')->get();

    expect($results->first()->price)->toEqual(300)
        ->and($results->last()->price)->toEqual(100);
});

it('can order latest', function () {
    Order::factory()->create(['price' => 100, 'created_at' => now()]);
    Order::factory()->create(['price' => 200, 'created_at' => now()->subMonth()]);
    Order::factory()->create(['price' => 300, 'created_at' => now()->subMonths(2)]);

    $results = DB::table('orders')->latest()->get();

    expect($results->first()->price)->toEqual(100)
        ->and($results->last()->price)->toEqual(300);
});

it('can order oldest', function () {
    Order::factory()->create(['price' => 100, 'created_at' => now()]);
    Order::factory()->create(['price' => 200, 'created_at' => now()->subMonth()]);
    Order::factory()->create(['price' => 300, 'created_at' => now()->subMonths(2)]);

    $results = DB::table('orders')->oldest()->get();

    expect($results->first()->price)->toEqual(300)
        ->and($results->last()->price)->toEqual(100);
});

it('can return random order', function () {
    User::factory()->count(25)->create();

    $resultsA = DB::table('users')->inRandomOrder()->get();
    $resultsB = DB::table('users')->inRandomOrder()->get();
    $resultsC = DB::table('users')->inRandomOrder()->get();

    expect($resultsA)->not->toEqual($resultsB)
        ->and($resultsA)->not->toEqual($resultsC)
        ->and($resultsB)->not->toEqual($resultsC);
});

it('can remove existing orderings', function () {
    Order::factory()->count(10)->create();

    $query = DB::table('orders')->orderByDesc('id');

    $results = $query->get();

    expect($results->first()->id)->toEqual(10)
        ->and($results->last()->id)->toEqual(1);

    $results = $query->reorder()->get();

    expect($results->first()->id)->toEqual(1)
        ->and($results->last()->id)->toEqual(10);
});

it('can pluck', function () {
    Order::factory()->count(10)->create();

    $results = DB::table('orders')->pluck('id');

    expect($results)->toHaveCount(10);
    foreach (range(1, 10) as $expectedId) {
        expect($results)->toContain($expectedId);
    }
});

it('can count', function () {
    Order::factory()->count(10)->create();

    $count = DB::table('orders')->count();

    expect($count)->toEqual(10);
});

it('can aggregate max', function () {
    Order::factory()
        ->count(5)
        ->state(new Sequence(
            ['price' => 68],
            ['price' => 92],
            ['price' => 12],
            ['price' => 37],
            ['price' => 54],
        ))
        ->create();

    $price = DB::table('orders')->max('price');

    expect($price)->toEqual(92);
});

it('can aggregate min', function () {
    Order::factory()
        ->count(5)
        ->state(new Sequence(
            ['price' => 68],
            ['price' => 92],
            ['price' => 12],
            ['price' => 37],
            ['price' => 54],
        ))
        ->create();

    $price = DB::table('orders')->min('price');

    expect($price)->toEqual(12);
});

it('can aggregate average', function () {
    Order::factory()
        ->count(5)
        ->state(new Sequence(
            ['price' => 68],
            ['price' => 92],
            ['price' => 12],
            ['price' => 37],
            ['price' => 54],
        ))
        ->create();

    $price = DB::table('orders')->avg('price');

    expect($price)->toEqual(52); // 52.6
});

it('can aggregate sum', function () {
    Order::factory()
        ->count(5)
        ->state(new Sequence(
            ['price' => 68],
            ['price' => 92],
            ['price' => 12],
            ['price' => 37],
            ['price' => 54],
        ))
        ->create();

    $price = DB::table('orders')->sum('price');

    expect($price)->toEqual(263);
});

it('can check exists', function () {
    User::factory()->create();

    expect(DB::table('users')->where('id', 1)->exists())->toBeTrue()
        ->and(DB::table('users')->where('id', null)->exists())->toBeFalse();
});

it('can execute raw expressions', function () {
    Order::factory()
        ->count(6)
        ->state(new Sequence(
            ['price' => 50],
            ['price' => 50],
            ['price' => 70],
            ['price' => 90],
            ['price' => 90],
            ['price' => 90],
        ))
        ->create();

    $results = DB::table('orders')
        ->select(DB::raw('count(*) as "price_count", "price"'))
        ->groupBy('price')
        ->get();

    expect($results)->toHaveCount(3)
        ->and($results->where('price', 50)->first()->price_count)->toEqual(2)
        ->and($results->where('price', 70)->first()->price_count)->toEqual(1)
        ->and($results->where('price', 90)->first()->price_count)->toEqual(3);
});

it('can execute raw select containing arithmetic', function () {
    // @phpstan-ignore-next-line
    if ($this->getDatabaseEngineVersion() >= 4.0) {
        // Ref: https://github.com/FirebirdSQL/php-firebird/issues/26
        // @phpstan-ignore-next-line
        $this->markTestSkipped('Skipped due to an issue with DECIMAL or NUMERIC types in the PHP Firebird PDO extension for database engine version 4.0+');
    }

    Order::factory()
        ->count(3)
        ->state(new Sequence(
            ['price' => 50],
            ['price' => 70],
            ['price' => 90],
        ))
        ->create();

    $results = DB::table('orders')
        ->selectRaw('"price", "price" * 1.1 as "price_with_tax"')
        ->get();

    foreach ($results as $result) {
        expect($result->price_with_tax)->toEqual(round($result->price * 1.1));
    }
});

it('can execute raw select containing sum', function () {
    Order::factory()
        ->count(3)
        ->state(new Sequence(
            ['price' => 50],
            ['price' => 70],
            ['price' => 90],
        ))
        ->create();

    $result = DB::table('orders')
        ->selectRaw('SUM("price") as "price"')
        ->get()
        ->first();

    expect($result->price)->toEqual(210);
});

it('can execute raw where', function () {
    User::factory()->count(3)->create();
    User::factory()->create(['city' => null]);

    $results = DB::table('users')
        ->whereRaw('"city" is not null')
        ->get();

    expect($results)->toHaveCount(3);
});

it('can execute raw order by', function () {
    Order::factory()
        ->count(3)
        ->state(new Sequence(
            ['price' => 50, 'quantity' => 10],
            ['price' => 70, 'quantity' => 5],
            ['price' => 90, 'quantity' => 1],
        ))
        ->create();

    $results = DB::table('orders')
        ->orderByRaw('"price" * "quantity" desc')
        ->get();

    $max = $results->first()->price * $results->first()->quantity;
    $min = $results->last()->price * $results->last()->quantity;

    expect($max)->toEqual(500)
        ->and($min)->toEqual(90);
});

it('can add inner join', function () {
    Order::factory()->count(10)->create();

    $results = DB::table('orders')
        ->join('users', 'users.id', '=', 'orders.user_id')
        ->get();

    expect($results)->toHaveCount(10)
        ->and($results->first())->toBeObject()
        ->and($results->first())->toHaveProperties(['name', 'email', 'state', 'price', 'quantity']);

});

it('can add inner join where', function () {
    Order::factory()->count(2)->create(['price' => 100]);
    Order::factory()->count(3)->create(['price' => 50]);

    $results = DB::table('orders')
        ->join('users', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('orders.price', 100);
        })
        ->get();

    expect($results)->toHaveCount(2)
        ->and($results->first()->price)->toEqual(100)
        ->and($results->first())->toHaveProperties(['name', 'email', 'state', 'price', 'quantity']);
});

it('can add left join', function () {
    Order::factory()->count(2)->create(['price' => 100]);
    Order::factory()->count(3)->create(['price' => 50]);

    $results = DB::table('orders')
        ->leftJoin('users', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('orders.price', 100);
        })
        ->get();

    expect($results)->toHaveCount(5)
        ->and($results->whereNull('price'))->toHaveCount(0)
        ->and($results->whereNull('email'))->toHaveCount(3);
});

it('can add right join', function () {
    Order::factory()->count(2)->create(['price' => 100]);
    Order::factory()->count(3)->create(['price' => 50]);

    $results = DB::table('orders')
        ->rightJoin('users', function ($join) {
            $join->on('users.id', '=', 'orders.user_id')
                ->where('orders.price', 100);
        })
        ->get();

    expect($results)->toHaveCount(5)
        ->and($results->whereNull('price'))->toHaveCount(3)
        ->and($results->whereNull('email'))->toHaveCount(0);
});

it('can add sub query join', function () {
    Order::factory()->create();

    $latestOrder = DB::table('orders')
        ->select('user_id', DB::raw('MAX("created_at") as "last_order_created_at"'))
        ->groupBy('user_id');

    $user = DB::table('users')
        ->joinSub($latestOrder, 'latest_order', function ($join) {
            $join->on('users.id', '=', 'latest_order.user_id');
        })->first();

    expect($user->last_order_created_at)->not->toBeNull();
});

it('can union queries', function () {
    Order::factory()
        ->count(5)
        ->state(new Sequence(
            ['price' => 110],
            ['price' => 100],
            ['price' => 100],
            ['price' => 80],
            ['price' => 16],
        ))
        ->create();

    $first = DB::table('orders')
        ->where('price', 100);

    $orders = DB::table('orders')
        ->where('price', 16)
        ->union($first)
        ->get();

    expect($orders)->toHaveCount(3);
});

it('can group having', function () {
    User::factory()->count(5)->create(['country' => 'Australia']);
    User::factory()->count(3)->create(['country' => 'New Zealand']);
    User::factory()->count(2)->create(['country' => 'England']);

    $results = DB::table('users')
        ->selectRaw('count("id") as "count", "country"')
        ->groupBy('country')
        ->having('country', '!=', 'England')
        ->get();

    expect($results)->toHaveCount(2);
    $results = $results->mapWithKeys(fn($result) => [$result->country => $result->count]);
    expect($results['Australia'])->toEqual(5)
        ->and($results['New Zealand'])->toEqual(3);
});

it('can group having raw', function () {
    User::factory()->count(5)->create(['country' => 'Australia']);
    User::factory()->count(3)->create(['country' => 'New Zealand']);
    User::factory()->count(2)->create(['country' => 'England']);

    $results = DB::table('users')
        ->selectRaw('count("id") as "count", "country"')
        ->groupBy('country')
        ->havingRaw('count("id") > 2')
        ->get();

    expect($results)->toHaveCount(2);
    $results = $results->mapWithKeys(fn($result) => [$result->country => $result->count]);
    expect($results['Australia'])->toEqual(5)
        ->and($results['New Zealand'])->toEqual(3);
});

it('can offset results', function () {
    User::factory()->count(10)->create();

    $results = DB::table('users')
        ->offset(3)
        ->get();

    expect($results)->toHaveCount(7)
        ->and($results->first()->id)->toEqual(4)
        ->and($results->last()->id)->toEqual(10);
});

it('can offset and limit results', function () {
    User::factory()->count(10)->create();

    $results = DB::table('users')
        ->offset(3)
        ->limit(3)
        ->get();

    expect($results)->toHaveCount(3)
        ->and($results->first()->id)->toEqual(4)
        ->and($results->last()->id)->toEqual(6);
});

it('can limit results', function () {
    User::factory()->count(10)->create();

    $results = DB::table('users')
        ->limit(3)
        ->get();

    expect($results)->toHaveCount(3)
        ->and($results->first()->id)->toEqual(1)
        ->and($results->last()->id)->toEqual(3);
});

it('can execute stored procedures', function () {
    $firstNumber = random_int(1, 10);
    $secondNumber = random_int(1, 10);

    $result = DB::query()
        ->fromProcedure('MULTIPLY', [
            $firstNumber, $secondNumber,
        ])
        ->first()
        ->RESULT;

    expect($result)->toEqual($firstNumber * $secondNumber);
});
