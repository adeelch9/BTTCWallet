<script setup>
    import { inject, ref, computed } from 'vue';
    import { useI18n } from "vue-i18n";
    import { useRouter, useRoute } from 'vue-router';

    import WebApp from '@twa-dev/sdk';

    import Header from '../components/Header.vue';

    import Utils from '../utils';

    // If user has no saved wallet, redirect to login
    if (!(Utils.GetWallet('wallet'))) {

        const router = useRouter();
        router.push('/login');

    }

    const i18nLocale = useI18n({ useScope: 'global' });

    // Set i18n locale based on the user's locale provided by <LocaleProvider>
    i18nLocale.locale.value = localStorage.getItem('dpxwallet_locale') || inject('locale', 'en');

    const status = ref('normal');

    const route = useRoute();
    const router = useRouter();

    const wallet = ref(Utils.GetWallet('wallet'));
    const secret = ref(Utils.GetWallet('secret'));

    const balance = ref(0);
    const gasEstimate = ref(189); // Default gas estimate, you might want to fetch this dynamically

    const destination = ref(route.params.destination || '');
    const amount = ref(parseFloat(route.params.amount) || '');

    const isValidAddress = computed(() => {
        return /^0x[a-fA-F0-9]{42}$/.test(destination.value);
    });

    const isValidAmount = computed(() => {
        return amount.value > 0 && amount.value <= (balance.value - gasEstimate.value);
    });

    const canSubmit = computed(() => {
        return isValidAddress.value && isValidAmount.value;
    });

    onMounted(async () => {
        // Fetch user's balance
        let balanceData = await Utils.DPXSendRequest(`/balance/${wallet.value}`, [], 'GET', i18nLocale);
        if (balanceData && balanceData.status === 'success') {
            balance.value = parseFloat(balanceData.result);
        }

        // You might want to fetch gas estimate here as well
    });

    const ScanQRCode = () => {
        Utils.ScanQRCode(i18nLocale.t('wallet.scan_wallet'), (data) => {
            if (/^0x[a-fA-F0-9]{42}$/.test(data.data)) {
                destination.value = data.data;
                return true;
            }
            Utils.Toast(i18nLocale.t('transfer.toast.invalid_address'), 2500);
            return false;
        });
    };

    const Submit = async () => {
        if (!canSubmit.value) {
            Utils.Toast(i18nLocale.t('transfer.toast.invalid_input'), 2500);
            return;
        }

        Utils.Prompt(i18nLocale.t('transfer.prompt.verify_transfer.title'), i18nLocale.t('transfer.prompt.verify_transfer.text', { amount: amount.value, destination: destination.value, fee: gasEstimate.value }), [
            {
                text: i18nLocale.t('general.no'),
                type: "default",
                event: () => { window.Telegram.WebApp.HapticFeedback.impactOccurred('soft'); },
            },
            {
                text: i18nLocale.t('general.yes'),
                type: "destructive",
                event: async () => {

                    status.value = 'progress';
                    window.Telegram.WebApp.HapticFeedback.impactOccurred('medium');

                    let result = await Utils.DPXSendRequest('/transfer', { 'departure': wallet.value, 'secret': secret.value, 'destination': destination.value, 'amount': amount.value }, 'POST', i18nLocale);

                    if (result) {

                        window.Telegram.WebApp.HapticFeedback.notificationOccurred('success');

                        status.value = 'success';
                        Utils.PlayAudio('Success.mp3');

                        Utils.Toast(i18nLocale.t('transfer.toast.success'), 2500);

                        localStorage.setItem(`transaction_${result.result.transaction}`, JSON.stringify(result.result));

                        setTimeout(() => {

                            router.push(`/transaction/${result.result.transaction}`);

                        }, 2500);

                    } else {

                        window.Telegram.WebApp.HapticFeedback.notificationOccurred('error');

                        status.value = 'failed';
                        Utils.PlayAudio('Failed.mp3');

                        setTimeout(() => { status.value = 'normal'; }, 2500);

                    }

                },
            },
        ]);

    };

    WebApp.BackButton.onClick(() => { router.push('/'); });
    WebApp.BackButton.show();
</script>

<template>
    <section id="section-transfer">

        <Header icon="icon-upload" :title="$t('transfer.title')"></Header>

        <div>

            <div class="form-item">
                <label>{{ $t('transfer.fields.departure') }}</label>
                <div>
                    <input type="text" enterkeyhint="done" :placeholder="$t('transfer.fields.departure')" v-model="wallet"
                        @keydown="Utils.hideKeyboardOnEnter" disabled />
                </div>
            </div>

            <div class="form-item">
                <label>{{ $t('transfer.fields.destination') }}</label>
                <div>
                    <input type="text" enterkeyhint="done" :placeholder="$t('transfer.fields.destination')" v-model="destination"
                        @keydown="Utils.hideKeyboardOnEnter" :class="{ 'invalid': destination && !isValidAddress }" />
                    <i @click="ScanQRCode()" class="icon-maximize"></i>
                </div>
                <span v-if="destination && !isValidAddress" class="error-message">{{ $t('transfer.errors.invalid_address') }}</span>
            </div>

            <div class="form-item">
                <label>{{ $t('transfer.fields.amount') }}</label>
                <div>
                    <input type="number" enterkeyhint="done" :placeholder="$t('transfer.fields.transfer_amount')" 
                        v-model="amount" @keydown="Utils.hideKeyboardOnEnter" 
                        :class="{ 'invalid': amount && !isValidAmount }" />
                </div>
                <span v-if="amount && !isValidAmount" class="error-message">{{ $t('transfer.errors.insufficient_balance') }}</span>
            </div>

            <div class="balance-info">
                <span>{{ $t('transfer.available_balance') }}: {{ balance.toFixed(6) }} BTT</span>
                <span>{{ $t('transfer.estimated_fee') }}: {{ gasEstimate }} BTT</span>
            </div>

            <div id="container-button">
                <button :class="['button', 'button-progress', 'normal', `button-progress-${status}`]" 
                    @click="Submit" :disabled="!canSubmit">
                    <i class="icon-upload"></i>
                    <span>{{ $t('transfer.request_transfer') }}</span>
                </button>
            </div>

        </div>

    </section>
</template>

<style lang="scss">

    #section-transfer {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        height: 100%;

        >div {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            flex-grow: 1;

            #container-button {
                width: 100%;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: flex-end;
            }
        }
    }
    
    .form-item {
        .error-message {
            color: red;
            font-size: 0.8rem;
            margin-top: 0.2rem;
        }

        input.invalid {
            border-color: red;
        }
    }

    .balance-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        margin-top: 1rem;
        font-size: 0.9rem;
        color: var(--tg-theme-hint-color);
    }

    #container-button {
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    }
</style>