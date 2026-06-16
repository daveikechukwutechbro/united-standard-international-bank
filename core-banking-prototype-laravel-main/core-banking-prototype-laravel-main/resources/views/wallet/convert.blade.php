<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Convert Currency') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                @if(!auth()->user()->accounts->first())
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
                        <p class="text-gray-600 dark:text-gray-400">Create an account to get started with currency conversion</p>
                    </div>
                @else
                    <form id="convert-form" class="space-y-6">
                    @csrf

                    <div>
                        <x-label for="account" value="{{ __('Account') }}" />
                        <select id="account" name="account_uuid" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                            @foreach(auth()->user()->accounts as $account)
                                <option value="{{ $account->uuid }}">
                                    {{ $account->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <x-label for="from_currency" value="{{ __('From Currency') }}" />
                            <select id="from_currency" name="from_currency" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                @if($balances->count() > 0)
                                    @foreach($balances as $balance)
                                        <option value="{{ $balance->asset_code }}">
                                            {{ $balance->asset_code }} - {{ $balance->asset->name }} ({{ $balance->formatted_balance }})
                                        </option>
                                    @endforeach
                                @else
                                    <option value="">No balances available</option>
                                @endif
                            </select>
                            <div id="from-currency-error" class="text-red-600 text-sm mt-1 hidden"></div>
                        </div>

                        <div>
                            <x-label for="to_currency" value="{{ __('To Currency') }}" />
                            <select id="to_currency" name="to_currency" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" required>
                                @foreach($assets as $asset)
                                    <option value="{{ $asset->code }}">{{ $asset->code }} - {{ $asset->name }}</option>
                                @endforeach
                            </select>
                            <div id="to-currency-error" class="text-red-600 text-sm mt-1 hidden"></div>
                        </div>
                    </div>

                    <div>
                        <x-label for="amount" value="{{ __('Amount') }}" />
                        <x-input id="amount" type="number" step="0.01" min="0.01" name="amount" class="mt-1 block w-full" required />
                        <div id="amount-error" class="text-red-600 text-sm mt-1 hidden"></div>
                    </div>

                    <!-- Exchange Rate Preview -->
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">{{ __('Exchange Rate') }}</h3>
                        <div class="text-2xl font-semibold text-gray-900 dark:text-gray-100" id="exchange-rate">
                            1 USD = -- EUR
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ __('You will receive approximately') }} <span id="converted-amount" class="font-semibold">--</span>
                        </p>
                    </div>

                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                                    {{ __('Low Fees') }}
                                </h3>
                                <div class="mt-2 text-sm text-green-700 dark:text-green-300">
                                    <p>{{ __('Currency conversion fee: 0.01% - The lowest in the market!') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="success-message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded hidden"></div>
                    <div id="error-message" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded hidden"></div>

                    <div class="flex items-center justify-end mt-6">
                        <x-button type="button" id="convert-btn">
                            {{ __('Convert Currency') }}
                        </x-button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const convertForm = document.getElementById('convert-form');
            const convertBtn = document.getElementById('convert-btn');
            const fromCurrency = document.getElementById('from_currency');
            const toCurrency = document.getElementById('to_currency');
            const amount = document.getElementById('amount');
            const exchangeRateDisplay = document.getElementById('exchange-rate');
            const convertedAmountDisplay = document.getElementById('converted-amount');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');

            // Update exchange rate preview
            async function updatePreview() {
                const from = fromCurrency.value;
                const to = toCurrency.value;
                const amountValue = parseFloat(amount.value) || 0;

                if (!from || !to) return;

                if (from === to) {
                    exchangeRateDisplay.textContent = '1 ' + from + ' = 1 ' + to;
                    convertedAmountDisplay.textContent = amountValue.toFixed(2) + ' ' + to;
                    return;
                }

                try {
                    const response = await fetch(`/api/exchange-rates?from=${from}&to=${to}`);
                    if (response.ok) {
                        const result = await response.json();
                        if (result.data && result.data.length > 0) {
                            const rate = parseFloat(result.data[0].rate);
                            exchangeRateDisplay.textContent = '1 ' + from + ' = ' + rate.toFixed(4) + ' ' + to;
                            convertedAmountDisplay.textContent = (amountValue * rate).toFixed(2) + ' ' + to;
                        }
                    }
                } catch (error) {
                    console.error('Failed to fetch exchange rate:', error);
                    exchangeRateDisplay.textContent = '1 ' + from + ' = -- ' + to;
                    convertedAmountDisplay.textContent = '--';
                }
            }

            // Handle currency conversion
            convertBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                
                clearErrors();
                
                const formData = new FormData(convertForm);
                const accountUuid = formData.get('account_uuid');
                
                const requestData = {
                    account_uuid: accountUuid,
                    from_currency: formData.get('from_currency'),
                    to_currency: formData.get('to_currency'),
                    amount: parseFloat(formData.get('amount'))
                };
                
                try {
                    convertBtn.disabled = true;
                    convertBtn.textContent = 'Converting...';
                    
                    const response = await fetch('/api/exchange/convert', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Authorization': `Bearer {{ auth()->user()->currentAccessToken()->token ?? auth()->user()->createToken('wallet-access')->plainTextToken }}`
                        },
                        body: JSON.stringify(requestData)
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        showSuccess(result.message || 'Currency conversion completed successfully');
                        convertForm.reset();
                        setTimeout(() => {
                            window.location.href = '{{ route("dashboard") }}';
                        }, 2000);
                    } else {
                        if (result.errors) {
                            showValidationErrors(result.errors);
                        } else {
                            showError(result.message || 'Currency conversion failed');
                        }
                    }
                } catch (error) {
                    showError('Network error occurred. Please try again.');
                    console.error('Conversion error:', error);
                } finally {
                    convertBtn.disabled = false;
                    convertBtn.textContent = 'Convert Currency';
                }
            });

            function clearErrors() {
                document.getElementById('from-currency-error').classList.add('hidden');
                document.getElementById('to-currency-error').classList.add('hidden');
                document.getElementById('amount-error').classList.add('hidden');
                successMessage.classList.add('hidden');
                errorMessage.classList.add('hidden');
            }
            
            function showSuccess(message) {
                successMessage.textContent = message;
                successMessage.classList.remove('hidden');
            }
            
            function showError(message) {
                errorMessage.textContent = message;
                errorMessage.classList.remove('hidden');
            }
            
            function showValidationErrors(errors) {
                if (errors.from_currency) {
                    const error = document.getElementById('from-currency-error');
                    error.textContent = errors.from_currency[0];
                    error.classList.remove('hidden');
                }
                if (errors.to_currency) {
                    const error = document.getElementById('to-currency-error');
                    error.textContent = errors.to_currency[0];
                    error.classList.remove('hidden');
                }
                if (errors.amount) {
                    const error = document.getElementById('amount-error');
                    error.textContent = errors.amount[0];
                    error.classList.remove('hidden');
                }
            }

            fromCurrency.addEventListener('change', updatePreview);
            toCurrency.addEventListener('change', updatePreview);
            amount.addEventListener('input', updatePreview);

            updatePreview();
        });
    </script>
    @endpush
</x-app-layout>