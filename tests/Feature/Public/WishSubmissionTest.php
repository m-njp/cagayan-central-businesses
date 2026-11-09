<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\BusinessType;
use App\Models\Municipality;
use App\Models\Wish;
use App\Services\FormTimer;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

// P3 with P12 and D21. Anonymous, one new wish per cooldown, a few edits per window.

const COOKIE = 'wish_submitter';

beforeEach(function () {
    $this->town = Municipality::factory()->connected()->create(['psgc_code' => '0201529000', 'name' => 'Tuguegarao City']);
    $this->pharmacy = BusinessType::factory()->create(['code' => 'pharmacy', 'name' => 'Pharmacy']);
    $this->bakery = BusinessType::factory()->create(['code' => 'bakery', 'name' => 'Bakery']);
});

/**
 * A valid submission, with a form token issued as if the page was opened a little while ago.
 */
function wishPayload(array $overrides = []): array
{
    return [
        'town' => '0201529000',
        'type' => 'pharmacy',
        'reason' => null,
        'form_token' => app(FormTimer::class)->issue(now()->subSeconds(30)),
        ...$overrides,
    ];
}

function submitterToken(TestResponse $response): string
{
    return $response->getCookie(COOKIE)->getValue();
}

test('a wish is stored with only a hash of the browser token', function () {
    $response = $this->post(route('public.wishes.store'), wishPayload(['reason' => 'None nearby']))
        ->assertRedirect(route('public.wishes.index', ['town' => '0201529000', 'type' => 'pharmacy']))
        ->assertCookie(COOKIE);

    $token = submitterToken($response);
    $wish = Wish::query()->sole();

    expect($wish->submitter_hash)->toBe(hash('sha256', $token))
        ->and($wish->submitter_hash)->not->toBe($token)
        ->and($wish->reason)->toBe('None nearby');
});

test('the wishes table stores no resident identity or raw network address', function () {
    expect(Schema::getColumnListing('wishes'))
        ->toEqualCanonicalizing(['id', 'municipality_id', 'business_type_id', 'specific_business', 'reason', 'submitter_hash', 'network_hash', 'created_at', 'updated_at']);
});

test('a wish can name a specific business without a type', function () {
    $this->post(route('public.wishes.store'), wishPayload([
        'type' => null,
        'specific_business' => 'Jollibee',
    ]))->assertSessionHasNoErrors();

    $wish = Wish::query()->sole();

    expect($wish->business_type_id)->toBeNull()
        ->and($wish->specific_business)->toBe('Jollibee');
});

test('a wish may include both a type and a specific business', function () {
    $this->post(route('public.wishes.store'), wishPayload([
        'type' => 'bakery',
        'specific_business' => 'Krispy Kreme',
    ]))->assertSessionHasNoErrors();

    $wish = Wish::query()->sole();

    expect($wish->business_type_id)->toBe($this->bakery->id)
        ->and($wish->specific_business)->toBe('Krispy Kreme');
});

test('a wish needs a type or a specific business', function () {
    $this->post(route('public.wishes.store'), wishPayload([
        'type' => null,
        'specific_business' => null,
    ]))->assertSessionHasErrors('type');

    expect(Wish::query()->count())->toBe(0);
});

test('the network is kept only as a keyed hash that changes every day', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('public.wishes.store'), wishPayload());

    $first = Wish::query()->sole()->network_hash;

    expect($first)->toHaveLength(64)
        ->and($first)->not->toContain('203.0.113.7')
        ->and($first)->not->toBe(hash('sha256', '203.0.113.7'));

    $this->travel(1)->day();
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('public.wishes.store'), wishPayload());

    expect(Wish::query()->latest('id')->first()->network_hash)->not->toBe($first);
});

test('one network can bring in only so many new respondents a day', function () {
    config(['cagayan.wishes.new_respondents_per_network_per_day' => 2]);

    // Each request has no cookie, as when someone clears cookies between submissions.
    foreach (range(1, 2) as $respondent) {
        $this->post(route('public.wishes.store'), wishPayload())->assertSessionHasNoErrors();
    }

    $this->post(route('public.wishes.store'), wishPayload())->assertSessionHasErrors('wish');

    expect(Wish::query()->count())->toBe(2);
});

test('a form sent back too quickly, too late, or with a forged token is rejected', function (Closure $token) {
    $this->post(route('public.wishes.store'), wishPayload(['form_token' => $token()]))
        ->assertSessionHasErrors('form_token');

    expect(Wish::query()->count())->toBe(0);
})->with([
    'too quickly' => fn () => app(FormTimer::class)->issue(now()),
    'too late' => fn () => app(FormTimer::class)->issue(now()->subMinutes(config('cagayan.wishes.max_fill_minutes') + 1)),
    'forged' => fn () => base64_encode((string) now()->subMinute()->getTimestamp()),
    'missing' => fn () => null,
]);

test('reasons are stored as plain text', function () {
    $this->post(route('public.wishes.store'), wishPayload(['reason' => "  <script>alert(1)</script>No   pharmacy\u{0007} nearby  "]));

    expect(Wish::query()->sole()->reason)->toBe('alert(1)No pharmacy nearby');
});

test('a specific business name is stored as plain text', function () {
    $this->post(route('public.wishes.store'), wishPayload([
        'specific_business' => "  <b>Krispy   Kreme</b>\u{0007}  ",
    ]));

    expect(Wish::query()->sole()->specific_business)->toBe('Krispy Kreme');
});

test('reasons with links are rejected', function (string $reason) {
    $this->post(route('public.wishes.store'), wishPayload(['reason' => $reason]))
        ->assertSessionHasErrors('reason');
})->with(['Visit https://spam.test', 'cheap pills at www.example', 'see example.com']);

test('a reason over the length limit is rejected', function () {
    $this->post(route('public.wishes.store'), wishPayload(['reason' => str_repeat('a', config('cagayan.wishes.reason_max') + 1)]))
        ->assertSessionHasErrors('reason');
});

test('a reason accepts 255 characters and rejects 256', function () {
    expect(config('cagayan.wishes.reason_max'))->toBe(255);

    $this->post(route('public.wishes.store'), wishPayload(['reason' => str_repeat('a', 255)]))
        ->assertSessionHasNoErrors();

    $this->travel(config('cagayan.wishes.cooldown_days'))->days();
    $this->travel(1)->minute();

    $this->post(route('public.wishes.store'), wishPayload(['reason' => str_repeat('a', 256)]))
        ->assertSessionHasErrors('reason');
});

test('reading the page does not hand out a cookie', function () {
    $this->get(route('public.wishes.index'))->assertOk()->assertCookieMissing(COOKIE);
});

test('a browser waits out the cooldown before its next wish', function () {
    $token = submitterToken($this->post(route('public.wishes.store'), wishPayload()));

    $this->withCookie(COOKIE, $token)
        ->post(route('public.wishes.store'), wishPayload(['type' => 'bakery']))
        ->assertSessionHasErrors('wish');

    expect(Wish::query()->count())->toBe(1);

    $this->travel(config('cagayan.wishes.cooldown_days'))->days();
    $this->travel(1)->minute();

    $this->withCookie(COOKIE, $token)
        ->post(route('public.wishes.store'), wishPayload(['type' => 'bakery']))
        ->assertSessionHasNoErrors();

    expect(Wish::query()->count())->toBe(2);
});

test('a browser may edit a few times per window and then must wait', function () {
    $token = submitterToken($this->post(route('public.wishes.store'), wishPayload()));
    $wish = Wish::query()->sole();
    $edits = config('cagayan.wishes.edits_per_window');

    foreach (range(1, $edits) as $edit) {
        $this->withCookie(COOKIE, $token)
            ->put(route('public.wishes.update', $wish), wishPayload(['type' => $edit % 2 ? 'bakery' : 'pharmacy']))
            ->assertSessionHasNoErrors();
    }

    $this->withCookie(COOKIE, $token)
        ->put(route('public.wishes.update', $wish), wishPayload(['reason' => 'one more']))
        ->assertSessionHasErrors('wish');

    expect($wish->edits()->count())->toBe($edits)
        ->and($wish->fresh()->reason)->toBeNull();

    $this->travel(config('cagayan.wishes.edit_window_days'))->days();
    $this->travel(1)->minute();

    $this->withCookie(COOKIE, $token)
        ->put(route('public.wishes.update', $wish), wishPayload(['reason' => 'one more']))
        ->assertSessionHasNoErrors();

    expect($wish->fresh()->reason)->toBe('one more');
});

test('nobody else can edit a wish', function (?string $token) {
    $wish = Wish::factory()->for($this->town)->for($this->pharmacy)->create();

    $request = $token === null ? $this : $this->withCookie(COOKIE, $token);

    $request->put(route('public.wishes.update', $wish), wishPayload(['type' => 'bakery']))->assertNotFound();

    expect($wish->fresh()->business_type_id)->toBe($this->pharmacy->id);
})->with(['no cookie' => null, 'another browser' => 'someone-elses-token']);

test('a filled honeypot is rejected', function () {
    $this->post(route('public.wishes.store'), wishPayload(['website' => 'https://spam.test']))
        ->assertSessionHasErrors('website');

    expect(Wish::query()->count())->toBe(0);
});

test('unknown towns and types are rejected', function () {
    $this->post(route('public.wishes.store'), wishPayload(['town' => '9999999999', 'type' => 'casino']))
        ->assertSessionHasErrors(['town', 'type']);
});

test('the form is rate limited per network address', function () {
    config(['cagayan.wishes.per_hour_per_ip' => 2]);

    // Fresh browsers each time, as when cookies are cleared. The hourly limit still holds.
    $this->post(route('public.wishes.store'), wishPayload());
    $this->post(route('public.wishes.store'), wishPayload());
    $this->post(route('public.wishes.store'), wishPayload())->assertTooManyRequests();
});

test('the page shows the tally and only this browser its own wish', function () {
    Wish::factory()->count(2)->for($this->town)->for($this->pharmacy)->create(['reason' => 'SENTINEL-OTHER-REASON']);
    $token = submitterToken($this->post(route('public.wishes.store'), wishPayload(['reason' => 'Mine'])));

    $this->withCookie(COOKIE, $token)
        ->get(route('public.wishes.index', ['town' => '0201529000', 'type' => 'pharmacy']))
        ->assertOk()
        ->assertDontSee('SENTINEL-OTHER-REASON')
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/wishes/index')
            ->where('page.tally.wishCount', 3)
            ->where('page.tally.registeredCount', null)
            ->where('page.myWish.reason', 'Mine')
            ->where('page.myWish.editsLeft', config('cagayan.wishes.edits_per_window'))
            ->whereNot('page.nextWishAt', null));
});

test('another browser sees the count but not the wish or its reason', function () {
    Wish::factory()->for($this->town)->for($this->pharmacy)->create([
        'reason' => 'SENTINEL-OTHER-REASON',
        'specific_business' => 'SENTINEL-OTHER-COMPANY',
    ]);

    $this->get(route('public.wishes.index', ['town' => '0201529000', 'type' => 'pharmacy']))
        ->assertDontSee('SENTINEL-OTHER-REASON')
        ->assertDontSee('SENTINEL-OTHER-COMPANY')
        ->assertInertia(fn (Assert $page) => $page
            ->where('page.tally.wishCount', 1)
            ->where('page.myWish', null)
            ->where('page.nextWishAt', null));
});

test('selection requests return only the category count without rebuilding the form payload', function () {
    Wish::factory()->count(2)->for($this->town)->for($this->pharmacy)->create([
        'reason' => 'SENTINEL-PRIVATE-REASON',
        'specific_business' => 'SENTINEL-PRIVATE-COMPANY',
    ]);
    Wish::factory()->for($this->town)->for($this->bakery)->create();

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'public/wishes/index',
        'X-Inertia-Partial-Data' => 'tally',
    ])->get(route('public.wishes.index', ['town' => $this->town->psgc_code, 'type' => $this->pharmacy->code]))
        ->assertOk()
        ->assertJsonPath('props.tally.wishCount', 2)
        ->assertJsonPath('props.tally.typeName', 'Pharmacy')
        ->assertJsonPath('props.tally.registeredCount', null)
        ->assertJsonMissingPath('props.page')
        ->assertDontSee('SENTINEL-PRIVATE-REASON')
        ->assertDontSee('SENTINEL-PRIVATE-COMPANY');
});

test('selection requests with missing or unknown codes have no tally', function (array $query) {
    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'public/wishes/index',
        'X-Inertia-Partial-Data' => 'tally',
    ])->get(route('public.wishes.index', $query))
        ->assertOk()
        ->assertJsonPath('props.tally', null)
        ->assertJsonMissingPath('props.page');
})->with([
    'no type' => [['town' => '0201529000']],
    'unknown town' => [['town' => '9999999999', 'type' => 'pharmacy']],
    'unknown type' => [['town' => '0201529000', 'type' => 'unknown']],
]);
