<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Domain\Account\Models\Account;
use App\Domain\Account\Models\AccountBalance;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

/**
 * Laravel Feature Context for Behat
 */
class LaravelFeatureContext implements Context
{
    protected static $app;
    protected static $kernel;
    public static $sharedUser; // Share user between contexts
    protected $currentUser;
    protected $lastResponse;
    protected $baseUrl;
    protected $expectedBalance;
    protected $lastDeposit = false;
    protected $lastWithdrawal = false;

    /**
     * @BeforeSuite
     */
    public static function prepare()
    {
        putenv('APP_ENV=testing');
        putenv('QUEUE_CONNECTION=sync');
        
        if (!static::$app) {
            static::$app = require __DIR__ . '/../../bootstrap/app.php';
            static::$kernel = static::$app->make(\Illuminate\Contracts\Console\Kernel::class);
            static::$kernel->bootstrap();
            
            // Ensure sync queue driver is used
            config(['queue.default' => 'sync']);
            
            // Run migrations and seeders for the test suite
            Artisan::call('migrate:fresh');
            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
            
            // Ensure required roles exist for all guards
            self::createRequiredRoles();
        }
    }
    
    /**
     * Create required roles for testing
     */
    private static function createRequiredRoles(): void
    {
        // Create super_admin role for all guards
        $guards = ['web', 'sanctum', 'api'];
        foreach ($guards as $guard) {
            if (!Role::where('name', 'super_admin')->where('guard_name', $guard)->exists()) {
                Role::create(['name' => 'super_admin', 'guard_name' => $guard]);
            }
        }
        
        // Create other required roles
        $roles = ['business', 'private', 'admin'];
        foreach ($roles as $role) {
            foreach ($guards as $guard) {
                if (!Role::where('name', $role)->where('guard_name', $guard)->exists()) {
                    Role::create(['name' => $role, 'guard_name' => $guard]);
                }
            }
        }
    }

    /**
     * @BeforeScenario
     */
    public function setUp()
    {
        $this->baseUrl = config('app.url');
        
        // Ensure sync queue driver for testing
        config(['queue.default' => 'sync']);
        config(['queue.connections.sync.driver' => 'sync']);
        
        // Also set workflow to use sync connection
        config(['workflows.monitor_connection' => 'sync']);
        config(['workflows.monitor_queue' => 'sync']);
        
        // Start database transaction
        DB::beginTransaction();
        
        // Run migrations if needed
        if (!$this->tablesExist()) {
            Artisan::call('migrate:fresh');
            Artisan::call('db:seed', ['--class' => 'DatabaseSeeder']);
        }
        
        // Ensure roles exist for current test
        $this->ensureRolesExist();
    }

    /**
     * @AfterScenario
     */
    public function tearDown()
    {
        // Rollback transaction
        DB::rollBack();
    }

    /**
     * Check if database tables exist
     */
    private function tablesExist(): bool
    {
        try {
            DB::table('users')->exists();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Ensure required roles exist
     */
    private function ensureRolesExist(): void
    {
        // Create super_admin role if it doesn't exist
        if (!Role::where('name', 'super_admin')->where('guard_name', 'sanctum')->exists()) {
            Role::create(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        }
        
        // Create other required roles
        $roles = ['business', 'private', 'admin'];
        foreach ($roles as $role) {
            if (!Role::where('name', $role)->where('guard_name', 'sanctum')->exists()) {
                Role::create(['name' => $role, 'guard_name' => 'sanctum']);
            }
        }
    }
    

    /**
     * @Given I am logged in as :email
     */
    public function iAmLoggedInAs($email)
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $user = User::factory()->create([
                'email' => $email,
                'password' => Hash::make('password'),
            ]);
        }
        
        $this->currentUser = $user;
        self::$sharedUser = $user; // Share with other contexts
        Sanctum::actingAs($user, ['read', 'write', 'delete']);
    }

    /**
     * @Given I have an account with balance :amount :currency
     */
    public function iHaveAnAccountWithBalance($amount, $currency)
    {
        // Convert amount to cents
        $amountInCents = (int) ($amount * 100);
        
        // Check if we already have a current account (for multi-currency scenarios)
        $account = $this->currentUser->getAttribute('current_account');
        
        if (!$account) {
            // Create new account
            $account = Account::factory()->create([
                'user_uuid' => $this->currentUser->uuid,
                'balance' => $currency === 'USD' ? $amountInCents : 0,
            ]);
        }
        
        // Check if currency is a basket and ensure it exists as an asset
        $asset = \App\Domain\Asset\Models\Asset::where('code', $currency)->first();
        if (!$asset) {
            // Check if it's a basket
            $basket = \App\Models\BasketAsset::where('code', $currency)->first();
            if ($basket) {
                // Create the basket as an asset
                \App\Domain\Asset\Models\Asset::create([
                    'code' => $currency,
                    'name' => $basket->name,
                    'type' => 'basket',
                    'precision' => 2,
                    'is_active' => true,
                ]);
            }
        }
        
        // Check if balance record already exists
        $existingBalance = AccountBalance::where('account_uuid', $account->uuid)
            ->where('asset_code', $currency)
            ->first();
            
        if (!$existingBalance) {
            // Create balance record for the currency
            AccountBalance::create([
                'account_uuid' => $account->uuid,
                'asset_code'   => $currency,
                'balance'      => $amountInCents,
            ]);
        } else {
            // Update existing balance
            $existingBalance->balance = $amountInCents;
            $existingBalance->save();
        }
        
        // Update legacy balance field if USD
        if ($currency === 'USD' && !$this->currentUser->getAttribute('current_account')) {
            $account->balance = $amountInCents;
            $account->save();
        }
        
        // Refresh to ensure we have the latest data
        $account->refresh();
        
        $this->currentUser->setAttribute('current_account', $account);
    }

    /**
     * @When I send a POST request to :endpoint
     */
    public function iSendAPostRequestTo($endpoint)
    {
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        
        if ($this->currentUser) {
            $token = $this->currentUser->createToken('behat-test', ['read', 'write', 'delete'])->plainTextToken;
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        
        // Special handling for account creation endpoint
        $body = null;
        if ($endpoint === '/api/accounts' && $this->currentUser) {
            $body = json_encode([
                'user_uuid' => $this->currentUser->uuid,
                'name' => 'Test Account'
            ]);
        }
        
        // Parse body if JSON
        $parameters = [];
        if ($body) {
            $parameters = json_decode($body, true);
        }
        
        $request = \Illuminate\Http\Request::create(
            $this->baseUrl . $endpoint,
            'POST',
            $parameters,
            [],
            [],
            $this->transformHeaders($headers),
            $body
        );
        
        $this->lastResponse = app()->handle($request);
    }

    /**
     * @When I deposit :amount :currency into my account
     */
    public function iDepositIntoMyAccount($amount, $currency)
    {
        $account = $this->currentUser->getAttribute('current_account') ??
                   Account::where('user_uuid', $this->currentUser->uuid)->first();
        
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        $token = $this->currentUser->createToken('behat-test', ['read', 'write', 'delete'])->plainTextToken;
        $headers['Authorization'] = 'Bearer ' . $token;
        
        $body = json_encode([
            'amount' => $amount, // Send amount as decimal, not cents
            'asset_code' => $currency
        ]);
        
        $request = \Illuminate\Http\Request::create(
            $this->baseUrl . "/api/accounts/{$account->uuid}/deposit",
            'POST',
            json_decode($body, true),
            [],
            [],
            $this->transformHeaders($headers),
            $body
        );
        
        $this->lastResponse = app()->handle($request);
    }

    /**
     * @When I withdraw :amount :currency from my account
     */
    public function iWithdrawFromMyAccount($amount, $currency)
    {
        $account = $this->currentUser->getAttribute('current_account') ??
                   Account::where('user_uuid', $this->currentUser->uuid)->first();
        
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        $token = $this->currentUser->createToken('behat-test', ['read', 'write', 'delete'])->plainTextToken;
        $headers['Authorization'] = 'Bearer ' . $token;
        
        $body = json_encode([
            'amount' => $amount, // Send amount as decimal, not cents
            'asset_code' => $currency
        ]);
        
        $request = \Illuminate\Http\Request::create(
            $this->baseUrl . "/api/accounts/{$account->uuid}/withdraw",
            'POST',
            json_decode($body, true),
            [],
            [],
            $this->transformHeaders($headers),
            $body
        );
        
        $this->lastResponse = app()->handle($request);
    }

    /**
     * @When I try to withdraw :amount :currency from my account
     */
    public function iTryToWithdrawFromMyAccount($amount, $currency)
    {
        $this->iWithdrawFromMyAccount($amount, $currency);
    }

    /**
     * @When I transfer :amount :currency to account :accountUuid
     */
    public function iTransferToAccount($amount, $currency, $accountUuid)
    {
        $fromAccount = $this->currentUser->getAttribute('current_account') ??
                       Account::where('user_uuid', $this->currentUser->uuid)->first();
        
        $amountInCents = (int) ($amount * 100);
        
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        $token = $this->currentUser->createToken('behat-test', ['read', 'write', 'delete'])->plainTextToken;
        $headers['Authorization'] = 'Bearer ' . $token;
        
        $request = \Illuminate\Http\Request::create(
            $this->baseUrl . '/api/transfers',
            'POST',
            [],
            [],
            [],
            $this->transformHeaders($headers),
            json_encode([
                'from_account_uuid' => $fromAccount->uuid,
                'to_account_uuid' => $accountUuid,
                'amount' => $amountInCents,
                'asset_code' => $currency,
            ])
        );
        
        $this->lastResponse = app()->handle($request);
    }

    /**
     * @When I try to transfer :amount :currency to account :accountUuid
     */
    public function iTryToTransferToAccount($amount, $currency, $accountUuid)
    {
        $this->iTransferToAccount($amount, $currency, $accountUuid);
    }

    /**
     * @Then the response status code should be :code
     */
    public function theResponseStatusCodeShouldBe($code)
    {
        $actualCode = $this->lastResponse->getStatusCode();
        if ($actualCode != $code) {
            throw new \Exception(
                "Expected status code $code but got $actualCode. Response: " . 
                $this->lastResponse->getContent()
            );
        }
    }

    /**
     * @Then the response should have a :field field
     */
    public function theResponseShouldHaveAField($field)
    {
        $content = json_decode($this->lastResponse->getContent(), true);
        
        // Check in root level first
        if (array_key_exists($field, $content)) {
            return;
        }
        
        // Check in data field if exists
        if (isset($content['data']) && array_key_exists($field, $content['data'])) {
            return;
        }
        
        throw new \Exception("Response does not have field: $field");
    }

    /**
     * @Then the response :field field should equal :value
     */
    public function theResponseFieldShouldEqual($field, $value)
    {
        $content = json_decode($this->lastResponse->getContent(), true);
        
        // Check in root level first
        if (isset($content[$field])) {
            if ($content[$field] != $value) {
                throw new \Exception(
                    "Expected $field to be '$value' but got '{$content[$field]}'"
                );
            }
            return;
        }
        
        // Check in data field if exists
        if (isset($content['data']) && isset($content['data'][$field])) {
            if ($content['data'][$field] != $value) {
                throw new \Exception(
                    "Expected $field to be '$value' but got '{$content['data'][$field]}'"
                );
            }
            return;
        }
        
        throw new \Exception("Response does not have field: $field");
    }

    /**
     * @Then the deposit should be successful
     */
    public function theDepositShouldBeSuccessful()
    {
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Then the withdrawal should be successful
     */
    public function theWithdrawalShouldBeSuccessful()
    {
        $this->theResponseStatusCodeShouldBe(200);
        
        // Debug: log the response
        $content = json_decode($this->lastResponse->getContent(), true);
        if (isset($content['message'])) {
            error_log('Withdrawal response: ' . json_encode($content));
        }
    }

    /**
     * @Then the withdrawal should fail with error :message
     */
    public function theWithdrawalShouldFailWithError($message)
    {
        $this->theResponseStatusCodeShouldBe(422);
        
        $content = json_decode($this->lastResponse->getContent(), true);
        
        // Check if the message is contained in the response message field or errors field
        $foundMessage = false;
        
        if (isset($content['message']) && str_contains(strtolower($content['message']), strtolower($message))) {
            $foundMessage = true;
        }
        
        if (!$foundMessage && isset($content['errors'])) {
            foreach ($content['errors'] as $field => $errors) {
                foreach ((array)$errors as $error) {
                    if (str_contains(strtolower($error), strtolower($message))) {
                        $foundMessage = true;
                        break 2;
                    }
                }
            }
        }
        
        if (!$foundMessage) {
            throw new \Exception("Expected error message containing '$message' but got: " . json_encode($content));
        }
    }

    /**
     * @Then the transfer should be successful
     */
    public function theTransferShouldBeSuccessful()
    {
        $this->theResponseStatusCodeShouldBe(200);
    }

    /**
     * @Then the transfer should fail with error :message
     */
    public function theTransferShouldFailWithError($message)
    {
        $this->theResponseStatusCodeShouldBe(422);
        
        $content = json_decode($this->lastResponse->getContent(), true);
        if (!isset($content['message']) || !str_contains($content['message'], $message)) {
            throw new \Exception("Expected error message containing '$message' but got: " . json_encode($content));
        }
    }

    /**
     * @Then my account balance should be :amount :currency
     */
    public function myAccountBalanceShouldBe($amount, $currency)
    {
        $expectedBalance = (int) ($amount * 100);
        
        $account = $this->currentUser->getAttribute('current_account') ??
                   Account::where('user_uuid', $this->currentUser->uuid)->first();
        
        // Refresh to get latest data
        $account->refresh();
        $account->load('balances');
        
        $actualBalance = $account->getBalance($currency);
        
        if ($actualBalance != $expectedBalance) {
            throw new \Exception(
                "Expected balance to be $expectedBalance but got $actualBalance"
            );
        }
    }

    /**
     * @Then account :accountUuid should have balance :amount :currency
     */
    public function accountShouldHaveBalance($accountUuid, $amount, $currency)
    {
        $account = Account::where('uuid', $accountUuid)->firstOrFail();
        
        $expectedBalance = (int) ($amount * 100);
        $actualBalance = $currency === 'USD' ? $account->balance : $account->getBalance($currency);
        
        if ($actualBalance != $expectedBalance) {
            throw new \Exception(
                "Expected account $accountUuid to have balance $expectedBalance but got $actualBalance"
            );
        }
    }

    /**
     * @When I check my total balance
     */
    public function iCheckMyTotalBalance()
    {
        // Get the last created account (which should have all balances)
        $account = $this->currentUser->getAttribute('current_account') ??
                   Account::where('user_uuid', $this->currentUser->uuid)->orderBy('created_at', 'desc')->first();
        
        $headers = ['Accept' => 'application/json'];
        $token = $this->currentUser->createToken('behat-test', ['read', 'write', 'delete'])->plainTextToken;
        $headers['Authorization'] = 'Bearer ' . $token;
        
        $request = \Illuminate\Http\Request::create(
            $this->baseUrl . "/api/accounts/{$account->uuid}/balances",
            'GET',
            [],
            [],
            [],
            $this->transformHeaders($headers)
        );
        
        $this->lastResponse = app()->handle($request);
    }

    /**
     * @Then I should see:
     */
    public function iShouldSee(TableNode $table)
    {
        $content = json_decode($this->lastResponse->getContent(), true);
        
        foreach ($table->getHash() as $row) {
            $currency = $row['Currency'];
            $expectedBalance = $row['Balance'];
            
            $found = false;
            
            // Check if response has balances array
            $balances = $content['data']['balances'] ?? $content['balances'] ?? $content['data'] ?? [];
            
            // Ensure it's an array
            if (!is_array($balances)) {
                $balances = [$balances];
            }
            
            foreach ($balances as $balance) {
                if (!is_array($balance)) {
                    continue;
                }
                
                $assetCode = $balance['asset_code'] ?? $balance['currency'] ?? null;
                if ($assetCode === $currency) {
                    $balanceAmount = $balance['balance'] ?? 0;
                    $actualBalance = number_format($balanceAmount / 100, 2, '.', '');
                    if ($actualBalance == $expectedBalance) {
                        $found = true;
                        break;
                    } else {
                        throw new \Exception(
                            "Expected $currency balance to be $expectedBalance but got $actualBalance"
                        );
                    }
                }
            }
            
            if (!$found) {
                throw new \Exception("Currency $currency not found in response. Response: " . json_encode($content));
            }
        }
    }

    /**
     * @When I submit the following bulk transfers:
     */
    public function iSubmitTheFollowingBulkTransfers(TableNode $table)
    {
        $fromAccount = $this->currentUser->getAttribute('current_account') ??
                       Account::where('user_uuid', $this->currentUser->uuid)->first();
        
        $transfers = [];
        foreach ($table->getHash() as $row) {
            $transfers[] = [
                'to_account_uuid' => $row['to_account'],
                'amount' => (int) ($row['amount'] * 100),
                'asset_code' => $row['currency'],
            ];
        }
        
        $headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
        $token = $this->currentUser->createToken('behat-test', ['read', 'write', 'delete'])->plainTextToken;
        $headers['Authorization'] = 'Bearer ' . $token;
        
        $request = \Illuminate\Http\Request::create(
            $this->baseUrl . '/api/bulk-transfers',
            'POST',
            [],
            [],
            [],
            $this->transformHeaders($headers),
            json_encode([
                'from_account_uuid' => $fromAccount->uuid,
                'transfers' => $transfers,
            ])
        );
        
        $this->lastResponse = app()->handle($request);
    }

    /**
     * @Then all transfers should be successful
     */
    public function allTransfersShouldBeSuccessful()
    {
        $this->theResponseStatusCodeShouldBe(200);
        
        $content = json_decode($this->lastResponse->getContent(), true);
        if (!isset($content['data']) || !is_array($content['data'])) {
            throw new \Exception("Expected response to have 'data' array");
        }
        
        foreach ($content['data'] as $transfer) {
            if ($transfer['status'] !== 'completed') {
                throw new \Exception("Transfer {$transfer['id']} failed with status: {$transfer['status']}");
            }
        }
    }

    /**
     * Transform headers for request
     */
    private function transformHeaders($headers)
    {
        $transformed = [];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), ['accept', 'content-type', 'authorization'])) {
                $transformed['HTTP_' . str_replace('-', '_', strtoupper($key))] = $value;
            } else {
                $transformed[$key] = $value;
            }
        }
        return $transformed;
    }
}